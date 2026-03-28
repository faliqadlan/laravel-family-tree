<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPrivacySetting extends Model
{
    use HasFactory;

    public const VISIBILITY_PUBLIC = 'public';
    public const VISIBILITY_CONNECTIONS = 'connections';
    public const VISIBILITY_PRIVATE = 'private';
    public const VISIBILITY_MASKED = 'masked';

    protected $fillable = [
        'user_id',
        'profile_visibility',
        'field_visibility',
    ];

    protected function casts(): array
    {
        return [
            'field_visibility' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Returns visibility level for a specific field, falling back to profile_visibility.
     */
    public function getFieldVisibility(string $field): string
    {
        $fieldVisibility = $this->field_visibility ?? [];

        return $fieldVisibility[$field] ?? $this->profile_visibility;
    }

    /**
     * Applies masking to a value based on field visibility settings.
     */
    public function maskValue(string $field, mixed $value, bool $isConnected): mixed
    {
        $visibility = $this->getFieldVisibility($field);

        if ($visibility === self::VISIBILITY_PUBLIC) {
            return $value;
        }

        if ($visibility === self::VISIBILITY_CONNECTIONS) {
            return $isConnected ? $value : $this->applyMask($field, $value);
        }

        if ($visibility === self::VISIBILITY_MASKED) {
            return $this->applyMask($field, $value);
        }

        // VISIBILITY_PRIVATE or hidden
        return null;
    }

    private function applyMask(string $field, mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if ($field === 'birthday' || str_contains($field, 'date')) {
            // Mask year: "198X"
            if ($value instanceof \DateTimeInterface) {
                return $value->format('Y') . 'X';
            }
            $str = (string) $value;
            if (preg_match('/(\d{4})/', $str, $m)) {
                return substr($str, 0, strpos($str, $m[1])) . substr($m[1], 0, 3) . 'X' . substr($str, strpos($str, $m[1]) + 4);
            }

            return $str;
        }

        if ($field === 'name') {
            // Mask name: "J*** S***"
            return implode(' ', array_map(function (string $part): string {
                if (strlen($part) <= 1) {
                    return $part . '***';
                }

                return $part[0] . str_repeat('*', strlen($part) - 1);
            }, explode(' ', (string) $value)));
        }

        // Generic masking: show first char + stars
        $str = (string) $value;

        return strlen($str) > 1 ? $str[0] . str_repeat('*', strlen($str) - 1) : '***';
    }
}
