<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookSignature extends Model
{
    protected $fillable = ['provider', 'transaction_id', 'signature', 'is_verified', 'verified_at'];

    protected $casts = [
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
    ];

    public static function isProcessed(string $signature): bool
    {
        return self::where('signature', $signature)->where('is_verified', true)->exists();
    }
}
