@extends('layouts.app')

@section('title', 'Mes tontines')

@section('content')
<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">Mes tontines</h4>
        <a href="{{ route('tontines.create') }}" class="btn btn-primary btn-sm rounded-pill">
            <i class="fas fa-plus me-1"></i>Créer
        </a>
    </div>

    {{-- Rejoindre par code --}}
    <div class="card mb-4">
        <form method="POST" action="{{ route('tontines.join') }}" class="d-flex gap-2">
            @csrf
            <input type="text" name="code" class="form-control @error('code') is-invalid @enderror"
                   placeholder="Code tontine (ex: SAND01)" maxlength="6" style="text-transform:uppercase">
            <button type="submit" class="btn btn-outline-primary rounded-pill px-3">Rejoindre</button>
            @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </form>
    </div>

    @forelse($tontines as $tontine)
    <a href="{{ route('tontines.show', $tontine) }}" class="card mb-3 text-decoration-none text-dark">
        <div class="d-flex align-items-center gap-3">
            <div class="tontine-avatar">{{ strtoupper(substr($tontine->name, 0, 2)) }}</div>
            <div class="flex-grow-1">
                <h6 class="fw-semibold mb-0">{{ $tontine->name }}</h6>
                <small class="text-muted">
                    {{ number_format($tontine->amount, 0, ',', ' ') }} FCFA
                    · {{ match($tontine->frequency) { 'daily' => 'Quotidienne', 'weekly' => 'Hebdomadaire', 'monthly' => 'Mensuelle', default => $tontine->frequency } }}
                    · {{ $tontine->active_members_count }}/{{ $tontine->max_members }} membres
                </small>
            </div>
            <span class="badge badge-{{ match($tontine->status) { 'active' => 'success', 'completed' => 'secondary', default => 'warning' } }}">
                {{ match($tontine->status) { 'active' => 'Active', 'completed' => 'Terminée', 'pending' => 'En attente', 'suspended' => 'Suspendue', default => ucfirst($tontine->status) } }}
            </span>
        </div>
    </a>
    @empty
    <div class="text-center py-5">
        <div class="empty-state-icon">🌱</div>
        <p class="text-muted">Vous n'avez pas encore de tontine.</p>
        <a href="{{ route('tontines.create') }}" class="btn btn-primary rounded-pill">
            <i class="fas fa-plus me-2"></i>Créer une tontine
        </a>
    </div>
    @endforelse

    <div class="mt-3">{{ $tontines->links() }}</div>

</div>
@endsection
