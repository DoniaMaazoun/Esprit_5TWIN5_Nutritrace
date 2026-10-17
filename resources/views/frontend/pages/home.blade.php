@extends('frontend.layouts.app')

@section('title', 'Accueil - Farm to Fork | Traçabilité Alimentaire')
@section('description', 'De la ferme à l\'assiette : tracez vos produits en toute transparence. Origine, exploitation, date de récolte et qualité de chaque lot.')

@php($lookupError = $errors->getBag('lookup')->first('code'))

@section('extra-css')
  <link rel="stylesheet" href="{{ asset('frontend/css/producer.css') }}">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
  <link rel="stylesheet" href="{{ asset('frontend/css/home.css') }}">
@endsection

@section('content')

{{-- ================= HERO ================= --}}
<section class="h-hero">
  <div class="h-hero__blob h-hero__blob--1"></div>
  <div class="h-hero__blob h-hero__blob--2"></div>
  <div class="container h-hero__grid">
    <div class="h-hero__content">
      <span class="h-eyebrow"><i class="fa-solid fa-shield-halved"></i> Traçabilité alimentaire 100 % tunisienne</span>
      <h1>De la ferme à l'assiette, <span>chaque produit raconte son origine.</span></h1>
      <p class="h-hero__lead">Exploitation, parcelle, date de récolte, qualité : retrouvez en un scan le parcours réel des produits déclarés par nos producteurs vérifiés.</p>

      <form method="GET" action="{{ route('traceability.lookup') }}" class="lot-search" novalidate>
        <div class="lot-search__box {{ $lookupError ? 'is-invalid' : '' }}">
          <i class="fa-solid fa-qrcode"></i>
          <input type="text" name="code" value="{{ old('code') }}" placeholder="Entrez un code de lot : LOT-AAAAMMJJ-XXXXX" maxlength="20" autocomplete="off" aria-label="Code du lot" data-lot-input class="{{ $lookupError ? 'is-invalid' : '' }}">
          <button type="submit"><span class="d-none d-sm-inline">Tracer</span> <i class="fa-solid fa-arrow-right"></i></button>
        </div>
        <small class="lot-search__error" @unless($lookupError) hidden @endunless>{{ $lookupError }}</small>
        @if($sampleCode)
          <small class="lot-search__hint">Essayez avec un lot réel : <button type="button" data-sample="{{ $sampleCode }}">{{ $sampleCode }}</button></small>
        @endif
      </form>

      <div class="h-hero__actions">
        <a href="{{ route('frontend.products') }}" class="h-btn h-btn--primary"><i class="fa-solid fa-basket-shopping"></i> Explorer les produits</a>
        <a href="{{ route('farms.directory') }}" class="h-btn h-btn--ghost"><i class="fa-solid fa-map-location-dot"></i> Voir les exploitations</a>
      </div>
    </div>

    <div class="h-hero__visual">
      <div class="h-hero__photo" style="background-image: url('{{ asset('frontend/images/hero-product.jpg') }}')"></div>
      @if($latest->first())
        @php($lp = $latest->first())
        <a href="{{ route('traceability.show', $lp->batch_code) }}" class="h-float h-float--product">
          <img src="{{ $lp->displayImageUrl() }}" alt="">
          <div><small>Dernier lot tracé</small><strong>{{ $lp->product_name }}</strong><span><i class="fa-solid fa-location-dot"></i> {{ $lp->farm->city }}, {{ $lp->farm->governorate }}</span></div>
        </a>
      @endif
      <div class="h-float h-float--verified"><i class="fa-solid fa-circle-check"></i><div><strong>{{ $stats['producers'] }} producteurs</strong><small>vérifiés par notre équipe</small></div></div>
      <div class="h-float h-float--qr"><i class="fa-solid fa-qrcode"></i><small>1 lot = 1 QR code</small></div>
    </div>
  </div>

  <div class="container">
    <div class="h-trust">
      <div><strong data-count="{{ $stats['products'] }}">{{ $stats['products'] }}</strong><span>lots tracés</span></div>
      <div><strong data-count="{{ $stats['producers'] }}">{{ $stats['producers'] }}</strong><span>producteurs vérifiés</span></div>
      <div><strong data-count="{{ $stats['farms'] }}">{{ $stats['farms'] }}</strong><span>exploitations</span></div>
      <div><strong data-count="{{ $stats['governorates'] }}">{{ $stats['governorates'] }}</strong><span>gouvernorats couverts</span></div>
    </div>
  </div>
</section>

{{-- ================= CATÉGORIES ================= --}}
@if($categories->isNotEmpty())
<section class="h-section h-section--tight">
  <div class="container">
    <div class="h-head h-head--row">
      <div><span class="h-label">Catalogue</span><h2>Parcourir par catégorie</h2></div>
      <a href="{{ route('frontend.products') }}" class="h-link">Tout le catalogue <i class="fa-solid fa-arrow-right"></i></a>
    </div>
    <div class="h-cats">
      @foreach($categories as $c)
        <a href="{{ route('frontend.products', ['category' => $c->id]) }}" class="h-cat" style="--cat: {{ $c->color }}">
          <span class="h-cat__img" style="background-image: url('{{ $c->illustrationUrl() }}')"></span>
          <span class="h-cat__body"><strong><i class="fa-solid {{ $c->icon }}"></i> {{ $c->name }}</strong><small>{{ $c->harvests_count }} produit(s)</small></span>
        </a>
      @endforeach
    </div>
  </div>
</section>
@endif

{{-- ================= DERNIERS PRODUITS ================= --}}
<section class="h-section" id="derniers-produits">
  <div class="container">
    <div class="h-head h-head--row">
      <div><span class="h-label">Nouveautés</span><h2>Derniers produits tracés</h2><p>Déclarés récemment par nos producteurs, avec leur origine complète.</p></div>
      <a href="{{ route('frontend.products') }}" class="h-link">Voir tout <i class="fa-solid fa-arrow-right"></i></a>
    </div>
    @if($latest->isNotEmpty())
      <div class="product-grid">
        @foreach($latest as $p)
          @include('frontend.partials.product-card', ['p' => $p, 'user' => auth()->user()])
        @endforeach
      </div>
    @else
      <p class="text-center text-muted">Les premiers produits arrivent bientôt.</p>
    @endif
  </div>
</section>

{{-- ================= COMMENT ÇA MARCHE ================= --}}
<section class="h-section h-section--mint">
  <div class="container">
    <div class="h-head"><span class="h-label">Fonctionnement</span><h2>Une traçabilité en 3 étapes</h2><p>Du champ jusqu'à votre assiette, chaque maillon est enregistré.</p></div>
    <div class="h-steps">
      <div class="h-step">
        <span class="h-step__num">01</span>
        <span class="h-step__icon"><i class="fa-solid fa-tractor"></i></span>
        <h3>Le producteur déclare</h3>
        <p>Il enregistre son exploitation, puis chaque récolte : parcelle, dates, quantité, qualité, traitements et photo.</p>
      </div>
      <div class="h-step">
        <span class="h-step__num">02</span>
        <span class="h-step__icon"><i class="fa-solid fa-qrcode"></i></span>
        <h3>Un QR code est généré</h3>
        <p>Chaque lot reçoit un code unique et un QR code à imprimer sur l'emballage, vérifié par notre plateforme.</p>
      </div>
      <div class="h-step">
        <span class="h-step__num">03</span>
        <span class="h-step__icon"><i class="fa-solid fa-mobile-screen"></i></span>
        <h3>Vous scannez et découvrez</h3>
        <p>Origine, carte de l'exploitation, date de récolte, qualité : toute l'histoire du produit en quelques secondes.</p>
      </div>
    </div>
  </div>
</section>

{{-- ================= CARTE ================= --}}
@if($mapPoints->isNotEmpty())
<section class="h-section">
  <div class="container h-map">
    <div class="h-map__text">
      <span class="h-label">Partout en Tunisie</span>
      <h2>Nos exploitations sur la carte</h2>
      <p>Du Cap Bon aux oasis de Tozeur, découvrez où sont cultivés les produits que vous consommez.</p>
      <ul class="h-map__list">
        @foreach($featuredFarms as $f)
          <li><a href="{{ $f->publicUrl() }}"><i class="fa-solid fa-location-dot"></i> <strong>{{ $f->name }}</strong> <small>{{ $f->city }}, {{ $f->governorate }}</small></a></li>
        @endforeach
      </ul>
      <a href="{{ route('farms.directory') }}" class="h-btn h-btn--primary"><i class="fa-solid fa-wheat-awn"></i> Toutes les exploitations</a>
    </div>
    <div id="home-map" class="h-map__canvas" aria-label="Carte des exploitations"></div>
  </div>
</section>
@endif

{{-- ================= EXPLOITATIONS À L'HONNEUR ================= --}}
@if($featuredFarms->isNotEmpty())
<section class="h-section h-section--cream">
  <div class="container">
    <div class="h-head h-head--row">
      <div><span class="h-label">Nos producteurs</span><h2>Exploitations à l'honneur</h2></div>
      <a href="{{ route('farms.directory') }}" class="h-link">Annuaire complet <i class="fa-solid fa-arrow-right"></i></a>
    </div>
    <div class="spot-grid">
      @foreach($featuredFarms as $f)
        @php($cover = $f->coverUrl() ?? $f->harvests->first()?->displayImageUrl() ?? asset('frontend/images/categories/default.svg'))
        <a href="{{ $f->publicUrl() }}" class="spot-card">
          <div class="spot-card__cover" style="background-image: linear-gradient(180deg, rgba(0,0,0,0) 35%, rgba(27,67,50,.82)), url('{{ $cover }}')">
            <span class="spot-card__type">{{ $f->farmingTypeLabel() }}</span>
            <div class="spot-card__title"><strong>{{ $f->name }}</strong><small><i class="fa-solid fa-location-dot"></i> {{ $f->city }}, {{ $f->governorate }}</small></div>
          </div>
          <div class="spot-card__body">
            <div><strong>{{ $f->harvests_count }}</strong><small>lots</small></div>
            <div><strong>{{ number_format($f->total_area, 0, ',', ' ') }}</strong><small>ha</small></div>
            <div><strong>{{ $f->harvests->pluck('category_id')->unique()->count() }}</strong><small>catégories</small></div>
          </div>
          <div class="spot-card__foot">{{ $f->user->company_name }} <span>Découvrir <i class="fa-solid fa-arrow-right"></i></span></div>
        </a>
      @endforeach
    </div>
  </div>
</section>
@endif

{{-- ================= POUR QUI ================= --}}
<section class="h-section">
  <div class="container">
    <div class="h-head"><span class="h-label">Pour qui ?</span><h2>Une plateforme, deux engagements</h2></div>
    <div class="h-audience">
      <div class="h-aud h-aud--consumer">
        <span class="h-aud__icon"><i class="fa-solid fa-basket-shopping"></i></span>
        <h3>Vous êtes consommateur</h3>
        <ul>
          <li><i class="fa-solid fa-check"></i> Connaître l'origine exacte de vos fruits et légumes</li>
          <li><i class="fa-solid fa-check"></i> Vérifier la qualité, la date de récolte et les traitements</li>
          <li><i class="fa-solid fa-check"></i> Soutenir les producteurs locaux et transparents</li>
        </ul>
        <a href="{{ route('frontend.products') }}" class="h-btn h-btn--ghost">Explorer le catalogue <i class="fa-solid fa-arrow-right"></i></a>
      </div>
      <div class="h-aud h-aud--producer">
        <span class="h-aud__icon"><i class="fa-solid fa-tractor"></i></span>
        <h3>Vous êtes producteur</h3>
        <ul>
          <li><i class="fa-solid fa-check"></i> Déclarer vos récoltes et générer vos QR codes</li>
          <li><i class="fa-solid fa-check"></i> Suivre rendements, stock et qualité sur votre tableau de bord</li>
          <li><i class="fa-solid fa-check"></i> Recevoir les conseils de saison de l'assistant AgriBot</li>
        </ul>
        @auth
          <a href="{{ auth()->user()->isProducer() ? route('pro.dashboard') : route('frontend.products') }}" class="h-btn h-btn--white">{{ auth()->user()->isProducer() ? 'Mon tableau de bord' : 'Découvrir' }} <i class="fa-solid fa-arrow-right"></i></a>
        @else
          <a href="{{ route('register') }}" class="h-btn h-btn--white">Créer un compte producteur <i class="fa-solid fa-arrow-right"></i></a>
        @endauth
      </div>
    </div>
  </div>
</section>

{{-- ================= TÉMOIGNAGES ================= --}}
<section class="h-section h-section--cream">
  <div class="container">
    <div class="h-head"><span class="h-label">Témoignages</span><h2>Ils nous font confiance</h2></div>
    <div class="h-testis">
      @foreach([
        ['Je scanne le QR code au marché et je vois la ferme, la parcelle et la date de récolte. C\'est rassurant de savoir d\'où vient ce que je mange.', 'Sarra M.', 'Consommatrice, Tunis', 'SM'],
        ['Déclarer mes récoltes me prend deux minutes. Mes clients voient enfin le travail derrière mes olives et ma certification bio est mise en avant.', 'Hichem B.', 'Oléiculteur, Sfax', 'HB'],
        ['Le tableau de bord m\'aide à suivre mon stock et mes rendements. Les conseils de saison d\'AgriBot sont vraiment adaptés à notre région.', 'Amel K.', 'Maraîchère, Nabeul', 'AK'],
      ] as [$text, $name, $role, $ini])
        <figure class="h-testi">
          <div class="h-testi__stars">@for($i = 0; $i < 5; $i++)<i class="fa-solid fa-star"></i>@endfor</div>
          <blockquote>« {{ $text }} »</blockquote>
          <figcaption><span>{{ $ini }}</span><div><strong>{{ $name }}</strong><small>{{ $role }}</small></div></figcaption>
        </figure>
      @endforeach
    </div>
  </div>
</section>

{{-- ================= FAQ ================= --}}
<section class="h-section">
  <div class="container h-faq">
    <div>
      <span class="h-label">Questions fréquentes</span>
      <h2>Tout savoir sur la traçabilité</h2>
      <p>Une autre question ? <a href="{{ route('frontend.contact') }}">Contactez-nous</a>.</p>
    </div>
    <div class="accordion" id="faq">
      @foreach([
        ['Comment retrouver l\'origine d\'un produit ?', 'Scannez le QR code de l\'emballage ou saisissez le code du lot (LOT-AAAAMMJJ-XXXXX) dans le champ « Tracer un lot ». Vous accédez à l\'exploitation, la parcelle, la date de récolte, la qualité et la carte.'],
        ['Les informations sont-elles vérifiées ?', 'Chaque compte professionnel est validé par notre équipe avant de pouvoir déclarer des produits. Les déclarations sont contrôlées automatiquement (dates cohérentes, surfaces, traitements interdits en bio…).'],
        ['Je suis producteur, comment rejoindre Farm to Fork ?', 'Créez un compte professionnel en choisissant « Producteur ». Après validation, vous pourrez ajouter vos exploitations, déclarer vos récoltes et imprimer vos QR codes.'],
        ['Que signifient les qualités A, B et C ?', 'A correspond à la catégorie Extra, B à la catégorie I et C à la catégorie II, selon le calibre et l\'aspect des produits déclarés par le producteur.'],
      ] as $i => [$q, $a])
        <div class="accordion-item">
          <h3 class="accordion-header">
            <button class="accordion-button {{ $i ? 'collapsed' : '' }}" type="button" data-bs-toggle="collapse" data-bs-target="#faq-{{ $i }}" aria-expanded="{{ $i ? 'false' : 'true' }}" aria-controls="faq-{{ $i }}">{{ $q }}</button>
          </h3>
          <div id="faq-{{ $i }}" class="accordion-collapse collapse {{ $i ? '' : 'show' }}" data-bs-parent="#faq">
            <div class="accordion-body">{{ $a }}</div>
          </div>
        </div>
      @endforeach
    </div>
  </div>
</section>

{{-- ================= CTA ================= --}}
<section class="h-cta">
  <div class="container h-cta__inner">
    <div>
      <h2>Prêt à consommer en toute transparence ?</h2>
      <p>Rejoignez les consommateurs et producteurs qui font confiance à Farm to Fork.</p>
    </div>
    <div class="h-cta__actions">
      @guest
        <a href="{{ route('register') }}" class="h-btn h-btn--white"><i class="fa-solid fa-user-plus"></i> Créer un compte gratuit</a>
      @endguest
      <a href="{{ route('frontend.products') }}" class="h-btn h-btn--outline-white"><i class="fa-solid fa-basket-shopping"></i> Voir les produits</a>
    </div>
  </div>
</section>

@endsection

@section('extra-js')
  @if($mapPoints->isNotEmpty())
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
  @endif
  <script>
    (function () {
      // Exemple de code de lot
      document.querySelectorAll('[data-sample]').forEach((b) => b.addEventListener('click', () => {
        const input = document.querySelector('.lot-search [data-lot-input]');
        input.value = b.dataset.sample;
        input.dispatchEvent(new Event('input'));
        input.focus();
      }));

      // Compteurs animés de la bande de confiance
      const counters = document.querySelectorAll('.h-trust [data-count]');
      const animate = (el) => {
        const target = +el.dataset.count; const start = performance.now();
        const tick = (t) => { const p = Math.min((t - start) / 900, 1); el.textContent = Math.round(target * (1 - Math.pow(1 - p, 3))); if (p < 1) requestAnimationFrame(tick); };
        requestAnimationFrame(tick);
        // Les animations sont suspendues dans un onglet masqué : on garantit la valeur finale
        setTimeout(() => { el.textContent = target; }, 1000);
      };
      if ('IntersectionObserver' in window) {
        const io = new IntersectionObserver((entries) => entries.forEach((e) => { if (e.isIntersecting) { animate(e.target); io.unobserve(e.target); } }));
        counters.forEach((c) => io.observe(c));
      }

      @if($mapPoints->isNotEmpty())
        const points = @json($mapPoints);
        const esc = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        const map = L.map('home-map', { scrollWheelZoom: false });
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(map);
        const markers = points.map((p) => L.marker([p.lat, p.lng]).addTo(map).bindPopup(`<strong>${esc(p.name)}</strong><br>${esc(p.info)}<br><a href="${p.url}">Voir l'exploitation</a>`));
        if (markers.length === 1) map.setView([points[0].lat, points[0].lng], 9);
        else map.fitBounds(L.featureGroup(markers).getBounds().pad(0.3));
      @endif
    })();
  </script>
@endsection
