@extends('layouts.app')

@section('title', 'Actualités')

@section('content')
<div class="container py-4">
    <h4 class="fw-bold mb-4">Actualités TontineSN</h4>

    @if($posts->isEmpty())
    <div class="card text-center py-5">
        <i class="fas fa-newspaper text-muted fs-1 mb-3"></i>
        <p class="text-muted mb-0">Aucune actualité pour le moment.</p>
    </div>
    @endif

    <div class="row g-3">
        @foreach($posts as $post)
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <small class="text-muted">{{ $post->published_at->isoFormat('D MMMM YYYY') }}</small>
                    <h5 class="fw-semibold mt-1 mb-2">{{ $post->title }}</h5>
                    @if($post->excerpt)
                    <p class="text-muted small">{{ $post->excerpt }}</p>
                    @endif
                    <a href="{{ route('posts.show', $post) }}" class="btn btn-sm btn-outline-primary">
                        Lire la suite <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="mt-4">
        {{ $posts->links() }}
    </div>
</div>
@endsection
