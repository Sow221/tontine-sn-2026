<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" x-data="{ sidebarOpen: false }" @keydown.escape.window="sidebarOpen = false">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#009639" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#0f172a" media="(prefers-color-scheme: dark)">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="TontineSN — Gérez vos tontines en ligne. Créez, suivez et payez vos cotisations en toute sécurité.">
    {{-- PWA / iOS --}}
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="TontineSN">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="application-name" content="TontineSN">
    <meta name="format-detection" content="telephone=no">
    <link rel="apple-touch-icon" href="{{ asset('images/icon-192.png') }}">
    <link rel="apple-touch-icon" sizes="192x192" href="{{ asset('images/icon-192.png') }}">
    <link rel="apple-touch-icon" sizes="512x512" href="{{ asset('images/icon-512.png') }}">

    <meta property="og:title" content="@yield('title', 'Accueil') — TontineSN">
    <meta property="og:description" content="Plateforme digitale de gestion de tontines au Sénégal.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('images/element-logo.png') }}">

    <title>@yield('title', 'Accueil') — TontineSN</title>

    <link rel="canonical" href="{{ url()->current() }}">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @stack('head-scripts')
    <link href="{{ asset('css/tontine.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body class="bg-off-white" x-data="{ dark: localStorage.getItem('tontine-theme') === 'dark' || (!localStorage.getItem('tontine-theme') && window.matchMedia('(prefers-color-scheme: dark)').matches) }" :class="{ 'dark-mode': dark }">

    <a href="#main-content" class="skip-link">Aller au contenu principal</a>

    <noscript>
        <div role="alert" style="background: var(--red); color: white; text-align: center; padding: 12px; font-weight: 600;">
            JavaScript est requis pour utiliser TontineSN. Veuillez l'activer dans votre navigateur.
        </div>
    </noscript>

@auth
<div class="app-wrapper">

    {{-- Overlay mobile sidebar --}}
    <div class="sidebar-overlay" :class="{ 'open': sidebarOpen }"
         @click="sidebarOpen = false"
         :aria-hidden="!sidebarOpen"
         role="presentation"></div>

    {{-- Sidebar --}}
    <aside class="app-sidebar" :class="{ 'open': sidebarOpen }" role="navigation" aria-label="Navigation principale"
           x-trap.noscroll="sidebarOpen">
        <a href="{{ route('dashboard') }}" class="sidebar-logo" aria-label="Retour au tableau de bord">
            <img src="{{ asset('images/element-logo.png') }}" alt="TontineSN" width="36" height="36">
            <span class="sidebar-logo-text">TontineSN</span>
        </a>

        <nav class="sidebar-nav">
            @if(auth()->user()->isAdmin())
                {{-- Navigation ADMIN pure : pas de liens membre --}}
                <span class="sidebar-section-label">Administration</span>
                <a href="{{ route('admin.dashboard') }}" class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="fas fa-tachometer-alt"></i> Tableau de bord
                </a>
                <a href="{{ route('admin.users') }}" class="sidebar-link {{ request()->routeIs('admin.users*') ? 'active' : '' }}">
                    <i class="fas fa-users"></i> Utilisateurs
                </a>
                <a href="{{ route('admin.tontines') }}" class="sidebar-link {{ request()->routeIs('admin.tontines*') ? 'active' : '' }}">
                    <i class="fas fa-layer-group"></i> Tontines
                </a>
                <a href="{{ route('admin.transactions') }}" class="sidebar-link {{ request()->routeIs('admin.transactions*') ? 'active' : '' }}">
                    <i class="fas fa-exchange-alt"></i> Transactions
                </a>
                <a href="{{ route('admin.notifications') }}" class="sidebar-link {{ request()->routeIs('admin.notifications') ? 'active' : '' }}">
                    <i class="fas fa-bell"></i> Notifications
                </a>
                <div class="sidebar-divider"></div>
                <span class="sidebar-section-label">Analyse</span>
                <a href="{{ route('admin.stats') }}" class="sidebar-link {{ request()->routeIs('admin.stats') ? 'active' : '' }}">
                    <i class="fas fa-chart-line"></i> Statistiques
                </a>
                <a href="{{ route('admin.logs') }}" class="sidebar-link {{ request()->routeIs('admin.logs') ? 'active' : '' }}">
                    <i class="fas fa-list-alt"></i> Journaux
                </a>
                <a href="{{ route('admin.api.docs') }}" class="sidebar-link {{ request()->routeIs('admin.api.docs') ? 'active' : '' }}">
                    <i class="fas fa-code"></i> API Docs
                </a>
                <div class="sidebar-divider"></div>
                <span class="sidebar-section-label">Contenu</span>
                <a href="{{ route('admin.posts') }}" class="sidebar-link {{ request()->routeIs('admin.posts*') ? 'active' : '' }}">
                    <i class="fas fa-newspaper"></i> Actualités
                </a>
            @else
                {{-- Navigation MEMBRE --}}
                <span class="sidebar-section-label">Menu</span>
                <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="fas fa-home"></i> Accueil
                </a>
                <a href="{{ route('tontines.index') }}" class="sidebar-link {{ request()->routeIs('tontines.*') ? 'active' : '' }}">
                    <i class="fas fa-users"></i> Mes tontines
                </a>
                <a href="{{ route('chat.index') }}" class="sidebar-link {{ request()->routeIs('chat.*') ? 'active' : '' }}">
                    <i class="fas fa-comments"></i> Messages
                </a>
                <a href="{{ route('notifications.index') }}" class="sidebar-link {{ request()->routeIs('notifications.*') ? 'active' : '' }}">
                    <i class="fas fa-bell"></i> Notifications
                    @if(($unreadNotificationsCount ?? 0) > 0)
                    <span class="badge bg-danger ms-auto">{{ $unreadNotificationsCount }}</span>
                    @endif
                </a>
                <a href="{{ route('profile.show') }}" class="sidebar-link {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                    <i class="fas fa-user"></i> Profil
                </a>
                <div class="sidebar-divider"></div>
                <a href="{{ route('historique.index') }}" class="sidebar-link {{ request()->routeIs('historique.*') ? 'active' : '' }}">
                    <i class="fas fa-history"></i> Historique
                </a>
                <a href="{{ route('faq.index') }}" class="sidebar-link {{ request()->routeIs('faq.index') ? 'active' : '' }}">
                    <i class="fas fa-question-circle"></i> FAQ
                </a>
            @endif
        </nav>

        <div class="sidebar-footer">
            <div class="sidebar-user mb-3">
                <div class="sidebar-user-avatar" aria-hidden="true">
                    {{ strtoupper(substr(auth()->user()->name ?? auth()->user()->email, 0, 1)) }}
                </div>
                <div class="overflow-hidden">
                    <div class="sidebar-user-name text-truncate">{{ auth()->user()->name ?? auth()->user()->email }}</div>
                    <div class="sidebar-user-role">{{ match(auth()->user()->role) { 'super_admin' => 'Super Admin', 'admin' => 'Admin', default => __('member.member') } }}</div>
                </div>
            </div>
            <form method="POST" action="{{ route('theme.toggle') }}" class="mb-2" x-data>
                @csrf
                <button type="button" class="btn btn-sm btn-outline-secondary w-100"
                        @click.prevent="
                            dark = !dark;
                            localStorage.setItem('tontine-theme', dark ? 'dark' : 'light');
                        ">
                    <i class="fas me-2" :class="dark ? 'fa-sun' : 'fa-moon'"></i>
                    <span x-text="dark ? 'Mode clair' : 'Mode sombre'"></span>
                </button>
            </form>
            <form method="POST" action="{{ route('auth.logout') }}">
                @csrf
                <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                    <i class="fas fa-sign-out-alt me-2"></i>Déconnexion
                </button>
            </form>
        </div>
    </aside>

    {{-- Contenu principal --}}
    <div class="app-main" id="main-content">

        {{-- Topbar --}}
        <header class="app-topbar">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-sm btn-light d-md-none" @click="sidebarOpen = true"
                        aria-label="Ouvrir le menu" :aria-expanded="sidebarOpen">
                    <i class="fas fa-bars"></i>
                </button>
                <span class="topbar-title">@yield('title', 'Tableau de bord')</span>
            </div>
            <div class="topbar-actions d-flex align-items-center gap-2">
                @if(auth()->user()->isAdmin())
                <span class="badge bg-danger d-none d-sm-inline">{{ auth()->user()->role === 'super_admin' ? 'Super Admin' : 'Admin' }}</span>
                @else
                <a href="{{ route('notifications.index') }}" class="btn btn-sm btn-light position-relative" aria-label="Notifications">
                    <i class="fas fa-bell"></i>
                    @if(($unreadNotificationsCount ?? 0) > 0)
                    <span class="badge bg-danger position-absolute" style="top:2px;right:2px;font-size:9px;min-width:15px;height:15px;line-height:9px;padding:3px;">{{ ($unreadNotificationsCount ?? 0) > 9 ? '9+' : $unreadNotificationsCount }}</span>
                    @endif
                </a>
                @endif
                <span class="text-muted small d-none d-md-inline">
                    {{ now()->isoFormat('dddd D MMMM YYYY') }}
                </span>
            </div>
        </header>

        {{-- Alerts --}}
        <div class="px-4 pt-3">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                </div>
            @endif
            @if(session('status'))
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <i class="fas fa-info-circle me-2"></i>{{ session('status') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                </div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                </div>
            @endif
        </div>

        {{-- Main content --}}
        <main class="main-content page-enter">
            @yield('content')
        </main>

        <x-toast />

        {{-- Footer --}}
        <footer class="text-center text-muted small py-3 border-top" style="background: white;">
            <div class="d-flex justify-content-center gap-3 mb-2">
                <a href="{{ route('cgu') }}" class="text-muted text-decoration-none">CGU</a>
                <a href="{{ route('mentions') }}" class="text-muted text-decoration-none">Mentions légales</a>
                <a href="{{ route('privacy') }}" class="text-muted text-decoration-none">Confidentialité</a>
                @if(!auth()->user()->isAdmin())
                <a href="{{ route('faq.index') }}" class="text-muted text-decoration-none">FAQ</a>
                @endif
            </div>
            &copy; {{ date('Y') }} TontineSN. Tous droits réservés.
        </footer>

    </div>

    {{-- Bottom Navigation Mobile --}}
    <nav class="app-bottom-nav" role="navigation" aria-label="Navigation rapide">
        @if(auth()->user()->isAdmin())
            <a href="{{ route('admin.dashboard') }}" class="bottom-nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <i class="fas fa-tachometer-alt"></i><span>Accueil</span>
            </a>
            <a href="{{ route('admin.users') }}" class="bottom-nav-link {{ request()->routeIs('admin.users*') ? 'active' : '' }}">
                <i class="fas fa-users"></i><span>Utilisateurs</span>
            </a>
            <a href="{{ route('admin.tontines') }}" class="bottom-nav-link {{ request()->routeIs('admin.tontines*') ? 'active' : '' }}">
                <i class="fas fa-layer-group"></i><span>Tontines</span>
            </a>
            <a href="{{ route('admin.posts') }}" class="bottom-nav-link {{ request()->routeIs('admin.posts*') ? 'active' : '' }}">
                <i class="fas fa-newspaper"></i><span>Posts</span>
            </a>
            <a href="{{ route('admin.stats') }}" class="bottom-nav-link {{ request()->routeIs('admin.stats') ? 'active' : '' }}">
                <i class="fas fa-chart-line"></i><span>Stats</span>
            </a>
        @else
            <a href="{{ route('dashboard') }}" class="bottom-nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="fas fa-home"></i><span>Accueil</span>
            </a>
            <a href="{{ route('tontines.index') }}" class="bottom-nav-link {{ request()->routeIs('tontines.*') ? 'active' : '' }}">
                <i class="fas fa-users"></i><span>Mes tontines</span>
            </a>
            <a href="{{ route('chat.index') }}" class="bottom-nav-link {{ request()->routeIs('chat.*') ? 'active' : '' }}">
                <i class="fas fa-comments"></i><span>Messages</span>
            </a>
            <a href="{{ route('historique.index') }}" class="bottom-nav-link {{ request()->routeIs('historique.*') ? 'active' : '' }}">
                <i class="fas fa-history"></i><span>Historique</span>
            </a>
            <a href="{{ route('profile.show') }}" class="bottom-nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                <i class="fas fa-user"></i><span>Profil</span>
            </a>
        @endif
    </nav>

</div>

@else
    <a href="#main-content-auth" class="skip-link">Aller au contenu</a>
    <div id="main-content-auth">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show m-3" role="alert">{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
            </div>
        @endif
        @if(session('status'))
            <div class="alert alert-info alert-dismissible fade show m-3" role="alert">{{ session('status') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
            </div>
        @endif
        @yield('content')
    </div>
@endauth

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/focus@3.x.x/dist/cdn.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
<script src="{{ asset('js/tontine.js') }}"></script>
<script nonce="{{ $cspNonce ?? '' }}">
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/serviceworker.js', { scope: '/' })
            .then((reg) => {
                // Détection de mise à jour disponible
                reg.addEventListener('updatefound', () => {
                    const newWorker = reg.installing;
                    newWorker.addEventListener('statechange', () => {
                        if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                            // Nouvelle version disponible : notifier l'utilisateur
                            const bar = document.createElement('div');
                            bar.style.cssText = 'position:fixed;bottom:0;left:0;right:0;background:#2D2F53;color:white;padding:12px 16px;display:flex;align-items:center;justify-content:space-between;z-index:9999;font-size:13px;';
                            bar.innerHTML = '<span>🔄 Nouvelle version disponible</span><button onclick="navigator.serviceWorker.controller.postMessage({type:\'SKIP_WAITING\'});window.location.reload();" style="background:#009639;color:white;border:none;border-radius:999px;padding:6px 16px;font-weight:700;cursor:pointer;">Mettre à jour</button>';
                            document.body.appendChild(bar);
                        }
                    });
                });
            })
            .catch(() => {});
    });
}
</script>
@stack('scripts')
</body>
</html>
