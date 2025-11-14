<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Send extends Model
{
    use HasUuids;

    protected $fillable = [
        'tenant_id',
        'campaign_id',
        'recipient_id',
        'channel',
        'status',
        'provider_message_id',
        'attempts',
        'last_attempt_at',
        'sent_at',
        'error_message',
    ];

    protected $casts = [
        'last_attempt_at' => 'datetime',
        'sent_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(Recipient::class);
    }

    // Helper methods
    public function isSuccessful(): bool
    {
        return in_array($this->status, ['sent', 'delivered']);
    }

    public function hasFailed(): bool
    {
        return in_array($this->status, ['failed', 'bounced']);
    }

    public function canRetry(): bool
    {
        return $this->hasFailed() && $this->attempts < 3;
    }

    public function markAsDelivered(string $providerMessageId = null): void
    {
        $this->status = 'delivered';
        if ($providerMessageId) {
            $this->provider_message_id = $providerMessageId;
        }
        $this->save();
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->status = 'failed';
        $this->error_message = $errorMessage;
        $this->save();
    }

    public function incrementAttempts(): void
    {
        $this->attempts++;
        $this->last_attempt_at = now();
        $this->save();
    }
}
