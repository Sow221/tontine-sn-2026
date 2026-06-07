<div x-data="{ open: false, action: '', message: '', confirmText: 'Confirmer', type: 'danger' }"
     x-on:open-modal.window="if ($event.detail.id === '{{ $id }}') { open = true; action = $event.detail.action; message = $event.detail.message; confirmText = $event.detail.confirmText || 'Confirmer'; type = $event.detail.type || 'danger'; }"
     x-show="open"
     x-cloak
     x-trap.noscroll="open"
     class="modal-overlay"
     @keydown.escape.window="open = false"
     style="display: none;"
     role="dialog"
     aria-modal="true"
     :aria-labelledby="'modal-title-{{ $id }}'">
    <div class="modal-content" @click.stop>
        <div class="text-center mb-3">
            <div class="fs-1 mb-2">
                <i class="fas fa-{{ $icon ?? 'exclamation-triangle' }}" :class="type === 'danger' ? 'text-red' : 'text-green'" aria-hidden="true"></i>
            </div>
            <h5 class="fw-bold mb-2" x-text="message" id="modal-title-{{ $id }}"></h5>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-light flex-grow-1" @click="open = false">Annuler</button>
            <form :action="action" method="POST" class="flex-grow-1" @submit="open = false">
                @csrf
                @method($method ?? 'DELETE')
                <button type="submit" class="btn w-100" :class="type === 'danger' ? 'btn-danger' : 'btn-primary'" x-text="confirmText"></button>
            </form>
        </div>
    </div>
</div>
