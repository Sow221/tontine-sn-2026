@extends('layouts.app')

@section('title', 'Créer une tontine')

@section('content')
<div class="container py-4" x-data="{
    type: '{{ old('type', 'fixed') }}',
    needsEndDate() { return ['forced_saving', 'ceremonial'].includes(this.type) },
    endDateLabel() {
        if (this.type === 'ceremonial')    return 'Date de l\'événement *'
        if (this.type === 'forced_saving') return 'Date de clôture de l\'épargne *'
        return 'Date de fin (optionnelle)'
    }
}">

    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="{{ route('tontines.index') }}" class="btn-back">
            <i class="fas fa-arrow-left" aria-hidden="true"></i>
            Mes tontines
        </a>
        <h4 class="fw-bold mb-0">Nouvelle tontine</h4>
    </div>

    <form method="POST" action="{{ route('tontines.store') }}">
        @csrf

        {{-- Informations générales --}}
        <div class="card mb-4">
            <h6 class="fw-semibold text-muted mb-3">Informations générales</h6>

            <div class="mb-3">
                <label class="form-label fw-semibold">Nom <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name') }}" placeholder="Ex: Tontine des femmes du marché" required>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Description</label>
                <textarea name="description" class="form-control" rows="2"
                          placeholder="Objectif de cette tontine...">{{ old('description') }}</textarea>
            </div>

            <div class="mb-0">
                <label class="form-label fw-semibold">Type de tontine <span class="text-danger">*</span></label>
                <div class="type-picker">
                    <label class="type-card" :class="{ 'type-card--active': type === 'fixed' }">
                        <input type="radio" name="type" value="fixed" x-model="type" class="d-none">
                        <span class="type-card__icon">🤝</span>
                        <span class="type-card__label">Fixe</span>
                        <small class="type-card__desc">Rotation classique — le pot tourne à chaque cycle</small>
                    </label>
                    <label class="type-card" :class="{ 'type-card--active': type === 'auction' }">
                        <input type="radio" name="type" value="auction" x-model="type" class="d-none">
                        <span class="type-card__icon">🏷️</span>
                        <span class="type-card__label">Enchères</span>
                        <small class="type-card__desc">Le plus offrant reçoit le pot en premier</small>
                    </label>
                    <label class="type-card" :class="{ 'type-card--active': type === 'forced_saving' }">
                        <input type="radio" name="type" value="forced_saving" x-model="type" class="d-none">
                        <span class="type-card__icon">💰</span>
                        <span class="type-card__label">Épargne forcée</span>
                        <small class="type-card__desc">Chacun épargne pour soi jusqu'à la clôture</small>
                    </label>
                    <label class="type-card" :class="{ 'type-card--active': type === 'ceremonial' }">
                        <input type="radio" name="type" value="ceremonial" x-model="type" class="d-none">
                        <span class="type-card__icon">🎊</span>
                        <span class="type-card__label">Cérémonielle</span>
                        <small class="type-card__desc">Mariage, baptême, funérailles…</small>
                    </label>
                </div>
                @error('type') <div class="invalid-feedback d-block mt-1">{{ $message }}</div> @enderror
            </div>
            <div class="mb-0">
                <label class="form-label fw-semibold">Visibilité</label>
                <div class="d-flex gap-3">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="visibility" id="vis_private"
                               value="private" {{ old('visibility', 'private') !== 'public' ? 'checked' : '' }}>
                        <label class="form-check-label" for="vis_private">
                            <i class="fas fa-lock me-1 text-muted"></i>Privée
                            <small class="d-block text-muted" style="font-size:11px;">Accessible uniquement sur invitation (code ou lien)</small>
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="visibility" id="vis_public"
                               value="public" {{ old('visibility') === 'public' ? 'checked' : '' }}>
                        <label class="form-check-label" for="vis_public">
                            <i class="fas fa-globe me-1 text-green"></i>Publique
                            <small class="d-block text-muted" style="font-size:11px;">Visible dans le catalogue Explorer</small>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        {{-- Paramètres financiers --}}
        <div class="card mb-4">
            <h6 class="fw-semibold text-muted mb-3">Paramètres financiers</h6>

            <div class="mb-3">
                <label class="form-label fw-semibold">Montant de cotisation (FCFA) <span class="text-danger">*</span></label>
                <div class="input-group">
                    <input type="number" name="amount" class="form-control @error('amount') is-invalid @enderror"
                           value="{{ old('amount') }}" min="500" max="500000" placeholder="5000" required>
                    <span class="input-group-text">FCFA</span>
                </div>
                @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="row g-3">
                <div class="col-6">
                    <label class="form-label fw-semibold">Fréquence <span class="text-danger">*</span></label>
                    <select name="frequency" class="form-select" required>
                        <option value="weekly"  {{ old('frequency') === 'weekly'             ? 'selected' : '' }}>Hebdomadaire</option>
                        <option value="monthly" {{ old('frequency', 'monthly') === 'monthly' ? 'selected' : '' }}>Mensuelle</option>
                        <option value="daily"   {{ old('frequency') === 'daily'              ? 'selected' : '' }}>Quotidienne</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Dates --}}
        <div class="card mb-4">
            <h6 class="fw-semibold text-muted mb-3">Dates</h6>

            <div class="mb-3">
                <label class="form-label fw-semibold">Date de début <span class="text-danger">*</span></label>
                <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror"
                       value="{{ old('start_date', now()->addDay()->format('Y-m-d')) }}"
                       min="{{ now()->format('Y-m-d') }}" required>
                @error('start_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-0">
                <label class="form-label fw-semibold" x-text="endDateLabel()"></label>
                <input type="date" name="end_date"
                       class="form-control @error('end_date') is-invalid @enderror"
                       value="{{ old('end_date') }}"
                       :required="needsEndDate()">
                @error('end_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                <small class="text-muted" x-show="type === 'ceremonial'">
                    Date à laquelle le bénéficiaire recevra le pot collecté.
                </small>
                <small class="text-muted" x-show="type === 'forced_saving'">
                    À cette date, chaque membre récupère son épargne personnelle.
                </small>
            </div>
        </div>

        {{-- Membres & Tirage --}}
        <div class="card mb-4">
            <h6 class="fw-semibold text-muted mb-3">Membres</h6>
            <div class="mb-0">
                <label class="form-label fw-semibold">Nombre maximum de membres <span class="text-danger">*</span></label>
                <input type="number" name="max_members" class="form-control"
                       value="{{ old('max_members', 10) }}" min="2" max="50" required>
            </div>
        </div>

        {{-- Options avancées --}}
        <div class="mb-4" x-data="{ open: {{ $errors->hasAny(['quorum','veto_threshold','weighted_draw','draw_method','penalty_rate']) ? 'true' : 'false' }} }">
            <button type="button" class="btn btn-sm btn-outline-secondary w-100 d-flex align-items-center justify-content-between"
                    @click="open = !open">
                <span><i class="fas fa-sliders-h me-2"></i>Options avancées (quorum, véto, tirage pondéré…)</span>
                <i class="fas fa-chevron-down" :style="open ? 'transform:rotate(180deg)' : ''" style="transition:transform 0.2s;"></i>
            </button>
            <div x-show="open" x-collapse class="card mt-2">

                <div class="row g-3 mb-3" x-show="!['forced_saving','ceremonial'].includes(type)">
                    <div class="col-6">
                        <label class="form-label fw-semibold">Méthode tirage</label>
                        <select name="draw_method" class="form-select"
                                :disabled="['forced_saving','ceremonial'].includes(type)">
                            <option value="sequential" {{ old('draw_method', 'sequential') === 'sequential' ? 'selected' : '' }}>Séquentiel</option>
                            <option value="random"     {{ old('draw_method') === 'random'                   ? 'selected' : '' }}>Aléatoire</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Pénalité retard (%)</label>
                        <input type="number" name="penalty_rate" class="form-control"
                               value="{{ old('penalty_rate', 0) }}" min="0" max="100" step="0.5">
                    </div>
                </div>

                <div class="row g-3 mb-3" x-show="!['forced_saving','ceremonial'].includes(type)">
                    <div class="col-6">
                        <label class="form-label fw-semibold d-flex align-items-center gap-1">
                            Quorum (%)
                            <span class="tooltip-icon" data-bs-toggle="tooltip" title="% membres devant avoir payé avant que le tirage soit possible.">
                                <i class="fas fa-question-circle text-muted" style="font-size:13px;"></i>
                            </span>
                        </label>
                        <input type="number" name="quorum" class="form-control"
                               value="{{ old('quorum') }}" min="1" max="100" placeholder="Ex: 80">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold d-flex align-items-center gap-1">
                            Seuil de véto (%)
                            <span class="tooltip-icon" data-bs-toggle="tooltip" title="% membres requis pour annuler un tirage contesté.">
                                <i class="fas fa-question-circle text-muted" style="font-size:13px;"></i>
                            </span>
                        </label>
                        <input type="number" name="veto_threshold" class="form-control"
                               value="{{ old('veto_threshold') }}" min="1" max="100" placeholder="Ex: 50">
                    </div>
                </div>

                <div x-show="!['forced_saving','ceremonial'].includes(type)">
                    <div class="form-check form-switch">
                        <input type="checkbox" name="weighted_draw" value="1"
                               class="form-check-input" id="weighted_draw"
                               {{ old('weighted_draw') ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold d-flex align-items-center gap-1" for="weighted_draw">
                            Tirage pondéré par score crédit
                            <span class="tooltip-icon" data-bs-toggle="tooltip" title="Les membres ponctuels ont plus de chances de recevoir le pot en premier.">
                                <i class="fas fa-question-circle text-muted" style="font-size:13px;"></i>
                            </span>
                        </label>
                    </div>
                </div>

                {{-- injecté dans le DOM uniquement pour forced_saving/ceremonial --}}
                <template x-if="['forced_saving','ceremonial'].includes(type)">
                    <input type="hidden" name="draw_method" value="sequential">
                </template>

            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-lg w-100">
            <i class="fas fa-check me-2"></i>Créer la tontine
        </button>
    </form>

</div>
@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));</script>
@endpush
@endsection
