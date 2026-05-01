<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $fillable = [
        'cycle_id', 'user_id', 'amount', 'method',
        'external_reference', 'status', 'failure_reason',
        'receipt_url', 'paid_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
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

    // ── Helpers ────────────────────────────────────────────────────────────

    public function isReversible(): bool
    {
        return $this->status === 'success'
            && $this->paid_at
            && $this->paid_at->diffInHours(now()) <= config('tontine.transaction.reverse_window_h');
    }
}
