@extends('frontend.layouts.app')

@php($farm = $harvest->farm)

@section('title', $harvest->product_name . ' — Origine du lot ' . $harvest->batch_code . ' | Farm to Fork')
@section('description', 'Découvrez l\'origine de ce lot de ' . $harvest->product_name . ' cultivé à ' . $farm->city . ' (' . $farm->governorate . ').')

@section('extra-css')
  <link rel="stylesheet" href="{{ asset('frontend/css/producer.css') }}">
  @if($farm->hasLocation())
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
  @endif
  <style>
    .trace-hero { background: linear-gradient(135deg, var(--primary-dark), var(--primary)); color: #fff; padding: 40px 0 70px; background-size: cover; background-position: center; }
    .trace-hero.has-photo { padding: 90px 0 90px; }
    .trace-hero h1 { color: #fff; font-size: 2rem; margin: 6px 0; }
    .trace-hero .verified { display: inline-flex; gap: 6px; align-items: center; background: rgba(255,255,255,.15); padding: 5px 12px; border-radius: 999px; font-size: .85rem; font-weight: 700; }
    .trace-body { margin-top: -45px; padding-bottom: 60px; }
    .timeline { list-style: none; padding: 0; margin: 0; position: relative; }
    .timeline::before { content: ''; position: absolute; left: 15px; top: 6px; bottom: 6px; width: 2px; background: var(--sage); }
    .timeline li { position: relative; padding: 0 0 18px 46px; }
    .timeline li i { position: absolute; left: 0; top: 0; width: 32px; height: 32px; border-radius: 50%; background: var(--mint); color: var(--primary-dark); display: grid; place-items: center; font-size: .85rem; }
    .timeline strong { display: block; color: var(--dark); }
  </style>
@endsection

@section('content')
<section class="trace-hero has-photo"
  style="background-image: linear-gradient(135deg, rgba(27,67,50,.88), rgba(45,106,79,.55)), url('{{ $harvest->displayImageUrl() }}')">
  <div class="container">
    <span class="verified"><i class="fa-solid fa-shield-halved"></i> Origine vérifiée Farm to Fork</span>
    <h1>{{ $harvest->product_name }}{{ $harvest->variety ? ' · ' . $harvest->variety : '' }}</h1>
    <p class="mb-2 opacity-75">Lot {{ $harvest->batch_code }} — {{ $harvest->categoryLabel() }}</p>
    @if($fresh['active'])
      <span class="fresh-chip fresh-chip--{{ $fresh['level'] }}"><i class="fa-solid fa-leaf"></i> {{ $fresh['label'] }} · à consommer de préférence avant le {{ $fresh['best_before']->format('d/m/Y') }}</span>
    @endif
  </div>
</section>

<div class="container trace-body">
  <div class="row g-4">
    <div class="col-lg-7">
      @if($harvest->public_description)
        <div class="p-card">
          <h2 class="p-card__title"><i class="fa-solid fa-quote-left text-success"></i> Le mot du producteur</h2>
          <p class="mb-0" style="white-space: pre-line">{{ $harvest->public_description }}</p>
        </div>
      @endif
      <div class="p-card">
        <h2 class="p-card__title"><i class="fa-solid fa-route text-success"></i> Parcours du produit</h2>
        <ul class="timeline">
          @if($harvest->planting_date)
            <li><i class="fa-solid fa-seedling"></i><strong>Plantation</strong>{{ $harvest->planting_date->translatedFormat('d F Y') }}{{ $harvest->plot_name ? ' — ' . $harvest->plot_name : '' }}</li>
          @endif
          <li><i class="fa-solid fa-hand-holding-heart"></i><strong>Récolte</strong>{{ $harvest->harvest_date->translatedFormat('d F Y') }}{{ $harvest->growingDays() ? ' — après ' . $harvest->growingDays() . ' jours de culture' : '' }}</li>
          <li><i class="fa-solid fa-award"></i><strong>Qualité</strong>{{ \App\Models\Harvest::QUALITY_GRADES[$harvest->quality_grade] }}</li>
          @if($harvest->storage_conditions)
            <li><i class="fa-solid fa-warehouse"></i><strong>Conservation</strong>{{ $harvest->storage_conditions }}</li>
          @endif
          <li><i class="fa-solid fa-flask"></i><strong>Traitements</strong>{{ $harvest->treatments ?: 'Aucun traitement déclaré par le producteur' }}</li>
        </ul>
      </div>

      <div class="p-card">
        <h2 class="p-card__title"><i class="fa-solid fa-wheat-awn text-success"></i> L'exploitation</h2>
        <h3 class="h5 mb-1"><a href="{{ $farm->publicUrl() }}" style="color: var(--dark)">{{ $farm->name }}</a></h3>
        <p class="text-muted mb-2"><i class="fa-solid fa-location-dot"></i> {{ $farm->city }}, {{ $farm->governorate }} · {{ $farm->user->company_name }}</p>
        <div class="d-flex gap-2 flex-wrap mb-3">
          <span class="tag tag--{{ $farm->farming_type }}">Agriculture {{ \Illuminate\Support\Str::lower($farm->farmingTypeLabel()) }}</span>
          @if($farm->certification)<span class="tag tag--active"><i class="fa-solid fa-certificate"></i> {{ $farm->certification }}</span>@endif
          <span class="tag tag--sold"><i class="fa-solid fa-droplet"></i> {{ $farm->irrigationTypeLabel() }}</span>
        </div>
        @if($farm->description)
          <p style="white-space: pre-line">{{ \Illuminate\Support\Str::limit($farm->description, 320) }}</p>
        @endif
        <a href="{{ $farm->publicUrl() }}" class="btn btn-sm btn-success" style="padding:.45rem .9rem; font-size:.88rem; background: var(--primary); border-color: var(--primary)"><i class="fa-solid fa-tractor"></i> Découvrir l'exploitation</a>
      </div>
    </div>

    <div class="col-lg-5">
      @if($farm->hasLocation())
        <div class="p-card">
          <h2 class="p-card__title"><i class="fa-solid fa-map-location-dot text-success"></i> D'où vient ce produit ?</h2>
          <div id="trace-map" class="detail-map"></div>
        </div>
      @endif
      <div class="p-card text-center">
        <i class="fa-solid fa-leaf fa-2x text-success mb-2"></i>
        <p class="mb-0">Ce lot a été enregistré par son producteur sur <strong>Farm to Fork</strong>, la plateforme tunisienne de traçabilité de la ferme à l'assiette.</p>
      </div>
    </div>
  </div>

  @if($similar->isNotEmpty())
    <h2 class="p-card__title mt-5 mb-1" style="font-size:1.3rem"><i class="fa-solid fa-wand-magic-sparkles" style="color:#7c3aed"></i> Produits similaires recommandés</h2>
    <p class="text-muted small mb-3">Même catégorie, d'autres producteurs — priorité à la même région et au même mode de culture.</p>
    <div class="product-grid mb-2">
      @foreach($similar as $p)
        @include('frontend.partials.product-card', ['p' => $p, 'user' => auth()->user()])
      @endforeach
    </div>
  @endif

  @if($related->isNotEmpty())
    <h2 class="p-card__title mt-5 mb-3" style="font-size:1.3rem"><i class="fa-solid fa-basket-shopping text-success"></i> Autres produits de {{ $farm->name }}</h2>
    <div class="product-grid">
      @foreach($related as $p)
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
      const map = L.map('trace-map').setView([{{ $farm->latitude }}, {{ $farm->longitude }}], 10);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(map);
      L.marker([{{ $farm->latitude }}, {{ $farm->longitude }}]).addTo(map).bindPopup(@json($farm->name)).openPopup();
    </script>
  @endif
@endsection
