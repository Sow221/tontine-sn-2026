<div x-data="{ show: false, message: '', type: 'success' }"
     x-on:notify.window="show = true; message = $event.detail.message; type = $event.detail.type || 'success'; setTimeout(() => show = false, 6000);"
     x-show="show"
     x-transition
     class="toast-container"
     style="display: none;"
     role="alert"
     aria-live="polite">
    <div class="toast" :class="'toast-' + type">
        <i class="fas" :class="type === 'success' ? 'fa-check-circle text-green' : type === 'error' ? 'fa-exclamation-circle text-red' : 'fa-info-circle text-yellow'"></i>
        <span x-text="message" class="flex-grow-1"></span>
        <button type="button" class="btn-close btn-sm" @click="show = false" aria-label="Fermer"></button>
    </div>
</div>
