<?php

namespace App\Jobs;

use App\Mail\SurveyEmail;
use App\Models\Recipient;
use App\Models\Send;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendEmailJob implements ShouldQueue
{
    use Queueable;

    public $recipient;
    public $tries = 3;
    public $timeout = 60;
    public $backoff = [60, 300, 900]; // 1 min, 5 min, 15 min

    /**
     * Create a new job instance.
     */
    public function __construct(Recipient $recipient)
    {
        $this->recipient = $recipient;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Check if recipient already responded
            if ($this->recipient->hasResponded()) {
                Log::info("Recipient {$this->recipient->id} already responded, skipping email send");
                return;
            }

            // Create or update Send record
            $send = Send::firstOrCreate(
                [
                    'tenant_id' => $this->recipient->tenant_id,
                    'campaign_id' => $this->recipient->campaign_id,
                    'recipient_id' => $this->recipient->id,
                ],
                [
                    'channel' => 'email',
                    'status' => 'pending',
                    'attempts' => 0,
                ]
            );

            // Increment attempts
            $send->increment('attempts');
            $send->update([
                'status' => 'sending',
                'last_attempt_at' => now(),
            ]);

            // Send email
            Mail::to($this->recipient->email)
                ->send(new SurveyEmail($this->recipient));

            // Update Send record as delivered
            $send->update([
                'status' => 'delivered',
                'sent_at' => now(),
            ]);

            // Update recipient status
            $this->recipient->update(['status' => 'sent']);

            Log::info("Email sent successfully to {$this->recipient->email} for campaign {$this->recipient->campaign_id}");

        } catch (\Exception $e) {
            Log::error("Failed to send email to {$this->recipient->email}: {$e->getMessage()}");

            // Update Send record as failed
            if (isset($send)) {
                $send->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
            }

            // Re-throw exception to trigger retry
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("SendEmailJob failed permanently for recipient {$this->recipient->id}: {$exception->getMessage()}");

        // Update recipient status as failed
        $this->recipient->update(['status' => 'failed']);

        // Update Send record
        $send = Send::where('recipient_id', $this->recipient->id)
            ->where('campaign_id', $this->recipient->campaign_id)
            ->first();

        if ($send) {
            $send->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);
        }
    }
}
