<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Tontine extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'code', 'description', 'amount', 'frequency',
        'type', 'status', 'visibility', 'start_date', 'end_date',
        'max_members', 'penalty_rate', 'quorum', 'draw_method',
        'weighted_draw', 'veto_threshold', 'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'penalty_rate' => 'float',
        'weighted_draw' => 'boolean',
        'veto_threshold' => 'integer',
    ];

    // ── Relations ──────────────────────────────────────────────────────────

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tontine_members')
            ->withPivot('status', 'position', 'joined_at', 'role')
            ->withTimestamps();
    }

    public function activeMembers(): BelongsToMany
    {
        return $this->members()->wherePivot('status', 'active');
    }

    public function managers(): BelongsToMany
    {
        return $this->members()->wherePivot('role', 'manager');
    }

    public function cycles(): HasMany
    {
        return $this->hasMany(Cycle::class);
    }

    public function currentCycle(): HasOne
    {
        return $this->hasOne(Cycle::class)
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->orderBy('cycle_number');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(ChatMessage::class)->latestOfMany();
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'auction' => 'Tontine enchères',
            'forced_saving' => 'Épargne forcée',
            'ceremonial' => 'Tontine cérémoniale',
            default => 'Tontine classique',
        };
    }

    public function isPublic(): bool
    {
        return $this->visibility === 'public';
    }

    public function isManager(User $user): bool
    {
        return $this->members()
            ->where('users.id', $user->id)
            ->wherePivot('role', 'manager')
            ->exists();
    }

    public function scopePubliclyVisible($query)
    {
        return $query->where('visibility', 'public')
            ->whereIn('status', ['pending', 'active']);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isFull(): bool
    {
        return $this->activeMembers()->count() >= $this->max_members;
    }

    public function acceptsNewMembers(): bool
    {
        return in_array($this->status, ['pending', 'active'], true) && ! $this->isFull();
    }

    /**
     * Estimation du prochain tour pour un membre actif (tontines à rotation).
     *
     * @return array{status: string, queue_position?: int, members_ahead?: int, total_in_queue?: int}|null
     */
    public function turnEstimateFor(int $userId): ?array
    {
        if (in_array($this->type, ['forced_saving', 'ceremonial'], true)) {
            return null;
        }

        // Charger la relation si absente pour éviter les requêtes implicites multiples
        if (! $this->relationLoaded('members')) {
            $this->load('members');
        }

        $winnersIds = $this->relationLoaded('cycles')
            ? $this->cycles->whereNotNull('beneficiary_id')->pluck('beneficiary_id')->unique()
            : $this->cycles()->whereNotNull('beneficiary_id')->pluck('beneficiary_id')->unique();

        if ($winnersIds->contains($userId)) {
            return ['status' => 'already_won'];
        }

        $remaining = $this->members
            ->where('pivot.status', 'active')
            ->reject(fn ($m) => $winnersIds->contains($m->id))
            ->sortBy(fn ($m) => $m->pivot->position ?? 999)
            ->values();

        $index = $remaining->search(fn ($m) => $m->id === $userId);

        if ($index === false) {
            return null;
        }

        return [
            'status' => 'waiting',
            'queue_position' => $index + 1,
            'members_ahead' => $index,
            'total_in_queue' => $remaining->count(),
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Tontine $tontine) {
            if (empty($tontine->code)) {
                do {
                    $code = Str::upper(Str::random(6));
                } while (static::withTrashed()->where('code', $code)->exists());
                $tontine->code = $code;
            }
        });
    }
}
