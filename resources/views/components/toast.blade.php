<div x-data="{
    show: false,
    message: '',
    type: 'success'
}"
     x-init="
     @if(session('success'))
         message = '{{ str_replace("'", "\\'", session('success')) }}'; type = 'success'; show = true; setTimeout(() => show = false, 6000);
     @elseif(session('error'))
         message = '{{ str_replace("'", "\\'", session('error')) }}'; type = 'error'; show = true; setTimeout(() => show = false, 8000);
     @elseif($errors->any())
         message = '{{ str_replace("'", "\\'", collect($errors->all())->first()) }}'; type = 'error'; show = true; setTimeout(() => show = false, 8000);
     @endif
     "
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
