@extends('layouts.app')
@section('title', 'Gestion des utilisateurs | TontineSN')

@section('content')
<div class="container py-4">

    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="{{ route('admin.dashboard') }}" class="btn btn-sm btn-light rounded-circle">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h4 class="fw-bold mb-0">Utilisateurs</h4>
        <span class="badge bg-secondary ms-1">{{ $users->total() }}</span>
        <a href="{{ route('admin.users.export') }}" class="btn btn-sm btn-outline-primary rounded-pill ms-auto">
            <i class="fas fa-download me-1"></i>Export CSV
        </a>
    </div>

    <form method="GET" action="{{ route('admin.users') }}" class="card mb-4">
        <div class="row g-2">
            <div class="col-12 col-sm-4">
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="Nom, email, téléphone…" value="{{ request('search') }}">
            </div>
            <div class="col-4 col-sm-2">
                <select name="role" class="form-select form-select-sm">
                    <option value="">Tous les rôles</option>
                    @foreach(['member' => 'Membre', 'admin' => 'Admin', 'super_admin' => 'Super Admin'] as $val => $label)
                    <option value="{{ $val }}" {{ request('role') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-4 col-sm-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">Tous</option>
                    <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>Actifs</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactifs</option>
                </select>
            </div>
            <div class="col-4 col-sm-2">
                <select name="kyc" class="form-select form-select-sm">
                    <option value="">KYC : tous</option>
                    <option value="pending"  {{ request('kyc') === 'pending'  ? 'selected' : '' }}>En attente</option>
                    <option value="verified" {{ request('kyc') === 'verified' ? 'selected' : '' }}>Vérifiés</option>
                    <option value="none"     {{ request('kyc') === 'none'     ? 'selected' : '' }}>Non soumis</option>
                </select>
            </div>
            <div class="col-12 col-sm-2">
                <button type="submit" class="btn btn-primary btn-sm w-100"><i class="fas fa-filter me-1"></i>Filtrer</button>
            </div>
        </div>
        @if(request()->hasAny(['search','role','status','kyc']))
        <a href="{{ route('admin.users') }}" class="text-muted small mt-2 d-inline-block">
            <i class="fas fa-times me-1"></i>Effacer les filtres
        </a>
        @endif
    </form>

    @forelse($users as $user)
    <div class="card mb-2 py-2">
        <div class="d-flex align-items-start gap-3">
            <a href="{{ route('admin.users.show', $user) }}" class="member-avatar text-decoration-none text-white">{{ strtoupper(substr($user->name ?? $user->email, 0, 2)) }}</a>
            <div class="flex-grow-1 min-width-0">
                <a href="{{ route('admin.users.show', $user) }}" class="text-decoration-none">
                    <p class="mb-0 fw-semibold small">{{ $user->name ?? '—' }}</p>
                </a>
                <small class="text-muted d-block">{{ $user->email }}</small>
                @if($user->phone_number)
                <small class="text-muted d-block"><i class="fas fa-phone me-1"></i>{{ $user->phone_number }}</small>
                @endif
                <small class="text-muted">Inscrit {{ $user->created_at->diffForHumans() }}
                    <span class="text-muted" style="font-size:10px;"> ({{ $user->created_at->format('d/m/Y') }})</span>
                </small>
            </div>
            <div class="d-flex flex-column align-items-end gap-1 flex-shrink-0">
                {{-- Badge rôle --}}
                <span class="badge bg-{{ $user->role === 'super_admin' ? 'danger' : ($user->role === 'admin' ? 'warning text-dark' : 'secondary') }}">
                    {{ match($user->role) { 'super_admin' => 'Super Admin', 'admin' => 'Admin', default => 'Membre' } }}
                </span>

                {{-- Statut actif/inactif --}}
                <span class="badge {{ $user->is_active ? 'badge-success' : 'badge-danger' }}">
                    {{ $user->is_active ? 'Actif' : 'Inactif' }}
                </span>

                {{-- KYC --}}
                @if($user->kyc_document && !$user->kyc_verified)
                <div class="d-flex gap-1 mt-1">
                    <form method="POST" action="{{ route('admin.users.kyc.approve', $user) }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-success rounded-pill">KYC ✓</button>
                    </form>
                    <form method="POST" action="{{ route('admin.users.kyc.reject', $user) }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill">KYC ✗</button>
                    </form>
                </div>
                @elseif($user->kyc_verified)
                <span class="badge badge-success" style="font-size:9px;"><i class="fas fa-shield-alt me-1"></i>KYC vérifié</span>
                @endif

                {{-- Actions --}}
                @if($user->id !== auth()->id())
                <div class="d-flex gap-1 mt-1">
                    {{-- Changer rôle --}}
                    <form method="POST" action="{{ route('admin.users.role', $user) }}" class="d-inline">
                        @csrf
                        <select name="role" class="form-select form-select-sm" style="width:auto;font-size:11px;"
                                onchange="if(confirm('Changer le rôle de {{ addslashes($user->name ?? $user->email) }} vers ' + this.options[this.selectedIndex].text + ' ?')) { this.form.submit(); } else { this.value = '{{ $user->role }}'; }">
                            @foreach(['member' => 'Membre', 'admin' => 'Admin', 'super_admin' => 'Super Admin'] as $val => $label)
                            <option value="{{ $val }}" {{ $user->role === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </form>
                    {{-- Activer/Désactiver --}}
                    <button type="button"
                            class="btn btn-sm btn-{{ $user->is_active ? 'outline-danger' : 'outline-success' }} rounded-pill"
                            x-data
                            @click.prevent="window.dispatchEvent(new CustomEvent('open-modal', { detail: { id: 'admin-confirm', action: '{{ route('admin.users.toggle', $user) }}', message: '{{ $user->is_active ? 'Désactiver' : 'Activer' }} {{ addslashes($user->name ?? $user->email) }} ?', confirmText: 'Oui, {{ $user->is_active ? 'désactiver' : 'activer' }}', type: 'danger' } }))">
                        {{ $user->is_active ? 'Désactiver' : 'Activer' }}
                    </button>
                </div>
                @endif
            </div>
        </div>
    </div>
    @empty
    <div class="text-center py-4 text-muted">Aucun utilisateur trouvé.</div>
    @endforelse

    <div class="mt-3">{{ $users->withQueryString()->links() }}</div>

</div>
@endsection
