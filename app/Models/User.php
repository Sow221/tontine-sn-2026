<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'email', 'name', 'password', 'avatar', 'phone_number',
        'google_id', 'kyc_verified', 'kyc_status', 'kyc_rejected_reason',
        'kyc_document', 'kyc_document_hash', 'is_active', 'last_seen_at', 'email_verified_at',
        'payment_streak', 'max_streak', 'notification_settings',
        'preferred_language', 'referral_code', 'referred_by', 'onboarding_completed',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $user) {
            if (empty($user->referral_code)) {
                do {
                    $code = strtoupper(Str::random(8));
                } while (self::where('referral_code', $code)->exists());

                $user->referral_code = $code;
            }
        });
    }

    protected $casts = [
        'password' => 'hashed',
        'kyc_verified' => 'boolean',
        'is_active' => 'boolean',
        'last_seen_at' => 'datetime',
        'email_verified_at' => 'datetime',
        'payment_streak' => 'integer',
        'max_streak' => 'integer',
        'notification_settings' => 'array',
        'onboarding_completed' => 'boolean',
    ];

    protected $hidden = ['password', 'remember_token'];

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
        return $this->hasOne(CreditScore::class);
    }

    public function twoFactorSecret(): HasOne
    {
        return $this->hasOne(TwoFactorSecret::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function badges(): BelongsToMany
    {
        return $this->belongsToMany(Badge::class, 'user_badges')
            ->withPivot('earned_at')
            ->withTimestamps();
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(User::class, 'referred_by');
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeKycPending(Builder $query): Builder
    {
        return $query->where('kyc_status', 'pending');
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'super_admin']);
    }

    public function hasTwoFactorEnabled(): bool
    {
        return $this->twoFactorSecret?->isEnabled() ?? false;
    }
}
