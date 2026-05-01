<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tontine extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'code', 'description', 'amount', 'frequency',
        'type', 'status', 'start_date', 'end_date',
        'max_members', 'penalty_rate', 'quorum', 'draw_method', 'created_by',
    ];

    protected $casts = [
        'start_date'   => 'date',
        'end_date'     => 'date',
        'penalty_rate' => 'float',
    ];

    // ── Relations ──────────────────────────────────────────────────────────

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tontine_members')
                    ->withPivot('status', 'position', 'joined_at')
                    ->withTimestamps();
    }

    public function activeMembers(): BelongsToMany
    {
        return $this->members()->wherePivot('status', 'active');
    }

    public function cycles(): HasMany
    {
        return $this->hasMany(Cycle::class);
    }

    public function currentCycle(): ?Cycle
    {
        return $this->cycles()->where('status', '!=', 'paid')->orderBy('cycle_number')->first();
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isFull(): bool
    {
        return $this->activeMembers()->count() >= $this->max_members;
    }

    protected static function booted(): void
    {
        static::creating(function (Tontine $tontine) {
            $tontine->code = strtoupper(substr(uniqid(), -6));
        });
    }
}
