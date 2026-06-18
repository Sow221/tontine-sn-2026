@extends('layouts.app')
@section('title', 'Mon profil')

@section('content')
<div class="container py-4">

    <nav aria-label="breadcrumb" class="mb-3 small">
        <ol class="breadcrumb bg-transparent p-0 m-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-green">Accueil</a></li>
            <li class="breadcrumb-item active" aria-current="page">Mon profil</li>
        </ol>
    </nav>

    {{-- Hero profil --}}
    @php
        $avatarUrl = $user->avatar
            ? (str_starts_with($user->avatar, 'http') ? $user->avatar : asset('storage/' . $user->avatar))
            : null;
        $kycStatus = $user->kyc_verified ? 'ok' : ($user->kyc_status === 'rejected' ? 'err' : ($user->kyc_document ? 'wait' : 'none'));
        $memberMonths = (int) $user->created_at->diffInMonths(now());
    @endphp
    <div class="profile-hero mb-4">
        <div class="profile-hero__left">
            <div class="profile-hero__avatar">
                @if($avatarUrl)
                    <img src="{{ $avatarUrl }}" alt="avatar">
                @else
                    {{ strtoupper(substr($user->name ?? $user->email, 0, 1)) }}
                @endif
            </div>
            <div>
                <h5 class="fw-bold mb-1" style="color:#fff;">{{ $user->name }}</h5>
                <p class="mb-1" style="font-size:13px;opacity:.75;color:#e2e8f0;">{{ $user->email }}</p>
                <span class="badge badge-{{ match($user->role) { 'super_admin' => 'danger', 'admin' => 'warning', default => 'secondary' } }}">
                    {{ match($user->role) { 'super_admin' => 'Super Admin', 'admin' => 'Admin', default => 'Membre' } }}
                </span>
            </div>
        </div>
        <div class="profile-hero__stats">
            <div class="profile-stat">
                <span class="profile-stat__val">
                    @if($kycStatus === 'ok') <i class="fas fa-shield-alt"></i>
                    @elseif($kycStatus === 'wait') <i class="fas fa-clock"></i>
                    @else <i class="fas fa-user-slash"></i>
                    @endif
                </span>
                <span class="profile-stat__key">KYC</span>
            </div>
            @if($user->creditScore)
            <div class="profile-stat">
                <span class="profile-stat__val">{{ $user->creditScore->score }}<span style="font-size:11px;font-weight:400;">/10</span></span>
                <span class="profile-stat__key">Score</span>
            </div>
            @endif
            <div class="profile-stat">
                <span class="profile-stat__val">{{ $memberMonths }}<span style="font-size:11px;font-weight:400;">m</span></span>
                <span class="profile-stat__key">Ancienneté</span>
            </div>
            @if($referralsCount > 0)
            <div class="profile-stat">
                <span class="profile-stat__val">{{ $referralsCount }}</span>
                <span class="profile-stat__key">Parrainages</span>
            </div>
            @endif
        </div>
    </div>

    {{-- Formulaire unique : nom + email + téléphone + avatar --}}
    <div class="card mb-4">
        <h6 class="fw-semibold mb-3">Informations personnelles</h6>
        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
            @csrf @method('PUT')

            <div class="mb-3">
                <label class="form-label fw-semibold small">Nom complet</label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name', $user->name) }}" required>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold small">Adresse email</label>
                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                       value="{{ old('email', $user->email) }}" required>
                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold small">Numéro de téléphone</label>
                <input type="tel" name="phone_number" class="form-control @error('phone_number') is-invalid @enderror"
                       value="{{ old('phone_number', $user->phone_number) }}"
                       placeholder="+221 77 000 00 00">
                @error('phone_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div class="form-text">Format : +221 77 000 00 00</div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold small">Photo de profil</label>
                <input type="file" name="avatar" class="form-control @error('avatar') is-invalid @enderror" accept="image/*">
                @error('avatar') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <div class="form-text">JPG, PNG. Max 2 Mo.</div>
            </div>

            <button type="submit" class="btn btn-primary w-100">
                <i class="fas fa-save me-2"></i>Enregistrer les modifications
            </button>
        </form>
    </div>

    {{-- KYC --}}
    <div class="card mb-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h6 class="fw-semibold mb-0">Vérification d'identité (KYC)</h6>
            @if($user->kyc_verified)
                <span class="badge badge-success"><i class="fas fa-check me-1"></i>Vérifié</span>
            @elseif($user->kyc_status === 'rejected')
                <span class="badge badge-danger"><i class="fas fa-times me-1"></i>Refusé</span>
            @elseif($user->kyc_document)
                <span class="badge badge-warning"><i class="fas fa-clock me-1"></i>En cours</span>
            @else
                <span class="badge badge-secondary">Non soumis</span>
            @endif
        </div>

        @if($user->kyc_verified)
            <p class="text-muted small mb-0">
                <i class="fas fa-shield-alt text-green me-1"></i>
                Votre identité a été vérifiée. Vous pouvez rejoindre toutes les tontines.
            </p>
        @elseif($user->kyc_status === 'rejected')
            <div class="alert alert-danger py-2 mb-3 small">
                <i class="fas fa-times-circle me-1"></i>
                <strong>Document refusé.</strong>
                @if($user->kyc_rejected_reason) Motif : {{ $user->kyc_rejected_reason }} @endif
                Vous pouvez soumettre un nouveau document ci-dessous.
            </div>
            <form method="POST" action="{{ route('profile.kyc') }}" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Nouveau document <span class="text-danger">*</span></label>
                    <input type="file" name="kyc_document" class="form-control @error('kyc_document') is-invalid @enderror"
                           accept=".jpg,.jpeg,.png,.pdf" required>
                    @error('kyc_document') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div class="form-text">CNI, passeport ou permis de conduire. JPG, PNG ou PDF. Max 5 Mo.</div>
                </div>
                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input @error('kyc_consent') is-invalid @enderror"
                               type="checkbox" name="kyc_consent" id="kyc_consent_rejected" value="1" required>
                        <label class="form-check-label small" for="kyc_consent_rejected">
                            J'accepte que ma pièce d'identité soit collectée et traitée uniquement à des fins de vérification,
                            conformément à notre <a href="{{ route('privacy') }}" target="_blank">politique de confidentialité</a>.
                        </label>
                        @error('kyc_consent') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-upload me-2"></i>Soumettre un nouveau document
                </button>
            </form>
        @elseif($user->kyc_document)
            <p class="text-muted small mb-3">
                <i class="fas fa-clock text-warning me-1"></i>
                Document soumis. Vérification en cours (24-48h).
            </p>
            <form method="POST" action="{{ route('profile.kyc') }}" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Soumettre un nouveau document</label>
                    <input type="file" name="kyc_document" class="form-control @error('kyc_document') is-invalid @enderror"
                           accept=".jpg,.jpeg,.png,.pdf" required>
                    @error('kyc_document') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div class="form-text">CNI, passeport ou permis de conduire. JPG, PNG ou PDF. Max 5 Mo.</div>
                </div>
                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input @error('kyc_consent') is-invalid @enderror"
                               type="checkbox" name="kyc_consent" id="kyc_consent_replace" value="1" required>
                        <label class="form-check-label small" for="kyc_consent_replace">
                            J'accepte que ma pièce d'identité soit collectée et traitée uniquement à des fins de vérification,
                            conformément à notre <a href="{{ route('privacy') }}" target="_blank">politique de confidentialité</a>.
                        </label>
                        @error('kyc_consent') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <button type="submit" class="btn btn-outline-primary w-100">
                    <i class="fas fa-upload me-2"></i>Remplacer le document
                </button>
            </form>
        @else
            <p class="text-muted small mb-3">
                Soumettez une pièce d'identité pour être vérifié et accéder à toutes les fonctionnalités.
            </p>
            <form method="POST" action="{{ route('profile.kyc') }}" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Pièce d'identité <span class="text-danger">*</span></label>
                    <input type="file" name="kyc_document" class="form-control @error('kyc_document') is-invalid @enderror"
                           accept=".jpg,.jpeg,.png,.pdf" required>
                    @error('kyc_document') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div class="form-text">CNI, passeport ou permis de conduire. JPG, PNG ou PDF. Max 5 Mo.</div>
                </div>
                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input @error('kyc_consent') is-invalid @enderror"
                               type="checkbox" name="kyc_consent" id="kyc_consent" value="1" required>
                        <label class="form-check-label small" for="kyc_consent">
                            J'accepte que ma pièce d'identité soit collectée et traitée uniquement à des fins de vérification d'identité,
                            conformément à notre <a href="{{ route('privacy') }}" target="_blank">politique de confidentialité</a>.
                            Le document sera supprimé après vérification.
                        </label>
                        @error('kyc_consent') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-upload me-2"></i>Soumettre mon document
                </button>
            </form>
        @endif

        {{-- Export données personnelles --}}
        <div class="border-top mt-3 pt-3">
            <p class="text-muted small mb-2"><i class="fas fa-download me-1"></i>Droit d'accès à vos données personnelles</p>
            <a href="{{ route('profile.export') }}" class="btn btn-sm btn-outline-secondary w-100 rounded-pill">
                Télécharger mes données
            </a>
        </div>
    </div>

    {{-- Préférences de notification --}}
    <div class="card mb-4">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <h6 class="fw-semibold mb-0">Notifications</h6>
                <small class="text-muted">Gérez vos préférences d'alertes email et WhatsApp</small>
            </div>
            <a href="{{ route('profile.notifications') }}" class="btn btn-sm btn-outline-primary rounded-pill">
                <i class="fas fa-sliders-h me-1"></i>Configurer
            </a>
        </div>
    </div>

    {{-- Parrainage --}}
    <div class="card mb-4">
        <h6 class="fw-semibold mb-1"><i class="fas fa-share-alt me-2 text-green"></i>Parrainez vos proches</h6>
        <p class="text-muted small mb-3">
            Invitez vos amis et famille à rejoindre TontineSN. Votre lien de parrainage est unique.
            @if($referralsCount > 0)
                <strong class="text-green">{{ $referralsCount }} personne(s) inscrite(s) via votre lien.</strong>
            @endif
        </p>
        <div class="bg-light rounded-3 p-3 mb-3">
            <div class="small text-muted mb-1">Votre lien de parrainage</div>
            <div class="d-flex align-items-center gap-2">
                <code class="flex-grow-1 small text-break">{{ $referralLink }}</code>
                <button type="button" class="btn btn-sm btn-outline-secondary flex-shrink-0" onclick="copyToClipboard('{{ $referralLink }}')"
                        aria-label="Copier le lien">
                    <i class="fas fa-copy"></i>
                </button>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="https://wa.me/?text={{ urlencode('Rejoins TontineSN, la tontine numérique du Sénégal 🇸🇳 ! Crée ton compte gratuitement : '.$referralLink) }}"
               target="_blank" rel="noreferrer" class="btn btn-sm btn-success">
                <i class="fab fa-whatsapp me-1"></i>Partager sur WhatsApp
            </a>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="copyToClipboard('{{ $referralLink }}')">
                <i class="fas fa-link me-1"></i>Copier le lien
            </button>
        </div>
    </div>

    {{-- Score crédit --}}
    @if($user->creditScore)
    <div class="card mb-4">
        <h6 class="fw-semibold mb-3">Mon score crédit</h6>
        <div class="d-flex align-items-center gap-3 mb-3">
            <div class="score-circle score-circle-lg">
                <svg viewBox="0 0 36 36" class="score-svg">
                    <path class="score-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                    <path class="score-fill" stroke-dasharray="{{ $user->creditScore->score * 10 }}, 100"
                          d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                    <text x="18" y="20.35" class="score-text">{{ $user->creditScore->score }}</text>
                </svg>
            </div>
            <div>
                <h5 class="fw-bold mb-0">{{ $user->creditScore->score }}<span class="text-muted fs-6">/10</span></h5>
                <span class="badge bg-{{ $user->creditScore->badgeColor() }}">{{ $user->creditScore->badgeLabel() }}</span>
                <p class="text-muted small mb-0 mt-1">{{ number_format($user->creditScore->total_contributed, 0, ',', ' ') }} FCFA cotisés au total</p>
            </div>
        </div>
        <div class="border-top pt-3">
            <div class="d-flex justify-content-between small mb-1">
                <span class="text-muted"><i class="fas fa-coins me-1"></i>Montant cotisé</span>
                <span class="fw-semibold">{{ number_format($user->creditScore->total_contributed, 0, ',', ' ') }} FCFA</span>
            </div>
            <div class="d-flex justify-content-between small mb-1">
                <span class="text-muted"><i class="fas fa-check me-1"></i>Paiements à l'heure</span>
                <span class="fw-semibold">{{ $user->creditScore->on_time_payments }} / {{ $user->creditScore->total_cycles }}</span>
            </div>
            <div class="d-flex justify-content-between small mb-3">
                <span class="text-muted"><i class="fas fa-calendar me-1"></i>Ancienneté</span>
                <span class="fw-semibold">{{ $user->creditScore->seniority_months }} mois</span>
            </div>
            <div class="progress progress-sm">
                <div class="progress-bar bg-{{ $user->creditScore->badgeColor() === 'light' ? 'secondary' : $user->creditScore->badgeColor() }}"
                     style="width:{{ $user->creditScore->score * 10 }}%"></div>
            </div>
        </div>
    </div>
    @endif

    {{-- Changer mot de passe --}}
    @if($user->password)
    <div class="card mb-4">
        <h6 class="fw-semibold mb-3">Changer le mot de passe</h6>
        <form method="POST" action="{{ route('profile.password') }}">
            @csrf @method('PUT')
            <div class="mb-3">
                <label class="form-label fw-semibold small">Mot de passe actuel</label>
                <input type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" required>
                @error('current_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold small">Nouveau mot de passe</label>
                <input type="password" name="password" class="form-control" minlength="8" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold small">Confirmer le nouveau mot de passe</label>
                <input type="password" name="password_confirmation" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-outline-primary w-100">
                <i class="fas fa-lock me-2"></i>Mettre à jour le mot de passe
            </button>
        </form>
    </div>
    @endif

    {{-- 2FA --}}
    <div class="card mb-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h6 class="fw-semibold mb-0">Authentification à deux facteurs (2FA)</h6>
                <small class="text-muted">Protégez votre compte avec un code TOTP en plus de votre mot de passe</small>
            </div>
            @if($user->hasTwoFactorEnabled())
                <span class="badge badge-success"><i class="fas fa-shield-alt me-1"></i>Activé</span>
            @else
                <span class="badge badge-secondary">Désactivé</span>
            @endif
        </div>

        @if(session('backup_codes'))
        <div class="alert alert-warning mb-3">
            <p class="fw-semibold mb-2"><i class="fas fa-exclamation-triangle me-1"></i>Sauvegardez ces codes de secours — ils ne seront affichés qu'une seule fois :</p>
            <div class="d-flex flex-wrap gap-2">
                @foreach(session('backup_codes') as $code)
                <code class="bg-light px-2 py-1 rounded fw-bold" style="font-size:13px;">{{ $code }}</code>
                @endforeach
            </div>
        </div>
        @endif

        @if($user->hasTwoFactorEnabled())
            <form method="POST" action="{{ route('2fa.disable') }}"
                  onsubmit="return confirm('Désactiver le 2FA ? Votre compte sera moins sécurisé.')">
                @csrf
                @error('code') <div class="alert alert-danger py-2 mb-2 small">{{ $message }}</div> @enderror
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Code de vérification ou code de secours</label>
                    <input type="text" name="code" class="form-control" placeholder="Code à 6 chiffres"
                           inputmode="numeric" maxlength="10" required autocomplete="one-time-code">
                </div>
                <button type="submit" class="btn btn-outline-danger w-100 rounded-pill">
                    <i class="fas fa-lock-open me-2"></i>Désactiver le 2FA
                </button>
            </form>
        @else
            <p class="text-muted small mb-3">
                Une fois activé, vous devrez saisir un code depuis votre application d'authentification à chaque connexion.
            </p>
            <a href="{{ route('2fa.setup') }}" class="btn btn-primary w-100 rounded-pill">
                <i class="fas fa-shield-alt me-2"></i>Configurer le 2FA
            </a>
        @endif
    </div>

    {{-- Supprimer mon compte --}}
    <div class="card mb-4 border-danger">
        <h6 class="fw-semibold mb-3 text-danger">Zone dangereuse</h6>
        <p class="text-muted small mb-3">
            La suppression de votre compte est irréversible. Vous devez d'abord quitter toutes vos tontines actives.
        </p>
        <form method="POST" action="{{ route('account.delete') }}" onsubmit="return confirm('Êtes-vous absolument sûr de vouloir supprimer votre compte ? Cette action est irréversible.');">
            @csrf @method('DELETE')
            <div class="mb-3">
                <label class="form-label fw-semibold small">Tapez <strong>SUPPRIMER</strong> pour confirmer</label>
                <input type="text" name="confirm_delete" class="form-control @error('confirm_delete') is-invalid @enderror"
                       placeholder="SUPPRIMER" required pattern="SUPPRIMER">
                @error('confirm_delete') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <button type="submit" class="btn btn-danger w-100">
                <i class="fas fa-trash me-2"></i>Supprimer mon compte
            </button>
        </form>
    </div>


</div>
@endsection
