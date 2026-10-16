<!-- Header -->
<header class="header">
  <div class="container header-inner">
    <a href="{{ route('frontend.home') }}" class="logo">
      <div class="logo-icon"><i class="fa-solid fa-leaf"></i></div>
      <div class="logo-text">Farm to Fork<span>Traçabilité</span></div>
    </a>
    <nav class="nav" role="navigation" aria-label="Main navigation">
      <a href="{{ route('frontend.home') }}" class="@if(Route::currentRouteName() === 'frontend.home') active @endif">Accueil</a>
      <a href="{{ route('frontend.about') }}" class="@if(Route::currentRouteName() === 'frontend.about') active @endif">À Propos</a>
      <a href="{{ route('frontend.products') }}" class="@if(Route::currentRouteName() === 'frontend.products') active @endif">Produits</a>
      <a href="{{ route('frontend.contact') }}" class="@if(Route::currentRouteName() === 'frontend.contact') active @endif">Contact</a>
      
      <!-- Authentication Links -->
      @if(auth()->check())
        <!-- User Menu (Connected) -->
        <div class="nav-auth-menu">
          <span class="nav-user-name">{{ auth()->user()->name }}</span>
          <form method="POST" action="{{ route('logout') }}" style="display: inline;">
            @csrf
            <button type="submit" class="btn btn--logout btn--sm">Déconnexion</button>
          </form>
        </div>
      @else
        <!-- Guest Links (Not Connected) -->
        <a href="{{ route('login') }}" class="btn btn--secondary btn--sm">Connexion</a>
        <a href="{{ route('register') }}" class="btn btn--primary btn--sm">S'inscrire</a>
      @endif
      
      <a href="{{ route('backend.dashboard') }}" class="btn btn--accent btn--sm" title="Scanner un produit">
        <i class="fa-solid fa-qrcode"></i> Scanner
      </a>
    </nav>
    <button class="nav-toggle" aria-label="Toggle navigation" aria-expanded="false">
      <span></span>
      <span></span>
      <span></span>
    </button>
  </div>
</header>

<script>
  const navToggle = document.querySelector('.nav-toggle');
  const nav = document.querySelector('.nav');
  if (navToggle && nav) {
    navToggle.addEventListener('click', () => {
      const isOpen = nav.classList.toggle('nav-open');
      navToggle.classList.toggle('active');
      navToggle.setAttribute('aria-expanded', isOpen);
    });
  }
</script>
