@extends('layouts.app')
@section('title', 'Notifications')

@section('content')
<div class="container py-4">

    <a href="{{ route('dashboard') }}" class="back-link">
        <i class="fas fa-arrow-left"></i>Tableau de bord
    </a>

    <h4 class="fw-bold mb-4">Notifications</h4>

    @forelse($notifications as $notification)
    <div class="card mb-3 py-2 {{ is_null($notification->read_at) ? 'notif-card--unread' : '' }}">
        <div class="d-flex align-items-center gap-3">
            <div class="icon-box {{ match($notification->channel) {
                'whatsapp' => 'bg-green-light',
                'push'     => 'bg-indigo-light',
                'email'    => 'bg-blue-light',
                default    => 'bg-blue-light'
            } }}">
                <i class="fas fa-{{ match($notification->channel) {
                    'whatsapp' => 'whatsapp text-green',
                    'push'     => 'bell text-indigo',
                    'email'    => 'envelope text-primary',
                    default    => 'bell text-primary'
                } }} fs-5"></i>
            </div>
            <div class="flex-grow-1">
                <p class="mb-0 small {{ is_null($notification->read_at) ? 'fw-bold' : 'fw-semibold' }}">
                    @if(is_null($notification->read_at))
                    <span class="badge-new me-1">Nouveau</span>
                    @endif
                    {{ $notification->message }}
                </p>
                <small class="text-muted">{{ $notification->created_at->isoFormat('D MMMM YYYY à HH:mm') }}</small>
                @if($notification->event)
                <span class="badge bg-light text-dark ms-2" style="font-size:10px;">
                    {{ match($notification->event) {
                        'beneficiary_notification' => '🎉 Bénéficiaire',
                        'payment_confirmed'        => '✅ Paiement',
                        'member_approved'          => '👋 Adhésion',
                        'payment_reminder'         => '🔔 Rappel',
                        'cycle_start'              => '📅 Nouveau cycle',
                        'savings_withdrawal'       => '💰 Retrait épargne',
                        'kyc_approved'             => '🛡️ KYC approuvé',
                        'kyc_rejected'             => '❌ KYC refusé',
                        default                    => $notification->event,
                    } }}
                </span>
                @endif
            </div>
            <span class="badge badge-{{ $notification->status === 'sent' ? 'success' : ($notification->status === 'failed' ? 'danger' : 'warning') }}">
                {{ match($notification->status) { 'sent' => 'Envoyée', 'failed' => 'Échouée', default => 'En attente' } }}
            </span>
        </div>
    </div>
    @empty
    <div class="text-center py-5">
        <div class="empty-scene">🔔😴</div>
        <p class="text-muted fw-semibold">Aucune notification</p>
        <p class="text-muted small">Les alertes de paiement et messages apparaîtront ici.</p>
    </div>
    @endforelse

    <div class="d-flex justify-content-center mt-3">
        {{ $notifications->links() }}
    </div>

</div>
@endsection
