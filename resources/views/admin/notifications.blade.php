@extends('layouts.app')
@section('title', 'Logs notifications')

@section('content')
<div class="container py-4">

    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="{{ route('admin.dashboard') }}" class="btn-back">
            <i class="fas fa-arrow-left" aria-hidden="true"></i>
            Tableau de bord
        </a>
        <h4 class="fw-bold mb-0">Notifications envoyées</h4>
        <span class="badge bg-secondary ms-1">{{ $notifications->total() }}</span>
    </div>

    <form method="GET" action="{{ route('admin.notifications') }}" class="card mb-4">
        <div class="row g-2">
            <div class="col-6 col-sm-3">
                <select name="channel" class="form-select form-select-sm">
                    <option value="">Tous les canaux</option>
                    <option value="email"    {{ request('channel') === 'email'    ? 'selected' : '' }}>Email</option>
                    <option value="whatsapp" {{ request('channel') === 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
                </select>
            </div>
            <div class="col-6 col-sm-3">
                <select name="status" class="form-select form-select-sm">
                    <option value="">Tous les statuts</option>
                    <option value="sent"    {{ request('status') === 'sent'    ? 'selected' : '' }}>Envoyé</option>
                    <option value="failed"  {{ request('status') === 'failed'  ? 'selected' : '' }}>Échoué</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>En attente</option>
                </select>
            </div>
            <div class="col-10 col-sm-4">
                <select name="event" class="form-select form-select-sm">
                    <option value="">Tous les événements</option>
                    @foreach([
                        'payment_confirmed'        => 'Paiement confirmé',
                        'beneficiary_notification' => 'Bénéficiaire désigné',
                        'payment_reminder'         => 'Rappel cotisation',
                        'cycle_start'              => 'Nouveau cycle',
                        'member_approved'          => 'Adhésion approuvée',
                        'kyc_approved'             => 'KYC approuvé',
                        'kyc_rejected'             => 'KYC refusé',
                        'savings_withdrawal'       => 'Retrait épargne',
                        'general'                  => 'Général',
                    ] as $val => $label)
                    <option value="{{ $val }}" {{ request('event') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-2 col-sm-2">
                <button type="submit" class="btn btn-primary btn-sm w-100"><i class="fas fa-filter"></i></button>
            </div>
        </div>
        @if(request()->hasAny(['channel', 'status', 'event']))
        <a href="{{ route('admin.notifications') }}" class="text-muted small mt-2 d-inline-block">
            <i class="fas fa-times me-1"></i>Effacer
        </a>
        @endif
    </form>

    @forelse($notifications as $notif)
    <div class="card mb-2 py-2">
        <div class="d-flex align-items-center gap-3">
            <div class="icon-box {{ $notif->status === 'sent' ? 'bg-green-light' : ($notif->status === 'failed' ? 'bg-red-light' : 'bg-yellow-light') }}">
                <i class="{{ $notif->channel === 'whatsapp' ? 'fab fa-whatsapp' : 'fas fa-envelope' }}
                   {{ $notif->status === 'sent' ? 'text-green' : ($notif->status === 'failed' ? 'text-danger' : 'text-warning') }}"></i>
            </div>
            <div class="flex-grow-1 min-width-0">
                <p class="mb-0 fw-semibold small">{{ $notif->user->name ?? $notif->user->email ?? '—' }}</p>
                <small class="text-muted text-truncate d-block" style="max-width:280px;">{{ $notif->message }}</small>
                <small class="text-muted">{{ $notif->event }} · {{ $notif->created_at->format('d/m/Y H:i') }}</small>
            </div>
            <div class="text-end flex-shrink-0">
                <span class="badge badge-{{ $notif->status === 'sent' ? 'success' : ($notif->status === 'failed' ? 'danger' : 'warning') }}">
                    {{ match($notif->status) { 'sent' => 'Envoyé', 'failed' => 'Échoué', default => 'En attente' } }}
                </span>
                <div class="mt-1">
                    <span class="badge bg-light text-dark border" style="font-size:9px;">
                        {{ strtoupper($notif->channel) }}
                    </span>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="text-center py-4 text-muted">Aucune notification trouvée.</div>
    @endforelse

    <div class="mt-3">{{ $notifications->withQueryString()->links() }}</div>

</div>
@endsection
