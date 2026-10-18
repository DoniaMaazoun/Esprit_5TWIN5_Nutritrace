@extends('pro.layouts.producer')

@section('title', $farm->name . ' | Farm to Fork')

@push('styles')
  @if($farm->hasLocation())
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
  @endif
@endpush

@section('producer-content')
@php($totalKg = $farm->totalHarvestedKg())
<div class="page-head">
  <div>
    <a href="{{ route('pro.farms.index') }}" class="small"><i class="fa-solid fa-arrow-left"></i> Mes exploitations</a>
    <h1>{{ $farm->name }}</h1>
    <p>
      <i class="fa-solid fa-location-dot"></i> {{ $farm->city }}, {{ $farm->governorate }}
      &nbsp;<span class="tag tag--{{ $farm->farming_type }}">{{ $farm->farmingTypeLabel() }}</span>
      <span class="tag tag--{{ $farm->status }}">{{ $farm->statusLabel() }}</span>
    </p>
  </div>
  <div class="page-head__actions">
    @can('update', $farm)
      @if($farm->status === 'active')
        <a href="{{ route('pro.harvests.create', ['farm' => $farm->id]) }}" class="btn btn-success"><i class="fa-solid fa-plus"></i> Déclarer une récolte</a>
      @endif
      <a href="{{ $farm->publicUrl() }}" target="_blank" class="btn btn-outline-success"><i class="fa-solid fa-eye"></i> Vitrine publique</a>
      <a href="{{ route('pro.farms.edit', $farm) }}" class="btn btn-outline-secondary"><i class="fa-solid fa-pen"></i> Modifier</a>
      <form method="POST" action="{{ route('pro.farms.destroy', $farm) }}"
            data-confirm="Supprimer l'exploitation « {{ $farm->name }} »{{ $farm->harvests->count() ? ' et ses ' . $farm->harvests->count() . ' récolte(s)' : '' }} ? Cette action est irréversible.">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-outline-danger"><i class="fa-solid fa-trash"></i> Supprimer</button>
      </form>
    @else
      <span class="tag tag--sold"><i class="fa-solid fa-lock"></i> Exploitation de {{ $farm->user->company_name }} — lecture seule</span>
    @endcan
  </div>
</div>

<div class="stat-grid">
  <div class="stat"><div class="stat__icon"><i class="fa-solid fa-ruler-combined"></i></div><div><div class="stat__value">{{ number_format($farm->total_area, 2, ',', ' ') }} ha</div><div class="stat__label">Surface</div></div></div>
  <div class="stat"><div class="stat__icon"><i class="fa-solid fa-basket-shopping"></i></div><div><div class="stat__value">{{ $farm->harvests->count() }}</div><div class="stat__label">Récoltes</div></div></div>
  <div class="stat"><div class="stat__icon"><i class="fa-solid fa-weight-hanging"></i></div><div><div class="stat__value">{{ number_format($totalKg, 0, ',', ' ') }} kg</div><div class="stat__label">Production totale</div></div></div>
  <div class="stat"><div class="stat__icon"><i class="fa-solid fa-chart-line"></i></div><div><div class="stat__value">{{ $farm->yieldPerHectare() !== null ? number_format($farm->yieldPerHectare(), 0, ',', ' ') : '—' }}</div><div class="stat__label">Rendement (kg/ha)</div></div></div>
</div>

<div class="row g-4">
  <div class="col-lg-7">
    <div class="p-card">
      <h2 class="p-card__title"><i class="fa-solid fa-circle-info text-success"></i> Fiche de l'exploitation</h2>
      @if($farm->description)
        <p style="white-space: pre-line">{{ $farm->description }}</p>
      @endif
      <dl class="kv">
        <dt>Adresse</dt><dd>{{ $farm->address ?: '—' }}</dd>
        <dt>Irrigation</dt><dd>{{ $farm->irrigationTypeLabel() }}</dd>
        <dt>Type de sol</dt><dd>{{ $farm->soilTypeLabel() ?? '—' }}</dd>
        <dt>Cultures</dt><dd>{{ $farm->main_crops ?: '—' }}</dd>
        <dt>Certification</dt><dd>{{ $farm->certification ?: '—' }}</dd>
        <dt>Création</dt><dd>{{ $farm->established_year ?: '—' }}</dd>
        <dt>Coordonnées GPS</dt><dd>{{ $farm->hasLocation() ? $farm->latitude . ', ' . $farm->longitude : 'Non renseignées' }}</dd>
      </dl>
    </div>

    <div class="p-card">
      <h2 class="p-card__title"><i class="fa-solid fa-chart-column text-success"></i> Production des 12 derniers mois (kg)</h2>
      @if($farm->harvests->isEmpty())
        <p class="text-muted mb-0">Aucune récolte enregistrée pour le moment.</p>
      @else
        <canvas id="monthly-chart" height="120"></canvas>
      @endif
    </div>
  </div>

  <div class="col-lg-5">
    @if($farm->hasLocation())
      <div class="p-card">
        <h2 class="p-card__title"><i class="fa-solid fa-map-location-dot text-success"></i> Localisation</h2>
        <div id="farm-map" class="detail-map"></div>
      </div>
    @endif

    @can('update', $farm)
      <div class="p-card" id="insights">
        <h2 class="p-card__title justify-content-between">
          <span><i class="fa-solid fa-brain" style="color:#7c3aed"></i> Analyse & recommandations</span>
          <button type="button" class="btn btn-sm ai-btn" id="run-insights"><i class="fa-solid fa-wand-magic-sparkles"></i> Analyser</button>
        </h2>
        <div id="insights-content" class="text-muted small">Cliquez sur « Analyser » pour obtenir un diagnostic de l'exploitation (rendement, qualité, eau, sol) et des recommandations personnalisées.</div>
      </div>
    @endcan

    @if($chart['products']->isNotEmpty())
      <div class="p-card">
        <h2 class="p-card__title"><i class="fa-solid fa-chart-pie text-success"></i> Répartition par produit</h2>
        <div style="position: relative; height: 260px"><canvas id="product-chart"></canvas></div>
      </div>
    @endif
  </div>
</div>

<div class="p-card mt-4">
  <h2 class="p-card__title justify-content-between">
    <span><i class="fa-solid fa-basket-shopping text-success"></i> Récoltes de l'exploitation</span>
    @if($farm->harvests->isNotEmpty() && auth()->user()->can('update', $farm))
      <a href="{{ route('pro.harvests.index', ['farm_id' => $farm->id]) }}" class="btn btn-sm btn-light">Tout voir dans « Récoltes »</a>
    @endif
  </h2>
  @if($farm->harvests->isEmpty())
    <div class="empty-state">
      <i class="fa-solid fa-basket-shopping d-block"></i>
      <p>Aucune récolte déclarée sur cette exploitation.</p>
    </div>
  @else
    <div class="table-responsive">
      <table class="p-table">
        <thead><tr><th>Lot</th><th>Produit</th><th>Parcelle</th><th>Récolte</th><th>Quantité</th><th>Qualité</th><th>Statut</th><th></th></tr></thead>
        <tbody>
          @foreach($farm->harvests->take(10) as $h)
            <tr>
              <td><code>{{ $h->batch_code }}</code></td>
              <td><strong>{{ $h->product_name }}</strong>@if($h->variety)<br><small class="text-muted">{{ $h->variety }}</small>@endif</td>
              <td>{{ $h->plot_name ?: '—' }}</td>
              <td>{{ $h->harvest_date->format('d/m/Y') }}</td>
              <td>{{ number_format($h->quantity, 2, ',', ' ') }} {{ $h->unitShort() }}</td>
              <td><span class="grade grade--{{ $h->quality_grade }}">{{ $h->quality_grade }}</span></td>
              <td><span class="tag tag--{{ $h->status }}">{{ $h->statusLabel() }}</span></td>
              <td class="text-end"><a href="{{ route('pro.harvests.show', $h) }}" class="btn btn-sm btn-light"><i class="fa-solid fa-qrcode"></i></a></td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>
@endsection

@push('scripts')
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  @if($farm->hasLocation())
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
  @endif
  <script>
    (function () {
      const chart = @json($chart);
      const green = '#2D6A4F';

      const monthly = document.getElementById('monthly-chart');
      if (monthly) {
        new Chart(monthly, {
          type: 'bar',
          data: { labels: chart.months, datasets: [{ label: 'kg récoltés', data: chart.monthly, backgroundColor: green, borderRadius: 6 }] },
          options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } },
        });
      }

      const products = document.getElementById('product-chart');
      if (products) {
        new Chart(products, {
          type: 'doughnut',
          data: { labels: chart.products, datasets: [{ data: chart.productKg, backgroundColor: ['#2D6A4F', '#F4A261', '#40916C', '#E76F51', '#95D5B2', '#7c3aed'] }] },
          options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } },
        });
      }

      @if($farm->hasLocation())
        const map = L.map('farm-map').setView([{{ $farm->latitude }}, {{ $farm->longitude }}], 12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(map);
        L.marker([{{ $farm->latitude }}, {{ $farm->longitude }}]).addTo(map).bindPopup(@json($farm->name)).openPopup();
      @endif

      // Analyse IA de l'exploitation
      const btn = document.getElementById('run-insights');
      const box = document.getElementById('insights-content');
      if (btn) btn.addEventListener('click', async () => {
        btn.disabled = true;
        box.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Analyse en cours…';
        try {
          const res = await fetch(@json(route('pro.ai.farm-insights', $farm)), { headers: { Accept: 'application/json' } });
          if (!res.ok) throw new Error('Erreur ' + res.status);
          const data = await res.json();
          const list = data.recommendations.map((r) => `<li>${AGRI.renderRich(r)}</li>`).join('');
          box.classList.remove('text-muted', 'small');
          box.innerHTML = `<div class="insights__summary">${AGRI.renderRich(data.summary)}</div>
            <h3 class="h6 mt-3 mb-0">Points de vigilance</h3><ul class="insights__list">${list}</ul>
            <div class="ai-source mt-2">${data.source === 'ai' ? '<i class="fa-solid fa-wand-magic-sparkles"></i> Synthèse rédigée par l\'IA' : '<i class="fa-solid fa-microchip"></i> Analyse du moteur de règles local'}</div>`;
        } catch (e) {
          box.innerHTML = `<span class="text-danger">Analyse impossible : ${e.message}</span>`;
        } finally {
          btn.disabled = false;
        }
      });
    })();
  </script>
@endpush
