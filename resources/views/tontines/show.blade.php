@extends('layouts.app')
@section('title', $tontine->name)

@section('content')
<div class="container py-4">

    @if($myMemberStatus === 'pending')
    <div class="alert alert-warning d-flex align-items-center gap-2 mb-4">
        <i class="fas fa-hourglass-half"></i>
        <div>
            <strong>{{ __('member.pending_request') }}</strong><br>
            <small>{{ __('member.pending_request_help') }}</small>
        </div>
    </div>
    @endif

    @if($myMemberStatus === 'excluded')
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-4">
        <i class="fas fa-ban"></i>
        <div><strong>{{ __('member.access_denied') }}</strong> — {{ __('member.excluded') }}</div>
    </div>
    @endif

    <nav aria-label="breadcrumb" class="mb-3 small">
        <ol class="breadcrumb bg-transparent p-0 m-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-green">{{ __('member.home') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('tontines.index') }}" class="text-green">{{ __('member.my_tontines') }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $tontine->name }}</li>
        </ol>
    </nav>

    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="{{ route('tontines.index') }}" class="btn btn-sm btn-light rounded-circle">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div class="flex-grow-1">
            <h4 class="fw-bold mb-0">{{ $tontine->name }}</h4>
            <small class="text-muted">
                Code : <strong>{{ $tontine->code }}</strong>
                · {{ match($tontine->type) {
                    'auction' => __('member.type_auction'),
                    'forced_saving' => __('member.type_saving'),
                    'ceremonial' => __('member.type_ceremonial'),
                    default => __('member.type_fixed'),
                } }}
            </small>
        </div>
        <span class="badge badge-{{ match($tontine->status) { 'active' => 'success', 'completed' => 'secondary', default => 'warning' } }} fs-6">
            {{ match($tontine->status) { 'active' => 'Active', 'completed' => 'Terminée', 'pending' => 'En attente', 'suspended' => 'Suspendue', default => ucfirst($tontine->status) } }}
        </span>
        @if(auth()->id() === $tontine->created_by)
        <a href="{{ route('tontines.edit', $tontine) }}" class="btn btn-sm btn-light rounded-circle">
            <i class="fas fa-pen"></i>
        </a>
        @if($tontine->status === 'pending')
        <button type="button" class="btn btn-sm btn-outline-danger rounded-circle"
                @click="window.dispatchEvent(new CustomEvent('open-modal', { detail: { id: 'delete-modal', action: '{{ route('tontines.destroy', $tontine) }}', message: 'Supprimer cette tontine ?', confirmText: 'Supprimer', type: 'danger' } }))" aria-label="Supprimer">
            <i class="fas fa-trash"></i>
        </button>
        @endif
        @endif
    </div>

    @if($myMemberStatus !== 'excluded')
    <div class="row g-3 mb-4">
        <div class="col-4">
            <div class="stat-card text-center">
                <div class="stat-value text-green">{{ number_format($tontine->amount, 0, ',', ' ') }}</div>
                <div class="stat-label">FCFA / cycle</div>
            </div>
        </div>
        <div class="col-4">
            <div class="stat-card text-center">
                <div class="stat-value text-indigo">{{ $tontine->members->where('pivot.status', 'active')->count() }}</div>
                <div class="stat-label">Membres</div>
            </div>
        </div>
        <div class="col-4">
            <div class="stat-card text-center">
                <div class="stat-value text-warning">{{ $cyclesPaids }}/{{ $tontine->cycles->count() }}</div>
                <div class="stat-label">Cycles</div>
            </div>
        </div>
    </div>
    @endif

    @if($myMemberStatus !== 'excluded')
        @include('tontines.partials.type-guide')
    @endif

    @if($myMemberStatus === 'active')
        @include('tontines.partials.member-action')
    @endif

    @if($myMemberStatus !== 'excluded' && $tontine->status !== 'pending')
    <div class="card mb-4">
        <h6 class="fw-semibold mb-3">{{ __('member.summary') }}</h6>
        <div class="row g-2 text-center">
            <div class="col-4">
                <div class="stat-card">
                    <div class="stat-value text-green fs-6">{{ number_format($totalCollecte / 1000, 0) }}K</div>
                    <div class="stat-label">{{ __('member.collected') }}</div>
                </div>
            </div>
            <div class="col-4">
                <div class="stat-card">
                    <div class="stat-value text-indigo fs-6">{{ $cyclesPaids }}/{{ $tontine->cycles->count() }}</div>
                    <div class="stat-label">{{ __('member.cycles_paid') }}</div>
                </div>
            </div>
            @if(in_array($tontine->type, ['fixed', 'auction']))
            <div class="col-4">
                <div class="stat-card">
                    @if($currentCycle && $currentCycle->beneficiary_id === auth()->id())
                        <div class="stat-value text-warning fs-6">Maintenant</div>
                        <div class="stat-label">{{ __('member.your_turn') }}</div>
                    @elseif($turnEstimate && ($turnEstimate['status'] ?? '') === 'waiting')
                        <div class="stat-value text-indigo fs-6">~{{ $turnEstimate['members_ahead'] }}</div>
                        <div class="stat-label">{{ __('member.turns_before_you') }}</div>
                    @elseif(($turnEstimate['status'] ?? '') === 'already_won')
                        <div class="stat-value text-success fs-6">✓</div>
                        <div class="stat-label">{{ __('member.already_served') }}</div>
                    @elseif($myPosition)
                        <div class="stat-value text-indigo fs-6">#{{ $myPosition }}</div>
                        <div class="stat-label">{{ __('member.my_position') }}</div>
                    @else
                        <div class="stat-value text-muted fs-6">—</div>
                        <div class="stat-label">{{ __('member.rotation') }}</div>
                    @endif
                </div>
            </div>
            @elseif($tontine->type === 'forced_saving' && isset($mySaved))
            <div class="col-4">
                <div class="stat-card">
                    <div class="stat-value text-green fs-6">{{ number_format($mySaved / 1000, 0) }}K</div>
                    <div class="stat-label">Mon épargne</div>
                </div>
            </div>
            @endif
        </div>
        @if($myPastWin && ($turnEstimate['status'] ?? null) === 'already_won')
        <p class="text-muted small mb-0 mt-2">
            <i class="fas fa-trophy me-1"></i>{{ __('member.last_pot_received', ['num' => $myPastWin->cycle_number]) }}
            ({{ $myPastWin->drawn_at?->format('d/m/Y') ?? '—' }}).
        </p>
        @endif
    </div>
    @endif

    @if($tontine->status === 'pending' && auth()->id() === $tontine->created_by)
    <div class="card mb-4 border-warning">
        @error('activate')
            <div class="alert alert-danger mb-3">{{ $message }}</div>
        @enderror
        <p class="text-muted mb-2">La tontine est en attente. Activez-la pour démarrer les cycles.</p>
        <p class="text-muted small mb-2">
            <i class="fas fa-users me-1"></i>
            {{ $tontine->activeMembers()->count() }} membre(s) actif(s) (minimum 2 requis)
        </p>
        <form method="POST" action="{{ route('tontines.activate', $tontine) }}">
            @csrf
            <button type="submit" class="btn btn-warning w-100"
                {{ $tontine->activeMembers()->count() < 2 ? 'disabled' : '' }}>
                <i class="fas fa-play me-2"></i>Activer la tontine
            </button>
        </form>
    </div>
    @endif

    @if($tontine->type === 'ceremonial' && auth()->id() === $tontine->created_by && $tontine->status === 'active')
    <div class="card mb-4">
        <h6 class="fw-semibold mb-3">🎯 Bénéficiaire de l'événement</h6>
        <form method="POST" action="{{ route('tontines.beneficiary', $tontine) }}" class="d-flex gap-2">
            @csrf
            <select name="beneficiary_id" class="form-select" required>
                <option value="">Sélectionner un membre...</option>
                @foreach($tontine->members->where('pivot.status', 'active') as $member)
                <option value="{{ $member->id }}"
                    {{ $currentCycle && $currentCycle->beneficiary_id === $member->id ? 'selected' : '' }}>
                    {{ $member->name }}
                </option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-primary">Changer</button>
        </form>
        @if($currentCycle && $currentCycle->beneficiary)
        <p class="text-muted small mt-2 mb-0">
            <i class="fas fa-user me-1"></i>Bénéficiaire actuel : <strong>{{ $currentCycle->beneficiary->name }}</strong>
        </p>
        @endif
    </div>
    @endif

    @if($myMemberStatus === 'active' || auth()->id() === $tontine->created_by)
        @include('tontines.partials.cycle-current')
    @endif

    @if(auth()->id() === $tontine->created_by)
        @if($acceptsNewMembers)
        <div class="card mb-4">
            <h6 class="fw-semibold mb-3">{{ __('member.invite_members') }}</h6>
            <div class="bg-light rounded-3 p-3 mb-3">
                <div class="small text-muted mb-1">Code d'invitation</div>
                <div class="d-flex align-items-center justify-content-between gap-2">
                    <strong class="fs-5" style="letter-spacing:.15em;">{{ $tontine->code }}</strong>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="copyToClipboard('{{ $tontine->code }}')">
                        <i class="fas fa-copy me-1"></i>Copier
                    </button>
                </div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="https://wa.me/?text={{ urlencode('Rejoins ma tontine «'.$tontine->name.'» avec le code '.$tontine->code.' : '.$inviteUrl) }}"
                   target="_blank" rel="noreferrer" class="btn btn-sm btn-success">
                    <i class="fab fa-whatsapp me-1"></i>Partager sur WhatsApp
                </a>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="copyToClipboard('{{ $inviteUrl }}')">
                    <i class="fas fa-link me-1"></i>Copier le lien
                </button>
            </div>
        </div>
        @else
        <div class="alert alert-secondary mb-4">
            <i class="fas fa-info-circle me-1"></i>
            {{ __('member.no_new_members') }}
        </div>
        @endif
    @endif

    @if($myMemberStatus !== 'excluded')
        @include('tontines.partials.members-list')

        @if($myMemberStatus === 'active')
            @include('tontines.partials.past-cycles')
        @endif
    @endif

    @if(auth()->id() !== $tontine->created_by && $myMemberStatus === 'active')
    <div class="card mb-4 border-danger">
        <div class="d-flex align-items-center gap-3">
            <div class="icon-box bg-red-light"><i class="fas fa-sign-out-alt text-danger"></i></div>
            <div class="flex-grow-1">
                <p class="fw-semibold mb-0">{{ __('member.leave_tontine') }}</p>
                <small class="text-muted">{{ __('member.leave_help') }}</small>
            </div>
            <form method="POST" action="{{ route('tontines.leave', $tontine) }}" onsubmit="return confirm(@js(__('member.leave_tontine') . ' ?'));">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill">{{ __('member.leave') }}</button>
            </form>
        </div>
    </div>
    @endif

    <x-confirm-modal id="delete-modal" method="DELETE"
        icon="trash"
        :action="route('tontines.destroy', $tontine)"
        confirm-text="Supprimer" />

    @if($currentCycle)
    <x-confirm-modal id="draw-modal" method="POST" type="primary"
        icon="random"
        :action="route('cycles.draw', $currentCycle)"
        message="Effectuer le tirage ? Le bénéficiaire sera désigné selon les règles de la tontine."
        confirm-text="Confirmer" />
    @endif

</div>
@endsection
