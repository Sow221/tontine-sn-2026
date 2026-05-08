@extends('layouts.app')

@section('title', __('app.my_tontines'))

@section('content')
<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0">{{ __('app.my_tontines') }}</h4>
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
            <button type="submit" class="btn btn-outline-primary rounded-pill px-3">
                {{ __('app.join_tontine') }}
            </button>
            @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </form>
    </div>

    @forelse($tontines as $tontine)
    <div class="card mb-3">
        <div class="d-flex align-items-center gap-3">
            <div class="tontine-avatar">{{ strtoupper(substr($tontine->name, 0, 2)) }}</div>
            <div class="flex-grow-1">
                <h6 class="fw-semibold mb-0">{{ $tontine->name }}</h6>
                <small class="text-muted">
                    {{ number_format($tontine->amount, 0, ',', ' ') }} FCFA
                    · {{ ucfirst($tontine->frequency) }}
                    · {{ $tontine->activeMembers()->count() }}/{{ $tontine->max_members }} membres
                </small>
            </div>
            <div class="d-flex flex-column align-items-end gap-1">
                <span class="badge badge-{{ $tontine->status === 'active' ? 'success' : 'warning' }}">
                    {{ ucfirst($tontine->status) }}
                </span>
                <a href="{{ route('tontines.show', $tontine) }}" class="btn btn-sm btn-light rounded-pill">
                    <i class="fas fa-eye"></i>
                </a>
            </div>
        </div>
    </div>
    @empty
    <div class="text-center py-5">
        <div class="empty-state-icon">🌱</div>
        <p class="text-muted">{{ __('app.no_tontines') }}</p>
        <a href="{{ route('tontines.create') }}" class="btn btn-primary rounded-pill">
            <i class="fas fa-plus me-2"></i>{{ __('app.create_tontine') }}
        </a>
    </div>
    @endforelse

    <div class="mt-3">{{ $tontines->links() }}</div>

</div>
@endsection
