@extends('layouts.app')
@section('title', 'Blog TontineSN')

@section('content')
<div class="container py-5" style="max-width:860px;">
    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="{{ route('home') }}" class="btn btn-sm btn-light rounded-circle">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h4 class="fw-bold mb-0">Blog TontineSN</h4>
    </div>

    @forelse($posts as $post)
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="fw-bold mb-1">
                <a href="{{ route('posts.show', $post) }}" class="text-decoration-none text-dark">{{ $post->title }}</a>
            </h5>
            @if($post->excerpt)
            <p class="text-muted small mb-2">{{ $post->excerpt }}</p>
            @endif
            <div class="d-flex align-items-center gap-3 text-muted" style="font-size:12px;">
                @if($post->author)
                <span><i class="fas fa-user me-1"></i>{{ $post->author->name }}</span>
                @endif
                <span><i class="fas fa-calendar me-1"></i>{{ $post->published_at->format('d/m/Y') }}</span>
                <a href="{{ route('posts.show', $post) }}" class="ms-auto btn btn-sm btn-outline-primary rounded-pill">Lire</a>
            </div>
        </div>
    </div>
    @empty
    <div class="text-center py-5">
        <i class="fas fa-newspaper fa-3x text-muted mb-3"></i>
        <h5 class="text-muted">Aucun article pour l'instant</h5>
        <p class="text-muted small">Revenez bientôt pour nos conseils sur les tontines.</p>
        <a href="{{ route('home') }}" class="btn btn-primary rounded-pill">Retour à l'accueil</a>
    </div>
    @endforelse

    @if($posts->hasPages())
    <div class="mt-4">{{ $posts->links() }}</div>
    @endif
</div>
@endsection
