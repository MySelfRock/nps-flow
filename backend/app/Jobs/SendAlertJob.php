<?php

namespace App\Jobs;

use App\Models\Alert;
use App\Models\Response;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendAlertJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Alert $alert,
        public Response $response
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Load relationships
        $this->response->load(['recipient', 'campaign']);

        // Send email notifications
        if (!empty($this->alert->notify_emails)) {
            $this->sendEmailNotifications();
        }

        // Send webhook notification
        if (!empty($this->alert->webhook_url)) {
            $this->sendWebhookNotification();
        }

        Log::info('Alert notification sent successfully', [
            'alert_id' => $this->alert->id,
            'response_id' => $this->response->id,
        ]);
    }

    /**
     * Send email notifications to configured addresses
     */
    private function sendEmailNotifications(): void
    {
        try {
            $emails = $this->alert->getNotificationEmails();

            if (empty($emails)) {
                return;
            }

            $data = $this->prepareNotificationData();

            foreach ($emails as $email) {
                Mail::send('emails.alert-notification', $data, function ($message) use ($email, $data) {
                    $message->to($email)
                        ->subject("Alerta NPS: {$data['category']} recebido - {$data['campaign_name']}");
                });
            }

            Log::info('Alert emails sent', [
                'alert_id' => $this->alert->id,
                'recipients' => $emails,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send alert emails', [
                'alert_id' => $this->alert->id,
                'error' => $e->getMessage(),
            ]);

            // Don't throw exception to allow webhook to still be sent
        }
    }

    /**
     * Send webhook notification
     */
    private function sendWebhookNotification(): void
    {
        try {
            $data = $this->prepareNotificationData();

            $response = Http::timeout(10)
                ->post($this->alert->webhook_url, [
                    'event' => 'alert_triggered',
                    'alert_id' => $this->alert->id,
                    'data' => $data,
                    'timestamp' => now()->toIso8601String(),
                ]);

            if ($response->successful()) {
                Log::info('Webhook notification sent', [
                    'alert_id' => $this->alert->id,
                    'webhook_url' => $this->alert->webhook_url,
                    'status' => $response->status(),
                ]);
            } else {
                Log::warning('Webhook notification failed', [
                    'alert_id' => $this->alert->id,
                    'webhook_url' => $this->alert->webhook_url,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send webhook notification', [
                'alert_id' => $this->alert->id,
                'webhook_url' => $this->alert->webhook_url,
                'error' => $e->getMessage(),
            ]);

            throw $e; // Rethrow to trigger retry
        }
    }

    /**
     * Prepare notification data
     */
    private function prepareNotificationData(): array
    {
        return [
            'campaign_name' => $this->response->campaign->name,
            'campaign_type' => $this->response->campaign->type,
            'recipient_name' => $this->response->recipient->name,
            'recipient_email' => $this->response->recipient->email,
            'score' => $this->response->score,
            'category' => $this->response->getCategory(),
            'comment' => $this->response->comment,
            'submitted_at' => $this->response->created_at->format('d/m/Y H:i'),
            'threshold' => $this->alert->condition['score_threshold'] ?? 'N/A',
        ];
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendAlertJob failed after all retries', [
            'alert_id' => $this->alert->id,
            'response_id' => $this->response->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
