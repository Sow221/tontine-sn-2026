<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditScore extends Model
{
    protected $fillable = [
        'user_id', 'score', 'total_contributed',
        'on_time_payments', 'total_cycles', 'seniority_months',
        'badge', 'calculated_at',
    ];

    protected $casts = [
        'score'         => 'float',
        'calculated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function badgeLabel(): string
    {
        return match($this->badge) {
            'gold'   => '🥇 Or',
            'silver' => '🥈 Argent',
            'bronze' => '🥉 Bronze',
            default  => '—',
        };
    }

    public function badgeColor(): string
    {
        return match($this->badge) {
            'gold'   => 'warning',
            'silver' => 'secondary',
            'bronze' => 'danger',
            default  => 'light',
        };
    }
}
