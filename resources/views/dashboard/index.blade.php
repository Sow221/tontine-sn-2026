@extends('layouts.app')
@section('title', 'Accueil')

@section('content')
@php
    $h = now()->hour;
    $greeting = $h < 6 || $h >= 18 ? __('member.greeting_evening') : ($h < 13 ? __('member.greeting_morning') : __('member.greeting_afternoon'));
    $totalCotise = $recentTransactions->where('status', 'success')->sum('amount');
    $activeTontinesCount = $activeTontines->count();
    $upcomingCount = $upcomingPayments->count();
    $overdueCount = $overduePayments->count();
@endphp

<div class="container py-4">

    {{-- ── NAVIGATION ONGLETS DASHBOARD ───────────────────────────── --}}
    @php
        $hasOverdue   = $overduePayments->isNotEmpty();
        $hasUpcoming  = $upcomingPayments->isNotEmpty();
        $pendingCount = $overduePayments->count() + $upcomingPayments->count();
    @endphp
    <nav class="dash-nav-tabs" aria-label="Sections du tableau de bord">
        <a href="#section-tontines" class="dash-nav-tab"><i class="fas fa-users"></i>Tontines</a>
        <a href="#section-paiements" class="dash-nav-tab">
            <i class="fas fa-credit-card"></i>Paiements
            @if($pendingCount > 0)<span class="tab-badge">{{ $pendingCount }}</span>@endif
        </a>
        <a href="#section-gamification" class="dash-nav-tab"><i class="fas fa-trophy"></i>Score</a>
        <a href="#section-transactions" class="dash-nav-tab"><i class="fas fa-history"></i>Historique</a>
    </nav>

    {{-- ── HERO RÉSUMÉ ──────────────────────────────────────────────── --}}
    <div class="dash-hero mb-4">
        <div class="dash-hero__left">
            <p class="dash-hero__greeting">{{ $greeting }}</p>
            <h2 class="dash-hero__name">{{ $user->name ?? __('member.member') }}</h2>
            <div class="dash-hero__meta">
                @if($creditScore->score > 0)
                <span class="dash-hero__score-badge bg-{{ $creditScore->badgeColor() }}">
                    ★ {{ $creditScore->score }}/10
                </span>
                @endif
                <span class="dash-hero__date">{{ now()->isoFormat('D MMM YYYY') }}</span>
            </div>
        </div>
        <div class="dash-hero__right">
            <div class="dash-kpi">
                <div class="dash-kpi__value">{{ $activeTontinesCount }}</div>
                <div class="dash-kpi__label">Tontines</div>
            </div>
            <div class="dash-kpi {{ $overdueCount > 0 ? 'dash-kpi--alert' : '' }}">
                <div class="dash-kpi__value">{{ $upcomingCount }}</div>
                <div class="dash-kpi__label">À payer{{ $overdueCount > 0 ? " ($overdueCount retard)" : '' }}</div>
            </div>
            <div class="dash-kpi">
                <div class="dash-kpi__value">{{ $gamification['payment_streak'] }}🔥</div>
                <div class="dash-kpi__label">Série</div>
            </div>
        </div>
    </div>

    {{-- ── NOUVEAU BADGE ─────────────────────────────────────────────── --}}
    @if($newBadges->isNotEmpty())
    <div class="alert-badge mb-3">
        <span class="fs-4">🏅</span>
        <div>
            <strong>Nouveau(x) badge(s) débloqué(s) !</strong>
            <div class="mt-1 d-flex flex-wrap gap-1">
                @foreach($newBadges as $badge)
                <span class="badge bg-warning text-dark">{{ $badge->icon }} {{ $badge->name }}</span>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- ── BÉNÉFICIAIRE — priorité absolue ──────────────────────────── --}}
    @foreach($beneficiaireCycles as $cycle)
    <div class="action-banner action-banner--gold mb-3">
        <div class="action-banner__icon">🎉</div>
        <div class="action-banner__body">
            <strong>C'est votre tour de recevoir !</strong>
            <p class="mb-0 small text-muted">
                {{ $cycle->tontine->name }} — Cycle {{ $cycle->cycle_number }} ·
                <strong class="text-green">{{ number_format($cycle->tontine->amount * $cycle->tontine->active_members_count, 0, ',', ' ') }} FCFA</strong>
            </p>
        </div>
        <a href="{{ route('tontines.show', $cycle->tontine) }}" class="btn btn-sm btn-warning rounded-pill flex-shrink-0">Voir</a>
    </div>
    @endforeach

    {{-- ── RETARDS ────────────────────────────────────────────────────── --}}
    @foreach($overduePayments as $cycle)
    <div class="action-banner action-banner--red mb-3">
        <div class="action-banner__icon"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="action-banner__body">
            <strong>Cotisation en retard</strong>
            <p class="mb-0 small text-muted">{{ $cycle->tontine->name }} · Échéance dépassée le {{ $cycle->due_date->format('d/m/Y') }}</p>
        </div>
        <div class="d-flex flex-column align-items-end gap-1 flex-shrink-0">
            <div class="d-flex gap-1">
                <img src="{{ asset('images/logo wave.png') }}" alt="Wave" title="Payer par Wave" style="width:22px;height:22px;border-radius:4px;object-fit:contain;border:1px solid #e2e8f0;">
                <img src="{{ asset('images/logo orange money.png') }}" alt="Orange Money" title="Payer par Orange Money" style="width:22px;height:22px;border-radius:4px;object-fit:contain;border:1px solid #e2e8f0;">
            </div>
            <a href="{{ route('cycles.pay', $cycle) }}" class="btn btn-sm btn-danger rounded-pill flex-shrink-0">Régulariser</a>
        </div>
    </div>
    @endforeach

    {{-- ── EN ATTENTE D'APPROBATION ───────────────────────────────────── --}}
    @foreach($pendingMemberships as $tontine)
    <div class="action-banner action-banner--yellow mb-3">
        <div class="action-banner__icon"><i class="fas fa-hourglass-half"></i></div>
        <div class="action-banner__body">
            <strong>Adhésion en attente</strong>
            <p class="mb-0 small text-muted">{{ $tontine->name }} — En attente d'approbation du créateur</p>
        </div>
        <div class="d-flex gap-2 flex-shrink-0">
            <a href="{{ route('tontines.show', $tontine) }}" class="btn btn-sm btn-outline-warning rounded-pill">Voir</a>
            <form method="POST" action="{{ route('tontines.leave', $tontine) }}" onsubmit="return confirm('Annuler votre demande d\'adhésion ?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill"><i class="fas fa-times"></i> Annuler</button>
            </form>
        </div>
    </div>
    @endforeach

    {{-- ── MES TONTINES ACTIVES ──────────────────────────────────────── --}}
    <div class="section-divider" id="section-tontines"></div>
    @if($activeTontines->isNotEmpty())
    <div class="section-header mb-3">
        <h5 class="section-header__title">Mes tontines actives</h5>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('tontines.create') }}" class="btn btn-sm btn-primary rounded-pill">
                <i class="fas fa-plus me-1"></i>Nouvelle
            </a>
            <a href="{{ route('tontines.index') }}" class="section-header__link">Voir tout</a>
        </div>
    </div>

    @foreach($activeTontines as $tontine)
    @php
        $currentCycle = $tontine->currentCycle;
        $typeColors = [
            'auction'       => ['bg' => '#f5f3ff', 'border' => '#8b5cf6', 'icon' => '🏷️'],
            'forced_saving' => ['bg' => '#eff6ff', 'border' => '#3b82f6', 'icon' => '💰'],
            'ceremonial'    => ['bg' => '#fdf2f8', 'border' => '#ec4899', 'icon' => '🎊'],
            'default'       => ['bg' => '#f0fdf4', 'border' => '#009639', 'icon' => '🤝'],
        ];
        $tc = $typeColors[$tontine->type] ?? $typeColors['default'];
        $progress = $currentCycle ? $currentCycle->completionRate() : 0;
    @endphp
    <a href="{{ route('tontines.show', $tontine) }}" class="tontine-card mb-3 text-decoration-none"
       style="--tc-bg:{{ $tc['bg'] }};--tc-border:{{ $tc['border'] }};">
        <div class="tontine-card__top">
            <div class="tontine-card__avatar">
                <span>{{ $tc['icon'] }}</span>
            </div>
            <div class="tontine-card__info">
                <h6 class="tontine-card__name">{{ $tontine->name }}</h6>
                <p class="tontine-card__meta">
                    {{ number_format($tontine->amount, 0, ',', ' ') }} FCFA ·
                    {{ match($tontine->frequency) { 'daily' => 'Quotidienne', 'weekly' => 'Hebdo', 'monthly' => 'Mensuelle', default => $tontine->frequency } }} ·
                    {{ $tontine->active_members_count }} membre(s)
                </p>
            </div>
            <div class="tontine-card__status">
                @if($tontine->has_paid_current)
                    <span class="status-pill status-pill--paid"><i class="fas fa-check me-1"></i>Payé</span>
                @elseif($tontine->payment_pending)
                    <span class="status-pill status-pill--pending"><i class="fas fa-clock me-1"></i>En cours</span>
                @elseif($currentCycle)
                    <div class="d-flex flex-column align-items-end gap-1">
                        <span class="status-pill status-pill--due"><i class="fas fa-circle me-1"></i>À payer</span>
                        <div class="d-flex gap-1">
                            <img src="{{ asset('images/logo wave.png') }}" alt="Wave" title="Wave" style="width:18px;height:18px;border-radius:3px;object-fit:contain;border:1px solid #e2e8f0;">
                            <img src="{{ asset('images/logo orange money.png') }}" alt="OM" title="Orange Money" style="width:18px;height:18px;border-radius:3px;object-fit:contain;border:1px solid #e2e8f0;">
                        </div>
                    </div>
                @endif
            </div>
        </div>

        @if($currentCycle)
        <div class="tontine-card__bottom">
            <div class="tontine-card__progress-wrap">
                <div class="tontine-card__progress-bar" style="width:{{ $progress }}%;background:{{ $tc['border'] }};"></div>
            </div>
            <div class="tontine-card__progress-info">
                <span class="small text-muted">Cycle {{ $currentCycle->cycle_number }} · Échéance {{ $currentCycle->due_date->format('d/m') }}</span>
                <span class="small fw-semibold" style="color:{{ $tc['border'] }};">{{ $progress }}%</span>
            </div>
        </div>
        @endif

        <div class="tontine-card__footer">
            <span class="tontine-card__pot">
                <i class="fas fa-coins me-1"></i>
                Pot : <strong>{{ number_format($tontine->pot_total, 0, ',', ' ') }} FCFA</strong>
            </span>
            @if($tontine->my_position)
            <span class="tontine-card__position">Position #{{ $tontine->my_position }}</span>
            @endif
        </div>
    </a>
    @endforeach

    @else
    {{-- Aucune tontine --}}
    <div class="empty-state mb-4">
        <div class="empty-state__icon">🤝</div>
        <h6 class="empty-state__title">Vous n'avez pas encore de tontine</h6>
        <p class="empty-state__desc">Créez votre première tontine ou rejoignez un groupe.</p>
        <div class="d-flex gap-2 justify-content-center flex-wrap">
            <a href="{{ route('tontines.create') }}" class="btn btn-primary rounded-pill">
                <i class="fas fa-plus me-2"></i>Créer une tontine
            </a>
            <a href="{{ route('tontines.join.form') }}" class="btn btn-outline-primary rounded-pill">
                Rejoindre avec un code
            </a>
        </div>
    </div>
    @endif

    {{-- ── COTISATIONS À VENIR ────────────────────────────────────────── --}}
    @if($upcomingPayments->isNotEmpty())
    <div class="section-divider" id="section-paiements"></div>
    <div class="section-header mb-3 mt-2">
        <h5 class="section-header__title">
            Cotisations à payer
            <span class="badge bg-danger ms-1" style="font-size:11px;">{{ $upcomingCount }}</span>
        </h5>
    </div>
    @foreach($upcomingPayments as $cycle)
    @php $isOverdue = $cycle->isOverdue(); @endphp
    <div class="pay-row mb-2 {{ $isOverdue ? 'pay-row--overdue' : '' }}">
        <div class="pay-row__ring">
            <svg viewBox="0 0 36 36">
                <circle cx="18" cy="18" r="14" fill="none" stroke="var(--gray-border)" stroke-width="3"/>
                <circle cx="18" cy="18" r="14" fill="none"
                    stroke="{{ $isOverdue ? 'var(--red)' : 'var(--green)' }}"
                    stroke-width="3" stroke-linecap="round"
                    stroke-dasharray="{{ $cycle->completionRate() * 0.88 }}, 88"
                    transform="rotate(-90 18 18)"/>
            </svg>
            <span class="pay-row__pct" style="color:{{ $isOverdue ? 'var(--red)' : 'var(--green)' }};">{{ (int)$cycle->completionRate() }}%</span>
        </div>
        <div class="pay-row__info">
            <p class="pay-row__name">{{ $cycle->tontine->name }}</p>
            <small class="{{ $isOverdue ? 'text-danger fw-semibold' : 'text-muted' }}">
                {{ $isOverdue ? 'En retard depuis le' : 'Échéance le' }} {{ $cycle->due_date->format('d/m/Y') }}
                @if(!$isOverdue) ({{ $cycle->due_date->diffForHumans() }}) @endif
            </small>
        </div>
        <div class="pay-row__right">
            <span class="pay-row__amount">{{ number_format($cycle->tontine->amount, 0, ',', ' ') }} F</span>
            {{-- Logos opérateurs visibles avant même de cliquer --}}
            <div class="d-flex justify-content-end gap-1 mt-1 mb-1">
                <img src="{{ asset('images/logo wave.png') }}" alt="Wave" title="Wave" style="width:20px;height:20px;border-radius:4px;object-fit:contain;border:1px solid #e2e8f0;">
                <img src="{{ asset('images/logo orange money.png') }}" alt="Orange Money" title="Orange Money" style="width:20px;height:20px;border-radius:4px;object-fit:contain;border:1px solid #e2e8f0;">
                <span title="Free Money" style="width:20px;height:20px;border-radius:4px;background:#fef2f2;border:1px solid #fca5a5;display:inline-flex;align-items:center;justify-content:center;font-size:7px;font-weight:800;color:#E3000F;">FM</span>
            </div>
            <a href="{{ route('cycles.pay', $cycle) }}"
               class="btn btn-sm {{ $isOverdue ? 'btn-danger' : 'btn-primary' }} rounded-pill">
                {{ $isOverdue ? 'Régulariser' : 'Payer' }}
            </a>
        </div>
    </div>
    @endforeach
    @endif

    {{-- ── CALENDRIER ÉCHÉANCES ─────────────────────────────────────────── --}}
    @if($upcomingPayments->isNotEmpty() || $overduePayments->isNotEmpty())
    <div class="section-divider"></div>
    <div class="section-header mb-2">
        <h5 class="section-header__title">Calendrier des échéances</h5>
    </div>
    <div class="calendar-strip mb-4" role="list" aria-label="Échéances à venir">
        @foreach($overduePayments as $cycle)
        <div class="calendar-day calendar-day--due" role="listitem"
             title="{{ $cycle->tontine->name }} — En retard">
            <div class="calendar-day__num">{{ $cycle->due_date->format('d') }}</div>
            <div class="calendar-day__month">{{ $cycle->due_date->isoFormat('MMM') }}</div>
            <div class="calendar-day__dot"></div>
        </div>
        @endforeach
        @foreach($upcomingPayments->whereNotIn('id', $overduePayments->pluck('id')) as $cycle)
        <div class="calendar-day calendar-day--upcoming" role="listitem"
             title="{{ $cycle->tontine->name }}">
            <div class="calendar-day__num">{{ $cycle->due_date->format('d') }}</div>
            <div class="calendar-day__month">{{ $cycle->due_date->isoFormat('MMM') }}</div>
            <div class="calendar-day__dot"></div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- ── GAMIFICATION (score, streak, badges, leaderboard) ─────────── --}}
    <div class="section-divider" id="section-gamification"></div>
    <div class="gamif-panel" x-data="{ open: false }">
        <button type="button" class="gamif-panel__toggle" @click="open = !open" :aria-expanded="open">
            <span><i class="fas fa-trophy me-2 text-warning"></i>Score, badges &amp; classement</span>
            <i class="fas fa-chevron-down gamif-panel__toggle-arrow"></i>
        </button>
        <div class="gamif-panel__body" x-show="open" x-collapse>
    <div class="row g-3 mb-4">
        {{-- Score crédit --}}
        <div class="col-12 col-md-5">
            <div class="score-panel">
                <div class="score-panel__left">
                    <p class="score-panel__label">Score crédit</p>
                    <h3 class="score-panel__value">{{ $creditScore->score }}<span class="score-panel__max">/10</span></h3>
                    <span class="badge bg-{{ $creditScore->badgeColor() }}">{{ $creditScore->badgeLabel() }}</span>
                    @if($scoreCalculating ?? false)
                    <p class="score-panel__hint"><i class="fas fa-spinner fa-spin me-1"></i>Score en cours de calcul…</p>
                    @elseif($creditScore->score == 0)
                    <p class="score-panel__hint">Effectuez votre premier paiement pour construire votre score.</p>
                    @endif
                </div>
                <div class="score-panel__ring">
                    <svg viewBox="0 0 36 36" class="score-svg" style="width:72px;height:72px;">
                        <path class="score-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                        <path class="score-fill" stroke-dasharray="{{ $creditScore->score * 10 }}, 100"
                              d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                        <text x="18" y="20.35" class="score-text">{{ $creditScore->score }}</text>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Streak + badges --}}
        <div class="col-6 col-md-4">
            <div class="streak-panel">
                <div class="streak-panel__icon">🔥</div>
                <div class="streak-panel__val">{{ $gamification['payment_streak'] }}</div>
                <div class="streak-panel__label">Série de paiements</div>
                <small class="text-muted">Record : {{ $gamification['max_streak'] }}</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="streak-panel streak-panel--purple">
                <div class="streak-panel__icon">🏅</div>
                <div class="streak-panel__val">{{ $gamification['total_badges'] }}</div>
                <div class="streak-panel__label">Badges</div>
                <small class="text-muted">
                    @if($gamification['gold_count']) 🥇{{ $gamification['gold_count'] }} @endif
                    @if($gamification['silver_count']) 🥈{{ $gamification['silver_count'] }} @endif
                    @if($gamification['bronze_count']) 🥉{{ $gamification['bronze_count'] }} @endif
                </small>
            </div>
        </div>
    </div>

        </div>{{-- /gamif-panel__body --}}
    </div>{{-- /gamif-panel --}}

    {{-- ── GRAPHIQUE ───────────────────────────────────────────────────── --}}
    @if($chartData['months']->isNotEmpty())
    <div class="section-divider"></div>
    <div class="card mb-4">
        <div class="section-header mb-3">
            <h6 class="section-header__title mb-0">Mes cotisations (12 mois)</h6>
            <a href="{{ route('historique.index') }}" class="section-header__link">Historique complet</a>
        </div>
        <div style="position:relative;height:160px;">
            <canvas id="paymentChart"></canvas>
        </div>
    </div>
    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('paymentChart');
        if (!ctx) return;
        const observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    observer.disconnect();
                    var s = document.createElement('script');
                    s.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js';
                    s.onload = function () {
                        new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: @json($chartData['months']),
                                datasets: [{
                                    data: @json($chartData['payments']),
                                    backgroundColor: 'rgba(0,150,57,0.15)',
                                    borderColor: '#009639',
                                    borderWidth: 2,
                                    borderRadius: 6,
                                    hoverBackgroundColor: 'rgba(0,150,57,0.3)',
                                }]
                            },
                            options: {
                                responsive: true, maintainAspectRatio: false,
                                plugins: { legend: { display: false } },
                                scales: {
                                    y: { beginAtZero: true, ticks: { callback: function (v) { return (v/1000).toFixed(0)+'K'; }, color: '#94a3b8' }, grid: { color: 'rgba(148,163,184,0.08)' } },
                                    x: { ticks: { color: '#94a3b8' }, grid: { display: false } }
                                }
                            }
                        });
                    };
                    document.head.appendChild(s);
                }
            });
        }, { threshold: 0.1 });
        observer.observe(ctx);
    });
    </script>
    @endpush
    @endif

    {{-- ── LEADERBOARD ────────────────────────────────────────────────── --}}
    @if($leaderboard->isNotEmpty())
    <div class="card mb-4">
        <div class="section-header mb-3">
            <h6 class="section-header__title mb-0">🏆 Classement</h6>
            <small class="text-muted">Top {{ $leaderboard->count() }} de vos tontines</small>
        </div>
        @foreach($leaderboard as $i => $member)
        <div class="leaderboard-row {{ $member->id === $user->id ? 'leaderboard-row--me' : '' }}">
            <span class="leaderboard-row__rank">
                @if($i===0)🥇@elseif($i===1)🥈@elseif($i===2)🥉@else {{ $i+1 }}.@endif
            </span>
            <div class="leaderboard-row__avatar">{{ strtoupper(substr($member->name, 0, 2)) }}</div>
            <span class="leaderboard-row__name">{{ $member->name }}@if($member->id === $user->id) <span class="text-muted">(moi)</span>@endif</span>
            <div class="ms-auto d-flex align-items-center gap-2">
                @if($member->credit_score > 0)
                <span class="badge bg-success" style="font-size:10px;">★ {{ $member->credit_score }}/10</span>
                @endif
                <span class="leaderboard-row__badges">{{ $member->badge_count }} 🏅</span>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- ── TRANSACTIONS RÉCENTES ──────────────────────────────────────── --}}
    @if($recentTransactions->isNotEmpty())
    <div class="section-divider" id="section-transactions"></div>
    <div class="section-header mb-3">
        <h6 class="section-header__title mb-0">Transactions récentes</h6>
        <a href="{{ route('historique.index') }}" class="section-header__link">Voir tout</a>
    </div>
    @foreach($recentTransactions as $tx)
    <div class="tx-row mb-2">
        <div class="tx-row__icon tx-row__icon--{{ $tx->status }}">
            <i class="fas fa-{{ $tx->status === 'success' ? 'check' : ($tx->status === 'failed' ? 'times' : 'clock') }}"></i>
        </div>
        <div class="tx-row__info">
            <p class="tx-row__name">{{ $tx->cycle->tontine->name ?? '—' }}</p>
            <small class="text-muted">{{ $tx->paid_at?->format('d/m/Y') ?? $tx->created_at->format('d/m/Y') }}</small>
        </div>
        <span class="tx-row__amount tx-row__amount--{{ $tx->status }}">
            {{ number_format($tx->amount, 0, ',', ' ') }} F
        </span>
    </div>
    @endforeach
    @endif

</div>

    {{-- FAB paiement --}}
@if($upcomingPayments->isNotEmpty())
<a href="{{ route('cycles.pay', $upcomingPayments->first()) }}" class="fab" title="Payer maintenant">
    <i class="fas fa-credit-card"></i>
</a>
@endif

{{-- FAB partage WhatsApp (si pas de paiement urgent) --}}
@if($upcomingPayments->isEmpty() && $activeTontines->isNotEmpty())
@php $firstTontine = $activeTontines->first(); @endphp
<a href="https://wa.me/?text={{ urlencode('Rejoins ma tontine «'.$firstTontine->name.'» sur TontineSN ! Code : '.$firstTontine->code.' — '.route('tontines.join.form', ['code' => $firstTontine->code])) }}"
   target="_blank" rel="noreferrer"
   class="fab fab--whatsapp" title="Inviter un membre sur WhatsApp" aria-label="Inviter sur WhatsApp">
    <i class="fab fa-whatsapp"></i>
</a>
@endif

@endsection

@push('scripts')
<script>
// Highlight onglet actif selon section visible
(function () {
    const tabs = document.querySelectorAll('.dash-nav-tab');
    const sections = ['section-tontines','section-paiements','section-gamification','section-transactions'];
    function setActive() {
        let current = sections[0];
        sections.forEach(id => {
            const el = document.getElementById(id);
            if (el && el.getBoundingClientRect().top <= 120) current = id;
        });
        tabs.forEach(t => {
            t.classList.toggle('active', t.getAttribute('href') === '#' + current);
        });
    }
    window.addEventListener('scroll', setActive, { passive: true });
    setActive();
})();
</script>
@endpush
