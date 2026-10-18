@extends('frontend.layouts.app')

@section('title', 'Exploitations partenaires | Farm to Fork')
@section('description', 'Découvrez les exploitations agricoles tunisiennes partenaires de Farm to Fork et leurs produits tracés.')

@php
  $points = $farms->filter->hasLocation()->map(fn ($f) => [
      'name' => $f->name, 'lat' => $f->latitude, 'lng' => $f->longitude, 'url' => $f->publicUrl(),
      'info' => "{$f->city}, {$f->governorate} · {$f->harvests_count} lot(s)",
  ])->values();
  $governorates = $farms->pluck('governorate')->unique()->sort()->values();
@endphp

@section('extra-css')
  <link rel="stylesheet" href="{{ asset('frontend/css/producer.css') }}">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
  <style>
    .dir-hero { background: linear-gradient(135deg, var(--primary-dark), var(--primary)); color: #fff; padding: 46px 0 90px; }
    .dir-hero h1 { color: #fff; font-size: 2.2rem; margin-bottom: 6px; }
    .dir-hero p { opacity: .88; max-width: 620px; margin: 0; }
    .dir-body { margin-top: -60px; padding-bottom: 70px; }
    #dir-map { height: 380px; border-radius: 16px; z-index: 1; box-shadow: var(--shadow-lg); }
    .dir-filters { display: flex; gap: 8px; flex-wrap: wrap; margin: 24px 0 18px; }
    .dir-filters a { padding: 7px 14px; border-radius: 999px; background: #fff; border: 1px solid #dfe9e2; color: var(--gray-dark); font-weight: 600; font-size: .86rem; }
    .dir-filters a.active, .dir-filters a:hover { background: var(--primary); border-color: var(--primary); color: #fff; }
    .dir-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 22px; }
    .dir-card { background: #fff; border-radius: 18px; overflow: hidden; box-shadow: var(--shadow-sm); border: 1px solid rgba(45,106,79,.08); color: var(--dark); display: flex; flex-direction: column; transition: transform .25s, box-shadow .25s; }
    .dir-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-lg); color: var(--dark); }
    .dir-card__cover { height: 170px; background-size: cover; background-position: center; position: relative; }
    .dir-card__cover span { position: absolute; top: 12px; left: 12px; }
    .dir-card__body { padding: 16px 18px; flex: 1; }
    .dir-card__body h2 { font-size: 1.15rem; margin: 0 0 4px; }
    .dir-card__body p { color: var(--gray); font-size: .86rem; margin: 0 0 10px; }
    .dir-card__cats { display: flex; gap: 6px; flex-wrap: wrap; }
    .dir-card__cats span { --cat: var(--primary); font-size: .74rem; font-weight: 600; padding: 3px 9px; border-radius: 999px; background: var(--gray-light); }
    .dir-card__cats i { color: var(--cat); }
    .dir-card__foot { display: flex; justify-content: space-between; padding: 12px 18px; border-top: 1px solid var(--gray-light); font-size: .85rem; color: var(--gray); }
    .dir-card__foot strong { color: var(--primary); }
  </style>
@endsection

@section('content')
<section class="dir-hero">
  <div class="container">
    <h1><i class="fa-solid fa-wheat-awn"></i> Exploitations partenaires</h1>
    <p>{{ $farms->count() }} exploitation(s) de producteurs vérifiés, réparties dans {{ $governorates->count() }} gouvernorat(s) de Tunisie.</p>
  </div>
</section>

<div class="container dir-body">
  @if($points->isNotEmpty())
    <div id="dir-map" aria-label="Carte des exploitations"></div>
  @endif

  <nav class="dir-filters" aria-label="Filtrer par gouvernorat">
    <a href="{{ route('farms.directory') }}" class="{{ $governorate ? '' : 'active' }}">Toutes les régions</a>
    @foreach(\App\Models\Farm::whereHas('user', fn ($u) => $u->where('status', 'approved'))->distinct()->orderBy('governorate')->pluck('governorate') as $g)
      <a href="{{ route('farms.directory', ['governorate' => $g]) }}" class="{{ $governorate === $g ? 'active' : '' }}">{{ $g }}</a>
    @endforeach
  </nav>

  @if($farms->isEmpty())
    <div class="p-card empty-state"><i class="fa-solid fa-seedling d-block"></i><p class="mb-0">Aucune exploitation dans cette région pour le moment.</p></div>
  @else
    <div class="dir-grid">
      @foreach($farms as $f)
        @php($cover = $f->coverUrl() ?? $f->harvests->first()?->displayImageUrl() ?? asset('frontend/images/categories/default.svg'))
        <a href="{{ $f->publicUrl() }}" class="dir-card">
          <div class="dir-card__cover" style="background-image: url('{{ $cover }}')"><span class="tag tag--{{ $f->farming_type }}">{{ $f->farmingTypeLabel() }}</span></div>
          <div class="dir-card__body">
            <h2>{{ $f->name }}</h2>
            <p><i class="fa-solid fa-location-dot"></i> {{ $f->city }}, {{ $f->governorate }} · {{ $f->user->company_name }}</p>
            <div class="dir-card__cats">
              @foreach($f->harvests->pluck('category')->filter()->unique('id')->take(4) as $c)
                <span style="--cat: {{ $c->color }}"><i class="fa-solid {{ $c->icon }}"></i> {{ $c->name }}</span>
              @endforeach
            </div>
          </div>
          <div class="dir-card__foot"><span>{{ $f->harvests_count }} lot(s) · {{ number_format($f->total_area, 0, ',', ' ') }} ha</span><strong>Découvrir <i class="fa-solid fa-arrow-right"></i></strong></div>
        </a>
      @endforeach
    </div>
  @endif
</div>
@endsection

@section('extra-js')
  @if($points->isNotEmpty())
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
    <script>
      (function () {
        const points = @json($points);
        const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        const map = L.map('dir-map', { scrollWheelZoom: false });
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(map);
        const markers = points.map((p) => L.marker([p.lat, p.lng]).addTo(map).bindPopup(`<strong>${esc(p.name)}</strong><br>${esc(p.info)}<br><a href="${p.url}">Voir l'exploitation</a>`));
        if (markers.length === 1) map.setView([points[0].lat, points[0].lng], 10);
        else map.fitBounds(L.featureGroup(markers).getBounds().pad(0.25));
      })();
    </script>
  @endif
@endsection
