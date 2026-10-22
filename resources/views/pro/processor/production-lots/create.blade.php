@extends('pro.layouts.processor')

@section('title', 'Créer un Lot de Production | Farm to Fork')

@section('processor-content')

<style>
  .production-form { max-width: 900px; }
  .form-section { margin-bottom: 2rem; padding-bottom: 2rem; border-bottom: 1px solid var(--gray-light); }
  .form-section:last-of-type { border-bottom: none; }
  .section-title { font-size: 1.1rem; font-weight: 700; color: var(--processor-primary); margin-bottom: 1rem; display: flex; align-items: center; gap: 8px; }
  .recipe-info { background: #f0f9f4; padding: 1rem; border-radius: 8px; margin: 1rem 0; border-left: 4px solid var(--processor-primary); }
  .recipe-info-row { display: flex; justify-content: space-between; margin-bottom: 0.5rem; }
  .recipe-info-label { font-weight: 600; color: var(--processor-primary); }
  .ingredient-list { background: #f0f9f4; padding: 1rem; border-radius: 8px; margin: 1rem 0; border-left: 4px solid var(--processor-primary); }
  .ingredient-item { display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid #e0e0e0; }
  .ingredient-item:last-child { border-bottom: none; }
  .ingredient-name { font-weight: 600; color: var(--processor-primary); }
  .ingredient-qty { font-size: 0.9rem; color: #666; }
  .form-actions { display: flex; gap: 1rem; margin-top: 2rem; justify-content: center; }
  .btn-submit { background: var(--processor-primary); color: white; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 1rem; }
  .btn-submit:hover { background: var(--processor-primary-dark); }
  .btn-cancel { background: #6c757d; color: white; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; }
  .btn-cancel:hover { background: #5a6268; }
  .error-message { color: #dc3545; font-size: 0.85rem; margin-top: 4px; }
  .is-invalid { border-color: #dc3545 !important; }
  .alert { padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }
  .alert-warning { background: #fff8e1; border-left: 4px solid #ff9800; color: #666; }
</style>

<div class="page-head">
  <div>
    <a href="{{ route('pro.processor.production-lots.index') }}" class="small"><i class="fa-solid fa-arrow-left"></i> Mes Lots</a>
    <h1><i class="fa-solid fa-plus"></i> Créer un Lot de Production</h1>
    <p>Créez un nouveau lot fini. Le stock des matières brutes sera automatiquement déduit.</p>
  </div>
</div>

<div class="p-card production-form">
  @if ($errors->any())
    <div class="alert alert-danger">
      <strong><i class="fa-solid fa-circle-exclamation"></i> Erreurs de validation:</strong>
      <ul class="mb-0">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  @if(session('error'))
    <div class="alert alert-danger">
      <i class="fa-solid fa-exclamation-circle"></i> {{ session('error') }}
    </div>
  @endif

  @if($recipes->isEmpty())
    <div class="alert alert-warning">
      <i class="fa-solid fa-exclamation-triangle"></i>
      <strong>Aucune recette active</strong><br>
      Vous devez d'abord créer et activer une recette depuis <a href="{{ route('pro.processor.recipes.index') }}">Gestion des Recettes</a>.
    </div>
  @else

  <form action="{{ route('pro.processor.production-lots.store') }}" method="POST" id="productionForm">
    @csrf

    <!-- Section: Recette & Ingrédients -->
    <div class="form-section">
      <h2 class="section-title"><i class="fa-solid fa-flask"></i> Sélection de la Recette</h2>

      <div class="mb-3">
        <label class="form-label" for="recipe_id">Recette *</label>
        <select id="recipe_id" name="recipe_id" class="form-select @error('recipe_id') is-invalid @enderror" onchange="updateRecipeInfo()">
          <option value="">-- Choisir une Recette --</option>
          @foreach($recipes as $recipe)
            <option value="{{ $recipe->id }}" data-yield="{{ $recipe->yield_quantity }}" data-yield-unit="{{ $recipe->yield_unit }}" data-items="{{ json_encode($recipe->items) }}" data-has-items="{{ $recipe->items->count() > 0 ? 'yes' : 'no' }}">
              {{ $recipe->name }} (Rendement: {{ $recipe->getYieldDisplay() }})
            </option>
          @endforeach
        </select>
        @error('recipe_id') <span class="error-message">{{ $message }}</span> @enderror
      </div>

      <!-- Recipe Info Display -->
      <div id="recipeInfo" style="display: none;">
        <div class="recipe-info">
          <div class="recipe-info-row">
            <span class="recipe-info-label">Rendement:</span>
            <span id="recipeYield">-</span>
          </div>
          <div class="recipe-info-row">
            <span class="recipe-info-label">Ingrédients:</span>
            <span id="recipeItemCount">-</span>
          </div>
        </div>

        <div id="noIngredientsWarning" style="display: none; background: #fff8e1; border-left: 4px solid #ff9800; padding: 1rem; border-radius: 4px; margin: 1rem 0;">
          <i class="fa-solid fa-exclamation-triangle"></i>
          <strong>Attention:</strong> Cette recette n'a pas d'ingrédients associés. Veuillez <a href="{{ route('pro.processor.recipes.index') }}">ajouter des ingrédients à cette recette</a> avant de créer un lot.
        </div>

        <h3 class="section-title" style="margin-top: 1.5rem; display: none;" id="ingredientsTitle"><i class="fa-solid fa-list"></i> Ingrédients Requis</h3>
        <div id="ingredientsList" class="ingredient-list"></div>
      </div>
    </div>

    <!-- Section: Production Details -->
    <div class="form-section">
      <h2 class="section-title"><i class="fa-solid fa-calendar"></i> Détails de Production</h2>

      <div class="mb-3">
        <label class="form-label" for="quantity_produced">Quantité Produite *</label>
        <input type="number" id="quantity_produced" name="quantity_produced" class="form-control @error('quantity_produced') is-invalid @enderror" 
          step="0.01" value="{{ old('quantity_produced') }}" placeholder="Ex: 500">
        <small class="text-muted">Sera en même unité que le rendement de la recette</small>
        @error('quantity_produced') <span class="error-message">{{ $message }}</span> @enderror
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label" for="production_date">Date de Production *</label>
          <input type="date" id="production_date" name="production_date" class="form-control @error('production_date') is-invalid @enderror" 
            value="{{ old('production_date', today()->format('Y-m-d')) }}">
          @error('production_date') <span class="error-message">{{ $message }}</span> @enderror
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label" for="expiry_date">Date de Péremption *</label>
          <input type="date" id="expiry_date" name="expiry_date" class="form-control @error('expiry_date') is-invalid @enderror" 
            value="{{ old('expiry_date', today()->addDays(30)->format('Y-m-d')) }}">
          <small class="text-muted">Doit être après la date de production</small>
          @error('expiry_date') <span class="error-message">{{ $message }}</span> @enderror
        </div>
      </div>

      <div class="mb-0">
        <label class="form-label" for="production_notes">Notes de Production</label>
        <textarea id="production_notes" name="production_notes" class="form-control @error('production_notes') is-invalid @enderror" 
          rows="3" placeholder="Observations, conditions spéciales, etc.">{{ old('production_notes') }}</textarea>
        @error('production_notes') <span class="error-message">{{ $message }}</span> @enderror
      </div>
    </div>

    <!-- Actions -->
    <div class="form-actions">
      <button type="submit" class="btn-submit" id="submitBtn">
        <i class="fa-solid fa-save"></i> Créer Lot
      </button>
      <a href="{{ route('pro.processor.production-lots.index') }}" class="btn-cancel">
        <i class="fa-solid fa-times"></i> Annuler
      </a>
    </div>
  </form>

  @endif
</div>

<script>
const form = document.getElementById('productionForm');
const recipeSelect = document.getElementById('recipe_id');
const recipeInfoDiv = document.getElementById('recipeInfo');

// Validate form on submit
if (form) {
  form.addEventListener('submit', function(e) {
    if (!validateForm()) {
      e.preventDefault();
      return false;
    }
  });
}

function updateRecipeInfo() {
  const selectedOption = recipeSelect.options[recipeSelect.selectedIndex];
  
  if (!selectedOption.value) {
    recipeInfoDiv.style.display = 'none';
    return;
  }

  const items = JSON.parse(selectedOption.dataset.items);
  const yieldQty = selectedOption.dataset.yield;
  const yieldUnit = selectedOption.dataset.yieldUnit;
  const hasItems = selectedOption.dataset.hasItems === 'yes';

  document.getElementById('recipeYield').textContent = yieldQty + ' ' + yieldUnit;
  document.getElementById('recipeItemCount').textContent = items.length + ' ingrédient(s)';

  const noIngredientsWarning = document.getElementById('noIngredientsWarning');
  const ingredientsTitle = document.getElementById('ingredientsTitle');
  const ingredientsList = document.getElementById('ingredientsList');

  if (!hasItems) {
    noIngredientsWarning.style.display = 'block';
    ingredientsTitle.style.display = 'none';
    ingredientsList.innerHTML = '';
  } else {
    noIngredientsWarning.style.display = 'none';
    ingredientsTitle.style.display = 'block';
    
    let ingredientsHTML = '';
    items.forEach(item => {
      ingredientsHTML += `
        <div class="ingredient-item">
          <div class="ingredient-name">${item.raw_material_name}</div>
          <div class="ingredient-qty">${item.quantity_required} ${item.unit}</div>
        </div>
      `;
    });
    ingredientsList.innerHTML = ingredientsHTML;
  }

  recipeInfoDiv.style.display = 'block';
}

function validateForm() {
  document.querySelectorAll('.is-invalid').forEach(el => {
    if (!el.classList.contains('form-select')) el.classList.remove('is-invalid');
  });

  let isValid = true;

  const recipeId = document.getElementById('recipe_id').value;
  if (!recipeId) {
    document.getElementById('recipe_id').classList.add('is-invalid');
    isValid = false;
  }

  const quantity = parseFloat(document.getElementById('quantity_produced').value);
  if (!quantity || quantity <= 0) {
    document.getElementById('quantity_produced').classList.add('is-invalid');
    isValid = false;
  }

  const prodDate = document.getElementById('production_date').value;
  if (!prodDate) {
    document.getElementById('production_date').classList.add('is-invalid');
    isValid = false;
  }

  const expDate = document.getElementById('expiry_date').value;
  if (!expDate) {
    document.getElementById('expiry_date').classList.add('is-invalid');
    isValid = false;
  }

  if (prodDate && expDate && expDate < prodDate) {
    document.getElementById('expiry_date').classList.add('is-invalid');
    alert('La date de péremption doit être après la date de production');
    isValid = false;
  }

  return isValid;
}

if (recipeSelect.value) {
  updateRecipeInfo();
}
</script>

@endsection
