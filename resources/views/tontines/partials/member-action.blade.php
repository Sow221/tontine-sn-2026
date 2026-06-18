@if($myMemberStatus === 'active' && $tontine->status === 'active')
@php $isCreator = auth()->id() === $tontine->created_by; @endphp
<div class="card mb-4 border-0 member-action-card">
    <div class="card-body py-3">
        <div class="d-flex align-items-start gap-3">
            <div class="icon-box bg-green-light flex-shrink-0"><i class="fas fa-compass text-green"></i></div>
            <div class="flex-grow-1 min-width-0">
                <p class="fw-bold mb-1 text-indigo">{{ __('member.what_to_do') }}</p>
                @if($currentCycle && $currentCycle->beneficiary_id === auth()->id())
                    <p class="mb-2 small">{{ __('member.your_turn_receive') }}</p>
                    <a href="#cycle" class="btn btn-sm btn-warning rounded-pill"><i class="fas fa-eye me-1"></i>{{ __('member.your_turn') }}</a>
                @elseif($paymentPending ?? false)
                    <p class="mb-0 small text-muted">{{ __('member.payment_validating') }}</p>
                @elseif($currentCycle && !$hasPaid && $tontine->type !== 'forced_saving')
                    @php
                        $actionPenalty = $currentCycle->isOverdue() && $tontine->penalty_rate > 0
                            ? (int) round($tontine->amount * $tontine->penalty_rate / 100) : 0;
                        $actionTotal = $tontine->amount + $actionPenalty;
                    @endphp
                    <p class="mb-2 small">
                        {{ __('member.contribution_due') }} <strong>{{ $currentCycle->due_date->format('d/m/Y') }}</strong>
                        @if($currentCycle->isOverdue()) <span class="badge bg-danger ms-1">En retard</span> @endif
                    </p>
                    <button type="button" class="btn btn-sm btn-primary rounded-pill"
                            data-bs-toggle="modal" data-bs-target="#payModal">
                        <i class="fas fa-money-bill-wave me-1"></i>Payer — {{ number_format($actionTotal, 0, ',', ' ') }} FCFA
                    </button>
                @elseif($tontine->type === 'forced_saving' && $currentCycle && !$hasPaid)
                    <p class="mb-2 small">{{ __('member.contribution_due') }} <strong>{{ $currentCycle->due_date->format('d/m/Y') }}</strong>.</p>
                    <button type="button" class="btn btn-sm btn-primary rounded-pill"
                            data-bs-toggle="modal" data-bs-target="#payModal">
                        <i class="fas fa-piggy-bank me-1"></i>Épargner — {{ number_format($tontine->amount, 0, ',', ' ') }} FCFA
                    </button>
                @elseif($tontine->type === 'auction' && $currentCycle && !$currentCycle->beneficiary_id && !($bidDeadlinePassed ?? false))
                    <p class="mb-2 small">{{ __('member.type_auction_help') }}</p>
                    <a href="#cycle" class="btn btn-sm btn-warning rounded-pill">{{ __('member.pay') }} / Enchère</a>
                @elseif(($myTotalDebt ?? 0) > 0)
                    <p class="mb-1 small fw-semibold" style="color:#dc2626;">⚠️ Vous avez une dette envers cette tontine</p>
                    <p class="mb-2 small text-muted">
                        Montant dû : <strong>{{ number_format($myTotalDebt, 0, ',', ' ') }} FCFA</strong>
                        @foreach($myPendingDebts ?? [] as $d)
                            (cycle&nbsp;#{{ $d->cycle->cycle_number ?? '?' }})
                        @endforeach
                    </p>
                    <p class="mb-0 small text-muted">
                        Vous ne pourrez pas recevoir le pot tant que votre dette n'est pas soldée par le créateur.
                        Réglez-la directement auprès de lui.
                    </p>
                @elseif($turnEstimate && ($turnEstimate['status'] ?? '') === 'waiting')
                    <p class="mb-0 small text-muted">
                        {{ __('member.queue_estimate', [
                            'ahead' => $turnEstimate['members_ahead'],
                            'pos' => $turnEstimate['queue_position'],
                            'total' => $turnEstimate['total_in_queue'],
                        ]) }}
                    </p>
                @elseif(($turnEstimate['status'] ?? '') === 'already_won')
                    <p class="mb-0 small text-muted">{{ __('member.already_received') }}</p>
                @elseif($isCreator && $currentCycle && $canDraw)
                    <p class="mb-2 small">{{ __('member.draw_ready') }}</p>
                    <a href="#cycle" class="btn btn-sm btn-outline-primary rounded-pill"><i class="fas fa-random me-1"></i>Tirage</a>
                @elseif($isCreator && $currentCycle && ($drawBlockReason ?? null))
                    <p class="mb-0 small text-muted"><i class="fas fa-info-circle me-1"></i>{{ $drawBlockReason }}</p>
                @else
                    <p class="mb-0 small text-muted">{{ __('member.nothing_todo') }}</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endif
