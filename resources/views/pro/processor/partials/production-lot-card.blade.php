{{-- Carte lot de production (similaire à recipe-card). Variables : $lot (ProductionLot) --}}
<article class="lot-card">
  <a href="{{ route('pro.processor.production-lots.show', $lot) }}" class="lot-card__visual" aria-label="Détails de {{ $lot->getProductName() }}">
    <i class="fa-solid fa-cube"></i>
    <span class="lot-card__status">{{ $lot->getStatusLabel() }}</span>
  </a>
  <div class="lot-card__body">
    <h3 class="lot-card__name">{{ $lot->getProductName() }}</h3>
    <div class="lot-card__desc">{{ Str::limit($lot->production_notes ?? 'Pas de notes', 80) }}</div>
    
    <div class="lot-card__line"><i class="fa-solid fa-calendar"></i><span>{{ $lot->production_date->format('d/m/Y') }}</span></div>
    <div class="lot-card__line"><i class="fa-solid fa-hourglass-end"></i><span>Péremption: <strong>{{ $lot->expiry_date->format('d/m/Y') }}</strong></span></div>
    <div class="lot-card__line"><i class="fa-solid fa-flask-vial"></i><span>Recette: <strong>{{ $lot->recipe->name }}</strong></span></div>
    <div class="lot-card__line"><i class="fa-solid fa-weight"></i><span>Quantité: <strong>{{ $lot->getQuantityDisplay() }}</strong></span></div>
    
    <div class="d-flex gap-1 flex-wrap mt-2">
      @if($lot->isExpired())
        <span class="tag" style="background: #f8d7da; color: #721c24;"><i class="fa-solid fa-exclamation-triangle"></i> Périmé</span>
      @elseif($lot->isCloseToExpiry())
        <span class="tag" style="background: #fff3cd; color: #856404;"><i class="fa-solid fa-hourglass-half"></i> À venir</span>
      @else
        <span class="tag tag--active"><i class="fa-solid fa-check-circle"></i> Valide</span>
      @endif
    </div>
  </div>
  <div class="lot-card__foot">
    <a href="{{ route('pro.processor.production-lots.show', $lot) }}" class="btn btn-sm btn-outline-success"><i class="fa-solid fa-eye"></i> Voir</a>
    <span class="d-flex gap-1">
      <a href="{{ route('pro.processor.production-lots.edit', $lot) }}" class="btn btn-sm btn-light" title="Éditer"><i class="fa-solid fa-pen"></i></a>
      <form method="POST" action="{{ route('pro.processor.production-lots.destroy', $lot) }}" onsubmit="return confirm('Supprimer le lot « {{ addslashes($lot->getProductName()) }} » ? Cette action est irréversible.');">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-sm btn-light text-danger" title="Supprimer"><i class="fa-solid fa-trash"></i></button>
      </form>
    </span>
  </div>
</article>
