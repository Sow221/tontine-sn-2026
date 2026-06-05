<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavingsWithdrawal extends Model
{
    use HasFactory;
    protected $fillable = ['tontine_id', 'user_id', 'amount', 'status', 'paid_at'];

    protected $casts = [
        'amount'  => 'integer',
        'paid_at' => 'datetime',
    ];

    public function tontine(): BelongsTo
    {
        return $this->belongsTo(Tontine::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', 'paid');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
}
