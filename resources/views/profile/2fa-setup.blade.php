@extends('layouts.app')
@section('title', 'Configurer le 2FA')

@section('content')
<div class="container py-4" style="max-width:480px;">

    <nav aria-label="breadcrumb" class="mb-3 small">
        <ol class="breadcrumb bg-transparent p-0 m-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-green">Accueil</a></li>
            <li class="breadcrumb-item"><a href="{{ route('profile.show') }}" class="text-green">Mon profil</a></li>
            <li class="breadcrumb-item active">Authentification 2FA</li>
        </ol>
    </nav>

    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="{{ route('profile.show') }}" class="btn-back">
            <i class="fas fa-arrow-left" aria-hidden="true"></i>
            Mon profil
        </a>
        <h4 class="fw-bold mb-0">Activer le 2FA</h4>
    </div>

    <div class="card mb-4">
        <p class="text-muted small mb-3">
            Scannez ce QR code avec une application d'authentification
            (<strong>Google Authenticator</strong>, <strong>Authy</strong>, <strong>Microsoft Authenticator</strong>)
            puis entrez le code à 6 chiffres affiché.
        </p>

        {{-- QR Code généré côté client via qrcode.js --}}
        <div class="text-center mb-3">
            <div id="qrcode" class="d-inline-block p-3 bg-white border rounded-3"></div>
        </div>

        {{-- Clé secrète en texte pour saisie manuelle --}}
        <div class="bg-light rounded-3 p-3 mb-4 text-center">
            <small class="text-muted d-block mb-1">Saisie manuelle de la clé :</small>
            <code class="fs-6 fw-bold" style="letter-spacing:.15em;">{{ $record->secret }}</code>
            <button type="button" class="btn btn-sm btn-outline-secondary ms-2"
                    onclick="navigator.clipboard.writeText('{{ $record->secret }}').then(() => this.textContent='Copié ✓')">
                Copier
            </button>
        </div>

        <form method="POST" action="{{ route('2fa.enable') }}">
            @csrf
            @error('code')
                <div class="alert alert-danger py-2 mb-3 small">{{ $message }}</div>
            @enderror
            <div class="mb-3">
                <label class="form-label fw-semibold">Code de vérification <span class="text-danger">*</span></label>
                <input type="text" name="code" class="form-control form-control-lg text-center fw-bold"
                       inputmode="numeric" pattern="\d{6}" maxlength="6"
                       placeholder="000 000" autocomplete="one-time-code" autofocus required
                       style="letter-spacing:.4em;font-size:1.5rem;">
                <div class="form-text">Le code se renouvelle toutes les 30 secondes.</div>
            </div>
            <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-shield-alt me-2"></i>Activer le 2FA
            </button>
        </form>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
<script>
QRCode.toCanvas(document.createElement('canvas'), {{ Js::from($otpUri) }}, { width: 200, margin: 1 }, function(err, canvas) {
    if (!err) document.getElementById('qrcode').appendChild(canvas);
});
</script>
@endpush
