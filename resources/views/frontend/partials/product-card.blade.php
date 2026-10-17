{{-- Carte produit (catalogue, accueil). Variables : $p (Harvest avec farm.user et category), $user (ou null) --}}
@php
  $farm = $p->farm;
  $isMine = $user && $farm->user_id === $user->id;
  $canEdit = $user?->can('update', $p);
  $editUrl = $user?->isAdmin() ? route('backend.products.edit', $p) : route('pro.harvests.edit', $p);
  $deleteUrl = $user?->isAdmin() ? route('backend.products.destroy', $p) : route('pro.harvests.destroy', $p);
@endphp
<article class="product-card {{ $isMine ? 'is-mine' : '' }}">
  <a href="{{ route('traceability.show', $p->batch_code) }}" class="product-card__visual" aria-label="Origine de {{ $p->product_name }}">
    @if($isMine)<span class="mine-badge"><i class="fa-solid fa-user-check"></i> Mon produit</span>@endif
    <img src="{{ $p->displayImageUrl() }}" alt="{{ $p->product_name }}" loading="lazy">
    @unless($p->hasPhoto())<span class="product-card__illus" title="Illustration de la catégorie">Illustration</span>@endunless
    <span class="grade grade--{{ $p->quality_grade }}" title="Qualité {{ $p->quality_grade }}">{{ $p->quality_grade }}</span>
    <span class="product-card__cat" style="--cat: {{ $p->category?->color ?? '#2D6A4F' }}"><i class="fa-solid {{ $p->categoryIcon() }}"></i> {{ $p->categoryLabel() }}</span>
  </a>
  <div class="product-card__body">
    <h3 class="product-card__name">{{ $p->product_name }}</h3>
    <div class="product-card__meta">{{ $p->variety ?: $p->categoryLabel() }}</div>
    <div class="product-card__line"><i class="fa-solid fa-wheat-awn"></i><a href="{{ $farm->publicUrl() }}" class="product-card__farm"><strong>{{ $farm->name }}</strong></a></div>
    <div class="product-card__line"><i class="fa-solid fa-location-dot"></i><span>{{ $farm->city }}, {{ $farm->governorate }}</span></div>
    <div class="product-card__line"><i class="fa-solid fa-user"></i><span>{{ $farm->user->company_name }}</span></div>
    <div class="product-card__line"><i class="fa-solid fa-calendar-check"></i><span>Récolté le {{ $p->harvest_date->format('d/m/Y') }}</span></div>
    <div class="d-flex gap-1 flex-wrap mt-2">
      <span class="tag tag--{{ $farm->farming_type }}">{{ $farm->farmingTypeLabel() }}</span>
      <span class="tag tag--{{ $p->status }}">{{ $p->statusLabel() }}</span>
      @php($fresh = app(\App\Services\ProductIntelligence::class)->freshness($p))
      @if($fresh['active'])
        <span class="fresh-chip fresh-chip--{{ $fresh['level'] }}" title="À consommer de préférence avant le {{ $fresh['best_before']->format('d/m/Y') }}"><i class="fa-solid fa-leaf"></i> {{ $fresh['percent'] }} %</span>
      @endif
    </div>
  </div>
  <div class="product-card__foot">
    <a href="{{ route('traceability.show', $p->batch_code) }}" class="btn btn-sm btn-outline-success"><i class="fa-solid fa-route"></i> Voir l'origine</a>
    @if($canEdit)
      <span class="d-flex gap-1">
        <a href="{{ $editUrl }}" class="btn btn-sm btn-light" title="Modifier"><i class="fa-solid fa-pen"></i></a>
        <form method="POST" action="{{ $deleteUrl }}" onsubmit="return confirm('Supprimer le lot {{ $p->batch_code }} ({{ addslashes($p->product_name) }}) ? Cette action est irréversible.');">
          @csrf @method('DELETE')
          <button type="submit" class="btn btn-sm btn-light text-danger" title="Supprimer"><i class="fa-solid fa-trash"></i></button>
        </form>
      </span>
    @elseif($user?->isProducer())
      <span class="readonly-hint" title="Produit d'un autre producteur"><i class="fa-solid fa-lock"></i> Lecture seule</span>
    @else
      <span class="product-card__qty">{{ number_format($p->quantity, 1, ',', ' ') }} {{ $p->unitShort() }}</span>
    @endif
  </div>
</article>
