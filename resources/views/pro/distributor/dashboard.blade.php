@extends('frontend.layouts.app')

@section('title', 'Dashboard carbone | Farm to Fork')

@section('extra-css')
  @include('pro.distributor.partials.styles')
@endsection

@php
  $kg = fn ($value, $decimals = 1) => number_format($value, $decimals, ',', ' ');
@endphp

@section('content')
<div class="distributor-page">
  @include('pro.distributor.partials.layout-start')

  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h2 class="h5 mb-0"><i class="fa-solid fa-chart-pie"></i> Empreinte carbone · {{ $stats['period_label'] }}</h2>
    <div class="btn-group btn-group-sm" role="group" aria-label="Période">
      @foreach($periods as $value => $label)
        <a href="{{ route('pro.distributor.dashboard', ['period' => $value]) }}"
           class="btn {{ $stats['period'] === $value ? 'btn-success' : 'btn-outline-success' }}">{{ $label }}</a>
      @endforeach
    </div>
  </div>

  <!-- Indicateurs -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-lg">
      <div class="card distributor-card h-100 text-center"><div class="card-body">
        <div class="stat-value text-success">{{ $kg($stats['total_co2_kg'], 2) }}</div>
        <div class="text-muted small">kg CO2e émis</div>
      </div></div>
    </div>
    <div class="col-6 col-lg">
      <div class="card distributor-card h-100 text-center"><div class="card-body">
        <div class="stat-value">{{ $stats['shipment_count'] }}</div>
        <div class="text-muted small">Expéditions ({{ $stats['delivered_count'] }} livrée(s))</div>
      </div></div>
    </div>
    <div class="col-6 col-lg">
      <div class="card distributor-card h-100 text-center"><div class="card-body">
        <div class="stat-value">{{ $kg($stats['total_distance_km']) }}</div>
        <div class="text-muted small">km parcourus</div>
      </div></div>
    </div>
    <div class="col-6 col-lg">
      <div class="card distributor-card h-100 text-center"><div class="card-body">
        <div class="stat-value">{{ $stats['co2_per_tonne'] !== null ? $kg($stats['co2_per_tonne'], 2) : '—' }}</div>
        <div class="text-muted small">kg CO2e par tonne livrée</div>
      </div></div>
    </div>
    <div class="col-12 col-lg">
      <div class="card distributor-card h-100 text-center"><div class="card-body">
        @if($stats['savings'] && $stats['savings']['legs'] > 0)
          <div class="stat-value text-success">{{ $kg($stats['savings']['co2_kg'], 2) }}</div>
          <div class="text-muted small">kg CO2e économisés grâce à {{ $stats['savings']['legs'] }} étape(s) recommandée(s) par l'IA, vs {{ $stats['savings']['reference'] }}</div>
        @else
          <div class="stat-value text-muted">—</div>
          <div class="text-muted small">CO2 économisé : appliquez une « Recommandation IA » lors de l'ajout d'une étape pour le mesurer</div>
        @endif
      </div></div>
    </div>
  </div>

  @if($stats['shipment_count'] === 0)
    <div class="alert alert-info">Aucune expédition sur cette période : les graphiques apparaîtront dès la première expédition.</div>
  @else
    <div class="row g-3">
      <div class="col-lg-5">
        <div class="card distributor-card h-100">
          <div class="card-header py-3"><i class="fa-solid fa-truck-fast"></i> CO2 par mode de transport</div>
          <div class="card-body">
            @if(count($stats['by_mode']) > 0)
              <canvas id="co2ByModeChart" height="260"></canvas>
            @else
              <div class="alert alert-info mb-0">Aucune étape de transport sur cette période.</div>
            @endif
          </div>
        </div>
      </div>
      <div class="col-lg-7">
        <div class="card distributor-card h-100">
          <div class="card-header py-3"><i class="fa-solid fa-chart-line"></i> CO2 par mois</div>
          <div class="card-body"><canvas id="co2ByMonthChart" height="260"></canvas></div>
        </div>
      </div>
    </div>
  @endif
</div>
</div>
@endsection

@section('extra-js')
  @if($stats['shipment_count'] > 0)
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
      (() => {
        const palette = ['#2d5a27', '#28a745', '#8fd19e', '#f4a261', '#e76f51', '#2a9d8f', '#264653', '#c9a227'];
        const kg = (value) => Number(value).toLocaleString('fr-FR', { maximumFractionDigits: 2 }) + ' kg CO2e';
        const byMode = @json($stats['by_mode']);
        const byMonth = @json($stats['by_month']);

        const modeCanvas = document.getElementById('co2ByModeChart');
        if (modeCanvas) {
          new Chart(modeCanvas, {
            type: 'doughnut',
            data: {
              labels: Object.keys(byMode),
              datasets: [{ data: Object.values(byMode), backgroundColor: palette }],
            },
            options: {
              plugins: {
                legend: { position: 'bottom' },
                tooltip: { callbacks: { label: (ctx) => `${ctx.label} : ${kg(ctx.parsed)}` } },
              },
            },
          });
        }

        new Chart(document.getElementById('co2ByMonthChart'), {
          type: 'line',
          data: {
            labels: Object.keys(byMonth),
            datasets: [{
              label: 'kg CO2e',
              data: Object.values(byMonth),
              borderColor: '#28a745',
              backgroundColor: 'rgba(40, 167, 69, 0.15)',
              fill: true,
              tension: 0.3,
              pointRadius: 4,
            }],
          },
          options: {
            scales: { y: { beginAtZero: true, title: { display: true, text: 'kg CO2e' } } },
            plugins: {
              legend: { display: false },
              tooltip: { callbacks: { label: (ctx) => kg(ctx.parsed.y) } },
            },
          },
        });
      })();
    </script>
  @endif
@endsection
