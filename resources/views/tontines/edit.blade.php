@extends('layouts.app')

@section('title', 'Modifier la tontine')

@section('content')
<div class="container py-4">

    <a href="{{ route('tontines.show', $tontine) }}" class="back-link">
        <i class="fas fa-arrow-left"></i>{{ $tontine->name }}
    </a>

    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="{{ route('tontines.show', $tontine) }}" class="btn-back">
            <i class="fas fa-arrow-left" aria-hidden="true"></i>
            {{ $tontine->name }}
        </a>
        <h4 class="fw-bold mb-0">Modifier la tontine</h4>
    </div>

    @if($tontine->status === 'active')
    <div class="alert alert-warning d-flex gap-2 align-items-center mb-4">
        <i class="fas fa-lock"></i>
        <small>Tontine active — montant, fréquence, type et date de début ne peuvent plus être modifiés.</small>
    </div>
    @endif

    <form method="POST" action="{{ route('tontines.update', $tontine) }}">
        @csrf
        @method('PUT')

        {{-- Informations générales --}}
        <div class="card mb-4">
            <h6 class="fw-semibold text-muted mb-3">Informations générales</h6>

            <div class="mb-3">
                <label class="form-label fw-semibold">Nom <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name', $tontine->name) }}" required>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Description</label>
                <textarea name="description" class="form-control" rows="3">{{ old('description', $tontine->description) }}</textarea>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Visibilité</label>
                <div class="d-flex gap-3">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="visibility" id="vis_private"
                               value="private" {{ old('visibility', $tontine->visibility ?? 'private') !== 'public' ? 'checked' : '' }}>
                        <label class="form-check-label" for="vis_private">
                            <i class="fas fa-lock me-1 text-muted"></i>Privée
                            <small class="d-block text-muted" style="font-size:11px;">Accessible uniquement sur invitation</small>
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="visibility" id="vis_public"
                               value="public" {{ old('visibility', $tontine->visibility) === 'public' ? 'checked' : '' }}>
                        <label class="form-check-label" for="vis_public">
                            <i class="fas fa-globe me-1 text-green"></i>Publique
                            <small class="d-block text-muted" style="font-size:11px;">Visible dans le catalogue Explorer</small>
                        </label>
                    </div>
                </div>
            </div>

            <div class="mb-0">
                <label class="form-label fw-semibold">Type <span class="text-danger">*</span></label>
                @if($tontine->status === 'active')
                <div class="form-control bg-light text-muted">
                    {{ match($tontine->type) {
                        'fixed'        => 'Fixe — Rotation classique',
                        'auction'      => 'Enchères',
                        'forced_saving'=> 'Épargne forcée',
                        'ceremonial'   => 'Cérémonielle',
                        default        => ucfirst($tontine->type)
                    } }}
                </div>
                <input type="hidden" name="type" value="{{ $tontine->type }}">
                <small class="text-muted">Le type ne peut pas être modifié sur une tontine active.</small>
                @else
                <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                    @foreach(['fixed' => 'Fixe', 'auction' => 'Enchères', 'forced_saving' => 'Épargne forcée', 'ceremonial' => 'Cérémonielle'] as $val => $label)
                    <option value="{{ $val }}" {{ old('type', $tontine->type) === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                @endif
            </div>
        </div>

        {{-- Paramètres financiers --}}
        <div class="card mb-4">
            <h6 class="fw-semibold text-muted mb-3">Paramètres financiers</h6>

            <div class="mb-3">
                <label class="form-label fw-semibold">Montant (FCFA)
                    @if($tontine->status !== 'active') <span class="text-danger">*</span> @endif
                </label>
                <div class="input-group">
                    <input type="number" name="amount" class="form-control"
                           value="{{ old('amount', $tontine->amount) }}" min="500" max="500000"
                           {{ $tontine->status === 'active' ? 'disabled' : 'required' }}>
                    <span class="input-group-text">FCFA</span>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-6">
                    <label class="form-label fw-semibold">Fréquence
                        @if($tontine->status !== 'active') <span class="text-danger">*</span> @endif
                    </label>
                    <select name="frequency" class="form-select" {{ $tontine->status === 'active' ? 'disabled' : 'required' }}>
                        @foreach(['daily' => 'Quotidienne', 'weekly' => 'Hebdomadaire', 'monthly' => 'Mensuelle'] as $val => $label)
                        <option value="{{ $val }}" {{ old('frequency', $tontine->frequency) === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6">
                    <label class="form-label fw-semibold">Pénalité (%)</label>
                    <input type="number" name="penalty_rate" class="form-control"
                           value="{{ old('penalty_rate', $tontine->penalty_rate) }}" min="0" max="100" step="0.5">
                </div>
            </div>
        </div>

        {{-- Dates --}}
        <div class="card mb-4">
            <h6 class="fw-semibold text-muted mb-3">Dates</h6>

            <div class="mb-0">
                <label class="form-label fw-semibold">Date de début
                    @if($tontine->status !== 'active') <span class="text-danger">*</span> @endif
                </label>
                <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror"
                       value="{{ old('start_date', $tontine->start_date->format('Y-m-d')) }}"
                       {{ $tontine->status === 'active' ? 'disabled' : 'required' }}>
                @error('start_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="card mb-4" x-data="{ weightedDraw: {{ old('weighted_draw', $tontine->weighted_draw) ? 'true' : 'false' }} }">
            <h6 class="fw-semibold text-muted mb-3">Membres & Tirage</h6>

            <div class="row g-3">
                <div class="col-6">
                    <label class="form-label fw-semibold">Nb. max membres <span class="text-danger">*</span></label>
                    <input type="number" name="max_members" class="form-control"
                           value="{{ old('max_members', $tontine->max_members) }}" min="2" max="50" required>
                </div>
                <div class="col-6">
                    <label class="form-label fw-semibold">Méthode tirage
                        @if($tontine->status !== 'active') <span class="text-danger">*</span> @endif
                    </label>
                    <select name="draw_method" class="form-select"
                            {{ $tontine->status === 'active' ? 'disabled' : '' }}
                            x-bind:disabled="weightedDraw"
                            x-effect="if (weightedDraw) $el.value = 'random'">
                        <option value="sequential" {{ old('draw_method', $tontine->draw_method) === 'sequential' ? 'selected' : '' }}>Séquentiel</option>
                        <option value="random"     {{ old('draw_method', $tontine->draw_method) === 'random'     ? 'selected' : '' }}>Aléatoire</option>
                    </select>
                </div>
            </div>

            <div class="row g-3 mt-0">
                <div class="col-6">
                    <label class="form-label fw-semibold">Quorum (%)
                        <span class="text-muted fw-normal" data-bs-toggle="tooltip"
                              title="% de membres devant avoir payé pour déclencher le tirage">
                            <i class="fas fa-info-circle"></i>
                        </span>
                    </label>
                    <input type="number" name="quorum" class="form-control"
                           value="{{ old('quorum', $tontine->quorum) }}" min="1" max="100" placeholder="Ex: 80">
                </div>
                <div class="col-6">
                    <label class="form-label fw-semibold">Seuil de véto (%)
                        <span class="text-muted fw-normal" data-bs-toggle="tooltip"
                              title="% de membres requis pour annuler un tirage">
                            <i class="fas fa-info-circle"></i>
                        </span>
                    </label>
                    <input type="number" name="veto_threshold" class="form-control"
                           value="{{ old('veto_threshold', $tontine->veto_threshold) }}"
                           min="1" max="100" placeholder="Ex: 50">
                </div>
            </div>

            <div class="mt-3">
                <div class="form-check form-switch">
                    <input type="checkbox" name="weighted_draw" value="1"
                           class="form-check-input" id="weighted_draw"
                           {{ old('weighted_draw', $tontine->weighted_draw) ? 'checked' : '' }}
                           x-model="weightedDraw">
                    <label class="form-check-label fw-semibold" for="weighted_draw">
                        Tirage pondéré par score crédit
                    </label>
                    <small class="d-block text-muted">Si activé, la méthode devient automatiquement aléatoire (pondérée). Le séquentiel est désactivé.</small>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-lg w-100">
            <i class="fas fa-save me-2"></i>Enregistrer les modifications
        </button>
    </form>

</div>
@endsection
