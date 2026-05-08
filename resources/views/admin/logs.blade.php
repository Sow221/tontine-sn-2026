@extends('layouts.app')

@section('title', 'Journaux d\'activité')

@section('content')
<div class="container py-4">

    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="{{ route('admin.dashboard') }}" class="btn btn-sm btn-light rounded-circle">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h4 class="fw-bold mb-0">Journaux d'activité</h4>
    </div>

    @forelse($logs as $log)
    <div class="card mb-2 py-2">
        <div class="d-flex align-items-start gap-3">
            <div class="member-avatar bg-light text-dark" style="font-size:0.7rem">
                {{ strtoupper(substr($log->name ?? '?', 0, 2)) }}
            </div>
            <div class="flex-grow-1">
                <p class="mb-0 fw-semibold small">{{ $log->name ?? '—' }}</p>
                <code class="small text-muted">{{ $log->action }}</code>
                <div class="d-flex gap-2 mt-1">
                    <small class="text-muted"><i class="fas fa-map-marker-alt me-1"></i>{{ $log->ip_address }}</small>
                    <small class="text-muted"><i class="fas fa-clock me-1"></i>{{ \Carbon\Carbon::parse($log->created_at)->diffForHumans() }}</small>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="text-center py-4 text-muted">Aucun journal disponible.</div>
    @endforelse

    <div class="mt-3">{{ $logs->links() }}</div>

</div>
@endsection
