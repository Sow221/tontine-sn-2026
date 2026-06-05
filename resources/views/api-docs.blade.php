@extends('layouts.app')
@section('title', 'Documentation API')
@section('content')
<div class="container py-4">
    <h4 class="fw-bold mb-3">Documentation API</h4>
    <p class="text-muted small mb-4">Points d'entrée REST de l'API TontineSN. Authentification par token Sanctum (Bearer).</p>
    <div id="swagger-ui"></div>
</div>
@endsection
@push('head')
<link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css">
@endpush
@push('scripts')
<script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    SwaggerUIBundle({
        url: '{{ route('api.spec') }}',
        dom_id: '#swagger-ui',
        deepLinking: true,
        presets: [SwaggerUIBundle.presets.apis],
        layout: 'BaseLayout',
    });
});
</script>
@endpush
