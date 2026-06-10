@extends('layouts.app')
@section('title', $tontine->name . ' — Tontine en ligne | TontineSN')
@section('meta_description', "Tontine {$tontine->name} — {$tontine->type_label}, montant: ".number_format($tontine->amount, 0, ',', ' ')." FCFA, {$tontine->active_members_count} membre(s).")

@section('content')
<div class="container py-4" style="max-width:860px;">

    {{-- 1. ALERTES STATUT (pending / excluded) --}}
    @if($myMemberStatus === 'pending')
    <div class="alert alert-warning d-flex align-items-center gap-2 mb-3">
        <i class="fas fa-hourglass-half flex-shrink-0"></i>
        <div class="flex-grow-1">
            <strong>{{ __('member.pending_request') }}</strong><br>
            <small>{{ __('member.pending_request_help') }}</small>
        </div>
        <form method="POST" action="{{ route('tontines.leave', $tontine) }}">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill flex-shrink-0"
                    @click.prevent="window.dispatchEvent(new CustomEvent('open-modal', { detail: { id: 'cancel-pending-modal', action: '{{ route('tontines.leave', $tontine) }}', method: 'DELETE', message: 'Annuler votre demande d\'adhésion ?', confirmText: 'Annuler ma demande', type: 'danger' } }))">
                <i class="fas fa-times me-1"></i>Annuler
            </button>
        </form>
    </div>
    @endif

    @if($myMemberStatus === 'excluded')
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-3">
        <i class="fas fa-ban flex-shrink-0"></i>
        <div class="flex-grow-1"><strong>{{ __('member.access_denied') }}</strong> — {{ __('member.excluded') }}</div>
        <a href="{{ route('tontines.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill flex-shrink-0">
            <i class="fas fa-arrow-left me-1"></i>Mes tontines
        </a>
    </div>
    @endif

    {{-- 2. BREADCRUMB + EN-TÊTE --}}
    <nav aria-label="breadcrumb" class="mb-3 small">
        <ol class="breadcrumb bg-transparent p-0 m-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-green">{{ __('member.home') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('tontines.index') }}" class="text-green">{{ __('member.my_tontines') }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $tontine->name }}</li>
        </ol>
    </nav>

    {{-- NAVIGATION ANCRES --}}
    <nav class="dash-nav-tabs mb-3" aria-label="Sections de la tontine">
        <a href="#section-cycle" class="dash-nav-tab"><i class="fas fa-sync-alt"></i>Cycle</a>
        <a href="#section-stats" class="dash-nav-tab"><i class="fas fa-chart-bar"></i>Stats</a>
        <a href="#section-membres" class="dash-nav-tab"><i class="fas fa-users"></i>Membres</a>
        @if(auth()->id() === $tontine->created_by)
        <a href="#section-gestion" class="dash-nav-tab"><i class="fas fa-cog"></i>Gestion</a>
        @endif
    </nav>

    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="{{ route('tontines.index') }}" class="btn btn-sm btn-light rounded-circle" aria-label="Retour">
            <i class="fas fa-arrow-left" aria-hidden="true"></i>
        </a>
        <div class="flex-grow-1 min-width-0">
            <h4 class="fw-bold mb-0 text-truncate">{{ $tontine->name }}</h4>
            <small class="text-muted">
                Code : <strong>{{ $tontine->code }}</strong>
                · {{ match($tontine->type) {
                    'auction'      => __('member.type_auction'),
                    'forced_saving'=> __('member.type_saving'),
                    'ceremonial'   => __('member.type_ceremonial'),
                    default        => __('member.type_fixed'),
                } }}
            </small>
        </div>
        <span class="badge badge-{{ match($tontine->status) { 'active' => 'success', 'completed' => 'secondary', default => 'warning' } }} flex-shrink-0">
            {{ match($tontine->status) { 'active' => 'Active', 'completed' => 'Terminée', 'pending' => 'En attente', 'suspended' => 'Suspendue', default => ucfirst($tontine->status) } }}
        </span>
        @if(auth()->id() === $tontine->created_by)
        <a href="{{ route('tontines.edit', $tontine) }}" class="btn btn-sm btn-light rounded-circle flex-shrink-0" aria-label="Modifier">
            <i class="fas fa-pen" aria-hidden="true"></i>
        </a>
        @if($tontine->status === 'pending')
        <button type="button" class="btn btn-sm btn-outline-danger rounded-circle flex-shrink-0"
                @click="window.dispatchEvent(new CustomEvent('open-modal', { detail: { id: 'delete-modal', action: '{{ route('tontines.destroy', $tontine) }}', method: 'DELETE', message: 'Supprimer cette tontine définitivement ?', confirmText: 'Supprimer', type: 'danger' } }))"
                aria-label="Supprimer">
            <i class="fas fa-trash" aria-hidden="true"></i>
        </button>
        @endif
        @endif
    </div>

    {{-- 3. ÉTAT INTERMÉDIAIRE : génération cycles async --}}
    @if($tontine->status === 'active' && $tontine->cycles->isEmpty())
    <div class="alert alert-info d-flex align-items-center gap-2 mb-4" id="cycles-generating">
        <i class="fas fa-spinner fa-spin flex-shrink-0"></i>
        <div class="flex-grow-1">
            <strong>Génération des cycles en cours…</strong>
            <p class="mb-0 small">Les cycles seront disponibles dans quelques secondes.</p>
        </div>
        <button class="btn btn-sm btn-outline-info rounded-pill flex-shrink-0" onclick="location.reload()">
            <i class="fas fa-sync-alt me-1"></i>Actualiser
        </button>
    </div>
    @push('scripts')
    <script>
    (function pollCycles() {
        let attempts = 0;
        const maxAttempts = 10;
        function poll() {
            if (attempts >= maxAttempts) {
                const el = document.getElementById('cycles-generating');
                if (el) el.innerHTML = '<i class="fas fa-exclamation-triangle text-warning flex-shrink-0"></i><div class="flex-grow-1"><strong>La génération prend plus de temps que prévu.</strong><p class="mb-0 small">Veuillez actualiser la page dans quelques instants.</p></div><button class="btn btn-sm btn-outline-warning rounded-pill flex-shrink-0" onclick="location.reload()"><i class="fas fa-sync-alt me-1"></i>Actualiser</button>';
                return;
            }
            attempts++;
            setTimeout(function() {
                fetch(location.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(r => r.text()).then(html => {
                        const tmp = document.createElement('div');
                        tmp.innerHTML = html;
                        tmp.querySelector('#cycles-generating') ? poll() : location.reload();
                    }).catch(() => poll());
            }, 3000);
        }
        poll();
    })();
    </script>
    @endpush
    @endif

    {{-- 4. ACTIVATION (créateur, tontine pending) --}}
    @if($tontine->status === 'pending' && auth()->id() === $tontine->created_by)
    <div class="card mb-4 border-warning">
        @error('activate')
            <div class="alert alert-danger mb-3">{{ $message }}</div>
        @enderror
        <p class="fw-semibold mb-1">Tontine en attente d'activation</p>
        <p class="text-muted small mb-3">
            <i class="fas fa-users me-1"></i>
            {{ $tontine->activeMembers()->count() }} membre(s) actif(s) — minimum 2 requis pour démarrer.
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

    @if($myMemberStatus !== 'excluded')

    {{-- 5. ACTION PRINCIPALE : CYCLE EN COURS (priorité absolue) --}}
    <div id="section-cycle">
    @if($myMemberStatus === 'active' || auth()->id() === $tontine->created_by)
        @include('tontines.partials.cycle-current')
    @endif
    </div>

    {{-- 5b. DASHBOARD CRÉATEUR --}}
    @include('tontines.partials.creator-dashboard')

    {{-- 6. MEMBRE-ACTION (raccourcis paiement rapide) --}}
    @if($myMemberStatus === 'active')
        @include('tontines.partials.member-action')
    @endif

    {{-- 7. BÉNÉFICIAIRE CÉRÉMONIEL (créateur) --}}
    @if($tontine->type === 'ceremonial' && auth()->id() === $tontine->created_by && $tontine->status === 'active')
    <div class="card mb-4">
        <h6 class="fw-semibold mb-1">🎯 Bénéficiaire de l'événement</h6>
        <p class="text-muted small mb-3">Le membre désigné recevra le pot collecté à la date de l'événement.</p>
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
            <button type="submit" class="btn btn-primary flex-shrink-0">Enregistrer</button>
        </form>
        @if($currentCycle && $currentCycle->beneficiary)
        <p class="text-muted small mt-2 mb-0">
            <i class="fas fa-user me-1"></i>Bénéficiaire actuel : <strong>{{ $currentCycle->beneficiary->name }}</strong>
        </p>
        @endif
    </div>
    @endif

    {{-- 8. STATS RAPIDES --}}
    <div id="section-stats">
    @if($tontine->pot_total > 0)
    <div class="pot-highlight mb-4">
        <i class="fas fa-coins"></i>
        Pot actuel : <strong>{{ number_format($tontine->pot_total, 0, ',', ' ') }} FCFA</strong>
    </div>
    @endif
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

    </div>{{-- /section-stats --}}

    {{-- Lien historique direct --}}
    <div class="d-flex justify-content-end mb-3">
        <a href="{{ route('historique.index', ['tontine_id' => $tontine->id]) }}" class="tontine-history-link">
            <i class="fas fa-history"></i>Voir l'historique de cette tontine
        </a>
    </div>

    {{-- 9. RÉSUMÉ PERSONNEL (collapsible) --}}
    @if($tontine->status !== 'pending')
    <div class="mb-4" x-data="{ open: false }">
        <button type="button" class="btn btn-sm btn-outline-secondary w-100 d-flex align-items-center justify-content-between"
                @click="open = !open" :aria-expanded="open">
            <span><i class="fas fa-chart-bar me-2"></i>{{ __('member.summary') }}</span>
            <i class="fas fa-chevron-down" :style="open ? 'transform:rotate(180deg)' : ''" style="transition:transform 0.2s;"></i>
        </button>
        <div x-show="open" x-collapse class="card mt-2">
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
                            @php
                                $estimatedCycle = $tontine->cycles->where('status', '!=', 'paid')->sortBy('cycle_number')->skip($turnEstimate['members_ahead'])->first();
                            @endphp
                            <div class="stat-value text-indigo fs-6">~{{ $turnEstimate['members_ahead'] }}</div>
                            <div class="stat-label">{{ __('member.turns_before_you') }}</div>
                            @if($estimatedCycle?->due_date)
                            <small class="text-muted d-block" style="font-size:10px;">≈ {{ $estimatedCycle->due_date->isoFormat('MMM YYYY') }}</small>
                            @endif
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
    </div>
    @endif

    {{-- 10. TYPE-GUIDE (collapsible, visible au besoin) --}}
    <div class="mb-4" x-data="{ open: false }">
        <button type="button" class="btn btn-sm btn-outline-secondary w-100 d-flex align-items-center justify-content-between"
                @click="open = !open" :aria-expanded="open">
            <span><i class="fas fa-info-circle me-2"></i>Comment fonctionne cette tontine ?</span>
            <i class="fas fa-chevron-down" :style="open ? 'transform:rotate(180deg)' : ''" style="transition:transform 0.2s;"></i>
        </button>
        <div x-show="open" x-collapse class="mt-2">
            @include('tontines.partials.type-guide')
        </div>
    </div>

    {{-- 11. INVITATION --}}
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
                <a href="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode($inviteUrl) }}"
                   target="_blank" rel="noreferrer" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-qrcode me-1"></i>QR Code
                </a>
            </div>
        </div>
        @else
        <div class="alert alert-secondary mb-4">
            <i class="fas fa-info-circle me-1"></i>
            {{ __('member.no_new_members') }}
        </div>
        @endif
    @elseif($myMemberStatus === 'active' && $acceptsNewMembers)
    {{-- Membres actifs peuvent aussi inviter --}}
    <div class="card mb-4">
        <h6 class="fw-semibold mb-3"><i class="fas fa-share-alt me-2"></i>Inviter quelqu'un</h6>
        <div class="d-flex flex-wrap gap-2">
            <a href="https://wa.me/?text={{ urlencode('Rejoins notre tontine «'.$tontine->name.'» ! Code : '.$tontine->code.' — '.$inviteUrl) }}"
               target="_blank" rel="noreferrer" class="btn btn-sm btn-success">
                <i class="fab fa-whatsapp me-1"></i>Partager sur WhatsApp
            </a>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="copyToClipboard('{{ $inviteUrl }}')">
                <i class="fas fa-link me-1"></i>Copier le lien
            </button>
            <a href="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode($inviteUrl) }}"
               target="_blank" rel="noreferrer" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-qrcode me-1"></i>QR Code
            </a>
        </div>
    </div>
    @endif

    {{-- 12. LISTE MEMBRES + HISTORIQUE CYCLES --}}
    <div id="section-membres">
    @include('tontines.partials.members-list')

    @if($myMemberStatus === 'active')
        @include('tontines.partials.past-cycles')
    @endif
    </div>

    {{-- 13. QUITTER LA TONTINE --}}
    <div id="section-gestion">
    @if(auth()->id() !== $tontine->created_by && $myMemberStatus === 'active')
    <div class="card mb-4 border-danger">
        <div class="d-flex align-items-center gap-3">
            <div class="icon-box bg-red-light"><i class="fas fa-sign-out-alt text-danger"></i></div>
            <div class="flex-grow-1">
                <p class="fw-semibold mb-0">{{ __('member.leave_tontine') }}</p>
                <small class="text-muted">{{ __('member.leave_help') }}</small>
            </div>
            <button type="button" class="btn btn-outline-danger btn-sm rounded-pill flex-shrink-0"
                    @click="window.dispatchEvent(new CustomEvent('open-modal', { detail: { id: 'leave-modal', action: '{{ route('tontines.leave', $tontine) }}', method: 'DELETE', message: '{{ __('member.leave_tontine') }} ?', confirmText: '{{ __('member.leave') }}', type: 'danger' } }))">
                {{ __('member.leave') }}
            </button>
        </div>
    </div>
    @endif

    {{-- 14. TRANSFERT DE PROPRIÉTÉ --}}
    @if(auth()->id() === $tontine->created_by && $tontine->members->where('pivot.status', 'active')->where('id', '!=', auth()->id())->count() > 0)
    <div class="card mb-4 border-warning" x-data="{ open: false }">
        <button type="button" class="d-flex align-items-center gap-2 w-100 bg-transparent border-0 p-0 text-start"
                @click="open = !open">
            <h6 class="fw-semibold mb-0 text-warning flex-grow-1">
                <i class="fas fa-exchange-alt me-1"></i>Transférer la propriété
            </h6>
            <i class="fas fa-chevron-down text-warning" :style="open ? 'transform:rotate(180deg)' : ''" style="transition:transform 0.2s;"></i>
        </button>
        <div x-show="open" x-collapse class="mt-3">
            <p class="text-muted small mb-3">Désignez un autre membre actif comme nouveau gestionnaire. Vous deviendrez membre ordinaire.</p>
            <form method="POST" action="{{ route('tontines.transfer', $tontine) }}">
                @csrf
                @error('new_owner_id')
                    <div class="alert alert-danger py-2 mb-2 small">{{ $message }}</div>
                @enderror
                <div class="d-flex gap-2">
                    <select name="new_owner_id" class="form-select" required>
                        <option value="">Choisir un membre...</option>
                        @foreach($tontine->members->where('pivot.status', 'active')->where('id', '!=', auth()->id()) as $m)
                        <option value="{{ $m->id }}">{{ $m->name }}</option>
                        @endforeach
                    </select>
                    <button type="button" class="btn btn-warning flex-shrink-0"
                            @click="window.dispatchEvent(new CustomEvent('open-modal', { detail: { id: 'transfer-modal', action: '{{ route('tontines.transfer', $tontine) }}', method: 'POST', message: 'Confirmer le transfert de propriété ? Vous perdrez les droits de gestion.', confirmText: 'Transférer', type: 'warning' } }))">
                        Transférer
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    @endif {{-- fin bloc non-exclu --}}
    </div>{{-- /section-gestion --}}

    {{-- MODALES --}}
    <x-confirm-modal id="delete-modal" method="DELETE"
        icon="trash"
        :action="route('tontines.destroy', $tontine)"
        message="Supprimer cette tontine définitivement ?"
        confirm-text="Supprimer" />

    <x-confirm-modal id="leave-modal" method="DELETE"
        icon="sign-out-alt"
        :action="route('tontines.leave', $tontine)"
        message="{{ __('member.leave_tontine') }} ?"
        confirm-text="{{ __('member.leave') }}" />

    <x-confirm-modal id="cancel-pending-modal" method="DELETE"
        icon="times"
        :action="route('tontines.leave', $tontine)"
        message="Annuler votre demande d'adhésion ?"
        confirm-text="Annuler ma demande" />

    <x-confirm-modal id="transfer-modal" method="POST"
        icon="exchange-alt"
        :action="route('tontines.transfer', $tontine)"
        message="Confirmer le transfert de propriété ? Vous perdrez les droits de gestion."
        confirm-text="Transférer" />

    @if($currentCycle)
    <x-confirm-modal id="draw-modal" method="POST" type="primary"
        icon="random"
        :action="route('cycles.draw', $currentCycle)"
        message="Effectuer le tirage ? Le bénéficiaire sera désigné selon les règles de la tontine."
        confirm-text="Confirmer" />
    @endif

</div>
@endsection

@push('scripts')
<script>
(function () {
    const tabs = document.querySelectorAll('.dash-nav-tab');
    const sections = ['section-cycle','section-stats','section-membres','section-gestion'];
    function setActive() {
        let current = sections[0];
        sections.forEach(id => {
            const el = document.getElementById(id);
            if (el && el.getBoundingClientRect().top <= 120) current = id;
        });
        tabs.forEach(t => t.classList.toggle('active', t.getAttribute('href') === '#' + current));
    }
    window.addEventListener('scroll', setActive, { passive: true });
    setActive();
})();
</script>
@endpush
