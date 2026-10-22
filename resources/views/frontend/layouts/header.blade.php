@php
  $u = auth()->user();
  $route = Route::currentRouteName();
  $active = fn (...$names) => in_array($route, $names, true) ? 'active' : '';
  $initials = $u ? collect(preg_split('/\s+/', trim($u->name)))->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('') : '';
  $role = match (true) {
      !$u => null,
      $u->isAdmin() => ['Administrateur', 'fa-user-shield'],
      $u->isProducer() => ['Producteur', 'fa-tractor'],
      $u->isPro() => [$u->getProTypeLabel(), 'fa-briefcase'],
      default => ['Consommateur', 'fa-basket-shopping'],
  };
  $links = [
      ['frontend.home', 'Accueil', 'fa-house', [$route === 'frontend.home']],
      ['frontend.products', 'Produits', 'fa-basket-shopping', [$route === 'frontend.products']],
      ['farms.directory', 'Exploitations', 'fa-wheat-awn', [in_array($route, ['farms.directory', 'farms.public'], true)]],
      ['frontend.about', 'À propos', 'fa-circle-info', [$route === 'frontend.about']],
      ['frontend.contact', 'Contact', 'fa-envelope', [$route === 'frontend.contact']],
  ];
  $lookupError = $errors->getBag('lookup')->first('code');
  // « Mon espace pro » : le distributeur arrive directement sur ses expéditions (Module 3)
  $proSpaceUrl = $u?->isDistributor() ? route('pro.distributor.shipments.index') : route('pro.dashboard');
@endphp

<header class="ftn" id="ftn">
  <div class="container ftn__inner">
    <a href="{{ route('frontend.home') }}" class="ftn__logo" aria-label="Farm to Fork — accueil">
      <span class="ftn__logo-icon"><i class="fa-solid fa-leaf"></i></span>
      <span class="ftn__logo-text">Farm to Fork<small>Traçabilité</small></span>
    </a>

    <nav class="ftn__links" aria-label="Navigation principale">
      @foreach($links as [$name, $label, $icon, [$isActive]])
        <a href="{{ route($name) }}" class="ftn__link {{ $isActive ? 'active' : '' }}" @if($isActive) aria-current="page" @endif>{{ $label }}</a>
      @endforeach
    </nav>

    <div class="ftn__actions">
      {{-- Tracer un lot --}}
      <div class="dropdown">
        <button class="ftn__trace" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" id="ftn-trace-btn">
          <i class="fa-solid fa-qrcode"></i><span class="d-none d-xl-inline">Tracer un lot</span>
        </button>
        <div class="dropdown-menu dropdown-menu-end ftn__panel" aria-labelledby="ftn-trace-btn">
          <form method="GET" action="{{ route('traceability.lookup') }}" class="ftn-lookup" novalidate>
            <strong><i class="fa-solid fa-magnifying-glass text-success"></i> Retrouver l'origine d'un produit</strong>
            <p>Saisissez le code inscrit sous le QR code de l'emballage.</p>
            <div class="ftn-lookup__field">
              <input type="text" name="code" value="{{ old('code') }}" placeholder="LOT-20261015-KFYVO" maxlength="20" autocomplete="off"
                     class="{{ $lookupError ? 'is-invalid' : '' }}" aria-label="Code du lot" data-lot-input>
              <button type="submit" aria-label="Rechercher"><i class="fa-solid fa-arrow-right"></i></button>
            </div>
            <small class="ftn-lookup__error" @unless($lookupError) hidden @endunless>{{ $lookupError }}</small>
          </form>
        </div>
      </div>

      @auth
        <div class="dropdown">
          <button class="ftn__user" type="button" data-bs-toggle="dropdown" aria-expanded="false" id="ftn-user-btn">
            <span class="ftn__avatar">{{ $initials }}</span>
            <span class="ftn__user-meta d-none d-lg-flex"><strong>{{ \Illuminate\Support\Str::limit($u->name, 18) }}</strong><small>{{ $role[0] }}</small></span>
            <i class="fa-solid fa-chevron-down ftn__chev"></i>
          </button>
          <div class="dropdown-menu dropdown-menu-end ftn__panel ftn__menu" aria-labelledby="ftn-user-btn">
            <div class="ftn__menu-head">
              <span class="ftn__avatar ftn__avatar--lg">{{ $initials }}</span>
              <div><strong>{{ $u->name }}</strong><small>{{ $u->email }}</small><span class="ftn__role"><i class="fa-solid {{ $role[1] }}"></i> {{ $role[0] }}</span></div>
            </div>
            @if($u->isAdmin())
              <a class="dropdown-item" href="{{ route('backend.dashboard') }}"><i class="fa-solid fa-gauge-high"></i> Back-office</a>
              <a class="dropdown-item" href="{{ route('backend.products.index') }}"><i class="fa-solid fa-basket-shopping"></i> Gérer les produits</a>
              <a class="dropdown-item" href="{{ route('backend.users.approvals') }}"><i class="fa-solid fa-user-check"></i> Comptes pro</a>
            @elseif($u->isProducer())
              <a class="dropdown-item" href="{{ route('pro.dashboard') }}"><i class="fa-solid fa-gauge-high"></i> Tableau de bord</a>
              <a class="dropdown-item" href="{{ route('pro.farms.index') }}"><i class="fa-solid fa-wheat-awn"></i> Mes exploitations</a>
              <a class="dropdown-item" href="{{ route('pro.harvests.index') }}"><i class="fa-solid fa-boxes-stacked"></i> Mes récoltes</a>
              <a class="dropdown-item ftn__menu-cta" href="{{ route('pro.harvests.create') }}"><i class="fa-solid fa-plus"></i> Déclarer une récolte</a>
            @elseif($u->isPro() && $u->isApproved())
              <a class="dropdown-item" href="{{ $proSpaceUrl }}"><i class="fa-solid fa-briefcase"></i> Mon espace pro</a>
            @else
              <a class="dropdown-item" href="{{ route('frontend.products') }}"><i class="fa-solid fa-basket-shopping"></i> Explorer les produits</a>
              <a class="dropdown-item" href="{{ route('farms.directory') }}"><i class="fa-solid fa-wheat-awn"></i> Découvrir les exploitations</a>
            @endif
            <div class="dropdown-divider"></div>
            <form method="POST" action="{{ route('logout') }}">
              @csrf
              <button type="submit" class="dropdown-item text-danger"><i class="fa-solid fa-right-from-bracket"></i> Déconnexion</button>
            </form>
          </div>
        </div>
      @else
        <a href="{{ route('login') }}" class="ftn__btn ftn__btn--ghost d-none d-md-inline-flex">Connexion</a>
        <a href="{{ route('register') }}" class="ftn__btn ftn__btn--primary d-none d-md-inline-flex"><i class="fa-solid fa-user-plus"></i> S'inscrire</a>
      @endauth

      <button class="ftn__burger" type="button" aria-label="Ouvrir le menu" aria-expanded="false" aria-controls="ftn-mobile" id="ftn-burger">
        <span></span><span></span><span></span>
      </button>
    </div>
  </div>

  {{-- Menu mobile --}}
  <div class="ftn-mobile" id="ftn-mobile" hidden>
    <div class="container">
      @foreach($links as [$name, $label, $icon, [$isActive]])
        <a href="{{ route($name) }}" class="ftn-mobile__link {{ $isActive ? 'active' : '' }}"><i class="fa-solid {{ $icon }}"></i> {{ $label }}</a>
      @endforeach
      @auth
        @if($u->isAdmin())
          <a href="{{ route('backend.dashboard') }}" class="ftn-mobile__link"><i class="fa-solid fa-gauge-high"></i> Back-office</a>
        @elseif($u->isPro() && $u->isApproved())
          <a href="{{ $proSpaceUrl }}" class="ftn-mobile__link"><i class="fa-solid fa-gauge-high"></i> Mon espace</a>
        @endif
      @else
        <div class="ftn-mobile__auth">
          <a href="{{ route('login') }}" class="ftn__btn ftn__btn--ghost">Connexion</a>
          <a href="{{ route('register') }}" class="ftn__btn ftn__btn--primary">S'inscrire</a>
        </div>
      @endauth
    </div>
  </div>
</header>

<script>
  (function () {
    const header = document.getElementById('ftn');
    const burger = document.getElementById('ftn-burger');
    const mobile = document.getElementById('ftn-mobile');
    const onScroll = () => header.classList.toggle('is-scrolled', window.scrollY > 8);
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
    burger.addEventListener('click', () => {
      const open = mobile.hidden;
      mobile.hidden = !open;
      burger.classList.toggle('active', open);
      burger.setAttribute('aria-expanded', String(open));
    });
    // Code de lot introuvable : on rouvre le panneau (sauf sur l'accueil, qui a son propre champ)
    @if($lookupError && $route !== 'frontend.home')
      document.addEventListener('DOMContentLoaded', () => bootstrap.Dropdown.getOrCreateInstance(document.getElementById('ftn-trace-btn')).show());
    @endif
    // Contrôle de saisie du code de lot (doublé côté serveur)
    document.addEventListener('DOMContentLoaded', () => document.querySelectorAll('.ftn-lookup, .lot-search').forEach((form) => {
      const input = form.querySelector('[data-lot-input]');
      const err = form.querySelector('.ftn-lookup__error, .lot-search__error');
      input.addEventListener('input', () => { input.value = input.value.toUpperCase(); input.classList.remove('is-invalid'); if (err) err.hidden = true; });
      form.addEventListener('submit', (e) => {
        const v = input.value.trim();
        const msg = !v ? 'Saisissez le code du lot (inscrit sous le QR code).'
          : (!/^LOT-\d{8}-[A-Z0-9]{5}$/.test(v) ? 'Format attendu : LOT-AAAAMMJJ-XXXXX (ex. : LOT-20261015-KFYVO).' : '');
        if (msg) { e.preventDefault(); input.classList.add('is-invalid'); if (err) { err.textContent = msg; err.hidden = false; } input.focus(); }
      });
    }));
  })();
</script>
