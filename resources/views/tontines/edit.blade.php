@extends('layouts.app')

@section('title', 'Modifier la tontine')

@section('content')
<div class="container py-4">

    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="{{ route('tontines.show', $tontine) }}" class="btn btn-sm btn-light rounded-circle">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h4 class="fw-bold mb-0">Modifier la tontine</h4>
    </div>

    <form method="POST" action="{{ route('tontines.update', $tontine) }}">
        @csrf
        @method('PUT')

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
                <label class="form-label fw-semibold">Type <span class="text-danger">*</span></label>
                <select name="type" class="form-select" required>
                    @foreach(['fixed' => 'Fixe', 'auction' => 'Enchères', 'forced_saving' => 'Épargne forcée', 'ceremonial' => 'Cérémonielle'] as $val => $label)
                    <option value="{{ $val }}" {{ old('type', $tontine->type) === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="card mb-4">
            <h6 class="fw-semibold text-muted mb-3">Paramètres financiers</h6>

            <div class="mb-3">
                <label class="form-label fw-semibold">Montant (FCFA) <span class="text-danger">*</span></label>
                <div class="input-group">
                    <input type="number" name="amount" class="form-control @error('amount') is-invalid @enderror"
                           value="{{ old('amount', $tontine->amount) }}" min="500" max="500000" required>
                    <span class="input-group-text">FCFA</span>
                </div>
                @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="row g-3">
                <div class="col-6">
                    <label class="form-label fw-semibold">Fréquence <span class="text-danger">*</span></label>
                    <select name="frequency" class="form-select" required>
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

        <div class="card mb-4">
            <h6 class="fw-semibold text-muted mb-3">Membres & Tirage</h6>

            <div class="row g-3">
                <div class="col-6">
                    <label class="form-label fw-semibold">Nb. max membres <span class="text-danger">*</span></label>
                    <input type="number" name="max_members" class="form-control"
                           value="{{ old('max_members', $tontine->max_members) }}" min="2" max="50" required>
                </div>
                <div class="col-6">
                    <label class="form-label fw-semibold">Méthode tirage <span class="text-danger">*</span></label>
                    <select name="draw_method" class="form-select" required>
                        <option value="sequential" {{ old('draw_method', $tontine->draw_method) === 'sequential' ? 'selected' : '' }}>Séquentiel</option>
                        <option value="random" {{ old('draw_method', $tontine->draw_method) === 'random' ? 'selected' : '' }}>Aléatoire</option>
                    </select>
                </div>
            </div>

            <div class="mt-3">
                <label class="form-label fw-semibold">Date de début <span class="text-danger">*</span></label>
                <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror"
                       value="{{ old('start_date', $tontine->start_date->format('Y-m-d')) }}" required>
                @error('start_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-lg w-100">
            <i class="fas fa-save me-2"></i>Enregistrer les modifications
        </button>
    </form>

</div>
@endsection
