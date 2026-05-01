<nav class="bottom-nav">
    <a href="{{ route('dashboard') }}" class="nav-icon {{ request()->routeIs('dashboard') ? 'active' : '' }}">
        <i class="fas fa-home"></i>
        <span>Accueil</span>
    </a>
    <a href="{{ route('tontines.index') }}" class="nav-icon {{ request()->routeIs('tontines.*') ? 'active' : '' }}">
        <i class="fas fa-users"></i>
        <span>Tontines</span>
    </a>
    <a href="{{ route('tontines.create') }}" class="nav-icon nav-icon-center">
        <i class="fas fa-plus"></i>
    </a>
    <a href="#" class="nav-icon">
        <i class="fas fa-history"></i>
        <span>Historique</span>
    </a>
    <a href="#" class="nav-icon">
        <i class="fas fa-user"></i>
        <span>Profil</span>
    </a>
</nav>
