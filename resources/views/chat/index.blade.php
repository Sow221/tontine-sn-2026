@extends('layouts.app')
@section('title', 'Messagerie')

@section('content')
<div class="container py-4">

    <nav aria-label="breadcrumb" class="mb-3 small">
        <ol class="breadcrumb bg-transparent p-0 m-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-green">Accueil</a></li>
            <li class="breadcrumb-item active" aria-current="page">Messagerie</li>
        </ol>
    </nav>

    <h4 class="fw-bold mb-4">Messagerie</h4>

    @forelse($tontines as $tontine)
    @php $unread = $unreadCounts[$tontine->id] ?? 0; @endphp
    <a href="{{ route('chat.show', $tontine) }}" class="card mb-3 text-decoration-none text-dark">
        <div class="d-flex align-items-center gap-3">
            <div class="tontine-avatar position-relative">
                {{ strtoupper(substr($tontine->name, 0, 2)) }}
                @if($unread > 0)
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:9px;min-width:16px;">
                    {{ $unread > 9 ? '9+' : $unread }}
                </span>
                @endif
            </div>
            <div class="flex-grow-1 min-width-0">
                <h6 class="fw-semibold mb-0 {{ $unread > 0 ? 'text-dark' : '' }}">{{ $tontine->name }}</h6>
                @if($tontine->latestMessage)
                <p class="text-muted small mb-0 text-truncate {{ $unread > 0 ? 'fw-semibold' : '' }}">
                    @if($tontine->latestMessage->user)
                    <span class="text-muted" style="font-size:11px;">{{ $tontine->latestMessage->user->name }} : </span>
                    @endif
                    {{ $tontine->latestMessage->message }}
                </p>
                <small class="text-muted" style="font-size:11px;">
                    {{ $tontine->latestMessage->created_at->isoFormat('D MMM YYYY à HH:mm') }}
                </small>
                @else
                <p class="text-muted small mb-0">Aucun message</p>
                @endif
            </div>
            <div class="d-flex flex-column align-items-end gap-1">
                <span class="badge bg-light text-muted">{{ $tontine->members_count }} membres</span>
                @if($unread > 0)
                <span class="badge bg-danger" style="font-size:10px;">{{ $unread }} nouveau{{ $unread > 1 ? 'x' : '' }}</span>
                @endif
            </div>
        </div>
    </a>
    @empty
    <div class="text-center py-5">
        <div class="empty-scene">💬🤝</div>
        <p class="text-muted fw-semibold">Vous n'avez aucune conversation</p>
        <p class="text-muted small mb-3">Rejoignez une tontine pour discuter avec les autres membres.</p>
        <a href="{{ route('tontines.index') }}" class="btn btn-primary rounded-pill mt-2">
            <i class="fas fa-users me-2"></i>Voir mes tontines
        </a>
    </div>
    @endforelse

</div>
@endsection
