<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TontineDebt extends Model
{
    protected $fillable = ['tontine_id', 'user_id', 'cycle_id', 'amount', 'status', 'paid_at'];

    protected $casts = ['paid_at' => 'datetime'];

    public function tontine(): BelongsTo
    {
        return $this->belongsTo(Tontine::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(Cycle::class);
    }

    public function markPaid(): void
    {
        $this->update(['status' => 'paid', 'paid_at' => now()]);
    }
}
