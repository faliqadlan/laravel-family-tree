<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GatheringComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'gathering_id',
        'user_id',
        'parent_id',
        'content',
        'is_deleted',
    ];

    protected $casts = [
        'is_deleted' => 'boolean',
    ];

    public function gathering(): BelongsTo
    {
        return $this->belongsTo(Gathering::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(GatheringComment::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(GatheringComment::class, 'parent_id');
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_deleted', false);
    }

    public function softDelete(): void
    {
        $this->is_deleted = true;
        $this->content = '[deleted]';
        $this->save();
    }
}
