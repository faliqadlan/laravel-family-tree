<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Gathering extends Model
{
    use HasFactory, SoftDeletes;

    // Type constants
    public const TYPE_REUNION = 'reunion';
    public const TYPE_MEMORIAL = 'memorial';
    public const TYPE_WEDDING = 'wedding';
    public const TYPE_BIRTHDAY = 'birthday';
    public const TYPE_HOLIDAY = 'holiday';
    public const TYPE_OTHER = 'other';

    // Status constants
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_COMPLETED = 'completed';

    // Privacy constants
    public const PRIVACY_PUBLIC = 'public';
    public const PRIVACY_CONNECTIONS = 'connections';
    public const PRIVACY_PRIVATE = 'private';

    protected $fillable = [
        'organizer_id',
        'tree_id',
        'person_id',
        'title',
        'type',
        'description',
        'location',
        'location_url',
        'start_date',
        'end_date',
        'is_virtual',
        'meeting_url',
        'status',
        'privacy',
        'funding_goal',
        'funding_currency',
        'funding_description',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_virtual' => 'boolean',
        'funding_goal' => 'decimal:2',
    ];

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function tree(): BelongsTo
    {
        return $this->belongsTo(Tree::class);
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(GatheringInvitation::class);
    }

    public function attendingInvitations(): HasMany
    {
        return $this->hasMany(GatheringInvitation::class)->where('status', GatheringInvitation::STATUS_ATTENDING);
    }

    public function contributions(): HasMany
    {
        return $this->hasMany(GatheringContribution::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(GatheringComment::class)
            ->where('is_deleted', false)
            ->whereNull('parent_id')
            ->orderBy('created_at');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('start_date', '>', now());
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $q) use ($user) {
            $q->where('organizer_id', $user->id)
              ->orWhereHas('invitations', fn (Builder $inv) => $inv->where('user_id', $user->id));
        });
    }

    public function getTotalContributions(): float
    {
        return (float) $this->contributions()
            ->whereIn('status', [GatheringContribution::STATUS_PLEDGED, GatheringContribution::STATUS_PAID])
            ->sum('amount');
    }

    public function getFundingProgress(): float
    {
        if (!$this->funding_goal || (float) $this->funding_goal <= 0) {
            return 0.0;
        }

        return min(100.0, ($this->getTotalContributions() / (float) $this->funding_goal) * 100);
    }

    public function isOrganizer(User $user): bool
    {
        return $this->organizer_id === $user->id;
    }

    public function getUserInvitation(User $user): ?GatheringInvitation
    {
        return $this->invitations()->where('user_id', $user->id)->first();
    }

    public function generateIcs(): string
    {
        $dtStamp = now()->format('Ymd\THis\Z');
        $dtStart = $this->start_date->format('Ymd\THis\Z');
        $dtEnd = ($this->end_date ?? $this->start_date->copy()->addHours(2))->format('Ymd\THis\Z');
        $description = str_replace(["\r\n", "\r", "\n"], '\\n', $this->description ?? '');

        return implode("\r\n", [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Family Tree//Gathering//EN',
            'BEGIN:VEVENT',
            'UID:' . $this->id . '@familytree',
            'DTSTAMP:' . $dtStamp,
            'DTSTART:' . $dtStart,
            'DTEND:' . $dtEnd,
            'SUMMARY:' . $this->title,
            'DESCRIPTION:' . $description,
            'LOCATION:' . ($this->location ?? ''),
            'END:VEVENT',
            'END:VCALENDAR',
        ]);
    }
}
