<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class GatheringInvitation extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_ATTENDING = 'attending';
    public const STATUS_MAYBE = 'maybe';
    public const STATUS_DECLINED = 'declined';

    protected $fillable = [
        'gathering_id',
        'user_id',
        'person_id',
        'email',
        'status',
        'guests_count',
        'notes',
        'token',
        'sent_at',
        'responded_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (GatheringInvitation $invitation) {
            if (empty($invitation->token)) {
                $invitation->token = Str::random(64);
            }
        });
    }

    public function gathering(): BelongsTo
    {
        return $this->belongsTo(Gathering::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->user?->name ?? $this->person?->name ?? $this->email ?? 'Unknown';
    }

    public function attend(?string $notes = null, int $guests = 0): void
    {
        $this->update([
            'status' => self::STATUS_ATTENDING,
            'notes' => $notes,
            'guests_count' => $guests,
            'responded_at' => now(),
        ]);
    }

    public function maybe(?string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_MAYBE,
            'notes' => $notes,
            'responded_at' => now(),
        ]);
    }

    public function decline(?string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_DECLINED,
            'notes' => $notes,
            'responded_at' => now(),
        ]);
    }
}
