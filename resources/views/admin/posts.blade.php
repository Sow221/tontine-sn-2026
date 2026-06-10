@extends('layouts.app')
@section('title', 'Actualités')

@section('content')
<div class="container py-4">

    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="{{ route('admin.dashboard') }}" class="btn btn-sm btn-light rounded-circle">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h4 class="fw-bold mb-0">Actualités</h4>
        <span class="badge bg-secondary ms-1">{{ $posts->total() }}</span>
    </div>

    {{-- Formulaire de création --}}
    <div class="card mb-4">
        <h6 class="fw-semibold mb-3"><i class="fas fa-pen me-2"></i>Nouvel article</h6>
        <form method="POST" action="{{ route('admin.posts.store') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label fw-semibold small">Titre <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                       value="{{ old('title') }}" required placeholder="Titre de l'article">
                @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold small">Résumé</label>
                <input type="text" name="excerpt" class="form-control"
                       value="{{ old('excerpt') }}" placeholder="Résumé court (affiché dans la liste)" maxlength="500">
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold small">Contenu <span class="text-danger">*</span></label>
                <textarea name="content" class="form-control @error('content') is-invalid @enderror"
                          rows="6" required placeholder="Contenu complet de l'article...">{{ old('content') }}</textarea>
                @error('content') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="d-flex gap-3 align-items-center">
                <div class="form-check">
                    <input type="checkbox" name="publish_now" value="1" class="form-check-input" id="publish_now"
                           {{ old('publish_now') ? 'checked' : '' }}>
                    <label class="form-check-label small fw-semibold" for="publish_now">Publier immédiatement</label>
                </div>
                <button type="submit" class="btn btn-primary rounded-pill ms-auto">
                    <i class="fas fa-save me-1"></i>Créer l'article
                </button>
            </div>
        </form>
    </div>

    {{-- Liste des articles --}}
    @forelse($posts as $post)
    <div class="card mb-2 py-2 {{ $post->published_at && $post->published_at->isPast() ? '' : 'opacity-75' }}">
        <div class="d-flex align-items-start gap-3">
            <div class="icon-box {{ $post->published_at && $post->published_at->isPast() ? 'bg-green-light' : 'bg-yellow-light' }}">
                <i class="fas fa-{{ $post->published_at && $post->published_at->isPast() ? 'check text-green' : 'eye-slash text-warning' }}"></i>
            </div>
            <div class="flex-grow-1 min-width-0">
                <p class="mb-0 fw-semibold small">{{ $post->title }}</p>
                @if($post->excerpt)
                <small class="text-muted d-block text-truncate">{{ $post->excerpt }}</small>
                @endif
                <small class="text-muted">
                    {{ $post->author->name ?? '—' }} ·
                    @if($post->published_at && $post->published_at->isPast())
                        Publié le {{ $post->published_at->format('d/m/Y H:i') }}
                    @else
                        Brouillon
                    @endif
                </small>
            </div>
            <div class="d-flex gap-1 flex-shrink-0">
                <a href="{{ route('posts.show', $post) }}" class="btn btn-sm btn-light rounded-pill" target="_blank" title="Voir">
                    <i class="fas fa-eye"></i>
                </a>
                <form method="POST" action="{{ route('admin.posts.publish', $post) }}">
                    @csrf
                    <button type="submit" class="btn btn-sm {{ $post->published_at && $post->published_at->isPast() ? 'btn-outline-warning' : 'btn-outline-success' }} rounded-pill"
                            title="{{ $post->published_at && $post->published_at->isPast() ? 'Dépublier' : 'Publier' }}">
                        <i class="fas fa-{{ $post->published_at && $post->published_at->isPast() ? 'eye-slash' : 'check' }}"></i>
                    </button>
                </form>
                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill"
                        x-data
                        @click.prevent="window.dispatchEvent(new CustomEvent('open-modal', { detail: { id: 'admin-confirm', action: '{{ route('admin.posts.destroy', $post) }}', message: 'Supprimer cet article ?', confirmText: 'Oui, supprimer', method: 'DELETE', type: 'danger' } }))"
                        title="Supprimer">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    </div>
    @empty
    <div class="text-center py-4 text-muted">Aucun article. Créez le premier ci-dessus.</div>
    @endforelse

    <div class="mt-3">{{ $posts->links() }}</div>

</div>
@endsection
