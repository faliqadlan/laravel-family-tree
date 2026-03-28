<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GatheringContribution extends Model
{
    use HasFactory;

    public const STATUS_PLEDGED = 'pledged';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'gathering_id',
        'user_id',
        'amount',
        'currency',
        'payment_method',
        'status',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function gathering(): BelongsTo
    {
        return $this->belongsTo(Gathering::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markPaid(): void
    {
        $this->update(['status' => self::STATUS_PAID]);
    }

    public function cancel(): void
    {
        $this->update(['status' => self::STATUS_CANCELLED]);
    }
}
