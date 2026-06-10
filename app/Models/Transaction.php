<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'cycle_id', 'user_id', 'amount', 'method',
        'external_reference', 'status', 'failure_reason',
        'receipt_url', 'paid_at',
        'type', 'description', 'metadata',
    ];

    protected $casts = [
        'amount' => 'integer',
        'paid_at' => 'datetime',
        'metadata' => 'array',
    ];

    // ── Relations ──────────────────────────────────────────────────────────

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(Cycle::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

    public function scopeSuccess(Builder $q): Builder
    {
        return $q->where('status', 'success');
    }

    public function scopePending(Builder $q): Builder
    {
        return $q->where('status', 'pending');
    }

    public function scopeForUser(Builder $q, int $userId): Builder
    {
        return $q->where('user_id', $userId);
    }

    public function scopeForCycle(Builder $q, int $cycleId): Builder
    {
        return $q->where('cycle_id', $cycleId);
    }

    public function scopeForTontine(Builder $q, int $tontineId): Builder
    {
        return $q->whereHas('cycle', fn ($q) => $q->where('tontine_id', $tontineId));
    }

    public function scopeExcludeRedistribution(Builder $q): Builder
    {
        return $q->where(function ($q) {
            $q->whereNull('external_reference')
                ->orWhere('external_reference', 'not like', 'redistribution-%');
        });
    }

    public function scopeActivePayment(Builder $q, int $cycleId, int $userId): Builder
    {
        return $q->forCycle($cycleId)->forUser($userId)
            ->whereIn('status', ['success', 'pending']);
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    public function isReversible(): bool
    {
        $fresh = $this->fresh();

        return $fresh
            && $fresh->status === 'success'
            && $fresh->paid_at
            && $fresh->paid_at->diffInHours(now()) <= config('tontine.transaction.reverse_window_h', 24);
    }

    public function isPendingOrSuccess(): bool
    {
        return in_array($this->status, ['pending', 'success'], true);
    }

    // ── Label helpers (centralisés — évite la duplication dans controllers/exports) ──

    public function getMethodLabelAttribute(): string
    {
        return match ($this->method) {
            'wave' => 'Wave',
            'orange_money' => 'Orange Money',
            'free_money' => 'Free Money',
            'card' => 'Carte bancaire',
            'cash' => 'Espèces',
            'direct_transfer' => 'Transfert P2P',
            default => ucfirst($this->method),
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'success' => 'Payé',
            'pending' => 'En attente',
            'failed' => 'Échoué',
            'reversed' => 'Remboursé',
            'cancelled' => 'Annulé',
            default => ucfirst((string) $this->status),
        };
    }
}
