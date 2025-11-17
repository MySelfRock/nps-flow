<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    use HasUuids;

    protected $fillable = [
        'tenant_id',
        'name',
        'type',
        'message_template',
        'sender_email',
        'sender_name',
        'scheduled_at',
        'sent_at',
        'status',
        'settings',
        'created_by',
    ];

    protected $casts = [
        'message_template' => 'array',
        'settings' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(Recipient::class);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(Response::class);
    }

    public function sends(): HasMany
    {
        return $this->hasMany(Send::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    // Helper methods
    public function isNPS(): bool
    {
        return $this->type === 'NPS';
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isSent(): bool
    {
        return $this->status === 'sent';
    }

    public function canBeSent(): bool
    {
        return in_array($this->status, ['draft', 'scheduled']);
    }

    public function getNPSScore(): ?float
    {
        if (!$this->isNPS()) {
            return null;
        }

        $responses = $this->responses()->whereNotNull('score')->get();

        if ($responses->count() === 0) {
            return null;
        }

        $promoters = $responses->where('score', '>=', 9)->count();
        $detractors = $responses->where('score', '<=', 6)->count();
        $total = $responses->count();

        return (($promoters - $detractors) / $total) * 100;
    }

    public function getResponseRate(): float
    {
        $sent = $this->sends()->where('status', 'sent')->count();

        if ($sent === 0) {
            return 0;
        }

        $responded = $this->responses()->count();

        return ($responded / $sent) * 100;
    }
}
