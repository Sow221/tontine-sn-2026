@extends('layouts.app')
@section('title', 'Explorer les tontines')

@section('content')
<div class="container py-4">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold mb-0">Explorer les tontines</h4>
            <small class="text-muted">
                @if($tontines->total() > 0)
                    {{ $tontines->total() }} tontine{{ $tontines->total() > 1 ? 's' : '' }} disponible{{ $tontines->total() > 1 ? 's' : '' }}
                @else
                    Découvrez des tontines ouvertes et rejoignez-les directement
                @endif
            </small>
        </div>
        <a href="{{ route('tontines.create') }}" class="btn btn-primary rounded-pill">
            <i class="fas fa-plus me-2"></i>Créer la mienne
        </a>
    </div>

    {{-- Filtres --}}
    <form method="GET" action="{{ route('tontines.explore') }}" class="mb-4">
        <div class="row g-2">
            <div class="col-12 col-sm-5">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control"
                           placeholder="Rechercher une tontine…"
                           value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-6 col-sm-2">
                <select name="type" class="form-select">
                    <option value="">Type</option>
                    <option value="fixed"         {{ request('type') === 'fixed'         ? 'selected' : '' }}>Fixe</option>
                    <option value="auction"       {{ request('type') === 'auction'       ? 'selected' : '' }}>Enchères</option>
                    <option value="forced_saving" {{ request('type') === 'forced_saving' ? 'selected' : '' }}>Épargne</option>
                    <option value="ceremonial"    {{ request('type') === 'ceremonial'    ? 'selected' : '' }}>Cérémonielle</option>
                </select>
            </div>
            <div class="col-6 col-sm-2">
                <select name="frequency" class="form-select">
                    <option value="">Fréquence</option>
                    <option value="weekly"  {{ request('frequency') === 'weekly'  ? 'selected' : '' }}>Hebdo</option>
                    <option value="monthly" {{ request('frequency') === 'monthly' ? 'selected' : '' }}>Mensuelle</option>
                    <option value="daily"   {{ request('frequency') === 'daily'   ? 'selected' : '' }}>Quotidienne</option>
                </select>
            </div>
            <div class="col-8 col-sm-2">
                <input type="number" name="max_amount" class="form-control"
                       placeholder="Max FCFA"
                       value="{{ request('max_amount') }}" min="0" step="500">
            </div>
            <div class="col-8 col-sm-2">
                <select name="sort" class="form-select">
                    <option value="latest"   {{ request('sort', 'latest') === 'latest'   ? 'selected' : '' }}>Plus récentes</option>
                    <option value="amount_asc"  {{ request('sort') === 'amount_asc'  ? 'selected' : '' }}>Montant ↑</option>
                    <option value="amount_desc" {{ request('sort') === 'amount_desc' ? 'selected' : '' }}>Montant ↓</option>
                    <option value="spots"    {{ request('sort') === 'spots'    ? 'selected' : '' }}>Places dispo</option>
                </select>
            </div>
            <div class="col-4 col-sm-1">
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter"></i></button>
            </div>
        </div>
        @if(request()->hasAny(['search','type','frequency','max_amount']) || (request('sort') && request('sort') !== 'latest'))
        <a href="{{ route('tontines.explore') }}" class="text-muted small mt-2 d-inline-block">
            <i class="fas fa-times me-1"></i>Effacer les filtres
        </a>
        @endif
    </form>

    @if($tontines->isEmpty())
    <div class="text-center py-5">
        <div class="empty-scene">🔍</div>
        <p class="text-muted fw-semibold">Aucune tontine publique trouvée</p>
        <p class="text-muted small mb-3">
            @if(request()->hasAny(['search','type','frequency','max_amount']))
                Essayez d'autres filtres ou
                <a href="{{ route('tontines.explore') }}">voir toutes les tontines</a>.
            @else
                Soyez le premier à créer une tontine publique !
            @endif
        </p>
        <a href="{{ route('tontines.create') }}" class="btn btn-primary rounded-pill">
            <i class="fas fa-plus me-2"></i>Créer une tontine
        </a>
    </div>
    @else

    <div class="row g-3">
        @foreach($tontines as $tontine)
        @php
            $isMember   = in_array($tontine->id, $myTontineIds);
            $isFull     = $tontine->active_members_count >= $tontine->max_members;
            $canJoin    = !$isMember && !$isFull && in_array($tontine->status, ['pending', 'active']);
            $gradClass  = match($tontine->type) {
                'auction'       => 'tontine-gradient-auction',
                'ceremonial'    => 'tontine-gradient-ceremonial',
                'forced_saving' => 'tontine-gradient-saving',
                default         => 'tontine-gradient-standard',
            };
            $typeLabel = match($tontine->type) {
                'auction'       => 'Enchères',
                'ceremonial'    => 'Cérémonielle',
                'forced_saving' => 'Épargne',
                default         => 'Fixe',
            };
            $freqLabel = match($tontine->frequency) {
                'weekly'  => 'Hebdo',
                'daily'   => 'Quotidien',
                default   => 'Mensuelle',
            };
        @endphp
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card h-100 {{ $gradClass }}" style="border-top: 3px solid #009639;">
                <div class="card-body d-flex flex-column">
                    {{-- En-tête --}}
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div class="tontine-avatar flex-shrink-0">
                            {{ strtoupper(substr($tontine->name, 0, 2)) }}
                        </div>
                        <div class="flex-grow-1 min-width-0">
                            <h6 class="fw-bold mb-0 text-truncate">{{ $tontine->name }}</h6>
                            <div class="d-flex gap-1 flex-wrap mt-1">
                                <span class="badge badge-{{ $tontine->status === 'active' ? 'success' : 'warning' }}" style="font-size:10px;">
                                    {{ $tontine->status === 'active' ? 'Active' : 'En attente' }}
                                </span>
                                <span class="badge bg-light text-muted" style="font-size:10px;">{{ $typeLabel }}</span>
                                <span class="badge bg-light text-muted" style="font-size:10px;">{{ $freqLabel }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Description --}}
                    @if($tontine->description)
                    <p class="text-muted small mb-3" style="line-height:1.4;">
                        {{ Str::limit($tontine->description, 80) }}
                    </p>
                    @endif

                    {{-- Métriques --}}
                    <div class="row g-2 text-center mb-3">
                        <div class="col-4">
                            <div class="bg-white bg-opacity-75 rounded-3 py-2">
                                <div class="fw-bold small">{{ number_format($tontine->amount, 0, ',', ' ') }}</div>
                                <div class="text-muted" style="font-size:10px;">FCFA</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="bg-white bg-opacity-75 rounded-3 py-2">
                                <div class="fw-bold small">{{ $tontine->active_members_count }}/{{ $tontine->max_members }}</div>
                                <div class="text-muted" style="font-size:10px;">Membres</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="bg-white bg-opacity-75 rounded-3 py-2">
                                @if($isFull)
                                    <div class="fw-bold small text-danger">Complet</div>
                                @else
                                    <div class="fw-bold small text-success">{{ $tontine->max_members - $tontine->active_members_count }} place(s)</div>
                                @endif
                                <div class="text-muted" style="font-size:10px;">Dispo.</div>
                            </div>
                        </div>
                    </div>

                    {{-- Créateur --}}
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center text-white"
                             style="width:24px;height:24px;font-size:10px;flex-shrink:0;">
                            {{ strtoupper(substr($tontine->creator->name ?? '?', 0, 1)) }}
                        </div>
                        <small class="text-muted">par <strong>{{ $tontine->creator->name ?? '—' }}</strong></small>
                        @if($tontine->start_date)
                        <small class="text-muted ms-auto">Début {{ $tontine->start_date->format('d/m/Y') }}</small>
                        @endif
                    </div>

                    {{-- Actions --}}
                    <div class="mt-auto d-flex gap-2">
                        @if($isMember)
                            <a href="{{ route('tontines.show', $tontine) }}"
                               class="btn btn-sm btn-outline-success rounded-pill flex-grow-1">
                                <i class="fas fa-eye me-1"></i>Voir ma tontine
                            </a>
                        @elseif($canJoin)
                            <form method="POST" action="{{ route('tontines.join') }}" class="flex-grow-1">
                                @csrf
                                <input type="hidden" name="code" value="{{ $tontine->code }}">
                                <button type="submit" class="btn btn-sm btn-primary rounded-pill w-100">
                                    <i class="fas fa-user-plus me-1"></i>Demander à rejoindre
                                </button>
                            </form>
                        @else
                            <button class="btn btn-sm btn-outline-secondary rounded-pill flex-grow-1" disabled>
                                <i class="fas fa-lock me-1"></i>{{ $isFull ? 'Complet' : 'Indisponible' }}
                            </button>
                        @endif
                        <button type="button"
                                class="btn btn-sm btn-outline-secondary rounded-pill"
                                onclick="copyToClipboard('{{ route('tontines.join.form', ['code' => $tontine->code]) }}')"
                                title="Partager ce lien">
                            <i class="fas fa-share-alt"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="d-flex justify-content-center mt-4">
        {{ $tontines->links() }}
    </div>

    @endif

    {{-- CTA rejoindre via code --}}
    <div class="card mt-4 border-0 bg-light">
        <div class="d-flex align-items-center gap-3">
            <div class="icon-box bg-green-light"><i class="fas fa-key text-green fs-5"></i></div>
            <div class="flex-grow-1">
                <p class="fw-semibold mb-0 small">Vous avez un code d'invitation ?</p>
                <small class="text-muted">Rejoignez directement une tontine privée</small>
            </div>
            <a href="{{ route('tontines.join.form') }}" class="btn btn-sm btn-outline-primary rounded-pill">
                Entrer le code
            </a>
        </div>
    </div>

</div>
@endsection
