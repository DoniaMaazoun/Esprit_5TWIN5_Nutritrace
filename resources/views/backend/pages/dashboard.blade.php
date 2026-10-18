@extends('backend.layouts.app')

@section('title', 'Tableau de bord - Farm to Fork Admin')

@php
  $initials = fn ($name) => collect(preg_split('/\s+/', trim($name)))->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
  $hour = now()->hour;
  $greeting = $hour < 12 ? 'Bonjour' : ($hour < 18 ? 'Bon après-midi' : 'Bonsoir');
@endphp

@section('extra-css')
  @include('backend.pages.partials.module-styles')
  <style>
    .dash-hero { background: linear-gradient(120deg, #1e3a2b 0%, #2d5a27 55%, #3f7d36 100%); color: #fff; border-radius: 14px; padding: 22px 24px; margin-bottom: 18px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; position: relative; overflow: hidden; }
    .dash-hero::after { content: ''; position: absolute; right: -60px; top: -60px; width: 220px; height: 220px; border-radius: 50%; background: rgba(255,255,255,.07); }
    .dash-hero h3 { color: #fff; margin: 0 0 4px; font-weight: 700; }
    .dash-hero p { margin: 0; color: rgba(255,255,255,.85) !important; }
    .dash-hero .btn { position: relative; z-index: 1; }
    .dash-hero .btn-light { color: #1e3a2b; font-weight: 600; }
    .dash-hero .btn-outline-light { border-color: rgba(255,255,255,.6); color: #fff; }
    .kpi-trend { font-size: .74rem; font-weight: 700; margin-left: 4px; }
    .kpi-trend.up { color: #2d7a3e; } .kpi-trend.down { color: #c53030; }
    .dash-grid { display: grid; grid-template-columns: repeat(12, 1fr); gap: 16px; margin-bottom: 16px; }
    .span-8 { grid-column: span 8; } .span-4 { grid-column: span 4; } .span-6 { grid-column: span 6; } .span-12 { grid-column: span 12; }
    @media (max-width: 1200px) { .span-8, .span-4, .span-6 { grid-column: span 12; } }
    .dash-card { background: #fff; border: 1px solid var(--m1-border); border-radius: 12px; padding: 18px; height: 100%; }
    .dash-card__head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
    .dash-card__head h5 { margin: 0; font-weight: 700; color: var(--m1-dark); font-size: 1rem; }
    .dash-card__head a { font-size: .82rem; }
    .chart-box { position: relative; height: 260px; }
    .chart-box--sm { height: 220px; }
    .alert-row { display: flex; gap: 12px; align-items: center; padding: 10px 12px; border-radius: 10px; margin-bottom: 8px; font-size: .86rem; }
    .alert-row i { font-size: 1.3rem; }
    .alert-row .btn { margin-left: auto; white-space: nowrap; }
    .alert-row.warning { background: #fff8e6; color: #7a5300; } .alert-row.danger { background: #fdecec; color: #8f2424; }
    .alert-row.info { background: #e9f3ff; color: #1d4f91; }
    .rank-item { display: flex; align-items: center; gap: 10px; padding: 8px 0; border-bottom: 1px solid var(--m1-border); }
    .rank-item:last-child { border-bottom: 0; }
    .rank-pos { width: 22px; font-weight: 800; color: var(--m1-muted); text-align: center; }
    .rank-item .cat-bar { flex: 1; height: 6px; background: #eef2f0; border-radius: 999px; overflow: hidden; }
    .rank-item .cat-bar span { display: block; height: 100%; background: var(--m1-green); border-radius: 999px; }
    .mini-list .m1-product { padding: 8px 0; border-bottom: 1px solid var(--m1-border); }
    .mini-list .m1-product:last-child { border-bottom: 0; }
    .quick-actions { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
    .quick-actions a { display: flex; flex-direction: column; gap: 6px; padding: 14px; border: 1px solid var(--m1-border); border-radius: 10px; color: var(--m1-dark); text-decoration: none; font-weight: 600; font-size: .85rem; transition: all .15s; }
    .quick-actions a i { font-size: 1.4rem; color: var(--m1-green); }
    .quick-actions a:hover { border-color: var(--m1-green); background: var(--m1-mint); }
  </style>
@endsection

@section('content')
<div class="dash-hero">
  <div>
    <h3>{{ $greeting }}, {{ auth()->user()->name }} 👋</h3>
    <p>{{ now()->translatedFormat('l d F Y') }} · Voici l'activité de la plateforme Farm to Fork.</p>
  </div>
  <div class="d-flex flex-wrap" style="gap:8px">
    <a href="{{ route('backend.products.index') }}" class="btn btn-light"><i class="mdi mdi-basket"></i> Gérer les produits</a>
    <a href="{{ route('backend.users.approvals') }}" class="btn btn-outline-light"><i class="mdi mdi-account-check"></i> Approbations @if($kpis['pending'])<span class="badge badge-warning">{{ $kpis['pending'] }}</span>@endif</a>
  </div>
</div>

<div class="m1-kpis">
  <div class="m1-kpi"><div class="m1-kpi__icon ic-green"><i class="mdi mdi-basket"></i></div><div>
    <strong>{{ $kpis['products'] }}
      @if($kpis['products_trend'] !== null)<span class="kpi-trend {{ $kpis['products_trend'] >= 0 ? 'up' : 'down' }}">{{ $kpis['products_trend'] >= 0 ? '▲' : '▼' }} {{ abs($kpis['products_trend']) }} %</span>@endif
    </strong><span>Produits tracés · {{ $kpis['products_month'] }} ce mois</span></div></div>
  <div class="m1-kpi"><div class="m1-kpi__icon ic-teal"><i class="mdi mdi-barn"></i></div><div><strong>{{ $kpis['farms'] }}</strong><span>Exploitations · {{ number_format($kpis['area'], 0, ',', ' ') }} ha</span></div></div>
  <div class="m1-kpi"><div class="m1-kpi__icon ic-blue"><i class="mdi mdi-account-group"></i></div><div><strong>{{ $kpis['producers'] }}</strong><span>Producteurs approuvés</span></div></div>
  <div class="m1-kpi"><div class="m1-kpi__icon ic-purple"><i class="mdi mdi-weight-kilogram"></i></div><div><strong>{{ number_format($kpis['tonnes'], 1, ',', ' ') }} t</strong><span>Production totale</span></div></div>
  <div class="m1-kpi"><div class="m1-kpi__icon ic-amber"><i class="mdi mdi-star-circle"></i></div><div><strong>{{ $kpis['grade_a'] }} %</strong><span>Lots en qualité A</span></div></div>
  <div class="m1-kpi"><div class="m1-kpi__icon ic-red"><i class="mdi mdi-account-multiple"></i></div><div><strong>{{ $kpis['consumers'] }}</strong><span>Consommateurs inscrits</span></div></div>
</div>

<div class="dash-grid">
  <div class="span-8"><div class="dash-card">
    <div class="dash-card__head"><h5><i class="mdi mdi-chart-bar text-success"></i> Production des 12 derniers mois (tonnes)</h5><a href="{{ route('backend.products.index') }}">Tous les produits →</a></div>
    <div class="chart-box"><canvas id="chart-monthly"></canvas></div>
  </div></div>
  <div class="span-4"><div class="dash-card">
    <div class="dash-card__head"><h5><i class="mdi mdi-tag-multiple text-success"></i> Produits par catégorie</h5><a href="{{ route('backend.categories.index') }}">Gérer →</a></div>
    <div class="chart-box"><canvas id="chart-categories"></canvas></div>
  </div></div>
</div>

<div class="dash-grid">
  <div class="span-4"><div class="dash-card">
    <div class="dash-card__head"><h5><i class="mdi mdi-bell-ring text-warning"></i> Alertes</h5><span class="m1-chip">{{ count($alerts) }}</span></div>
    @forelse($alerts as [$type, $icon, $text, $url, $label])
      <div class="alert-row {{ $type }}"><i class="mdi mdi-{{ $icon }}"></i><span>{{ $text }}</span><a href="{{ $url }}" class="btn btn-sm btn-light">{{ $label }}</a></div>
    @empty
      <div class="m1-empty py-4"><i class="mdi mdi-check-decagram"></i>Aucune alerte : tout est en ordre.</div>
    @endforelse
  </div></div>
  <div class="span-4"><div class="dash-card">
    <div class="dash-card__head"><h5><i class="mdi mdi-trophy text-warning"></i> Top producteurs</h5><a href="{{ route('backend.farms.index') }}">Exploitations →</a></div>
    @php($maxKg = max($topProducers->max('kg') ?? 0, 1))
    @forelse($topProducers as $i => $p)
      <div class="rank-item">
        <span class="rank-pos">{{ $i + 1 }}</span>
        <span class="m1-avatar">{{ $initials($p['user']->company_name) }}</span>
        <div style="flex:1; min-width:0">
          <div class="d-flex justify-content-between"><strong class="text-truncate" style="font-size:.86rem">{{ $p['user']->company_name }}</strong><span class="m1-sub">{{ number_format($p['kg'] / 1000, 1, ',', ' ') }} t</span></div>
          <div class="cat-bar"><span style="width: {{ round($p['kg'] / $maxKg * 100) }}%"></span></div>
        </div>
      </div>
    @empty
      <div class="m1-empty py-4"><i class="mdi mdi-account-off"></i>Aucune production enregistrée.</div>
    @endforelse
  </div></div>
  <div class="span-4"><div class="dash-card">
    <div class="dash-card__head"><h5><i class="mdi mdi-flash text-success"></i> Actions rapides</h5></div>
    <div class="quick-actions">
      <a href="{{ route('backend.products.index', ['photo' => 'without']) }}"><i class="mdi mdi-image-off"></i>Produits sans photo</a>
      <a href="{{ route('backend.categories.index') }}"><i class="mdi mdi-tag-plus"></i>Nouvelle catégorie</a>
      <a href="{{ route('backend.users.approvals') }}"><i class="mdi mdi-account-check"></i>Approuver les pros</a>
      <a href="{{ route('frontend.products') }}" target="_blank"><i class="mdi mdi-storefront"></i>Catalogue public</a>
    </div>
  </div></div>
</div>

<div class="dash-grid">
  <div class="span-8"><div class="dash-card">
    <div class="dash-card__head"><h5><i class="mdi mdi-clock-outline text-success"></i> Derniers produits déclarés</h5><a href="{{ route('backend.products.index') }}">Voir tout →</a></div>
    <div class="table-responsive">
      <table class="table m1-table">
        <thead><tr><th>Produit</th><th>Producteur</th><th>Quantité</th><th>Qualité</th><th>Déclaré</th></tr></thead>
        <tbody>
          @forelse($latest as $h)
            <tr>
              <td><div class="m1-product">
                <img src="{{ $h->displayImageUrl() }}" alt="" class="m1-thumb">
                <div><a href="{{ route('backend.products.edit', $h) }}"><strong>{{ $h->product_name }}</strong></a><span class="m1-sub">{{ $h->categoryLabel() }}</span></div>
              </div></td>
              <td class="m1-owner">{{ $h->farm->user->company_name }}<small>{{ $h->farm->name }}</small></td>
              <td class="text-nowrap">{{ number_format($h->quantity, 2, ',', ' ') }} {{ $h->unitShort() }}</td>
              <td><span class="m1-grade m1-grade-{{ $h->quality_grade }}">{{ $h->quality_grade }}</span></td>
              <td class="m1-sub">{{ $h->created_at->diffForHumans() }}</td>
            </tr>
          @empty
            <tr><td colspan="5" class="m1-empty">Aucun produit.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div></div>
  <div class="span-4"><div class="dash-card">
    <div class="dash-card__head"><h5><i class="mdi mdi-map-marker-radius text-success"></i> Exploitations par région</h5></div>
    <div class="chart-box chart-box--sm"><canvas id="chart-governorates"></canvas></div>
    <div class="dash-card__head mt-3"><h5><i class="mdi mdi-sprout text-success"></i> Types d'agriculture</h5></div>
    <div class="chart-box" style="height:180px"><canvas id="chart-farming"></canvas></div>
  </div></div>
</div>

@if($pendingUsers->isNotEmpty())
  <div class="dash-card mb-3">
    <div class="dash-card__head"><h5><i class="mdi mdi-account-clock text-warning"></i> Comptes professionnels en attente</h5><a href="{{ route('backend.users.approvals') }}">Tout traiter →</a></div>
    <div class="d-flex flex-wrap" style="gap:12px">
      @foreach($pendingUsers as $u)
        <div class="m1-producer" style="border:1px solid var(--m1-border); border-radius:10px; padding:10px 14px; min-width:240px">
          <span class="m1-avatar" style="background:#b7791f">{{ $initials($u->company_name ?? $u->name) }}</span>
          <div class="m1-owner" style="flex:1">{{ $u->company_name }}<small>{{ $u->getProTypeLabel() }} · {{ $u->created_at->diffForHumans() }}</small></div>
          <form method="POST" action="{{ route('backend.users.approve', $u) }}">@csrf<button class="btn btn-sm btn-success" title="Approuver"><i class="mdi mdi-check"></i></button></form>
        </div>
      @endforeach
    </div>
  </div>
@endif
@endsection

@section('extra-js')
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <script>
    (function () {
      const c = @json($charts);
      Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
      Chart.defaults.color = '#6c757d';
      const grid = { color: 'rgba(0,0,0,.05)' };

      new Chart(document.getElementById('chart-monthly'), {
        type: 'bar',
        data: { labels: c.months, datasets: [{ label: 'Tonnes', data: c.monthly, backgroundColor: '#2d5a27', borderRadius: 6, maxBarThickness: 34 }] },
        options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid }, x: { grid: { display: false } } } },
      });

      new Chart(document.getElementById('chart-categories'), {
        type: 'doughnut',
        data: { labels: c.categories, datasets: [{ data: c.categoryCounts, backgroundColor: c.categoryColors, borderWidth: 2 }] },
        options: { maintainAspectRatio: false, cutout: '62%', plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, padding: 10 } } } },
      });

      new Chart(document.getElementById('chart-governorates'), {
        type: 'bar',
        data: { labels: c.governorates, datasets: [{ data: c.governorateCounts, backgroundColor: '#40916C', borderRadius: 5 }] },
        options: { indexAxis: 'y', maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { precision: 0 }, grid }, y: { grid: { display: false } } } },
      });

      new Chart(document.getElementById('chart-farming'), {
        type: 'doughnut',
        data: { labels: c.farmingTypes, datasets: [{ data: c.farmingTypeCounts, backgroundColor: ['#2d5a27', '#95D5B2', '#1d6fd1', '#F4A261', '#6b46c1'] }] },
        options: { maintainAspectRatio: false, plugins: { legend: { position: 'right', labels: { boxWidth: 10 } } } },
      });
    })();
  </script>
@endsection
