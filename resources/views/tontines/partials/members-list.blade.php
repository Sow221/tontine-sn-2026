@php
    $pendingMembers = $tontine->members->where('pivot.status', 'pending');
    $membersList = $tontine->members->where('pivot.status', 'active');
    $visibleMembers = $membersList->take(20);
    $hiddenCount = max(0, $membersList->count() - 20);
    $isCreator = auth()->id() === $tontine->created_by;
@endphp

{{-- Demandes en attente (créateur) --}}
@if($isCreator && $pendingMembers->isNotEmpty())
<div class="card mb-4 border-warning">
    <h6 class="fw-semibold mb-3">
        <i class="fas fa-user-clock text-warning me-1"></i>
        {{ __('member.pending_requests') }} ({{ $pendingMembers->count() }})
    </h6>
    @foreach($pendingMembers as $member)
    <div class="d-flex align-items-center gap-3 mb-2 pb-2 {{ !$loop->last ? 'border-bottom' : '' }}">
        <div class="member-avatar avatar-sm">{{ strtoupper(substr($member->name ?? $member->phone_number, 0, 2)) }}</div>
        <div class="flex-grow-1 min-width-0">
            <p class="mb-0 fw-semibold small">{{ $member->name ?? $member->phone_number }}</p>
            <small class="text-muted">Demandé le {{ $member->pivot->joined_at ? \Carbon\Carbon::parse($member->pivot->joined_at)->format('d/m/Y') : '—' }}</small>
        </div>
        <form method="POST" action="{{ route('tontines.members.approve', [$tontine, $member]) }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-sm btn-success rounded-pill" title="Approuver">
                <i class="fas fa-check me-1"></i>{{ __('member.approve') }}
            </button>
        </form>
        <form method="POST" action="{{ route('tontines.members.reject', [$tontine, $member]) }}" class="d-inline"
              onsubmit="return confirm('Refuser cette demande ?');">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill" title="Refuser">
                <i class="fas fa-times"></i>
            </button>
        </form>
    </div>
    @endforeach
</div>
@endif

{{-- Suivi paiements — membres actifs uniquement --}}
@if($currentCycle && $tontine->status === 'active' && ($myMemberStatus ?? null) === 'active')
<div class="card mb-4">
    <h6 class="fw-semibold mb-3">{{ __('member.payment_tracking') }} — Cycle {{ $currentCycle->cycle_number }}</h6>
    @foreach($membersList as $member)
    <div class="d-flex align-items-center gap-2 mb-2">
        <div class="member-avatar avatar-sm">{{ strtoupper(substr($member->name ?? $member->phone_number, 0, 2)) }}</div>
        <span class="flex-grow-1 small fw-semibold">
            <a href="{{ route('members.show', $member) }}" class="text-decoration-none text-reset">
                {{ $member->name ?? $member->phone_number }}
            </a>
            @if($member->id === auth()->id()) <span class="text-muted">(moi)</span> @endif
        </span>
        @if($paidMemberIds->contains($member->id))
            <span class="badge badge-success"><i class="fas fa-check me-1"></i>Payé</span>
        @else
            <span class="badge badge-warning">En attente</span>
            @if($isCreator && $member->id !== auth()->id())
            <form method="POST" action="{{ route('tontines.members.remind', [$tontine, $member]) }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-secondary rounded-pill" title="Envoyer un rappel"
                        onclick="return confirm('Envoyer un rappel de paiement à {{ addslashes($member->name ?? $member->phone_number) }} ?')">
                    <i class="fas fa-bell"></i>
                </button>
            </form>
            @endif
        @endif
    </div>
    @endforeach
</div>
@endif

@if($membersList->isNotEmpty())
<div class="d-flex align-items-center gap-3 mb-3">
    <div class="avatar-stack">
        @foreach($membersList->take(4) as $member)
        <div class="avatar-stack-item" style="background:{{ ['#009639','#2D2F53','#FCD116','#EF3340'][$loop->index] }};">
            {{ strtoupper(substr($member->name ?? '?', 0, 1)) }}
        </div>
        @endforeach
        @if($membersList->count() > 4)
        <div class="avatar-stack-more">+{{ $membersList->count() - 4 }}</div>
        @endif
    </div>
    <div>
        <p class="fw-semibold mb-0 small">{{ $membersList->count() }} membre{{ $membersList->count() > 1 ? 's' : '' }}</p>
        <small class="text-muted">Membres actifs</small>
    </div>
</div>
@endif

<h6 class="fw-semibold mb-3">Membres actifs ({{ $membersList->count() }})</h6>
<div id="members-limited">
@foreach($visibleMembers as $member)
<div class="card mb-2 py-2">
    <div class="d-flex align-items-center gap-3">
        <div class="member-avatar">{{ strtoupper(substr($member->name ?? $member->phone_number, 0, 2)) }}</div>
        <div class="flex-grow-1">
            <p class="mb-0 fw-semibold small">
                <a href="{{ route('members.show', $member) }}" class="text-decoration-none text-reset">
                    {{ $member->name ?? $member->phone_number }}
                </a>
                @if($member->id === $tontine->created_by)
                    <span class="badge badge-info ms-1">Créateur</span>
                @endif
            </p>
            <small class="text-muted">
                Position {{ $member->pivot->position ?? '—' }}
                @if(($member->pivot->start_cycle_number ?? 1) > 1)
                    · <span class="text-warning fw-semibold">Dès cycle {{ $member->pivot->start_cycle_number }}</span>
                @endif
            </small>
        </div>
        <span class="badge badge-success">Actif</span>
    </div>
</div>
@endforeach
</div>
@if($hiddenCount > 0)
<div id="members-all" style="display:none;">
@foreach($membersList->skip(20) as $member)
<div class="card mb-2 py-2">
    <div class="d-flex align-items-center gap-3">
        <div class="member-avatar">{{ strtoupper(substr($member->name ?? $member->phone_number, 0, 2)) }}</div>
        <div class="flex-grow-1">
            <p class="mb-0 fw-semibold small">
                <a href="{{ route('members.show', $member) }}" class="text-decoration-none text-reset">
                    {{ $member->name ?? $member->phone_number }}
                </a>
                @if($member->id === $tontine->created_by)
                    <span class="badge badge-info ms-1">Créateur</span>
                @endif
            </p>
            <small class="text-muted">
                Position {{ $member->pivot->position ?? '—' }}
                @if(($member->pivot->start_cycle_number ?? 1) > 1)
                    · <span class="text-warning fw-semibold">Dès cycle {{ $member->pivot->start_cycle_number }}</span>
                @endif
            </small>
        </div>
        <span class="badge badge-success">Actif</span>
    </div>
</div>
@endforeach
</div>
<button type="button" class="btn btn-sm btn-outline-primary w-100 mt-2" onclick="document.getElementById('members-all').style.display='block';this.style.display='none';">
    <i class="fas fa-users me-1"></i>Voir tous les membres ({{ $membersList->count() }})
</button>
@endif
