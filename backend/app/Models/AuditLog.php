<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasUuids;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'action_type',
        'details',
    ];

    protected $casts = [
        'details' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Helper method to log an action
    public static function logAction(string $actionType, ?User $user, array $details = []): self
    {
        return self::create([
            'tenant_id' => $user?->tenant_id,
            'user_id' => $user?->id,
            'action_type' => $actionType,
            'details' => $details,
        ]);
    }
}
