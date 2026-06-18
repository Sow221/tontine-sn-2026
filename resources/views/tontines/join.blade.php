@extends('layouts.app')

@section('title', 'Rejoindre une tontine')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">

            {{-- Aperçu de la tontine (si code déjà saisi) --}}
            @if($preview)
            <div class="card mb-4 border-green">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="tontine-avatar" style="width:48px;height:48px;font-size:18px;">
                            {{ strtoupper(substr($preview->name, 0, 2)) }}
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="fw-bold mb-0">{{ $preview->name }}</h5>
                            <small class="text-muted">Code : <strong>{{ $preview->code }}</strong></small>
                        </div>
                        <span class="badge badge-{{ match($preview->status) { 'active' => 'success', 'completed' => 'secondary', default => 'warning' } }}">
                            {{ match($preview->status) { 'active' => 'Active', 'completed' => 'Terminée', default => 'En attente' } }}
                        </span>
                    </div>

                    <div class="row g-2 text-center small">
                        <div class="col-4">
                            <div class="bg-light rounded-3 py-2">
                                <div class="fw-bold">{{ number_format($preview->amount, 0, ',', ' ') }} F</div>
                                <div class="text-muted" style="font-size:11px;">Montant/cycle</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="bg-light rounded-3 py-2">
                                <div class="fw-bold">{{ $preview->active_members_count }}</div>
                                <div class="text-muted" style="font-size:11px;">Membres</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="bg-light rounded-3 py-2">
                                <div class="fw-bold">{{ ucfirst($preview->frequency) }}</div>
                                <div class="text-muted" style="font-size:11px;">Fréquence</div>
                            </div>
                        </div>
                    </div>

                    @if($preview->description)
                    <p class="text-muted small mt-3 mb-0">{{ $preview->description }}</p>
                    @endif

                    @if(!$preview->acceptsNewMembers())
                    <div class="alert alert-warning mt-3 mb-0 py-2 small">
                        <i class="fas fa-info-circle me-1"></i>
                        Cette tontine n'accepte plus de nouveaux membres (complète ou clôturée).
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <nav aria-label="breadcrumb" class="mb-3 small">
                <ol class="breadcrumb bg-transparent p-0 m-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-green">Accueil</a></li>
                    <li class="breadcrumb-item active">Rejoindre une tontine</li>
                </ol>
            </nav>

            <div class="card">
                <div class="mb-4">
                    <h4 class="fw-bold">Rejoindre une tontine</h4>
                    <p class="text-muted mb-0">Entrez le code reçu ou utilisez le lien partagé pour rejoindre la tontine. Vous devrez vous connecter ou vous inscrire pour finaliser l'adhésion.</p>
                </div>

                    <form method="POST" action="{{ route('tontines.join') }}">
                        @csrf
                        <div class="mb-3">
                            <label for="code" class="form-label">Code tontine</label>
                            <input type="text" name="code" id="code"
                                   class="form-control text-uppercase @error('code') is-invalid @enderror"
                                   value="{{ old('code', $code) }}"
                                   maxlength="6"
                                   placeholder="SAND01" required>
                            @error('code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Le code est fourni par le créateur de la tontine.</div>
                        </div>

                        @auth
                            <button type="submit" class="btn btn-primary"
                                {{ ($preview && !$preview->acceptsNewMembers()) ? 'disabled' : '' }}>
                                Rejoindre cette tontine
                            </button>
                        @else
                            <div class="alert alert-info mb-3">
                                Connectez-vous ou créez un compte pour finaliser votre adhésion.
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <a href="{{ route('auth.login') }}" class="btn btn-primary">Se connecter</a>
                                <a href="{{ route('auth.register') }}" class="btn btn-outline-primary">S'inscrire</a>
                            </div>
                        @endauth
                    </form>

                    @if($code && !$preview)
                        <div class="alert alert-warning mt-4">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Aucune tontine trouvée avec ce code. Vérifiez le code et réessayez.
                        </div>
                    @endif
            </div>
        </div>
    </div>
</div>
@endsection