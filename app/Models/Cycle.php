<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cycle extends Model
{
    use HasFactory;

    protected $fillable = [
        'tontine_id', 'cycle_number', 'beneficiary_id',
        'due_date', 'status', 'total_collected', 'bid_amount', 'draw_hash', 'drawn_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'drawn_at' => 'datetime',
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

    public function auctionBids(): HasMany
    {
        return $this->hasMany(AuctionBid::class);
    }

    public function vetos(): HasMany
    {
        return $this->hasMany(CycleVeto::class);
    }

    public function myBid(int $userId): ?AuctionBid
    {
        return $this->auctionBids()->where('user_id', $userId)->first();
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    public function isOverdue(): bool
    {
        return $this->due_date->copy()->endOfDay()->isPast() && $this->status !== 'paid';
    }

    public function expectedTotal(): int
    {
        // Utilise les données déjà chargées si disponibles, sinon requête
        $amount = $this->tontine->amount;
        $members = $this->tontine->relationLoaded('members')
            ? $this->tontine->members->where('pivot.status', 'active')->count()
            : $this->tontine->activeMembers()->count();

        return $amount * $members;
    }

    public function completionRate(): float
    {
        $expected = $this->expectedTotal();

        return $expected > 0 ? round(($this->total_collected / $expected) * 100, 1) : 0;
    }
}
