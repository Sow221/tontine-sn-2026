@extends('layouts.app')
@section('title', $post->title . ' — Blog TontineSN')
@section('og_title', $post->title)
@section('og_image', route('posts.og', $post))

@section('content')
<div class="container py-5" style="max-width:720px;">
    <nav aria-label="breadcrumb" class="mb-3 small">
        <ol class="breadcrumb bg-transparent p-0 m-0">
            <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-green">Accueil</a></li>
            <li class="breadcrumb-item"><a href="{{ route('posts.index') }}" class="text-green">Blog</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ Str::limit($post->title, 40) }}</li>
        </ol>
    </nav>

    <h1 class="fw-bold mb-2">{{ $post->title }}</h1>

    <div class="d-flex align-items-center gap-3 text-muted mb-4" style="font-size:13px;">
        @if($post->author)
        <span><i class="fas fa-user me-1"></i>{{ $post->author->name }}</span>
        @endif
        <span><i class="fas fa-calendar me-1"></i>{{ $post->published_at->isoFormat('D MMMM YYYY') }}</span>
    </div>

    @if($post->excerpt)
    <p class="lead text-muted mb-4">{{ $post->excerpt }}</p>
    @endif

    <div class="post-content">
        {!! nl2br(e($post->content)) !!}
    </div>

    <div class="mt-5 pt-4 border-top">
        <a href="{{ route('posts.index') }}" class="btn-back">
            <i class="fas fa-arrow-left" aria-hidden="true"></i>
            Tous les articles
        </a>
    </div>
</div>
@endsection
