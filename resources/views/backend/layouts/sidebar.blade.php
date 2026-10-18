@php
  $me = auth()->user();
  $meInitials = collect(preg_split('/\s+/', trim($me->name)))->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
  $counts = [
      'products' => \App\Models\Harvest::count(),
      'categories' => \App\Models\Category::count(),
      'farms' => \App\Models\Farm::count(),
      'pending' => \App\Models\User::where('role', 'pro')->where('status', 'pending')->count(),
  ];
  $is = fn (...$patterns) => request()->routeIs(...$patterns);
  $inTrace = $is('backend.history.*');
  $inCertif = $is('backend.certifications.*', 'backend.labels.*', 'backend.greenwashing.*');
  $inLogistics = $is('backend.logistics.*');
@endphp

<style>
  /* Barre latérale Farm to Fork (remplace le style du template) */
  #sidebar.sidebar { padding: 0; }
  .ftf-side { display: flex; flex-direction: column; min-height: calc(100vh - 63px); padding: 16px 12px 18px; color: rgba(255,255,255,.85); }
  .ftf-side a { text-decoration: none !important; }
  .ftf-profile { display: flex; align-items: center; gap: 10px; padding: 12px; border-radius: 12px; background: rgba(255,255,255,.08); margin-bottom: 18px; }
  .ftf-profile__avatar { width: 40px; height: 40px; border-radius: 12px; background: #fff; color: #2d5a27; font-weight: 800; display: flex; align-items: center; justify-content: center; position: relative; flex-shrink: 0; }
  .ftf-profile__avatar::after { content: ''; position: absolute; right: -2px; bottom: -2px; width: 11px; height: 11px; border-radius: 50%; background: #4ade80; border: 2px solid #24452a; }
  .ftf-profile strong { display: block; color: #fff; font-size: .88rem; line-height: 1.2; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 150px; }
  .ftf-profile small { color: rgba(255,255,255,.6); font-size: .72rem; }
  .ftf-section { font-size: .66rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; color: rgba(255,255,255,.45); margin: 16px 10px 6px; }
  .ftf-link { display: flex; align-items: center; gap: 11px; padding: 9px 12px; border-radius: 10px; color: rgba(255,255,255,.82) !important; font-size: .87rem; font-weight: 500; transition: background .15s, color .15s; position: relative; cursor: pointer; }
  .ftf-link i.ftf-ic { font-size: 1.15rem; width: 20px; text-align: center; color: rgba(255,255,255,.7); }
  .ftf-link:hover { background: rgba(255,255,255,.08); color: #fff !important; }
  .ftf-link.active { background: #fff; color: #1e3a2b !important; font-weight: 700; box-shadow: 0 4px 14px rgba(0,0,0,.18); }
  .ftf-link.active i.ftf-ic { color: #2d5a27; }
  .ftf-link .ftf-badge { margin-left: auto; font-size: .7rem; font-weight: 700; padding: 1px 8px; border-radius: 999px; background: rgba(255,255,255,.15); color: #fff; }
  .ftf-link.active .ftf-badge { background: #e3f4e8; color: #2d5a27; }
  .ftf-badge--alert { background: #f59e0b !important; color: #1e3a2b !important; }
  .ftf-link .ftf-caret { margin-left: auto; transition: transform .2s; font-size: 1rem; }
  .ftf-link[aria-expanded="true"] .ftf-caret { transform: rotate(90deg); }
  .ftf-sub { margin: 2px 0 4px 31px; border-left: 1px solid rgba(255,255,255,.15); padding-left: 8px; }
  .ftf-sub a { display: block; padding: 6px 10px; border-radius: 8px; color: rgba(255,255,255,.65) !important; font-size: .82rem; }
  .ftf-sub a:hover { color: #fff !important; background: rgba(255,255,255,.06); }
  .ftf-sub a.active { color: #fff !important; font-weight: 700; }
  .ftf-soon { font-size: .6rem; padding: 1px 6px; border-radius: 4px; background: rgba(255,255,255,.12); margin-left: 6px; vertical-align: middle; }
  .ftf-footer { margin-top: auto; padding-top: 18px; }
  .ftf-card { background: linear-gradient(135deg, rgba(255,255,255,.14), rgba(255,255,255,.05)); border: 1px solid rgba(255,255,255,.12); border-radius: 12px; padding: 14px; font-size: .8rem; }
  .ftf-card strong { color: #fff; display: block; margin-bottom: 4px; }
  .ftf-card .btn { margin-top: 10px; width: 100%; background: #fff; color: #1e3a2b; font-weight: 700; border: 0; font-size: .8rem; }
  .ftf-logout { width: 100%; margin-top: 10px; background: transparent; border: 1px solid rgba(255,255,255,.2); color: rgba(255,255,255,.8); border-radius: 10px; padding: 8px; font-size: .82rem; }
  .ftf-logout:hover { background: rgba(220,53,69,.25); color: #fff; border-color: transparent; }
  /* Le template masque le texte quand la barre est réduite : on garde un rendu propre */
  .sidebar-icon-only .ftf-side .ftf-section, .sidebar-icon-only .ftf-side .ftf-footer, .sidebar-icon-only .ftf-profile div { display: none; }
</style>

<nav class="sidebar sidebar-offcanvas" id="sidebar" aria-label="Menu du back-office">
  <div class="ftf-side">
    <div class="ftf-profile">
      <span class="ftf-profile__avatar">{{ $meInitials }}</span>
      <div><strong>{{ $me->name }}</strong><small>Administrateur</small></div>
    </div>

    <a href="{{ route('backend.dashboard') }}" class="ftf-link {{ $is('backend.dashboard') ? 'active' : '' }}">
      <i class="mdi mdi-view-dashboard ftf-ic"></i> Tableau de bord
    </a>

    <div class="ftf-section">Catalogue & production</div>
    <a href="{{ route('backend.products.index') }}" class="ftf-link {{ $is('backend.products.*') ? 'active' : '' }}">
      <i class="mdi mdi-basket ftf-ic"></i> Produits <span class="ftf-badge">{{ $counts['products'] }}</span>
    </a>
    <a href="{{ route('backend.categories.index') }}" class="ftf-link {{ $is('backend.categories.*') ? 'active' : '' }}">
      <i class="mdi mdi-tag-multiple ftf-ic"></i> Catégories <span class="ftf-badge">{{ $counts['categories'] }}</span>
    </a>
    <a href="{{ route('backend.farms.index') }}" class="ftf-link {{ $is('backend.farms.*') ? 'active' : '' }}">
      <i class="mdi mdi-barn ftf-ic"></i> Exploitations <span class="ftf-badge">{{ $counts['farms'] }}</span>
    </a>
    <a href="{{ route('frontend.products') }}" target="_blank" class="ftf-link">
      <i class="mdi mdi-storefront ftf-ic"></i> Catalogue public <i class="mdi mdi-open-in-new ftf-caret" style="font-size:.85rem"></i>
    </a>

    <div class="ftf-section">Utilisateurs</div>
    <a href="{{ route('backend.users.approvals') }}" class="ftf-link {{ $is('backend.users.*') ? 'active' : '' }}">
      <i class="mdi mdi-account-check ftf-ic"></i> Comptes pro
      @if($counts['pending'])<span class="ftf-badge ftf-badge--alert" title="En attente">{{ $counts['pending'] }}</span>@endif
    </a>

    <div class="ftf-section">Traçabilité & qualité</div>
    <a class="ftf-link {{ $inTrace ? 'active' : '' }}" data-toggle="collapse" href="#ftf-trace" aria-expanded="{{ $inTrace ? 'true' : 'false' }}" aria-controls="ftf-trace">
      <i class="mdi mdi-link-variant ftf-ic"></i> Traçabilité <i class="mdi mdi-chevron-right ftf-caret"></i>
    </a>
    <div class="collapse {{ $inTrace ? 'show' : '' }}" id="ftf-trace">
      <div class="ftf-sub">
        <a href="{{ route('backend.products.index') }}">Lots de produits</a>
        <a href="{{ route('backend.history.index') }}" class="{{ $is('backend.history.*') ? 'active' : '' }}">Historique chaîne <span class="ftf-soon">bientôt</span></a>
      </div>
    </div>
    <a class="ftf-link {{ $inCertif ? 'active' : '' }}" data-toggle="collapse" href="#ftf-certif" aria-expanded="{{ $inCertif ? 'true' : 'false' }}" aria-controls="ftf-certif">
      <i class="mdi mdi-certificate ftf-ic"></i> Certifications <i class="mdi mdi-chevron-right ftf-caret"></i>
    </a>
    <div class="collapse {{ $inCertif ? 'show' : '' }}" id="ftf-certif">
      <div class="ftf-sub">
        <a href="{{ route('backend.certifications.index') }}" class="{{ $is('backend.certifications.*') ? 'active' : '' }}">Certificats <span class="ftf-soon">bientôt</span></a>
        <a href="{{ route('backend.labels.index') }}" class="{{ $is('backend.labels.*') ? 'active' : '' }}">Vérifier labels <span class="ftf-soon">bientôt</span></a>
        <a href="{{ route('backend.greenwashing.index') }}" class="{{ $is('backend.greenwashing.*') ? 'active' : '' }}">Anti-greenwashing <span class="ftf-soon">bientôt</span></a>
      </div>
    </div>
    <!-- Module 3 : Logistique & Carbone -->
    <a class="ftf-link {{ $inLogistics ? 'active' : '' }}" data-toggle="collapse" href="#ftf-logistics" aria-expanded="{{ $inLogistics ? 'true' : 'false' }}" aria-controls="ftf-logistics">
      <i class="mdi mdi-truck-delivery ftf-ic"></i> Logistique & Carbone <i class="mdi mdi-chevron-right ftf-caret"></i>
    </a>
    <div class="collapse {{ $inLogistics ? 'show' : '' }}" id="ftf-logistics">
      <div class="ftf-sub">
        <a href="{{ route('backend.logistics.shipments.index') }}" class="{{ $is('backend.logistics.shipments.*') ? 'active' : '' }}">Expéditions</a>
        <a href="{{ route('backend.logistics.transport-modes.index') }}" class="{{ $is('backend.logistics.transport-modes.*') ? 'active' : '' }}">Facteurs d'émission</a>
      </div>
    </div>
    <a href="{{ route('backend.ecoscore.index') }}" class="ftf-link {{ $is('backend.ecoscore.*') ? 'active' : '' }}">
      <i class="mdi mdi-leaf ftf-ic"></i> Éco-Score <span class="ftf-soon">bientôt</span>
    </a>

    <div class="ftf-footer">
      <div class="ftf-card">
        <strong><i class="mdi mdi-earth"></i> Farm to Fork</strong>
        {{ $counts['products'] }} produits tracés sur {{ $counts['farms'] }} exploitations.
        <a href="{{ route('frontend.home') }}" target="_blank" class="btn btn-sm"><i class="mdi mdi-web"></i> Voir le site</a>
      </div>
      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="ftf-logout"><i class="mdi mdi-power"></i> Déconnexion</button>
      </form>
    </div>
  </div>
</nav>
