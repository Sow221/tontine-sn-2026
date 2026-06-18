@extends('layouts.app')
@section('title', 'Journaux d\'activité')

@section('content')
<div class="container py-4">

    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="{{ route('admin.dashboard') }}" class="btn-back">
            <i class="fas fa-arrow-left" aria-hidden="true"></i>
            Tableau de bord
        </a>
        <h4 class="fw-bold mb-0">Journaux d'activité</h4>
        <span class="badge bg-secondary ms-1">{{ $logs->total() }}</span>
        <a href="{{ route('admin.logs.export') }}" class="btn btn-sm btn-outline-primary rounded-pill ms-auto">
            <i class="fas fa-download me-1"></i>Export CSV
        </a>
    </div>

    <form method="GET" action="{{ route('admin.logs') }}" class="card mb-4">
        <div class="row g-2">
            <div class="col-12 col-sm-5">
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="Nom utilisateur ou action…" value="{{ request('search') }}">
            </div>
            <div class="col-6 col-sm-3">
                <input type="date" name="date_from" class="form-control form-control-sm"
                       value="{{ request('date_from') }}" placeholder="Du">
            </div>
            <div class="col-6 col-sm-3">
                <input type="date" name="date_to" class="form-control form-control-sm"
                       value="{{ request('date_to') }}" placeholder="Au">
            </div>
            <div class="col-12 col-sm-1">
                <button type="submit" class="btn btn-primary btn-sm w-100"><i class="fas fa-search"></i></button>
            </div>
        </div>
        @if(request()->hasAny(['search','date_from','date_to']))
        <a href="{{ route('admin.logs') }}" class="text-muted small mt-2 d-inline-block">
            <i class="fas fa-times me-1"></i>Effacer
        </a>
        @endif
    </form>

    @forelse($logs as $log)
    <div class="card mb-2 py-2">
        <div class="d-flex align-items-center gap-3">
            <div class="icon-box bg-light">
                <i class="fas fa-terminal text-muted"></i>
            </div>
            <div class="flex-grow-1 min-width-0">
                <a href="{{ route('admin.users.show', $log->user_id) }}" class="text-decoration-none">
                    <p class="mb-0 fw-semibold small">{{ $log->name ?? $log->email ?? '—' }}</p>
                </a>
                <small class="text-muted text-truncate d-block">{{ $log->action }}</small>
                <small class="text-muted">{{ $log->ip_address }} · {{ \Carbon\Carbon::parse($log->created_at)->format('d/m/Y H:i:s') }}</small>
            </div>
        </div>
    </div>
    @empty
    <div class="text-center py-4 text-muted">Aucun journal trouvé.</div>
    @endforelse

    <div class="mt-3">{{ $logs->withQueryString()->links() }}</div>

</div>
@endsection
