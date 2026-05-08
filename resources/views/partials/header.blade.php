<header class="app-header">
    <div class="d-flex align-items-center justify-content-between px-3 py-2">
        <a href="{{ route('dashboard') }}" class="header-logo">
            <span class="logo-icon">🌿</span>
            <span class="logo-text">TontineSN</span>
        </a>

        @auth
        <div class="d-flex align-items-center gap-3">
            <span class="text-muted small">{{ auth()->user()->name ?? auth()->user()->email }}</span>
            <form method="POST" action="{{ route('auth.logout') }}">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill">
                    <i class="fas fa-sign-out-alt"></i>
                </button>
            </form>
        </div>
        @endauth
    </div>
</header>
