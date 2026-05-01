<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cycle extends Model
{
    protected $fillable = [
        'tontine_id', 'cycle_number', 'beneficiary_id',
        'due_date', 'status', 'total_collected', 'draw_hash', 'drawn_at',
    ];

    protected $casts = [
        'due_date'  => 'date',
        'drawn_at'  => 'datetime',
    ];

    // ── Relations ──────────────────────────────────────────────────────────

    public function tontine(): BelongsTo
    {
        return $this->belongsTo(Tontine::class);
    }

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(User::class, 'beneficiary_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function successfulTransactions(): HasMany
    {
        return $this->transactions()->where('status', 'success');
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    public function isOverdue(): bool
    {
        return $this->due_date->isPast() && $this->status !== 'paid';
    }

    public function expectedTotal(): int
    {
        return $this->tontine->amount * $this->tontine->activeMembers()->count();
    }

    public function completionRate(): float
    {
        $expected = $this->expectedTotal();
        return $expected > 0 ? round(($this->total_collected / $expected) * 100, 1) : 0;
    }
}
