@extends('pro.layouts.producer')

@section('title', 'Mes exploitations | Farm to Fork')

@push('styles')
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
@endpush

@section('producer-content')
<div class="page-head">
  <div>
    <h1>Mes exploitations</h1>
    <p>Gérez vos fermes et parcelles : localisation, surfaces, pratiques culturales.</p>
  </div>
  <div class="page-head__actions">
    <a href="{{ route('pro.farms.export', request()->query()) }}" class="btn btn-outline-secondary"><i class="fa-solid fa-file-csv"></i> Exporter CSV</a>
    <a href="{{ route('pro.farms.create') }}" class="btn btn-success"><i class="fa-solid fa-plus"></i> Nouvelle exploitation</a>
  </div>
</div>

<div class="stat-grid">
  <div class="stat"><div class="stat__icon"><i class="fa-solid fa-wheat-awn"></i></div><div><div class="stat__value">{{ $stats['farms'] }}</div><div class="stat__label">Exploitations</div></div></div>
  <div class="stat"><div class="stat__icon"><i class="fa-solid fa-ruler-combined"></i></div><div><div class="stat__value">{{ number_format($stats['area'], 1, ',', ' ') }} ha</div><div class="stat__label">Surface totale</div></div></div>
  <div class="stat"><div class="stat__icon"><i class="fa-solid fa-basket-shopping"></i></div><div><div class="stat__value">{{ $stats['harvests'] }}</div><div class="stat__label">Récoltes enregistrées</div></div></div>
  <div class="stat"><div class="stat__icon"><i class="fa-solid fa-weight-hanging"></i></div><div><div class="stat__value">{{ number_format($stats['kg'] / 1000, 2, ',', ' ') }} t</div><div class="stat__label">Production totale</div></div></div>
</div>

@if($mapPoints->isNotEmpty())
  <div class="p-card mb-4">
    <h2 class="p-card__title"><i class="fa-solid fa-map-location-dot text-success"></i> Carte de mes exploitations</h2>
    <div id="farms-map" class="detail-map"></div>
  </div>
@endif

<form method="GET" action="{{ route('pro.farms.index') }}" class="p-card mb-4">
  <div class="filters">
    <div>
      <label class="form-label" for="q">Recherche</label>
      <input type="search" id="q" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="Nom, ville, culture…">
    </div>
    <div>
      <label class="form-label" for="f-governorate">Gouvernorat</label>
      <select id="f-governorate" name="governorate" class="form-select">
        <option value="">Tous</option>
        @foreach(\App\Models\Farm::GOVERNORATES as $g)
          <option value="{{ $g }}" @selected(($filters['governorate'] ?? '') === $g)>{{ $g }}</option>
        @endforeach
      </select>
    </div>
    <div>
      <label class="form-label" for="f-type">Agriculture</label>
      <select id="f-type" name="farming_type" class="form-select">
        <option value="">Toutes</option>
        @foreach(\App\Models\Farm::FARMING_TYPES as $k => $label)
          <option value="{{ $k }}" @selected(($filters['farming_type'] ?? '') === $k)>{{ $label }}</option>
        @endforeach
      </select>
    </div>
    <div>
      <label class="form-label" for="f-status">Statut</label>
      <select id="f-status" name="status" class="form-select">
        <option value="">Tous</option>
        @foreach(\App\Models\Farm::STATUSES as $k => $label)
          <option value="{{ $k }}" @selected(($filters['status'] ?? '') === $k)>{{ $label }}</option>
        @endforeach
      </select>
    </div>
    <div>
      <label class="form-label" for="f-sort">Trier par</label>
      <select id="f-sort" name="sort" class="form-select">
        <option value="recent" @selected(request('sort') === 'recent')>Plus récentes</option>
        <option value="name" @selected(request('sort') === 'name')>Nom (A→Z)</option>
        <option value="area_desc" @selected(request('sort') === 'area_desc')>Surface ↓</option>
        <option value="area_asc" @selected(request('sort') === 'area_asc')>Surface ↑</option>
      </select>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-success" type="submit"><i class="fa-solid fa-filter"></i> Filtrer</button>
      @if(array_filter($filters) || request('sort'))
        <a href="{{ route('pro.farms.index') }}" class="btn btn-light" title="Réinitialiser"><i class="fa-solid fa-rotate-left"></i></a>
      @endif
    </div>
  </div>
</form>

@if($farms->isEmpty())
  <div class="p-card empty-state">
    <i class="fa-solid fa-seedling d-block"></i>
    @if(array_filter($filters))
      <p>Aucune exploitation ne correspond à vos critères.</p>
      <a href="{{ route('pro.farms.index') }}" class="btn btn-outline-success">Réinitialiser les filtres</a>
    @else
      <p>Vous n'avez pas encore d'exploitation.</p>
      <a href="{{ route('pro.farms.create') }}" class="btn btn-success"><i class="fa-solid fa-plus"></i> Créer ma première exploitation</a>
    @endif
  </div>
@else
  <div class="farm-grid">
    @foreach($farms as $farm)
      <article class="farm-card">
        <div class="farm-card__cover" @if($farm->coverUrl()) style="background-image: linear-gradient(180deg, rgba(0,0,0,0) 40%, rgba(27,67,50,.55)), url('{{ $farm->coverUrl() }}')" @endif>
          @unless($farm->coverUrl())<i class="fa-solid fa-tractor"></i>@endunless
        </div>
        <div class="farm-card__top">
          <div class="d-flex gap-1 flex-wrap">
            <span class="tag tag--{{ $farm->farming_type }}">{{ $farm->farmingTypeLabel() }}</span>
            <span class="tag tag--{{ $farm->status }}">{{ $farm->statusLabel() }}</span>
          </div>
          <h3 class="farm-card__name">{{ $farm->name }}</h3>
          <div class="farm-card__loc"><i class="fa-solid fa-location-dot"></i> {{ $farm->city }}, {{ $farm->governorate }}</div>
        </div>
        <div class="farm-card__body">
          <div class="farm-card__facts">
            <div><span>Surface</span><br><strong>{{ number_format($farm->total_area, 2, ',', ' ') }} ha</strong></div>
            <div><span>Récoltes</span><br><strong>{{ $farm->harvests_count }}</strong></div>
            <div><span>Irrigation</span><br><strong>{{ $farm->irrigationTypeLabel() }}</strong></div>
            <div><span>Sol</span><br><strong>{{ $farm->soilTypeLabel() ?? '—' }}</strong></div>
          </div>
          @if($farm->main_crops)
            <p class="mt-2 mb-0 text-muted small"><i class="fa-solid fa-leaf"></i> {{ \Illuminate\Support\Str::limit($farm->main_crops, 70) }}</p>
          @endif
        </div>
        <div class="farm-card__foot">
          <a href="{{ route('pro.farms.show', $farm) }}" class="btn btn-sm btn-outline-success">Voir le détail <i class="fa-solid fa-arrow-right"></i></a>
          <div class="d-flex gap-1">
            <a href="{{ route('pro.farms.edit', $farm) }}" class="btn btn-sm btn-light" title="Modifier"><i class="fa-solid fa-pen"></i></a>
            <form method="POST" action="{{ route('pro.farms.destroy', $farm) }}"
                  data-confirm="Supprimer l'exploitation « {{ $farm->name }} »{{ $farm->harvests_count ? ' et ses ' . $farm->harvests_count . ' récolte(s)' : '' }} ? Cette action est irréversible.">
              @csrf @method('DELETE')
              <button type="submit" class="btn btn-sm btn-light text-danger" title="Supprimer"><i class="fa-solid fa-trash"></i></button>
            </form>
          </div>
        </div>
      </article>
    @endforeach
  </div>

  <div class="mt-4 d-flex justify-content-center">{{ $farms->links('pagination::bootstrap-5') }}</div>
@endif
@endsection

@push('scripts')
  @if($mapPoints->isNotEmpty())
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
    <script>
      (function () {
        const points = @json($mapPoints);
        const map = L.map('farms-map');
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(map);
        const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        const markers = points.map((p) => L.marker([p.lat, p.lng]).addTo(map)
          .bindPopup(`<strong>${esc(p.name)}</strong><br>${esc(p.info)}<br><a href="${p.url}">Voir l'exploitation</a>`));
        if (markers.length === 1) map.setView([points[0].lat, points[0].lng], 11);
        else map.fitBounds(L.featureGroup(markers).getBounds().pad(0.2));
      })();
    </script>
  @endif
@endpush
