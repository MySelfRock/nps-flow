<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'cnpj',
        'plan',
        'billing_customer_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
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

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function billingSubscriptions(): HasMany
    {
        return $this->hasMany(BillingSubscription::class);
    }

    // Helper methods
    public function isOnPlan(string $plan): bool
    {
        return $this->plan === $plan;
    }

    public function isPremium(): bool
    {
        return in_array($this->plan, ['pro', 'enterprise']);
    }
}
