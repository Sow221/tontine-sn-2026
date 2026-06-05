@extends('layouts.app')
@section('title', $tontine->name . ' — Messages')

@section('content')
<div class="container py-4" style="max-width:720px;">

    <nav aria-label="breadcrumb" class="mb-3 small">
        <ol class="breadcrumb bg-transparent p-0 m-0">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-green">Accueil</a></li>
            <li class="breadcrumb-item"><a href="{{ route('chat.index') }}" class="text-green">Messagerie</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $tontine->name }}</li>
        </ol>
    </nav>

    <div class="d-flex align-items-center gap-2 mb-3">
        <a href="{{ route('chat.index') }}" class="btn btn-sm btn-light rounded-circle">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h5 class="fw-bold mb-0">{{ $tontine->name }}</h5>
        <span class="badge bg-light text-muted ms-auto">{{ $messages->total() }} message(s)</span>
    </div>

    {{-- Pagination EN HAUT pour charger les messages plus anciens --}}
    @if($messages->hasPages())
    <div class="d-flex justify-content-center mb-3">
        {{ $messages->links() }}
    </div>
    @endif

    {{-- Zone messages --}}
    <div class="chat-window mb-3" id="chat-messages"
         style="min-height:300px;max-height:60vh;overflow-y:auto;display:flex;flex-direction:column;gap:8px;padding:12px;background:var(--bg-card,#fff);border-radius:12px;border:1px solid var(--gray-border);">

        @forelse($messages as $msg)
        @php $isMe = $msg->user_id === auth()->id(); @endphp
        <div class="d-flex {{ $isMe ? 'justify-content-end' : 'justify-content-start' }}">
            @if(!$isMe)
            <div class="member-avatar avatar-sm flex-shrink-0 me-2" style="align-self:flex-end;">
                {{ strtoupper(substr($msg->user->name ?? '?', 0, 2)) }}
            </div>
            @endif
            <div class="chat-bubble {{ $isMe ? 'chat-bubble--me' : 'chat-bubble--other' }}"
                 style="max-width:75%;padding:10px 14px;border-radius:{{ $isMe ? '18px 18px 4px 18px' : '18px 18px 18px 4px' }};background:{{ $isMe ? '#009639' : 'var(--bg-light,#f8f9fa)' }};color:{{ $isMe ? '#fff' : 'inherit' }};">
                @if(!$isMe)
                <small class="fw-semibold d-block mb-1" style="font-size:11px;color:#009639;">
                    {{ $msg->user->name ?? '—' }}
                </small>
                @endif
                <p class="mb-0 small" style="word-break:break-word;">{{ $msg->message }}</p>
                <small class="d-block mt-1" style="font-size:10px;opacity:.65;text-align:{{ $isMe ? 'right' : 'left' }};">
                    {{ $msg->created_at->isoFormat('HH:mm · D MMM') }}
                </small>
            </div>
        </div>
        @empty
        <div class="text-center py-5 text-muted">
            <div style="font-size:2.5rem;">💬</div>
            <p class="mt-2 mb-0">Aucun message. Soyez le premier à écrire !</p>
        </div>
        @endforelse
    </div>

    {{-- Formulaire d'envoi --}}
    <div class="card">
        <form method="POST" action="{{ route('chat.send', $tontine) }}" id="chat-form">
            @csrf
            @error('message')
            <div class="alert alert-danger py-2 mb-2 small">{{ $message }}</div>
            @enderror
            <div class="d-flex gap-2 align-items-end">
                <textarea name="message" id="chat-input"
                          class="form-control"
                          rows="2"
                          placeholder="Écrivez votre message… (Entrée pour envoyer, Maj+Entrée pour saut de ligne)"
                          required maxlength="2000"
                          style="resize:none;border-radius:12px;"></textarea>
                <button type="submit" class="btn btn-primary rounded-pill px-3 align-self-end" style="height:42px;">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
            <div class="d-flex justify-content-end mt-1">
                <small class="text-muted" id="char-count">0/2000</small>
            </div>
        </form>
    </div>

</div>
@endsection

@push('scripts')
<script>
// Scroll vers le bas à l'arrivée (messages récents)
const chatBox = document.getElementById('chat-messages');
if (chatBox) chatBox.scrollTop = chatBox.scrollHeight;

// Compteur de caractères
const input = document.getElementById('chat-input');
const counter = document.getElementById('char-count');
if (input && counter) {
    input.addEventListener('input', function () {
        counter.textContent = this.value.length + '/2000';
        counter.style.color = this.value.length > 1800 ? 'var(--red)' : '';
    });
    // Entrée pour envoyer, Maj+Entrée pour saut de ligne
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            if (this.value.trim()) {
                document.getElementById('chat-form').submit();
            }
        }
    });
}
</script>
@endpush
