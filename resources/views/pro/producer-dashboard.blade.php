@extends('pro.layouts.producer')

@section('title', 'Tableau de bord - ' . $user->company_name . ' | Farm to Fork')

@php
  $hour = now()->hour;
  $greeting = $hour < 12 ? 'Bonjour' : ($hour < 18 ? 'Bon après-midi' : 'Bonsoir');
  $fmt = fn ($n, $d = 0) => number_format($n, $d, ',', ' ');
@endphp

@push('styles')
<style>
  .pd-hero { background: linear-gradient(120deg, var(--primary-dark) 0%, var(--primary) 60%, var(--primary-light) 100%); color: #fff; border-radius: 16px; padding: 26px 28px; display: flex; justify-content: space-between; align-items: center; gap: 18px; flex-wrap: wrap; margin-bottom: 20px; position: relative; overflow: hidden; }
  .pd-hero::before { content: ''; position: absolute; width: 260px; height: 260px; right: -70px; top: -90px; border-radius: 50%; background: rgba(255,255,255,.08); }
  .pd-hero h1 { color: #fff; font-size: 1.6rem; margin-bottom: 4px; }
  .pd-hero p { margin: 0; opacity: .85; }
  .pd-hero__actions { display: flex; gap: 8px; flex-wrap: wrap; position: relative; }
  .pd-hero .btn-light { color: var(--primary-dark); font-weight: 700; }
  .pd-hero .btn-outline-light { border-color: rgba(255,255,255,.6) !important; }
  .pd-grid { display: grid; grid-template-columns: repeat(12, 1fr); gap: 18px; margin-bottom: 18px; }
  .c-8 { grid-column: span 8; } .c-4 { grid-column: span 4; } .c-6 { grid-column: span 6; }
  @media (max-width: 992px) { .c-8, .c-4, .c-6 { grid-column: span 12; } }
  .pd-card { background: #fff; border-radius: 14px; border: 1px solid rgba(45,106,79,.08); box-shadow: var(--shadow-sm); padding: 20px; height: 100%; }
  .pd-card__head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; }
  .pd-card__head h2 { font-size: 1.05rem; margin: 0; display: flex; gap: 8px; align-items: center; }
  .pd-card__head a { font-size: .85rem; font-weight: 600; }
  .season { background: linear-gradient(135deg, #fff8ec, #fdf2e2); border: 1px solid #f6dfbc; }
  .season i.big { font-size: 2rem; color: var(--accent); }
  .alert-line { display: flex; gap: 12px; align-items: center; padding: 10px 12px; border-radius: 10px; font-size: .88rem; margin-bottom: 8px; color: inherit; }
  .alert-line:hover { filter: brightness(.97); color: inherit; }
  .alert-line.danger { background: #fdecec; color: #8f2424; } .alert-line.info { background: #e9f3ff; color: #1d4f91; } .alert-line.warning { background: #fff8e6; color: #7a5300; }
  .alert-line .fa-arrow-right { margin-left: auto; opacity: .6; }
  .recent-item { display: flex; gap: 12px; align-items: center; padding: 10px 0; border-bottom: 1px solid var(--gray-light); }
  .recent-item:last-child { border-bottom: 0; }
  .recent-item .thumb { width: 48px; height: 48px; }
  .recent-item__meta { flex: 1; min-width: 0; font-size: .86rem; }
  .recent-item__meta strong { color: var(--dark); }
  .farm-row { display: flex; align-items: center; gap: 12px; padding: 10px 0; border-bottom: 1px solid var(--gray-light); }
  .farm-row:last-child { border-bottom: 0; }
  .farm-row__icon { width: 40px; height: 40px; border-radius: 10px; background: var(--mint); color: var(--primary-dark); display: grid; place-items: center; }
  .shortcut-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
  .shortcut { display: flex; flex-direction: column; gap: 6px; padding: 14px; border-radius: 12px; border: 1px solid #e3ece6; font-weight: 600; font-size: .88rem; color: var(--dark); transition: all .15s; }
  .shortcut i { font-size: 1.3rem; color: var(--primary); }
  .shortcut:hover { border-color: var(--primary-light); background: #f3faf5; color: var(--dark); transform: translateY(-2px); }
  .chart-wrap { position: relative; height: 250px; }
</style>
@endpush

@section('producer-content')
<section class="pd-hero">
  <div>
    <h1>{{ $greeting }}, {{ $user->name }} 👋</h1>
    <p>{{ $user->company_name }} · {{ now()->translatedFormat('l d F Y') }}</p>
  </div>
  <div class="pd-hero__actions">
    <a href="{{ route('pro.harvests.create') }}" class="btn btn-light"><i class="fa-solid fa-plus"></i> Déclarer une récolte</a>
    <a href="{{ route('pro.farms.create') }}" class="btn btn-outline-light text-white"><i class="fa-solid fa-wheat-awn"></i> Nouvelle exploitation</a>
  </div>
</section>

<div class="stat-grid">
  <div class="stat"><div class="stat__icon"><i class="fa-solid fa-wheat-awn"></i></div><div><div class="stat__value">{{ $kpis['farms'] }}</div><div class="stat__label">Exploitations · {{ $fmt($kpis['area'], 1) }} ha</div></div></div>
  <div class="stat"><div class="stat__icon"><i class="fa-solid fa-basket-shopping"></i></div><div><div class="stat__value">{{ $kpis['harvests'] }}</div><div class="stat__label">Produits déclarés</div></div></div>
  <div class="stat"><div class="stat__icon"><i class="fa-solid fa-weight-hanging"></i></div><div><div class="stat__value">{{ $fmt($kpis['kg'] / 1000, 2) }} t</div><div class="stat__label">Production totale</div></div></div>
  <div class="stat"><div class="stat__icon"><i class="fa-solid fa-warehouse"></i></div><div><div class="stat__value">{{ $fmt($kpis['in_stock_kg']) }} kg</div><div class="stat__label">En stock</div></div></div>
  <div class="stat"><div class="stat__icon"><i class="fa-solid fa-award"></i></div><div><div class="stat__value">{{ $kpis['grade_a'] }} %</div><div class="stat__label">Lots qualité A</div></div></div>
  <div class="stat"><div class="stat__icon"><i class="fa-solid fa-chart-line"></i></div><div><div class="stat__value">{{ $kpis['yield'] !== null ? $fmt($kpis['yield']) : '—' }}</div><div class="stat__label">Rendement moyen (kg/ha)</div></div></div>
  <div class="stat" title="Somme des prix conseillés des lots en stock"><div class="stat__icon" style="background:#ede9fe;color:#5b21b6"><i class="fa-solid fa-coins"></i></div><div><div class="stat__value">{{ $fmt($stockValue) }} TND</div><div class="stat__label">Valeur estimée du stock</div></div></div>
</div>

<div class="pd-grid">
  <div class="c-8"><div class="pd-card">
    <div class="pd-card__head">
      <h2><i class="fa-solid fa-chart-column text-success"></i> Production 12 mois + prévision (kg)</h2>
      <span class="tag {{ $forecast['trend'] === 'hausse' ? 'tag--active' : ($forecast['trend'] === 'baisse' ? 'tag--inactive' : 'tag--sold') }}" title="Tendance calculée par régression linéaire">
        <i class="fa-solid fa-arrow-trend-{{ $forecast['trend'] === 'baisse' ? 'down' : 'up' }}"></i> Tendance : {{ $forecast['trend'] }}
      </span>
    </div>
    @if($kpis['harvests'])
      <div class="chart-wrap"><canvas id="pd-monthly"></canvas></div>
    @else
      <div class="empty-state"><i class="fa-solid fa-chart-column d-block"></i><p>Déclarez votre première récolte pour voir votre production.</p></div>
    @endif
  </div></div>
  <div class="c-4"><div class="pd-card season">
    <div class="pd-card__head"><h2><i class="fa-solid fa-sun" style="color: var(--accent)"></i> Conseil de saison</h2><span class="tag tag--fallow">AgriBot</span></div>
    <p class="mb-3">{{ $seasonTip }}</p>
    <button type="button" class="btn btn-sm btn-outline-success" onclick="document.getElementById('chatbot-toggle').click()"><i class="fa-solid fa-robot"></i> Poser une question à AgriBot</button>
  </div></div>
</div>

<div class="pd-grid">
  <div class="c-4"><div class="pd-card">
    <div class="pd-card__head"><h2><i class="fa-solid fa-bell text-warning"></i> À traiter</h2><span class="tag tag--sold">{{ count($alerts) }}</span></div>
    @forelse($alerts as [$type, $icon, $text, $url])
      <a href="{{ $url }}" class="alert-line {{ $type }}"><i class="fa-solid {{ $icon }}"></i><span>{{ $text }}</span><i class="fa-solid fa-arrow-right"></i></a>
    @empty
      <div class="empty-state py-3"><i class="fa-solid fa-circle-check d-block"></i><p class="mb-0">Tout est à jour, bravo !</p></div>
    @endforelse
  </div></div>
  <div class="c-4"><div class="pd-card">
    <div class="pd-card__head"><h2><i class="fa-solid fa-clock-rotate-left text-success"></i> Dernières récoltes</h2><a href="{{ route('pro.harvests.index') }}">Tout voir →</a></div>
    @forelse($recent as $h)
      <a href="{{ route('pro.harvests.show', $h) }}" class="recent-item text-decoration-none">
<img src="{{ $h->displayImageUrl() }}" alt="" class="thumb">
        <div class="recent-item__meta">
          <strong>{{ $h->product_name }}</strong> <span class="grade grade--{{ $h->quality_grade }}" style="width:20px;height:20px;font-size:.7rem">{{ $h->quality_grade }}</span>
          <div class="text-muted small text-truncate">{{ $h->farm->name }} · {{ $h->harvest_date->format('d/m/Y') }}</div>
        </div>
        <span class="small fw-bold text-nowrap" style="color: var(--dark)">{{ $fmt($h->quantity, 1) }} {{ $h->unitShort() }}</span>
      </a>
    @empty
      <div class="empty-state py-3"><i class="fa-solid fa-basket-shopping d-block"></i><p class="mb-2">Aucune récolte pour l'instant.</p><a href="{{ route('pro.harvests.create') }}" class="btn btn-sm btn-success">Déclarer</a></div>
    @endforelse
  </div></div>
  <div class="c-4"><div class="pd-card">
    <div class="pd-card__head"><h2><i class="fa-solid fa-chart-pie text-success"></i> Statut de mes lots</h2></div>
    @if($kpis['harvests'])
      <div class="chart-wrap" style="height:220px"><canvas id="pd-status"></canvas></div>
    @else
      <div class="empty-state py-3"><i class="fa-solid fa-chart-pie d-block"></i><p class="mb-0">Aucun lot.</p></div>
    @endif
  </div></div>
</div>

<div class="pd-grid">
  <div class="c-6"><div class="pd-card">
    <div class="pd-card__head"><h2><i class="fa-solid fa-wheat-awn text-success"></i> Mes exploitations</h2><a href="{{ route('pro.farms.index') }}">Gérer →</a></div>
    @forelse($farms as $f)
      <a href="{{ route('pro.farms.show', $f) }}" class="farm-row text-decoration-none">
        <span class="farm-row__icon"><i class="fa-solid fa-tractor"></i></span>
        <div style="flex:1; min-width:0">
          <strong style="color: var(--dark)">{{ $f->name }}</strong>
          <div class="text-muted small">{{ $f->city }}, {{ $f->governorate }} · {{ $fmt($f->total_area, 1) }} ha</div>
        </div>
        <span class="tag tag--{{ $f->status }}">{{ $f->statusLabel() }}</span>
        <span class="small text-muted text-nowrap">{{ $f->harvests->count() }} lot(s)</span>
      </a>
    @empty
      <div class="empty-state py-3"><i class="fa-solid fa-seedling d-block"></i><p class="mb-2">Aucune exploitation.</p><a href="{{ route('pro.farms.create') }}" class="btn btn-sm btn-success">Créer</a></div>
    @endforelse
  </div></div>
  <div class="c-6"><div class="pd-card">
    <div class="pd-card__head"><h2><i class="fa-solid fa-bolt text-warning"></i> Raccourcis</h2></div>
    <div class="shortcut-grid">
      <a href="{{ route('pro.harvests.create') }}" class="shortcut"><i class="fa-solid fa-plus"></i>Déclarer une récolte</a>
      <a href="{{ route('pro.farms.create') }}" class="shortcut"><i class="fa-solid fa-map-location-dot"></i>Ajouter une exploitation</a>
      <a href="{{ route('frontend.products', ['mine' => 1]) }}" class="shortcut"><i class="fa-solid fa-store"></i>Mes produits au catalogue</a>
      <a href="{{ route('pro.harvests.export') }}" class="shortcut"><i class="fa-solid fa-file-csv"></i>Exporter mes récoltes</a>
    </div>
  </div></div>
</div>
@endsection

@push('scripts')
  @if($kpis['harvests'])
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
      (function () {
        const c = @json($chart);
        const real = c.kg.concat(c.forecast.map(() => null));
        // La prévision part du dernier mois réel pour une courbe continue
        const predicted = c.kg.map((v, i) => (i === c.kg.length - 1 ? v : null)).concat(c.forecast);
        new Chart(document.getElementById('pd-monthly'), {
          data: {
            labels: c.months.concat(c.forecastMonths),
            datasets: [
              { type: 'bar', label: 'Production réelle (kg)', data: real, backgroundColor: '#2D6A4F', borderRadius: 6, maxBarThickness: 32 },
              { type: 'line', label: 'Prévision (kg)', data: predicted, borderColor: '#7c3aed', backgroundColor: 'rgba(124,58,237,.12)', borderDash: [6, 5], pointRadius: 4, fill: true, tension: .3, spanGaps: false },
            ],
          },
          options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } }, scales: { y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,.05)' } }, x: { grid: { display: false } } } },
        });
        new Chart(document.getElementById('pd-status'), {
          type: 'doughnut',
          data: { labels: ['En stock', 'Expédié', 'Transformé', 'Vendu'], datasets: [{ data: Object.values(c.status), backgroundColor: ['#40916C', '#F4A261', '#6366F1', '#ADB5BD'] }] },
          options: { maintainAspectRatio: false, cutout: '60%', plugins: { legend: { position: 'bottom', labels: { boxWidth: 10 } } } },
        });
      })();
    </script>
  @endif
@endpush
