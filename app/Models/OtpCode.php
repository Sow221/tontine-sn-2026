<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtpCode extends Model
{
    protected $fillable = ['phone_number', 'code', 'attempts', 'used', 'expires_at'];

    protected $casts = [
        'used'       => 'boolean',
        'expires_at' => 'datetime',
    ];

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isValid(string $code): bool
    {
        return !$this->used
            && !$this->isExpired()
            && $this->attempts < config('tontine.otp.max_attempts')
            && $this->code === $code;
    }
}
