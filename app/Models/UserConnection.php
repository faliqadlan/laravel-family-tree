<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserConnection extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_BLOCKED = 'blocked';

    protected $fillable = [
        'requester_id',
        'receiver_id',
        'status',
        'message',
    ];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function approve(): self
    {
        $this->update(['status' => self::STATUS_APPROVED]);

        return $this;
    }

    public function reject(): self
    {
        $this->update(['status' => self::STATUS_REJECTED]);

        return $this;
    }

    public function block(): self
    {
        $this->update(['status' => self::STATUS_BLOCKED]);

        return $this;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public static function getConnectionBetween(int $userA, int $userB): ?self
    {
        return static::where(function ($q) use ($userA, $userB) {
            $q->where('requester_id', $userA)->where('receiver_id', $userB);
        })->orWhere(function ($q) use ($userA, $userB) {
            $q->where('requester_id', $userB)->where('receiver_id', $userA);
        })->first();
    }
}
