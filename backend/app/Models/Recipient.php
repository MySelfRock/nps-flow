<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Recipient extends Model
{
    use HasUuids;

    protected $fillable = [
        'tenant_id',
        'campaign_id',
        'external_id',
        'name',
        'email',
        'phone',
        'token',
        'status',
        'tags',
    ];

    protected $casts = [
        'tags' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($recipient) {
            if (empty($recipient->token)) {
                $recipient->token = Str::random(64);
            }
        });
    }

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function response(): HasOne
    {
        return $this->hasOne(Response::class);
    }

    public function sends(): HasMany
    {
        return $this->hasMany(Send::class);
    }

    // Helper methods
    public function hasResponded(): bool
    {
        return $this->status === 'responded';
    }

    public function getResponseLink(): string
    {
        return url("/r/{$this->token}");
    }

    public function markAsResponded(): void
    {
        $this->status = 'responded';
        $this->save();
    }
}
