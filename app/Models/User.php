<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'email', 'name', 'avatar',
        'role', 'preferred_language', 'kyc_verified',
        'kyc_document', 'is_active', 'last_seen_at',
    ];

    protected $hidden = ['remember_token'];

    protected $casts = [
        'kyc_verified' => 'boolean',
        'is_active'    => 'boolean',
        'last_seen_at' => 'datetime',
    ];

    // ── Relations ──────────────────────────────────────────────────────────

    public function tontines(): HasMany
    {
        return $this->hasMany(Tontine::class, 'created_by');
    }

    public function memberships(): BelongsToMany
    {
        return $this->belongsToMany(Tontine::class, 'tontine_members')
                    ->withPivot('status', 'position', 'joined_at')
                    ->withTimestamps();
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function creditScore(): HasOne
    {
        return $this->hasOne(CreditScore::class)->latestOfMany('calculated_at');
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'super_admin']);
    }

    public function isManager(): bool
    {
        return in_array($this->role, ['manager', 'admin', 'super_admin']);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }
}
