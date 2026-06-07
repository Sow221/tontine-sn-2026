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
        <span class="badge bg-light text-muted ms-auto" id="chat-online"></span>
        <span class="badge bg-light text-muted">{{ $messages->total() }} message(s)</span>
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
        <form id="chat-form">
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
            <div class="d-flex justify-content-between mt-1">
                <small class="text-danger d-none" id="chat-error"></small>
                <small class="text-muted ms-auto" id="char-count">0/2000</small>
            </div>
        </form>
    </div>

</div>
@endsection

@push('scripts')
<script>
const chatBox    = document.getElementById('chat-messages');
const chatInput  = document.getElementById('chat-input');
const chatForm   = document.getElementById('chat-form');
const counter    = document.getElementById('char-count');
const errorEl    = document.getElementById('chat-error');
const onlineEl   = document.getElementById('chat-online');
const sendUrl    = '{{ route('chat.send', $tontine) }}';
const streamUrl  = '{{ route('chat.stream', $tontine) }}';
const pollUrl    = '{{ route('chat.poll', $tontine) }}';
const csrfToken  = document.querySelector('meta[name="csrf-token"]')?.content
                 || document.querySelector('input[name="_token"]')?.value || '';
const myId       = {{ auth()->id() }};

// Scroll initial vers le bas
if (chatBox) chatBox.scrollTop = chatBox.scrollHeight;

// ── Compteur caractères + Entrée pour envoyer ──────────────────────────────
if (chatInput && counter) {
    chatInput.addEventListener('input', function () {
        counter.textContent = this.value.length + '/2000';
        counter.style.color = this.value.length > 1800 ? 'var(--red)' : '';
    });
    chatInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            if (this.value.trim()) chatForm.dispatchEvent(new Event('submit', { cancelable: true }));
        }
    });
}

// ── Envoi de message ───────────────────────────────────────────────────────
if (chatForm) {
    chatForm.addEventListener('submit', async function (e) {
        e.preventDefault();
        const msg = chatInput.value.trim();
        if (!msg) return;

        errorEl.classList.add('d-none');
        const btn = chatForm.querySelector('button[type=submit]');
        btn.disabled = true;

        try {
            const res = await fetch(sendUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ message: msg }),
            });

            if (!res.ok) {
                const data = await res.json().catch(() => ({}));
                errorEl.textContent = data?.errors?.message?.[0] || 'Erreur lors de l\'envoi.';
                errorEl.classList.remove('d-none');
                return;
            }

            const data = await res.json();
            appendBubble({ user_id: myId, message: msg, time: data.time ?? 'maintenant', name: null, id: data.id });
            chatInput.value = '';
            counter.textContent = '0/2000';
            // Mettre à jour le curseur local pour éviter de re-recevoir son propre message
            lastMessageId = data.id ?? lastMessageId;
        } catch (_) {
            errorEl.textContent = 'Erreur réseau. Vérifiez votre connexion.';
            errorEl.classList.remove('d-none');
        } finally {
            btn.disabled = false;
            chatInput.focus();
        }
    });
}

// ── Réception temps réel : SSE avec fallback polling ─────────────────────
let lastMessageId = {{ $messages->last()?->id ?? 0 }};
let pollTimer     = null;
let pollFailures  = 0;
let pollActive    = true;

function handleIncomingMessage(m) {
    // Ignorer les doublons déjà affichés à l'envoi
    if (m.user_id === myId && document.querySelector(`[data-msg-id="${m.id}"]`)) return;
    const wasAtBottom = chatBox.scrollHeight - chatBox.scrollTop - chatBox.clientHeight < 60;
    appendBubble(m);
    lastMessageId = m.id;
    if (wasAtBottom) chatBox.scrollTop = chatBox.scrollHeight;
    else showNewMessageBadge();
}

if (typeof EventSource !== 'undefined') {
    let es = null;

    function connectSSE() {
        if (es) es.close();
        es = new EventSource(`${streamUrl}?after=${lastMessageId}`, { withCredentials: true });
        es.onmessage = e => handleIncomingMessage(JSON.parse(e.data));
        es.addEventListener('heartbeat', e => updateOnlineIndicator(JSON.parse(e.data).online ?? null));
        es.onerror = () => { es.close(); startPolling(); };
    }

    connectSSE();

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden') {
            if (es) es.close();
        } else {
            connectSSE();
        }
    });
    window.addEventListener('beforeunload', () => { if (es) es.close(); });

} else {
    startPolling();
}

function startPolling() {
    async function poll() {
        if (!pollActive) return;
        try {
            const res = await fetch(`${pollUrl}?after=${lastMessageId}`, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            });
            if (!res.ok) { pollFailures++; }
            else {
                pollFailures = 0;
                const data = await res.json();
                (data.messages ?? []).forEach(handleIncomingMessage);
                updateOnlineIndicator(data.online ?? null);
            }
        } catch (_) { pollFailures++; }
        const delay = Math.min(5000 * Math.pow(1.5, Math.min(pollFailures, 4)), 30000);
        pollTimer = setTimeout(poll, delay);
    }
    poll();
    document.addEventListener('visibilitychange', () => {
        pollActive = document.visibilityState === 'visible';
        if (pollActive) { clearTimeout(pollTimer); poll(); }
    });
    window.addEventListener('beforeunload', () => { pollActive = false; clearTimeout(pollTimer); });
}

// ── Rendu d'une bulle de message ──────────────────────────────────────────
function appendBubble(m) {
    const isMe = m.user_id === myId;
    const wrap = document.createElement('div');
    wrap.className = `d-flex ${isMe ? 'justify-content-end' : 'justify-content-start'}`;
    wrap.dataset.msgId = m.id;

    const avatarHtml = !isMe
        ? `<div class="member-avatar avatar-sm flex-shrink-0 me-2" style="align-self:flex-end;">${escHtml((m.name ?? '?').substring(0, 2).toUpperCase())}</div>`
        : '';

    const nameHtml = !isMe
        ? `<small class="fw-semibold d-block mb-1" style="font-size:11px;color:#009639;">${escHtml(m.name ?? '—')}</small>`
        : '';

    wrap.innerHTML = `${avatarHtml}
        <div class="chat-bubble"
             style="max-width:75%;padding:10px 14px;border-radius:${isMe ? '18px 18px 4px 18px' : '18px 18px 18px 4px'};background:${isMe ? '#009639' : 'var(--bg-light,#f8f9fa)'};color:${isMe ? '#fff' : 'inherit'};">
            ${nameHtml}
            <p class="mb-0 small" style="word-break:break-word;">${escHtml(m.message)}</p>
            <small class="d-block mt-1" style="font-size:10px;opacity:.65;text-align:${isMe ? 'right' : 'left'};">${escHtml(m.time ?? '')}</small>
        </div>`;

    chatBox.appendChild(wrap);
}

// ── Badge "nouveaux messages" ─────────────────────────────────────────────
function showNewMessageBadge() {
    let badge = document.getElementById('new-msg-badge');
    if (!badge) {
        badge = document.createElement('button');
        badge.id = 'new-msg-badge';
        badge.className = 'btn btn-sm btn-primary rounded-pill';
        badge.style.cssText = 'position:sticky;bottom:8px;left:50%;transform:translateX(-50%);display:block;margin:4px auto;';
        badge.onclick = () => { chatBox.scrollTop = chatBox.scrollHeight; badge.remove(); };
        chatBox.after(badge);
    }
    badge.textContent = '↓ Nouveaux messages';
}

// ── Indicateur en ligne (si fourni par le serveur) ────────────────────────
function updateOnlineIndicator(count) {
    if (onlineEl && count !== null) onlineEl.textContent = `${count} en ligne`;
}

function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
@endpush
