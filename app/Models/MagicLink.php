<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MagicLink extends Model
{
    use HasFactory;

    protected $fillable = ['email', 'token', 'used', 'expires_at'];

    protected $casts = [
        'used' => 'boolean',
        'expires_at' => 'datetime',
    ];

    public function isValid(): bool
    {
        return ! $this->used && $this->expires_at->isFuture();
    }

    public function scopeValid(Builder $query): Builder
    {
        return $query->where('used', false)->where('expires_at', '>', now());
    }
}
