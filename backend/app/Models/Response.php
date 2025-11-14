<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Response extends Model
{
    use HasUuids;

    protected $fillable = [
        'tenant_id',
        'campaign_id',
        'recipient_id',
        'score',
        'answers',
        'comment',
        'metadata',
    ];

    protected $casts = [
        'answers' => 'array',
        'metadata' => 'array',
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
    public function isPromoter(): bool
    {
        return $this->score >= 9;
    }

    public function isPassive(): bool
    {
        return $this->score >= 7 && $this->score <= 8;
    }

    public function isDetractor(): bool
    {
        return $this->score <= 6;
    }

    public function getCategory(): string
    {
        if ($this->isPromoter()) {
            return 'promoter';
        }

        if ($this->isPassive()) {
            return 'passive';
        }

        return 'detractor';
    }
}
