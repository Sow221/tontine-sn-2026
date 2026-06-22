{{-- Historique des cycles passés — transparence totale --}}
@if($pastCycles->isNotEmpty())
<div class="card mb-4">
    <h6 class="fw-semibold mb-3">Historique des cycles</h6>
    @foreach($pastCycles as $cycle)
    <div class="d-flex align-items-center gap-3 mb-2 pb-2 {{ !$loop->last ? 'border-bottom' : '' }}">
        <div class="icon-box bg-green-light">
            <span class="fw-bold text-green small">{{ $cycle->cycle_number }}</span>
        </div>
        <div class="flex-grow-1">
            <p class="mb-0 small fw-semibold">{{ $cycle->beneficiary->name ?? '—' }}</p>
            <small class="text-muted">{{ $cycle->drawn_at?->format('d/m/Y') ?? $cycle->due_date->format('d/m/Y') }}</small>
        </div>
        <div class="text-end">
            <span class="fw-bold text-green small">{{ number_format($tontine->amount, 0, ',', ' ') }} F</span>
            @if($cycle->draw_hash)
            <br><small class="text-muted" title="Preuve cryptographique : {{ $cycle->draw_hash }}">
                <i class="fas fa-shield-alt me-1"></i>Vérifié
            </small>
            @endif
        </div>
    </div>
    @endforeach
</div>
@endif
