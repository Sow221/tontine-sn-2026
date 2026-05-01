@extends('layouts.app')

@section('title', 'Créer une tontine')

@section('content')
<div class="container py-4">

    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="{{ route('tontines.index') }}" class="btn btn-sm btn-light rounded-circle">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h4 class="fw-bold mb-0">Nouvelle tontine</h4>
    </div>

    <form method="POST" action="{{ route('tontines.store') }}">
        @csrf

        {{-- Informations de base --}}
        <div class="card mb-4">
            <h6 class="fw-semibold text-muted mb-3">Informations générales</h6>

            <div class="mb-3">
                <label class="form-label fw-semibold">Nom de la tontine <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name') }}" placeholder="Ex: Tontine des femmes du marché" required>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Description</label>
                <textarea name="description" class="form-control" rows="3"
                          placeholder="Décrivez l'objectif de cette tontine...">{{ old('description') }}</textarea>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Type de tontine <span class="text-danger">*</span></label>
                <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                    <option value="fixed" {{ old('type') === 'fixed' ? 'selected' : '' }}>Fixe (montant identique)</option>
                    <option value="auction" {{ old('type') === 'auction' ? 'selected' : '' }}>Enchères</option>
                    <option value="forced_saving" {{ old('type') === 'forced_saving' ? 'selected' : '' }}>Épargne forcée</option>
                    <option value="ceremonial" {{ old('type') === 'ceremonial' ? 'selected' : '' }}>Cérémonielle</option>
                </select>
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
                        <option value="weekly" {{ old('frequency') === 'weekly' ? 'selected' : '' }}>Hebdomadaire</option>
                        <option value="monthly" {{ old('frequency', 'monthly') === 'monthly' ? 'selected' : '' }}>Mensuelle</option>
                        <option value="daily" {{ old('frequency') === 'daily' ? 'selected' : '' }}>Quotidienne</option>
                    </select>
                </div>
                <div class="col-6">
                    <label class="form-label fw-semibold">Pénalité retard (%)</label>
                    <input type="number" name="penalty_rate" class="form-control"
                           value="{{ old('penalty_rate', 0) }}" min="0" max="100" step="0.5">
                </div>
            </div>
        </div>

        {{-- Membres & Tirage --}}
        <div class="card mb-4">
            <h6 class="fw-semibold text-muted mb-3">Membres & Tirage</h6>

            <div class="row g-3">
                <div class="col-6">
                    <label class="form-label fw-semibold">Nb. max membres <span class="text-danger">*</span></label>
                    <input type="number" name="max_members" class="form-control"
                           value="{{ old('max_members', 10) }}" min="2" max="50" required>
                </div>
                <div class="col-6">
                    <label class="form-label fw-semibold">Méthode tirage <span class="text-danger">*</span></label>
                    <select name="draw_method" class="form-select" required>
                        <option value="sequential" {{ old('draw_method', 'sequential') === 'sequential' ? 'selected' : '' }}>Séquentiel</option>
                        <option value="random" {{ old('draw_method') === 'random' ? 'selected' : '' }}>Aléatoire</option>
                    </select>
                </div>
            </div>

            <div class="mt-3">
                <label class="form-label fw-semibold">Date de début <span class="text-danger">*</span></label>
                <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror"
                       value="{{ old('start_date', now()->addDay()->format('Y-m-d')) }}"
                       min="{{ now()->format('Y-m-d') }}" required>
                @error('start_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-lg w-100">
            <i class="fas fa-check me-2"></i>Créer la tontine
        </button>
    </form>

</div>
@endsection
