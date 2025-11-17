<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alert extends Model
{
    use HasUuids;

    protected $fillable = [
        'tenant_id',
        'campaign_id',
        'condition',
        'notify_emails',
        'webhook_url',
        'enabled',
    ];

    protected $casts = [
        'condition' => 'array',
        'notify_emails' => 'array',
        'enabled' => 'boolean',
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

    // Helper methods
    public function shouldTrigger(Response $response): bool
    {
        if (!$this->enabled) {
            return false;
        }

        $threshold = $this->condition['score_threshold'] ?? null;

        if ($threshold === null) {
            return false;
        }

        return $response->score <= $threshold;
    }

    public function getNotificationEmails(): array
    {
        return $this->notify_emails ?? [];
    }
}
