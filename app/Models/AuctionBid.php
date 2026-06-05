<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuctionBid extends Model
{
    use HasFactory;
    protected $fillable = ['cycle_id', 'user_id', 'bid_rate'];

    protected $casts = ['bid_rate' => 'float'];

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(Cycle::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function netAmount(): int
    {
        $pot = $this->cycle->tontine->amount * $this->cycle->tontine->activeMembers()->count();
        return (int) round($pot * (1 - $this->bid_rate / 100));
    }
}
