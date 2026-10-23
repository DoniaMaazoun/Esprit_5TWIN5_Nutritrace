@php
  $admin = auth()->user();
  $adminInitials = collect(preg_split('/\s+/', trim($admin->name)))->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
  // Notifications calculées : comptes à approuver, lots en stock anciens, produits sans photo
  $notifications = collect([
      ['count' => \App\Models\User::where('role', 'pro')->where('status', 'pending')->count(), 'icon' => 'account-clock', 'color' => 'warning',
       'text' => 'compte(s) pro en attente', 'url' => route('backend.users.approvals')],
      ['count' => \App\Models\Harvest::where('status', 'in_stock')->whereDate('harvest_date', '<', now()->subDays(60))->count(), 'icon' => 'clock-alert', 'color' => 'danger',
       'text' => 'lot(s) en stock depuis + 60 jours', 'url' => route('backend.products.index', ['status' => 'in_stock'])],
      ['count' => \App\Models\Harvest::whereNull('image_path')->count(), 'icon' => 'image-off', 'color' => 'info',
       'text' => 'produit(s) sans photo', 'url' => route('backend.products.index', ['photo' => 'without'])],
  ])->where('count', '>', 0);
@endphp

<style>
  .ftf-brand { display: flex; align-items: center; gap: 10px; color: #fff !important; font-weight: 700; font-size: 1.05rem; text-decoration: none !important; }
  .ftf-brand__icon { width: 34px; height: 34px; border-radius: 9px; background: #fff; color: #2d5a27; display: inline-flex; align-items: center; justify-content: center; font-size: 1.2rem; }
  .ftf-brand small { display: block; font-size: .62rem; font-weight: 600; letter-spacing: .08em; opacity: .75; text-transform: uppercase; }
  .ftf-search { position: relative; width: 340px; }
  .ftf-search i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: rgba(255,255,255,.7); }
  .ftf-search input { background: rgba(255,255,255,.12) !important; border: 1px solid rgba(255,255,255,.18) !important; color: #fff !important; border-radius: 999px !important; padding-left: 34px !important; height: 38px; }
  .ftf-search input::placeholder { color: rgba(255,255,255,.65); }
  .ftf-avatar { width: 34px; height: 34px; border-radius: 50%; background: #fff; color: #2d5a27; font-weight: 800; font-size: .8rem; display: inline-flex; align-items: center; justify-content: center; }
  .ftf-avatar--lg { width: 56px; height: 56px; font-size: 1.1rem; background: #2d5a27; color: #fff; }
  .ftf-notif-item { display: flex; gap: 10px; align-items: center; white-space: normal; }
  .ftf-notif-item strong { font-size: 1rem; }
</style>

<nav class="navbar default-layout col-lg-12 col-12 p-0 fixed-top d-flex flex-row">
  <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-center">
    <a class="ftf-brand" href="{{ route('backend.dashboard') }}" title="Tableau de bord">
      <span class="ftf-brand__icon"><i class="mdi mdi-leaf"></i></span>
      <span class="d-none d-lg-inline text-left">Farm to Fork<small>Back-office</small></span>
    </a>
  </div>
  <div class="navbar-menu-wrapper d-flex align-items-center">
    <form class="ftf-search d-none d-md-block" action="{{ route('backend.products.index') }}" method="GET" role="search">
      <i class="mdi mdi-magnify"></i>
      <input type="search" name="q" class="form-control" placeholder="Rechercher un produit, une variété, un code lot…" value="{{ request()->routeIs('backend.products.*') ? request('q') : '' }}" aria-label="Rechercher un produit">
    </form>
    <ul class="navbar-nav ml-auto align-items-center">
      <li class="nav-item d-none d-lg-block">
        <a class="nav-link" href="{{ route('frontend.home') }}" target="_blank" title="Voir le site"><i class="mdi mdi-web"></i> Voir le site</a>
      </li>

      <li class="nav-item dropdown">
        <a class="nav-link count-indicator" id="notificationDropdown" href="#" data-toggle="dropdown" aria-label="Notifications">
          <i class="mdi mdi-bell-outline"></i>
          @if($notifications->isNotEmpty())<span class="count bg-warning">{{ $notifications->count() }}</span>@endif
        </a>
        <div class="dropdown-menu dropdown-menu-right navbar-dropdown preview-list pb-0" aria-labelledby="notificationDropdown" style="min-width: 300px">
          <div class="dropdown-item py-3 border-bottom"><p class="mb-0 font-weight-semibold">Notifications</p></div>
          @forelse($notifications as $n)
            <a class="dropdown-item py-3 ftf-notif-item" href="{{ $n['url'] }}">
              <i class="mdi mdi-{{ $n['icon'] }} text-{{ $n['color'] }}" style="font-size:1.4rem"></i>
              <span><strong>{{ $n['count'] }}</strong> {{ $n['text'] }}</span>
            </a>
          @empty
            <div class="dropdown-item py-3 text-muted"><i class="mdi mdi-check-circle text-success"></i> Rien à signaler.</div>
          @endforelse
        </div>
      </li>

      <li class="nav-item dropdown user-dropdown">
        <a class="nav-link dropdown-toggle d-flex align-items-center" id="UserDropdown" href="#" data-toggle="dropdown" aria-expanded="false" style="gap:8px">
          <span class="ftf-avatar">@include('profile._avatar', ['user' => $admin])</span>
          <span class="d-none d-xl-inline text-white">{{ $admin->name }}</span>
        </a>
        <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="UserDropdown">
          <div class="dropdown-header text-center">
            <span class="ftf-avatar ftf-avatar--lg">@include('profile._avatar', ['user' => $admin])</span>
            <p class="mb-1 mt-3 font-weight-semibold">{{ $admin->name }}</p>
            <p class="font-weight-light text-muted mb-0">{{ $admin->email }}</p>
          </div>
          <a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="mdi mdi-account-settings mr-2"></i> Mon profil</a>
          <a class="dropdown-item" href="{{ route('backend.dashboard') }}"><i class="mdi mdi-view-dashboard mr-2"></i> Tableau de bord</a>
          <a class="dropdown-item" href="{{ route('frontend.products') }}" target="_blank"><i class="mdi mdi-storefront mr-2"></i> Catalogue public</a>
          <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="dropdown-item text-danger"><i class="mdi mdi-power mr-2"></i> Déconnexion</button>
          </form>
        </div>
      </li>
    </ul>
    <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button" data-toggle="offcanvas" aria-label="Menu">
      <span class="mdi mdi-menu"></span>
    </button>
  </div>
</nav>
