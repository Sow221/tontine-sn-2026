<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#009639" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#0f172a" media="(prefers-color-scheme: dark)">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="@yield('meta_description', 'TontineSN — Gérez vos tontines en ligne. Créez, suivez et payez vos cotisations en toute sécurité.')">
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

    <meta property="og:title" content="@yield('og_title', 'Tontine en Ligne - Épargne &amp; Cotisations Sénégal | TontineSN')">
    <meta property="og:description" content="@yield('meta_description', 'Plateforme digitale de gestion de tontines au Sénégal. Créez, suivez et payez vos cotisations en toute sécurité.')">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="@yield('og_image', asset('images/icon-512.png'))">
    <meta name="twitter:card" content="summary_large_image">

    <title>@yield('title', 'Accueil') - Gestion de Tontine en Ligne | TontineSN</title>

    <link rel="canonical" href="{{ url()->current() }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/icon-192.svg') }}">
    <link rel="icon" type="image/png" sizes="96x96" href="{{ asset('images/icon-192.png') }}">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <link href="{{ asset('css/vendor/google-fonts.css') }}" rel="stylesheet">
    <link href="{{ asset('css/vendor/bootstrap.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/vendor/fontawesome.min.css') }}">
    @stack('head-scripts')
    <link href="{{ asset('css/tontine.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body class="bg-off-white" x-data="{ sidebarOpen: false, dark: localStorage.getItem('tontine-theme') === 'dark' || (!localStorage.getItem('tontine-theme') && window.matchMedia('(prefers-color-scheme: dark)').matches), sidebarCollapsed: localStorage.getItem('sidebar-collapsed') === 'true' }" :class="{ 'dark-mode': dark }" @keydown.escape.window="sidebarOpen = false">

    {{-- Anti-FOUC + blocage transition au chargement --}}
    <script nonce="{{ $cspNonce }}">
        (function () {
            try {
                var t = localStorage.getItem('tontine-theme');
                if (t === 'dark' || (!t && matchMedia('(prefers-color-scheme:dark)').matches)) {
                    document.body.classList.add('dark-mode');
                }
                // Bloque les transitions pendant l'init (évite le flash de transition au chargement)
                document.body.classList.add('no-dm-transition');
                window.addEventListener('DOMContentLoaded', function () {
                    setTimeout(function () {
                        document.body.classList.remove('no-dm-transition');
                    }, 50);
                });
            } catch (e) {}
        })();
    </script>

    <a href="#main-content" class="skip-link">Aller au contenu principal</a>

    <noscript>
        <div role="alert" style="background: var(--red); color: white; text-align: center; padding: 12px; font-weight: 600;">
            JavaScript est requis pour utiliser TontineSN. Veuillez l'activer dans votre navigateur.
        </div>
    </noscript>

@auth
<div class="app-wrapper" :class="{ 'sidebar-collapsed': sidebarCollapsed }">

    {{-- Overlay mobile sidebar --}}
    <div class="sidebar-overlay" :class="{ 'open': sidebarOpen }"
         @click="sidebarOpen = false"
         :aria-hidden="!sidebarOpen"
         role="presentation"></div>

    {{-- Sidebar --}}
    <aside class="app-sidebar" :class="{ 'open': sidebarOpen, 'collapsed': sidebarCollapsed }" role="navigation" aria-label="Navigation principale"
           x-trap.noscroll="sidebarOpen">
        <div class="sidebar-header">
            <a href="{{ route('dashboard') }}" class="sidebar-logo" aria-label="Retour au tableau de bord">
                <picture>
                    <source srcset="{{ asset('images/element-logo.webp') }}" type="image/webp">
                    <img src="{{ asset('images/element-logo.png') }}" alt="TontineSN" width="36" height="36">
                </picture>
                <span class="sidebar-logo-text">TontineSN</span>
            </a>
            <button class="sidebar-collapse-btn d-none d-md-flex"
                    @click="sidebarCollapsed = !sidebarCollapsed; localStorage.setItem('sidebar-collapsed', sidebarCollapsed)"
                    :title="sidebarCollapsed ? 'Développer la barre latérale' : 'Réduire la barre latérale'"
                    aria-label="Basculer la barre latérale">
                <i class="fas fa-chevron-left" :class="{ 'fa-rotate-180': sidebarCollapsed }"></i>
            </button>
        </div>

        <nav class="sidebar-nav">
            @if(auth()->user()->isAdmin())
                {{-- Navigation ADMIN pure : pas de liens membre --}}
                <span class="sidebar-section-label">Administration</span>
                <a href="{{ route('admin.dashboard') }}" class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="fas fa-tachometer-alt"></i><span class="sidebar-link-text">Tableau de bord</span>
                </a>
                <a href="{{ route('admin.users') }}" class="sidebar-link {{ request()->routeIs('admin.users*') ? 'active' : '' }}">
                    <i class="fas fa-users"></i><span class="sidebar-link-text">Utilisateurs</span>
                </a>
                <a href="{{ route('admin.tontines') }}" class="sidebar-link {{ request()->routeIs('admin.tontines*') ? 'active' : '' }}">
                    <i class="fas fa-layer-group"></i><span class="sidebar-link-text">Tontines</span>
                </a>
                <a href="{{ route('admin.transactions') }}" class="sidebar-link {{ request()->routeIs('admin.transactions*') ? 'active' : '' }}">
                    <i class="fas fa-exchange-alt"></i><span class="sidebar-link-text">Transactions</span>
                </a>
                <a href="{{ route('admin.notifications') }}" class="sidebar-link {{ request()->routeIs('admin.notifications') ? 'active' : '' }}">
                    <i class="fas fa-bell"></i><span class="sidebar-link-text">Notifications</span>
                </a>
                <div class="sidebar-divider"></div>
                <span class="sidebar-section-label">Analyse</span>
                <a href="{{ route('admin.stats') }}" class="sidebar-link {{ request()->routeIs('admin.stats') ? 'active' : '' }}">
                    <i class="fas fa-chart-line"></i><span class="sidebar-link-text">Statistiques</span>
                </a>
                <a href="{{ route('admin.logs') }}" class="sidebar-link {{ request()->routeIs('admin.logs') ? 'active' : '' }}">
                    <i class="fas fa-list-alt"></i><span class="sidebar-link-text">Journaux</span>
                </a>
            @else
                {{-- Navigation MEMBRE --}}
                <span class="sidebar-section-label">Menu</span>
                <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="fas fa-home"></i><span class="sidebar-link-text">Accueil</span>
                </a>
                <a href="{{ route('tontines.index') }}" class="sidebar-link {{ request()->routeIs('tontines.*') && !request()->routeIs('tontines.explore') ? 'active' : '' }}">
                    <i class="fas fa-users"></i><span class="sidebar-link-text">Mes tontines</span>
                </a>
                <a href="{{ route('tontines.explore') }}" class="sidebar-link {{ request()->routeIs('tontines.explore') ? 'active' : '' }}">
                    <i class="fas fa-compass"></i><span class="sidebar-link-text">Explorer</span>
                </a>
                <a href="{{ route('chat.index') }}" class="sidebar-link {{ request()->routeIs('chat.*') ? 'active' : '' }}">
                    <i class="fas fa-comments"></i><span class="sidebar-link-text">Messages</span>
                </a>
                <a href="{{ route('notifications.index') }}" class="sidebar-link {{ request()->routeIs('notifications.*') ? 'active' : '' }}">
                    <i class="fas fa-bell"></i><span class="sidebar-link-text">Notifications</span>
                    @if(($unreadNotificationsCount ?? 0) > 0)
                    <span class="badge bg-danger ms-auto">{{ $unreadNotificationsCount }}</span>
                    @endif
                </a>
                <div class="sidebar-divider"></div>
                <a href="{{ route('historique.index') }}" class="sidebar-link {{ request()->routeIs('historique.*') ? 'active' : '' }}">
                    <i class="fas fa-history"></i><span class="sidebar-link-text">Historique</span>
                </a>
                <a href="{{ route('faq.index') }}" class="sidebar-link {{ request()->routeIs('faq.index') ? 'active' : '' }}">
                    <i class="fas fa-question-circle"></i><span class="sidebar-link-text">FAQ</span>
                </a>
            @endif
        </nav>

        <div class="sidebar-footer">
            <a href="{{ route('profile.show') }}" class="sidebar-user sidebar-user-link">
                <div class="sidebar-user-avatar" aria-hidden="true">
                    {{ strtoupper(substr(auth()->user()->name ?? auth()->user()->email, 0, 1)) }}
                </div>
                <div class="sidebar-user-info">
                    <div class="sidebar-user-name text-truncate">{{ auth()->user()->name ?? auth()->user()->email }}</div>
                    <div class="sidebar-user-role">{{ match(auth()->user()->role) { 'super_admin' => 'Super Admin', 'admin' => 'Admin', default => __('member.member') } }} · SN-{{ str_pad(auth()->id(), 4, '0', STR_PAD_LEFT) }}</div>
                </div>
                <i class="fas fa-chevron-right sidebar-user-chevron" aria-hidden="true"></i>
            </a>
            <form method="POST" action="{{ route('auth.logout') }}" class="sidebar-footer-actions">
                @csrf
                <button type="submit" class="sidebar-footer-action-btn sidebar-footer-action-btn--danger">
                    <i class="fas fa-sign-out-alt"></i>
                    <span class="sidebar-action-label">Déconnexion</span>
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
                {{-- Theme toggle --}}
                <button class="btn btn-sm btn-light theme-toggle-topbar"
                        @click.prevent="dark = !dark; localStorage.setItem('tontine-theme', dark ? 'dark' : 'light')"
                        :title="dark ? 'Mode clair' : 'Mode sombre'"
                        aria-label="Basculer le thème">
                    <i class="fas" :class="dark ? 'fa-sun' : 'fa-moon'"></i>
                </button>

                <div class="position-relative" x-data="{ notifOpen: false }" @click.outside="notifOpen = false">
                    <button class="btn btn-sm btn-light position-relative d-none d-sm-inline-flex"
                            @click="notifOpen = !notifOpen"
                            aria-label="Notifications" :aria-expanded="notifOpen">
                        <i class="fas fa-bell"></i>
                        @if(($unreadNotificationsCount ?? 0) > 0)
                        <span class="badge bg-danger position-absolute" style="top:2px;right:2px;font-size:9px;min-width:15px;height:15px;line-height:9px;padding:3px;">{{ ($unreadNotificationsCount ?? 0) > 9 ? '9+' : $unreadNotificationsCount }}</span>
                        @endif
                    </button>
                    <template x-if="notifOpen">
                    <div x-transition:enter="dropdown-enter" x-transition:enter-start="dropdown-enter-start" x-transition:enter-end="dropdown-enter-end"
                         x-transition:leave="dropdown-leave" x-transition:leave-start="dropdown-leave-start" x-transition:leave-end="dropdown-leave-end"
                         class="topbar-dropdown-menu" style="min-width:280px;">
                        <div class="topbar-dropdown-header d-flex align-items-center justify-content-between">
                            <span class="topbar-dropdown-name">Notifications</span>
                            @if(($unreadNotificationsCount ?? 0) > 0)
                            <span class="badge bg-danger" style="font-size:10px;">{{ $unreadNotificationsCount }} nouvelle(s)</span>
                            @endif
                        </div>
                        <div class="topbar-dropdown-divider"></div>
                        @forelse($latestNotifications ?? [] as $notif)
                        <a href="{{ route('notifications.index') }}" class="topbar-dropdown-item {{ $notif->read_at ? '' : 'fw-semibold' }}">
                            <i class="fas fa-circle" style="font-size:6px;color:{{ $notif->read_at ? 'var(--gray-border)' : 'var(--green)' }};"></i>
                            <span class="text-truncate" style="max-width:220px;">{{ Str::limit($notif->message ?? 'Notification', 50) }}</span>
                        </a>
                        @empty
                        <p class="text-muted small text-center py-3 mb-0">Aucune notification</p>
                        @endforelse
                        <div class="topbar-dropdown-divider"></div>
                        <a href="{{ route('notifications.index') }}" class="topbar-dropdown-item" style="color:var(--green);justify-content:center;font-weight:600;">
                            Voir tout
                        </a>
                    </div>
                    </template>
                </div>

                {{-- User dropdown --}}
                <div class="position-relative" x-data="{ dropdownOpen: false }" @click.outside="dropdownOpen = false">
                    <button class="topbar-user-btn" @click="dropdownOpen = !dropdownOpen"
                            aria-haspopup="true" :aria-expanded="dropdownOpen"
                            aria-label="Menu utilisateur">
                        <span class="topbar-user-avatar">{{ strtoupper(substr(auth()->user()->name ?? auth()->user()->email, 0, 1)) }}</span>
                        <i class="fas fa-chevron-down topbar-chevron" :class="{ 'open': dropdownOpen }"></i>
                    </button>
                    <template x-if="dropdownOpen">
                    <div x-transition:enter="dropdown-enter" x-transition:enter-start="dropdown-enter-start" x-transition:enter-end="dropdown-enter-end"
                         x-transition:leave="dropdown-leave" x-transition:leave-start="dropdown-leave-start" x-transition:leave-end="dropdown-leave-end"
                         class="topbar-dropdown-menu">
                        <div class="topbar-dropdown-header">
                            <span class="topbar-dropdown-name">{{ auth()->user()->name ?? auth()->user()->email }}</span>
                            <span class="topbar-dropdown-role">{{ match(auth()->user()->role) { 'super_admin' => 'Super Admin', 'admin' => 'Admin', default => 'Membre' } }}</span>
                        </div>
                        <div class="topbar-dropdown-divider"></div>
                        <a href="{{ route('profile.show') }}" class="topbar-dropdown-item">
                            <i class="fas fa-user"></i> Mon Profil &amp; Paramètres
                        </a>
                        <div class="topbar-dropdown-divider"></div>
                        <form method="POST" action="{{ route('auth.logout') }}">
                            @csrf
                            <button type="submit" class="topbar-dropdown-item topbar-dropdown-item--danger">
                                <i class="fas fa-sign-out-alt"></i> Déconnexion
                            </button>
                        </form>
                    </div>
                    </template>
                </div>

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
            @if(session('status'))
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <i class="fas fa-info-circle me-2"></i>{{ session('status') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                </div>
            @endif
            @if($errors->any())
            @php
                $inlineHandled  = ['activate', 'new_owner_id', 'draw', 'error', 'bid_rate', 'veto', 'payment', 'reverse'];
                $globalMessages = collect($errors->getBag('default')->toArray())
                    ->reject(fn($msgs, $key) => in_array($key, $inlineHandled))
                    ->flatten();
            @endphp
            @if($globalMessages->isNotEmpty())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        @foreach($globalMessages as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                </div>
            @endif
            @endif
        </div>

        {{-- Main content --}}
        <main class="main-content page-enter">
            @yield('content')
        </main>

        <x-toast />

        {{-- Footer --}}
        <footer class="text-center text-muted small py-3 border-top app-footer">
            <div class="d-flex justify-content-center gap-3 mb-2">
                <a href="{{ route('cgu') }}" class="text-muted text-decoration-none">CGU</a>
                <a href="{{ route('mentions') }}" class="text-muted text-decoration-none">Mentions légales</a>
                <a href="{{ route('privacy') }}" class="text-muted text-decoration-none">Confidentialité</a>
                @if(!auth()->user()->isAdmin())
                <a href="{{ route('faq.index') }}" class="text-muted text-decoration-none">FAQ</a>
                <a href="{{ route('contact') }}" class="text-muted text-decoration-none">Support</a>
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
            <a href="{{ route('admin.transactions') }}" class="bottom-nav-link {{ request()->routeIs('admin.transactions*') ? 'active' : '' }}">
                <i class="fas fa-exchange-alt"></i><span>Transactions</span>
            </a>
            <a href="{{ route('admin.logs') }}" class="bottom-nav-link {{ request()->routeIs('admin.logs') ? 'active' : '' }}">
                <i class="fas fa-list-alt"></i><span>Journaux</span>
            </a>
        @else
            <a href="{{ route('dashboard') }}" class="bottom-nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="fas fa-home"></i><span>Accueil</span>
            </a>
            <a href="{{ route('tontines.index') }}" class="bottom-nav-link {{ request()->routeIs('tontines.*') && !request()->routeIs('tontines.explore') ? 'active' : '' }}">
                <i class="fas fa-users"></i><span>Mes tontines</span>
            </a>
            <a href="{{ route('tontines.explore') }}" class="bottom-nav-link {{ request()->routeIs('tontines.explore') ? 'active' : '' }}">
                <i class="fas fa-compass"></i><span>Explorer</span>
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

<x-confirm-modal id="admin-confirm" />

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

<script defer src="{{ asset('js/vendor/bootstrap.bundle.min.js') }}"></script>
<script defer src="{{ asset('js/vendor/alpine-focus.min.js') }}"></script>
<script defer src="{{ asset('js/vendor/alpine-collapse.min.js') }}"></script>
<script defer src="{{ asset('js/vendor/alpine.min.js') }}"></script>
<script defer src="{{ asset('js/tontine.js') }}"></script>
<script nonce="{{ $cspNonce ?? '' }}">
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/serviceworker.js', { scope: '/' })
            .then((reg) => {
                reg.addEventListener('updatefound', () => {
                    const newWorker = reg.installing;
                    newWorker.addEventListener('statechange', () => {
                        if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                            const bar = document.createElement('div');
                            bar.style.cssText = 'position:fixed;bottom:0;left:0;right:0;background:#2D2F53;color:white;padding:12px 16px;display:flex;align-items:center;justify-content:space-between;z-index:9999;font-size:13px;';
                            const msg = document.createElement('span');
                            msg.textContent = '🔄 Nouvelle version disponible';
                            const btn = document.createElement('button');
                            btn.textContent = 'Mettre à jour';
                            btn.style.cssText = 'background:#009639;color:white;border:none;border-radius:999px;padding:6px 16px;font-weight:700;cursor:pointer;';
                            btn.onclick = () => {
                                navigator.serviceWorker.controller?.postMessage({type: 'SKIP_WAITING'});
                                window.location.reload();
                            };
                            bar.appendChild(msg);
                            bar.appendChild(btn);
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
