<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Badge extends Model
{
    protected $fillable = [
        'slug', 'name', 'description', 'icon',
        'tier', 'criteria_type', 'criteria_value',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_badges')
            ->withPivot('earned_at')
            ->withTimestamps();
    }

    public function tierIndex(): int
    {
        return match ($this->tier) {
            'bronze' => 0,
            'silver' => 1,
            'gold' => 2,
            default => 0,
        };
    }

    public static function tiers(): array
    {
        return ['bronze', 'silver', 'gold'];
    }
}
