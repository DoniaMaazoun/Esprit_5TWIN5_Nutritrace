{{-- Carte recette (similaire à product-card). Variables : $recipe (Recipe) --}}
<article class="recipe-card">
  <a href="{{ route('pro.processor.recipes.show', $recipe) }}" class="recipe-card__visual" aria-label="Détails de {{ $recipe->name }}">
    <i class="fa-solid fa-flask-vial"></i>
    <span class="recipe-card__status">{{ $recipe->is_active ? 'Actif' : 'Inactif' }}</span>
  </a>
  <div class="recipe-card__body">
    <h3 class="recipe-card__name">{{ $recipe->name }}</h3>
    <div class="recipe-card__desc">{{ Str::limit($recipe->description ?? 'Pas de description', 80) }}</div>
    
    <div class="recipe-card__line"><i class="fa-solid fa-cubes"></i><span><strong>{{ $recipe->itemsCount() }}</strong> ingrédient{{ $recipe->itemsCount() !== 1 ? 's' : '' }}</span></div>
    <div class="recipe-card__line"><i class="fa-solid fa-hourglass-half"></i><span>{{ $recipe->preparation_time_minutes }} min</span></div>
    <div class="recipe-card__line"><i class="fa-solid fa-box"></i><span>Rendement: <strong>{{ $recipe->getYieldDisplay() }}</strong></span></div>
    
    <div class="d-flex gap-1 flex-wrap mt-2">
      @if($recipe->is_active)
        <span class="tag tag--active"><i class="fa-solid fa-check-circle"></i> Actif</span>
      @else
        <span class="tag" style="background: #e2e3e5; color: #495057;"><i class="fa-solid fa-pause-circle"></i> Inactif</span>
      @endif
    </div>
  </div>
  <div class="recipe-card__foot">
    <a href="{{ route('pro.processor.recipes.show', $recipe) }}" class="btn btn-sm btn-outline-success"><i class="fa-solid fa-eye"></i> Voir</a>
    <span class="d-flex gap-1">
      <a href="{{ route('pro.processor.recipes.edit', $recipe) }}" class="btn btn-sm btn-light" title="Éditer"><i class="fa-solid fa-pen"></i></a>
      <form method="POST" action="{{ route('pro.processor.recipes.destroy', $recipe) }}" onsubmit="return confirm('Supprimer la recette « {{ addslashes($recipe->name) }} » ? Cette action est irréversible.');">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-sm btn-light text-danger" title="Supprimer"><i class="fa-solid fa-trash"></i></button>
      </form>
    </span>
  </div>
</article>
