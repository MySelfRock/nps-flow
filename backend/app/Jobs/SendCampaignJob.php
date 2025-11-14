<?php

namespace App\Jobs;

use App\Models\Campaign;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendCampaignJob implements ShouldQueue
{
    use Queueable;

    public $campaign;
    public $timeout = 600; // 10 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(Campaign $campaign)
    {
        $this->campaign = $campaign;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Starting email send for campaign {$this->campaign->id}: {$this->campaign->name}");

        // Get all pending recipients
        $recipients = $this->campaign->recipients()
            ->whereIn('status', ['pending', 'failed'])
            ->whereNotNull('email')
            ->get();

        if ($recipients->isEmpty()) {
            Log::warning("No recipients to send for campaign {$this->campaign->id}");

            // If no pending recipients but campaign is sending, mark as sent
            if ($this->campaign->status === 'sending') {
                $this->campaign->update(['status' => 'sent']);
            }

            return;
        }

        Log::info("Dispatching {$recipients->count()} email jobs for campaign {$this->campaign->id}");

        // Dispatch individual email jobs with rate limiting
        $delaySeconds = 0;
        foreach ($recipients as $recipient) {
            SendEmailJob::dispatch($recipient)
                ->delay(now()->addSeconds($delaySeconds));

            // Add 2-second delay between dispatches to avoid rate limits
            $delaySeconds += 2;
        }

        // Update campaign status
        $this->campaign->update([
            'status' => 'sending',
            'sent_at' => now(),
        ]);

        Log::info("Successfully dispatched all email jobs for campaign {$this->campaign->id}");
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("SendCampaignJob failed for campaign {$this->campaign->id}: {$exception->getMessage()}");

        // Update campaign status
        $this->campaign->update(['status' => 'failed']);
    }
}
