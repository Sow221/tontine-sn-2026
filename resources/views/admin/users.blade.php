@extends('layouts.app')

@section('title', 'Gestion des utilisateurs')

@section('content')
<div class="container py-4">

    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="{{ route('admin.dashboard') }}" class="btn btn-sm btn-light rounded-circle">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h4 class="fw-bold mb-0">Utilisateurs</h4>
    </div>

    {{-- Filtres --}}
    <form method="GET" action="{{ route('admin.users') }}" class="card mb-4">
        <div class="row g-2">
            <div class="col-8">
                <input type="text" name="search" class="form-control"
                       placeholder="Nom ou email..." value="{{ request('search') }}">
            </div>
            <div class="col-4">
                <select name="role" class="form-select">
                    <option value="">Tous les rôles</option>
                    @foreach(['member', 'manager', 'admin', 'super_admin'] as $role)
                    <option value="{{ $role }}" {{ request('role') === $role ? 'selected' : '' }}>
                        {{ ucfirst($role) }}
                    </option>
                    @endforeach
                </select>
            </div>
        </div>
        <button type="submit" class="btn btn-primary btn-sm mt-2 rounded-pill">
            <i class="fas fa-search me-1"></i>Filtrer
        </button>
    </form>

    @forelse($users as $user)
    <div class="card mb-2 py-2">
        <div class="d-flex align-items-center gap-3">
            <div class="member-avatar">{{ strtoupper(substr($user->name ?? $user->email, 0, 2)) }}</div>
            <div class="flex-grow-1">
                <p class="mb-0 fw-semibold small">{{ $user->name ?? '—' }}</p>
                <small class="text-muted">{{ $user->email }}</small>
            </div>
            <div class="d-flex flex-column align-items-end gap-1">
                <span class="badge bg-{{ $user->role === 'super_admin' ? 'danger' : ($user->role === 'admin' ? 'warning' : 'secondary') }}">
                    {{ $user->role }}
                </span>
                <form method="POST" action="{{ route('admin.users.toggle', $user) }}">
                    @csrf
                    <button type="submit" class="btn btn-xs btn-{{ $user->is_active ? 'outline-danger' : 'outline-success' }} rounded-pill" style="font-size:0.7rem;padding:2px 8px">
                        {{ $user->is_active ? 'Désactiver' : 'Activer' }}
                    </button>
                </form>
            </div>
        </div>
    </div>
    @empty
    <div class="text-center py-4 text-muted">Aucun utilisateur trouvé.</div>
    @endforelse

    <div class="mt-3">{{ $users->withQueryString()->links() }}</div>

</div>
@endsection
