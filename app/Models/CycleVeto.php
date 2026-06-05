<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CycleVeto extends Model
{
    protected $fillable = ['cycle_id', 'user_id'];

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(Cycle::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
