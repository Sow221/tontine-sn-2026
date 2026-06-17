{{-- Cycle actuel --}}
@if($currentCycle)
<div class="card mb-4" id="cycle">
    <h6 class="fw-semibold mb-3">
        @if($tontine->type === 'ceremonial') 🎉 Événement — {{ $currentCycle->due_date->format('d/m/Y') }}
        @elseif($tontine->type === 'forced_saving') 💰 Cycle {{ $currentCycle->cycle_number }} — Épargne en cours
        @else Cycle {{ $currentCycle->cycle_number }} en cours
        @endif
    </h6>

    {{-- Countdown pour tontine cérémonielle --}}
    @if($tontine->type === 'ceremonial' && $currentCycle->due_date->isFuture())
    @php
        $daysLeft = (int) now()->diffInDays($currentCycle->due_date, false);
        $hoursLeft = (int) now()->diffInHours($currentCycle->due_date, false);
    @endphp
    <div class="d-flex align-items-center gap-3 p-3 rounded mb-3"
         style="background:linear-gradient(135deg,#fdf2f8,#fce7f3);border:1px solid #fbcfe8;">
        <div class="text-center flex-shrink-0">
            <div style="font-size:2rem;font-weight:800;color:#ec4899;line-height:1;">
                {{ $daysLeft > 0 ? $daysLeft : max(1, $hoursLeft) }}
            </div>
            <div style="font-size:0.7rem;color:#9d174d;font-weight:600;text-transform:uppercase;">
                {{ $daysLeft > 0 ? ($daysLeft > 1 ? 'jours' : 'jour') : ($hoursLeft > 1 ? 'heures' : 'heure') }}
            </div>
        </div>
        <div class="flex-grow-1">
            <p class="fw-semibold mb-0 small" style="color:#9d174d;">Compte à rebours avant l'événement</p>
            <small class="text-muted">{{ $currentCycle->due_date->isoFormat('dddd D MMMM YYYY') }}</small>
        </div>
        <i class="fas fa-calendar-alt flex-shrink-0" style="color:#ec4899;font-size:1.4rem;"></i>
    </div>
    @endif

    {{-- Bannière bénéficiaire prominent --}}
    @if($currentCycle->beneficiary_id === auth()->id())
    <div class="your-turn-banner" role="status" aria-live="polite">
        <div class="your-turn-banner__icon">🎉</div>
        <div>
            <div class="your-turn-banner__amount">{{ number_format($tontine->pot_total ?? ($tontine->amount * $tontine->active_members_count), 0, ',', ' ') }} FCFA</div>
            <div class="your-turn-banner__label">C'est votre tour de recevoir le pot !</div>
        </div>
    </div>
    @endif

    <div class="cycle-progress-bar mb-2" role="progressbar"
         aria-valuenow="{{ $currentCycle->completionRate() }}" aria-valuemin="0" aria-valuemax="100"
         aria-label="Progression du cycle : {{ $currentCycle->completionRate() }}%">
        <div class="cycle-progress-bar__fill {{ $currentCycle->isOverdue() ? 'cycle-progress-bar__fill--overdue' : '' }}"
             style="width:{{ $currentCycle->completionRate() }}%"></div>
    </div>
    <div class="d-flex justify-content-between small text-muted mb-3">
        <span>{{ number_format($currentCycle->total_collected, 0, ',', ' ') }} FCFA collectés</span>
        <span>{{ $currentCycle->completionRate() }}%</span>
    </div>

    {{-- Alerte retard --}}
    @if($currentCycle->isOverdue() && !$hasPaid)
    <div class="alert alert-danger py-2 mb-3 d-flex align-items-center gap-2">
        <i class="fas fa-exclamation-triangle"></i>
        <div>
            <strong>Cotisation en retard</strong> — Date limite dépassée le {{ $currentCycle->due_date->format('d/m/Y') }}.
            Une pénalité de {{ $tontine->penalty_rate }}% s'applique.
        </div>
    </div>
    @endif

    {{-- AUCTION : enchère --}}
    @if($tontine->type === 'auction' && !$currentCycle->beneficiary_id)
    <div class="p-3 bg-light rounded mb-3">
        <p class="fw-semibold small mb-1">🏷️ Enchère — Proposez votre taux</p>
        <p class="text-muted small mb-2">Le taux le plus élevé reçoit le pot en premier, mais reçoit moins (pot × (1 - taux)). La différence est redistribuée aux autres.</p>
        <p class="text-muted small mb-2">
            <i class="fas fa-clock me-1"></i>Date limite des enchères : <strong>{{ $currentCycle->due_date->format('d/m/Y') }}</strong>
            @if($currentCycle->due_date->isPast())
            <span class="badge bg-danger ms-1">Fermée</span>
            @endif
        </p>
        @if(!$bidDeadlinePassed)
        @php
            $myBid = $currentCycle->myBid(auth()->id());
            $potTotal = $tontine->amount * $tontine->active_members_count;
        @endphp
        @if($myBid)
        <div class="alert alert-success py-2 mb-2">
            <i class="fas fa-check-circle me-1"></i>Votre enchère : <strong>{{ $myBid->bid_rate }}%</strong>
            — vous recevrez environ <strong>{{ number_format($potTotal * (1 - $myBid->bid_rate / 100), 0, ',', ' ') }} FCFA</strong>
        </div>
        @endif
        <form method="POST" action="{{ route('cycles.bid', $currentCycle) }}"
              x-data="{ rate: {{ $myBid?->bid_rate ?? 0 }}, pot: {{ $potTotal }} }">
            @csrf
            <div class="input-group mb-2">
                <input type="number" name="bid_rate" class="form-control" step="0.5" min="0.5" max="30"
                       x-model="rate"
                       value="{{ $myBid?->bid_rate ?? '' }}" placeholder="Ex: 5.0">
                <span class="input-group-text">%</span>
            </div>
            <template x-if="rate > 0">
                <p class="text-muted small mb-2">
                    💰 Vous recevrez environ
                    <strong x-text="Math.round(pot * (1 - rate / 100)).toLocaleString('fr-FR') + ' FCFA'"></strong>
                </p>
            </template>
            <button type="submit" class="btn btn-warning w-100">Enchérir</button>
        </form>
        @endif
        @if($currentCycle->auctionBids->count() > 0)
        <p class="text-muted small mt-2 mb-0">{{ $currentCycle->auctionBids->count() }} enchère(s) soumise(s)</p>
        @endif
    </div>
    @endif

    {{-- FORCED_SAVING : épargne personnelle --}}
    @if($tontine->type === 'forced_saving')
        @if(auth()->id() === $tontine->created_by)
            @if($withdrawals->isEmpty())
            <form method="POST" action="{{ route('cycles.close-saving', $currentCycle) }}" class="mb-3">
                @csrf
                <button type="submit" class="btn btn-outline-success w-100"
                        onclick="return confirm('Clôturer l\'épargne ? Chaque membre recevra son épargne personnelle.')">
                    <i class="fas fa-lock-open me-2"></i>Clôturer — {{ number_format($currentCycle->total_collected, 0, ',', ' ') }} FCFA
                </button>
            </form>
            @else
            <div class="alert alert-success py-2 mb-3">
                <i class="fas fa-check-circle me-1"></i>Épargne clôturée — retraits à effectuer
            </div>
            @foreach($withdrawals as $w)
            <div class="d-flex align-items-center gap-2 mb-2">
                <div class="member-avatar avatar-sm">{{ strtoupper(substr($w->user->name ?? '?', 0, 2)) }}</div>
                <span class="flex-grow-1 small fw-semibold">{{ $w->user->name }}</span>
                <span class="fw-bold text-green">{{ number_format($w->amount, 0, ',', ' ') }} FCFA</span>
                <span class="badge badge-{{ $w->status === 'paid' ? 'success' : 'warning' }}">
                    {{ $w->status === 'paid' ? 'Versé' : 'À verser' }}
                </span>
                @if(auth()->id() === $tontine->created_by && $w->status === 'pending')
                <form method="POST" action="{{ route('withdrawals.confirm', $w) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-success rounded-pill px-2 py-0" title="Confirmer le versement">
                        <i class="fas fa-check"></i>
                    </button>
                </form>
                @endif
            </div>
            @endforeach
            @endif
        @else
        <div class="p-3 bg-light rounded mb-3">
            <p class="text-muted small mb-1">💰 Mon épargne personnelle</p>
            <p class="fw-bold fs-5 mb-0">{{ number_format($mySaved, 0, ',', ' ') }} FCFA</p>
            @if($myWithdrawal)
            <span class="badge badge-{{ $myWithdrawal->status === 'paid' ? 'success' : 'warning' }} mt-1">
                {{ $myWithdrawal->status === 'paid' ? 'Versé' : 'En attente de versement' }}
            </span>
            @endif
        </div>
        @endif
    @endif

    {{-- VETO — visible si bénéficiaire désigné et seuil configuré --}}
    @if($currentCycle->beneficiary_id && $vetoRequired > 0 && !in_array($tontine->type, ['forced_saving', 'ceremonial']))
    <div class="p-3 bg-light rounded mb-3">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <p class="fw-semibold small mb-1">🛑 Véto sur le tirage</p>
                <p class="text-muted small mb-0">
                    <span class="fw-bold">{{ $vetoCount }}/{{ $vetoRequired }}</span> votes requis pour annuler
                </p>
            </div>
            @if(!$hasVetoed)
            <form method="POST" action="{{ route('cycles.veto', $currentCycle) }}">
                @csrf
                <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill"
                        onclick="return confirm('Voter le véto ? Si le seuil est atteint, le tirage sera annulé.')">
                    <i class="fas fa-ban me-1"></i>Voter le véto
                </button>
            </form>
            @else
            <span class="badge bg-danger">Véto voté</span>
            @endif
        </div>
    </div>
    @endif

    {{-- CYCLE BLOQUÉ : véto appliqué, aucun membre éligible restant --}}
    @if(!$currentCycle->beneficiary_id && $tontine->status === 'active'
        && !in_array($tontine->type, ['forced_saving', 'ceremonial'])
        && $currentCycle->completionRate() >= 100)
    @php
        $allWon = $tontine->cycles->whereNotNull('beneficiary_id')->pluck('beneficiary_id')->unique();
        $eligible = $tontine->members->where('pivot.status', 'active')->whereNotIn('id', $allWon->toArray());
    @endphp
    @if($eligible->isEmpty())
    <div class="alert alert-warning d-flex align-items-center gap-2 mb-3">
        <i class="fas fa-exclamation-triangle flex-shrink-0"></i>
        <div>
            <strong>Cycle bloqué</strong> — Tous les membres actifs ont déjà reçu le pot.
            Le créateur de la tontine a été notifié. Contactez le groupe pour décider de la suite.
        </div>
    </div>
    @endif
    @endif

    {{-- CASH EN ATTENTE : validation par le créateur --}}
    @if(auth()->id() === $tontine->created_by && $tontine->status === 'active')
    @php
        $pendingCash = $currentCycle
            ? \App\Models\Transaction::where('cycle_id', $currentCycle->id)
                ->where('method', 'cash')
                ->where('status', 'pending')
                ->with('user')
                ->get()
            : collect();
    @endphp
    @if($pendingCash->isNotEmpty())
    <div class="p-3 bg-light rounded mb-3">
        <p class="fw-semibold small mb-2"><i class="fas fa-money-bill text-warning me-1"></i>Paiements espèces à valider ({{ $pendingCash->count() }})</p>
        @foreach($pendingCash as $cashTx)
        <div class="d-flex align-items-center gap-2 mb-2">
            <div class="member-avatar avatar-sm">{{ strtoupper(substr($cashTx->user->name ?? '?', 0, 2)) }}</div>
            <span class="flex-grow-1 small fw-semibold">{{ $cashTx->user->name ?? '—' }}</span>
            <span class="fw-bold text-warning">{{ number_format($cashTx->amount, 0, ',', ' ') }} FCFA</span>
            <form method="POST" action="{{ route('tontines.cash.confirm', [$tontine, $cashTx]) }}">
                @csrf
                <button type="submit" class="btn btn-sm btn-success rounded-pill"
                        onclick="return confirm('Confirmer la remise en espèces de {{ $cashTx->user->name ?? \'ce membre\' }} ?')">
                    <i class="fas fa-check"></i> Valider
                </button>
            </form>
        </div>
        @endforeach
    </div>
    @endif
    @endif

    @if($hasPaid && ($lastSuccessTransaction ?? null))
    <div class="d-flex align-items-center justify-content-between p-3 bg-green-light rounded mb-3 flex-wrap gap-2">
        <span class="small fw-semibold text-green"><i class="fas fa-check-circle me-1"></i>{{ __('member.paid') }}</span>
        <a href="{{ route('transactions.receipt', $lastSuccessTransaction) }}" class="btn btn-sm btn-outline-success rounded-pill" target="_blank">
            <i class="fas fa-file-pdf me-1"></i>{{ __('member.download_receipt') }}
        </a>
    </div>
    @endif

    <div class="d-flex gap-2 mt-2 flex-wrap">
        @php
            $payPenalty = $currentCycle->isOverdue() && $tontine->penalty_rate > 0
                ? (int) round($tontine->amount * $tontine->penalty_rate / 100) : 0;
            $payTotal = $tontine->amount + $payPenalty;
        @endphp
        @if($tontine->type === 'forced_saving')
            @if($hasPaid)
            <div class="btn btn-success flex-grow-1 disabled"><i class="fas fa-check-circle me-2"></i>{{ __('member.paid_contribution') }}</div>
            @elseif($paymentPending ?? false)
            <div class="btn btn-warning flex-grow-1 disabled"><i class="fas fa-clock me-2"></i>{{ __('member.payment_validating_btn') }}</div>
            @else
            <button type="button" class="btn btn-primary flex-grow-1" data-bs-toggle="modal" data-bs-target="#payModal">
                <i class="fas fa-piggy-bank me-2"></i>{{ __('member.pay_amount') }} — {{ number_format($payTotal, 0, ',', ' ') }} FCFA
            </button>
            @endif
        @elseif($tontine->type === 'ceremonial')
            @if($hasPaid)
            <div class="btn btn-success flex-grow-1 disabled"><i class="fas fa-check-circle me-2"></i>{{ __('member.contribution_sent') }}</div>
            @elseif($paymentPending ?? false)
            <div class="btn btn-warning flex-grow-1 disabled"><i class="fas fa-clock me-2"></i>{{ __('member.contribution_validating') }}</div>
            @else
            <button type="button" class="btn btn-primary flex-grow-1" data-bs-toggle="modal" data-bs-target="#payModal">
                <i class="fas fa-heart me-2"></i>{{ __('member.contribute') }} — {{ number_format($payTotal, 0, ',', ' ') }} FCFA
            </button>
            @endif
        @else
            @if($hasPaid)
            <div class="btn btn-success flex-grow-1 disabled"><i class="fas fa-check-circle me-2"></i>{{ __('member.paid_contribution') }}</div>
            @elseif($paymentPending ?? false)
            <div class="btn btn-warning flex-grow-1 disabled"><i class="fas fa-clock me-2"></i>{{ __('member.payment_validating_btn') }}</div>
            @else
            <button type="button" class="btn btn-primary flex-grow-1" data-bs-toggle="modal" data-bs-target="#payModal">
                <i class="fas fa-money-bill-wave me-2"></i>{{ __('member.pay_amount') }} — {{ number_format($payTotal, 0, ',', ' ') }} FCFA
            </button>
            @endif
            @if(auth()->id() === $tontine->created_by)
                @if($canDraw)
                <button type="button" class="btn btn-outline-primary" @click="window.dispatchEvent(new CustomEvent('open-modal', { detail: { id: 'draw-modal', action: '{{ route('cycles.draw', $currentCycle) }}', message: 'Effectuer le tirage ?', confirmText: 'Confirmer', type: 'primary' } }))" title="Tirage">
                    <i class="fas fa-random"></i>
                </button>
                @elseif(!$currentCycle->beneficiary_id)
                <button class="btn btn-outline-secondary" disabled title="{{ $drawBlockReason ?? 'Tirage non disponible' }}">
                    <i class="fas fa-random"></i>
                </button>
                @endif
            @endif
        @endif
    </div>
</div>

{{-- Modal paiement inline — évite la navigation vers une page dédiée --}}
@if(!$hasPaid && !($paymentPending ?? false))
@php $payPenalty2 = $currentCycle->isOverdue() && $tontine->penalty_rate > 0 ? (int) round($tontine->amount * $tontine->penalty_rate / 100) : 0; $payTotal2 = $tontine->amount + $payPenalty2; @endphp
<div class="modal fade" id="payModal" tabindex="-1" aria-labelledby="payModalLabel" aria-modal="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" x-data="{ method: 'wave', submitting: false }">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold" id="payModalLabel">
                    @if($tontine->type === 'forced_saving') Épargner
                    @elseif($tontine->type === 'ceremonial') Contribuer
                    @else Payer ma cotisation
                    @endif
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <div class="text-center py-3 mb-4 bg-light rounded">
                    <p class="text-muted small mb-1">{{ $tontine->name }} · Cycle {{ $currentCycle->cycle_number }}</p>
                    <div class="fw-bold fs-3 text-green">{{ number_format($payTotal2, 0, ',', ' ') }} FCFA</div>
                    <small class="text-muted">Date limite : {{ $currentCycle->due_date->format('d/m/Y') }}</small>
                    @if($payPenalty2 > 0)
                    <div class="alert alert-warning mt-2 mb-0 py-2 small text-start">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        Pénalité : {{ number_format($tontine->amount, 0, ',', ' ') }} + {{ number_format($payPenalty2, 0, ',', ' ') }} FCFA ({{ $tontine->penalty_rate }}%)
                    </div>
                    @endif
                </div>
                @error('payment')
                <div class="alert alert-danger py-2 mb-3"><i class="fas fa-exclamation-circle me-2"></i>{{ $message }}</div>
                @enderror
                <form method="POST" action="{{ route('cycles.pay.initiate', $currentCycle) }}" id="payModalForm">
                    @csrf
                    <h6 class="fw-semibold mb-3">Mode de paiement</h6>
                    <div class="payment-methods mb-3">
                        <label class="payment-option" :class="method === 'wave' ? 'payment-wave' : ''">
                            <input type="radio" name="method" value="wave" x-model="method" class="d-none">
                            <div class="d-flex align-items-center gap-3">
                                <div class="payment-logo wave-logo"><img src="{{ asset('images/logo wave.webp') }}" alt="Wave" class="pay-method-icon"></div>
                                <div><p class="fw-semibold mb-0">Wave</p><small class="text-muted">Paiement instantané</small></div>
                                <template x-if="method === 'wave'"><span class="badge bg-success ms-auto">Recommandé</span></template>
                                <i class="fas fa-check-circle ms-auto text-green" x-show="method === 'wave'" x-cloak></i>
                            </div>
                        </label>
                        <label class="payment-option" :class="method === 'orange_money' ? 'payment-orange' : ''">
                            <input type="radio" name="method" value="orange_money" x-model="method" class="d-none">
                            <div class="d-flex align-items-center gap-3">
                                <div class="payment-logo om-logo"><img src="{{ asset('images/logo orange money.webp') }}" alt="Orange Money" class="pay-method-icon"></div>
                                <div><p class="fw-semibold mb-0">Orange Money</p><small class="text-muted">Paiement mobile</small></div>
                                <i class="fas fa-check-circle ms-auto text-green" x-show="method === 'orange_money'" x-cloak></i>
                            </div>
                        </label>
                        <label class="payment-option" :class="method === 'free_money' ? 'payment-free' : ''">
                            <input type="radio" name="method" value="free_money" x-model="method" class="d-none">
                            <div class="d-flex align-items-center gap-3">
                                <div class="payment-logo" style="background:#E8F5E9;border:1.5px solid #E2E8F0;"><span class="fw-bold text-green" style="font-size:11px;">FREE</span></div>
                                <div><p class="fw-semibold mb-0">Free Money</p><small class="text-muted">Free Sénégal</small></div>
                                <i class="fas fa-check-circle ms-auto text-green" x-show="method === 'free_money'" x-cloak></i>
                            </div>
                        </label>
                        <label class="payment-option" :class="method === 'card' ? 'payment-card' : ''">
                            <input type="radio" name="method" value="card" x-model="method" class="d-none">
                            <div class="d-flex align-items-center gap-3">
                                <div class="payment-logo card-logo"><img src="{{ asset('images/carte bancaire.webp') }}" alt="Carte" class="pay-method-icon"></div>
                                <div><p class="fw-semibold mb-0">Carte bancaire</p><small class="text-muted">Visa / Mastercard</small></div>
                                <i class="fas fa-check-circle ms-auto text-green" x-show="method === 'card'" x-cloak></i>
                            </div>
                        </label>
                        <label class="payment-option" :class="method === 'cash' ? 'payment-cash' : ''">
                            <input type="radio" name="method" value="cash" x-model="method" class="d-none">
                            <div class="d-flex align-items-center gap-3">
                                <div class="payment-logo cash-logo"><i class="fas fa-money-bill"></i></div>
                                <div><p class="fw-semibold mb-0">Espèces</p><small class="text-muted">Remise en main propre</small></div>
                                <i class="fas fa-check-circle ms-auto text-green" x-show="method === 'cash'" x-cloak></i>
                            </div>
                        </label>
                    </div>
                    <div class="alert alert-light d-flex gap-2 align-items-start mb-0" x-show="method !== 'cash'" x-cloak>
                        <i class="fas fa-info-circle text-muted mt-1 flex-shrink-0"></i>
                        <small class="text-muted">Traitement sécurisé via <strong>PayTech</strong>. Vous serez redirigé vers la page de paiement.</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" form="payModalForm" class="btn btn-primary px-4"
                        :disabled="submitting"
                        @click="submitting = true">
                    <span x-show="!submitting"><i class="fas fa-lock me-2"></i>Confirmer — {{ number_format($payTotal2, 0, ',', ' ') }} FCFA</span>
                    <span x-show="submitting" x-cloak><span class="spinner-border spinner-border-sm me-2"></span>Traitement...</span>
                </button>
            </div>
        </div>
    </div>
</div>
@endif
@endif
