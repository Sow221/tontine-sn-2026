@extends('layouts.app')
@section('title', $title)

@push('styles')
<meta property="og:title"       content="{{ $title }}">
<meta property="og:description" content="{{ $excerpt }}">
<meta property="og:image"       content="{{ $ogImage }}">
<meta property="og:type"        content="article">
<meta name="twitter:card"       content="summary_large_image">
<meta name="twitter:title"      content="{{ $title }}">
<meta name="twitter:description" content="{{ $excerpt }}">
<meta name="twitter:image"      content="{{ $ogImage }}">
@endpush

@section('content')
<div class="container py-4" style="max-width:760px;">

    <nav aria-label="breadcrumb" class="mb-3 small">
        <ol class="breadcrumb bg-transparent p-0 m-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-green">Accueil</a></li>
            <li class="breadcrumb-item"><a href="{{ route('posts.index') }}" class="text-green">Actualités</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ Str::limit($title, 40) }}</li>
        </ol>
    </nav>

    <div class="d-flex align-items-center gap-2 mb-4">
        <a href="{{ route('posts.index') }}" class="btn btn-sm btn-light rounded-circle">
            <i class="fas fa-arrow-left"></i>
        </a>
    </div>

    <article>
        <header class="mb-4">
            <small class="text-muted">
                <i class="fas fa-calendar-alt me-1"></i>
                {{ $post->published_at->isoFormat('D MMMM YYYY') }}
                @if($post->author)
                · <i class="fas fa-user me-1"></i>{{ $post->author->name }}
                @endif
            </small>
            <h1 class="fw-bold mt-2 mb-3" style="font-size:1.7rem;line-height:1.3;">{{ $title }}</h1>
            @if($excerpt)
            <p class="text-muted fs-6 border-start border-3 border-success ps-3">{{ $excerpt }}</p>
            @endif
        </header>

        <div class="post-content" style="line-height:1.8;font-size:1rem;">
            {!! nl2br(e($post->content)) !!}
        </div>
    </article>

    <div class="border-top mt-5 pt-4">
        <a href="{{ route('posts.index') }}" class="btn btn-outline-secondary rounded-pill">
            <i class="fas fa-arrow-left me-2"></i>Retour aux actualités
        </a>
    </div>

</div>
@endsection
