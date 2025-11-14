<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingSubscription extends Model
{
    use HasUuids;

    protected $fillable = [
        'tenant_id',
        'plan',
        'status',
        'stripe_subscription_id',
        'next_billing_date',
        'amount',
        'currency',
    ];

    protected $casts = [
        'next_billing_date' => 'datetime',
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // Helper methods
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isCanceled(): bool
    {
        return $this->status === 'canceled';
    }

    public function isPastDue(): bool
    {
        return $this->status === 'past_due';
    }

    public function cancel(): void
    {
        $this->status = 'canceled';
        $this->save();
    }

    public function activate(): void
    {
        $this->status = 'active';
        $this->save();
    }
}
