@extends('frontend.layouts.app')

@section('title', $farm->name . ' — ' . $farm->city . ' | Farm to Fork')
@section('description', \Illuminate\Support\Str::limit($farm->description ?: "Découvrez {$farm->name}, exploitation agricole à {$farm->city} ({$farm->governorate}) et ses produits tracés.", 155))

@php($cover = $harvests->first(fn ($h) => $h->hasPhoto())?->imageUrl() ?? $harvests->first()?->displayImageUrl())

@section('extra-css')
  <link rel="stylesheet" href="{{ asset('frontend/css/producer.css') }}">
  @if($farm->hasLocation())
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
  @endif
  <style>
    .farm-hero { position: relative; color: #fff; padding: 70px 0 100px; background: linear-gradient(135deg, var(--primary-dark), var(--primary)); background-size: cover; background-position: center; }
    .farm-hero h1 { color: #fff; font-size: 2.4rem; margin: 10px 0 6px; }
    .farm-hero p { opacity: .9; margin: 0; }
    .farm-hero .chips { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 16px; }
    .farm-hero .chip { background: rgba(255,255,255,.16); border: 1px solid rgba(255,255,255,.25); padding: 5px 12px; border-radius: 999px; font-size: .82rem; font-weight: 600; backdrop-filter: blur(6px); }
    .farm-hero .crumb { font-size: .85rem; opacity: .8; } .farm-hero .crumb a { color: #fff; }
    .farm-body { margin-top: -60px; padding-bottom: 70px; position: relative; }
    .farm-producer { display: flex; align-items: center; gap: 12px; }
    .farm-producer__avatar { width: 52px; height: 52px; border-radius: 14px; background: var(--primary); color: #fff; font-weight: 800; display: grid; place-items: center; font-size: 1.1rem; }
    .farm-section-title { font-family: var(--font-heading); font-size: 1.35rem; margin: 34px 0 16px; display: flex; align-items: center; gap: 10px; }
    .cat-chip { --cat: var(--primary); display: inline-flex; gap: 6px; align-items: center; padding: 4px 11px; border-radius: 999px; background: #fff; border: 1px solid #e1ebe4; font-size: .8rem; font-weight: 600; }
    .cat-chip i { color: var(--cat); }
    .other-farm { display: flex; gap: 12px; align-items: center; padding: 12px 0; border-bottom: 1px solid var(--gray-light); color: var(--dark); }
    .other-farm:last-child { border-bottom: 0; }
    .other-farm:hover { color: var(--primary); }
    .other-farm__icon { width: 42px; height: 42px; border-radius: 12px; background: var(--mint); color: var(--primary-dark); display: grid; place-items: center; }
  </style>
@endsection

@section('content')
<section class="farm-hero" @if($cover) style="background-image: linear-gradient(135deg, rgba(27,67,50,.9), rgba(45,106,79,.55)), url('{{ $cover }}')" @endif>
  <div class="container">
    <div class="crumb"><a href="{{ route('frontend.products') }}">Produits</a> / Exploitations</div>
    <h1>{{ $farm->name }}</h1>
    <p><i class="fa-solid fa-location-dot"></i> {{ $farm->city }}, {{ $farm->governorate }} — {{ $farm->user->company_name }}</p>
    <div class="chips">
      <span class="chip"><i class="fa-solid fa-seedling"></i> Agriculture {{ \Illuminate\Support\Str::lower($farm->farmingTypeLabel()) }}</span>
      @if($farm->certification)<span class="chip"><i class="fa-solid fa-certificate"></i> {{ $farm->certification }}</span>@endif
      <span class="chip"><i class="fa-solid fa-droplet"></i> {{ $farm->irrigationTypeLabel() }}</span>
      <span class="chip"><i class="fa-solid fa-ruler-combined"></i> {{ number_format($farm->total_area, 1, ',', ' ') }} ha</span>
      @if($farm->established_year)<span class="chip"><i class="fa-solid fa-clock-rotate-left"></i> Depuis {{ $farm->established_year }}</span>@endif
    </div>
  </div>
</section>

<div class="container farm-body">
  <div class="stat-grid">
    <div class="stat"><div class="stat__icon"><i class="fa-solid fa-basket-shopping"></i></div><div><div class="stat__value">{{ $stats['products'] }}</div><div class="stat__label">Lots tracés</div></div></div>
    <div class="stat"><div class="stat__icon"><i class="fa-solid fa-weight-hanging"></i></div><div><div class="stat__value">{{ number_format($stats['kg'] / 1000, 1, ',', ' ') }} t</div><div class="stat__label">Production déclarée</div></div></div>
    <div class="stat"><div class="stat__icon"><i class="fa-solid fa-award"></i></div><div><div class="stat__value">{{ $stats['grade_a'] !== null ? $stats['grade_a'] . ' %' : '—' }}</div><div class="stat__label">Lots en qualité A</div></div></div>
    <div class="stat"><div class="stat__icon"><i class="fa-solid fa-tags"></i></div><div><div class="stat__value">{{ $stats['categories']->count() }}</div><div class="stat__label">Catégories cultivées</div></div></div>
  </div>

  <div class="row g-4">
    <div class="col-lg-7">
      <div class="p-card h-100">
        <h2 class="p-card__title"><i class="fa-solid fa-book-open text-success"></i> Présentation</h2>
        <p style="white-space: pre-line">{{ $farm->description ?: "{$farm->name} est une exploitation de {$farm->city} ({$farm->governorate}) qui déclare ses récoltes sur Farm to Fork pour garantir leur origine." }}</p>
        @if($farm->main_crops)
          <p class="mb-2"><strong>Cultures principales :</strong> {{ $farm->main_crops }}</p>
        @endif
        @if($stats['categories']->isNotEmpty())
          <div class="d-flex gap-2 flex-wrap mt-3">
            @foreach($stats['categories'] as $c)
              <a href="{{ route('frontend.products', ['category' => $c->id]) }}" class="cat-chip" style="--cat: {{ $c->color }}"><i class="fa-solid {{ $c->icon }}"></i> {{ $c->name }}</a>
            @endforeach
          </div>
        @endif
      </div>
    </div>
    <div class="col-lg-5">
      <div class="p-card">
        <h2 class="p-card__title"><i class="fa-solid fa-user-tie text-success"></i> Le producteur</h2>
        <div class="farm-producer">
          <span class="farm-producer__avatar">{{ collect(preg_split('/\s+/', trim($farm->user->company_name)))->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('') }}</span>
          <div><strong>{{ $farm->user->company_name }}</strong><div class="text-muted small">Producteur vérifié par Farm to Fork <i class="fa-solid fa-circle-check text-success"></i></div></div>
        </div>
        @if($otherFarms->isNotEmpty())
          <div class="mt-3">
            <div class="small text-muted fw-bold text-uppercase mb-1">Autres exploitations</div>
            @foreach($otherFarms as $o)
              <a href="{{ $o->publicUrl() }}" class="other-farm">
                <span class="other-farm__icon"><i class="fa-solid fa-tractor"></i></span>
                <span style="flex:1"><strong>{{ $o->name }}</strong><br><small class="text-muted">{{ $o->city }}, {{ $o->governorate }}</small></span>
                <small class="text-muted">{{ $o->harvests_count }} lot(s)</small>
              </a>
            @endforeach
          </div>
        @endif
      </div>
      @if($farm->hasLocation())
        <div class="p-card">
          <h2 class="p-card__title"><i class="fa-solid fa-map-location-dot text-success"></i> Localisation</h2>
          <div id="farm-map" class="detail-map"></div>
        </div>
      @endif
    </div>
  </div>

  <h2 class="farm-section-title"><i class="fa-solid fa-basket-shopping text-success"></i> Produits de l'exploitation</h2>
  @if($harvests->isEmpty())
    <div class="p-card empty-state"><i class="fa-solid fa-seedling d-block"></i><p class="mb-0">Aucun produit déclaré pour le moment.</p></div>
  @else
    <div class="product-grid">
      @foreach($harvests as $p)
        @include('frontend.partials.product-card', ['p' => $p, 'user' => auth()->user()])
      @endforeach
    </div>
  @endif
</div>
@endsection

@section('extra-js')
  @if($farm->hasLocation())
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
    <script>
      const map = L.map('farm-map').setView([{{ $farm->latitude }}, {{ $farm->longitude }}], 11);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(map);
      L.marker([{{ $farm->latitude }}, {{ $farm->longitude }}]).addTo(map).bindPopup(@json($farm->name)).openPopup();
    </script>
  @endif
@endsection
