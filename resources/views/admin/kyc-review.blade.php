@extends('layouts.app')
@section('title', 'VÃ©rification KYC â€” ' . ($user->name ?? $user->email))

@section('content')
<div class="container py-4" style="max-width:900px">

    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="{{ route('admin.users.show', $user) }}" class="btn btn-sm btn-light rounded-circle">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h4 class="fw-bold mb-0">VÃ©rification KYC</h4>
    </div>

    <div class="row g-4">

        {{-- Colonne gauche : document --}}
        <div class="col-12 col-md-6">
            <div class="card h-100">
                <h6 class="fw-semibold mb-3"><i class="fas fa-id-card me-2 text-green"></i>Document soumis</h6>
                @php
                    $ext = pathinfo($user->kyc_document, PATHINFO_EXTENSION);
                @endphp
                @if(in_array(strtolower($ext), ['jpg','jpeg','png']))
                    <img src="{{ route('admin.users.kyc.document', $user) }}"
                         class="img-fluid rounded border" alt="Document KYC"
                         style="max-height:400px;object-fit:contain;width:100%">
                @else
                    <a href="{{ route('admin.users.kyc.document', $user) }}"
                       class="btn btn-outline-primary w-100" target="_blank">
                        <i class="fas fa-file-pdf me-2"></i>Ouvrir le PDF
                    </a>
                @endif
            </div>
        </div>

        {{-- Colonne droite : profil + OCR + actions --}}
        <div class="col-12 col-md-6 d-flex flex-column gap-3">

            {{-- Profil utilisateur --}}
            <div class="card">
                <h6 class="fw-semibold mb-3"><i class="fas fa-user me-2 text-indigo"></i>Profil enregistrÃ©</h6>
                <div class="small">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Nom</span>
                        <span class="fw-semibold">{{ $user->name ?? 'â€”' }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Email</span>
                        <span class="fw-semibold">{{ $user->email }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">TÃ©lÃ©phone</span>
                        <span class="fw-semibold">{{ $user->phone_number ?? 'â€”' }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Inscrit le</span>
                        <span class="fw-semibold">{{ $user->created_at->isoFormat('D MMM YYYY') }}</span>
                    </div>
                </div>
            </div>

            {{-- RÃ©sultat OCR Tesseract --}}
            <div class="card">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="fw-semibold mb-0"><i class="fas fa-robot me-2 text-warning"></i>Analyse OCR</h6>
                    @if($ocrText !== null)
                        @if($ocrMatched)
                            <span class="badge badge-success"><i class="fas fa-check me-1"></i>Nom dÃ©tectÃ©</span>
                        @else
                            <span class="badge badge-warning"><i class="fas fa-exclamation me-1"></i>VÃ©rifier manuellement</span>
                        @endif
                    @else
                        <span class="badge badge-secondary">OCR non disponible</span>
                    @endif
                </div>

                @if($ocrText)
                    <div class="bg-light rounded p-2 small" style="max-height:150px;overflow-y:auto;font-family:monospace;white-space:pre-wrap;">{{ $ocrText }}</div>
                    @if($ocrMatched)
                        <p class="text-success small mt-2 mb-0">
                            <i class="fas fa-check-circle me-1"></i>
                            Le nom Â« {{ $user->name }} Â» a Ã©tÃ© retrouvÃ© dans le document.
                        </p>
                    @else
                        <p class="text-warning small mt-2 mb-0">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            Le nom Â« {{ $user->name }} Â» n'a pas Ã©tÃ© retrouvÃ© clairement. VÃ©rifiez manuellement.
                        </p>
                    @endif
                @else
                    <p class="text-muted small mb-0">
                        Tesseract OCR non installÃ© sur ce serveur. VÃ©rification visuelle manuelle requise.
                        <br><a href="https://tesseract-ocr.github.io/tessdoc/Installation.html" target="_blank" class="small">Installer Tesseract</a>
                    </p>
                @endif
            </div>

            {{-- Actions --}}
            <div class="card">
                <h6 class="fw-semibold mb-3"><i class="fas fa-gavel me-2 text-danger"></i>DÃ©cision</h6>
                <button type="button" class="btn btn-success w-100 rounded-pill mb-2"
                        x-data
                        @click.prevent="window.dispatchEvent(new CustomEvent('open-modal', { detail: { id: 'admin-confirm', action: '{{ route('admin.users.kyc.approve', $user) }}', message: 'Approuver le KYC de {{ $user->name }} ? Le fichier sera supprimÃ©.', confirmText: 'Oui, approuver', type: 'danger' } }))">
                    <i class="fas fa-check me-1"></i>Approuver
                </button>
                <form method="POST" action="{{ route('admin.users.kyc.reject', $user) }}">
                    @csrf
                    <div class="mb-2">
                        <label class="form-label fw-semibold small">Motif du refus</label>
                        <input type="text" name="reason" class="form-control form-control-sm"
                               placeholder="Ex: Document illisible, identitÃ© non conforme..."
                               value="Document non valide ou illisible.">
                    </div>
                    <button type="submit" class="btn btn-outline-danger w-100 rounded-pill"
                            onclick="return confirm('Rejeter le KYC de {{ $user->name }} ?')">
                        <i class="fas fa-times me-1"></i>Rejeter
                    </button>
                </form>
            </div>

        </div>
    </div>
</div>
@endsection
