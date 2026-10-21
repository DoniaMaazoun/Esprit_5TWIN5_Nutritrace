@extends('pro.layouts.processor')

@section('title', 'Créer une Recette | Farm to Fork')

@section('processor-content')

<style>
  .recipe-form { max-width: 900px; }
  .form-section { margin-bottom: 2rem; padding-bottom: 2rem; border-bottom: 1px solid var(--gray-light); }
  .form-section:last-of-type { border-bottom: none; }
  .section-title { font-size: 1.1rem; font-weight: 700; color: var(--processor-primary); margin-bottom: 1rem; display: flex; align-items: center; gap: 8px; }
  .ingredient-item { background: #f0f9f4; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border-left: 4px solid var(--processor-primary); }
  .ingredient-grid { display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 8px; align-items: end; }
  .btn-remove-item { background: #dc3545; color: white; border: none; padding: 8px 12px; border-radius: 6px; cursor: pointer; font-size: .9rem; height: 40px; }
  .btn-remove-item:hover { background: #c82333; }
  .btn-add-item { background: var(--processor-primary); color: white; border: none; padding: 10px 16px; border-radius: 8px; cursor: pointer; font-weight: 600; margin-bottom: 1rem; gap: 6px; }
  .btn-add-item:hover { background: var(--processor-primary-dark); }
  .form-actions { display: flex; gap: 1rem; margin-top: 2rem; justify-content: center; }
  .btn-submit { background: var(--processor-primary); color: white; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 1rem; }
  .btn-submit:hover { background: var(--processor-primary-dark); }
  .btn-cancel { background: #6c757d; color: white; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; }
  .btn-cancel:hover { background: #5a6268; }
  .error-message { color: #dc3545; font-size: 0.85rem; margin-top: 4px; }
  .is-invalid { border-color: #dc3545 !important; }
</style>

<div class="page-head">
  <div>
    <a href="{{ route('pro.processor.recipes.index') }}" class="small"><i class="fa-solid fa-arrow-left"></i> Mes recettes</a>
    <h1><i class="fa-solid fa-plus"></i> Créer une Recette</h1>
    <p>Définissez une nouvelle formule de transformation avec ses ingrédients.</p>
  </div>
</div>

<div class="p-card recipe-form">
  @if ($errors->any())
    <div class="alert alert-danger" role="alert">
      <strong><i class="fa-solid fa-circle-exclamation"></i> Erreurs de validation:</strong>
      <ul class="mb-0">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form action="{{ route('pro.processor.recipes.store') }}" method="POST" id="recipeForm">
    @csrf

    <!-- Section: Infos générales -->
    <div class="form-section">
      <h2 class="section-title"><i class="fa-solid fa-info-circle"></i> Informations Générales</h2>

      <div class="mb-3">
        <label class="form-label" for="name">Nom de la Recette *</label>
        <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" 
          value="{{ old('name') }}" placeholder="Ex: Sauce Tomate Bio 1L" required>
        @error('name') <span class="error-message">{{ $message }}</span> @enderror
      </div>

      <div class="mb-3">
        <label class="form-label" for="description">Description</label>
        <textarea id="description" name="description" class="form-control @error('description') is-invalid @enderror" 
          rows="3" placeholder="Décrivez votre recette...">{{ old('description') }}</textarea>
        @error('description') <span class="error-message">{{ $message }}</span> @enderror
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label" for="yield_quantity">Quantité Produite *</label>
          <input type="number" id="yield_quantity" name="yield_quantity" class="form-control @error('yield_quantity') is-invalid @enderror" 
            step="0.01" value="{{ old('yield_quantity') }}" placeholder="500" required>
          @error('yield_quantity') <span class="error-message">{{ $message }}</span> @enderror
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label" for="yield_unit">Unité *</label>
          <select id="yield_unit" name="yield_unit" class="form-select @error('yield_unit') is-invalid @enderror" required>
            <option value="">-- Choisir --</option>
            <option value="kg" @selected(old('yield_unit') === 'kg')>Kilogrammes (kg)</option>
            <option value="g" @selected(old('yield_unit') === 'g')>Grammes (g)</option>
            <option value="quintal" @selected(old('yield_unit') === 'quintal')>Quintaux (q)</option>
            <option value="tonne" @selected(old('yield_unit') === 'tonne')>Tonnes (t)</option>
            <option value="L" @selected(old('yield_unit') === 'L')>Litres (L)</option>
            <option value="mL" @selected(old('yield_unit') === 'mL')>Millilitres (mL)</option>
            <option value="unités" @selected(old('yield_unit') === 'unités')>Unités</option>
          </select>
          @error('yield_unit') <span class="error-message">{{ $message }}</span> @enderror
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label" for="preparation_time">Temps de Préparation (min) *</label>
        <input type="number" id="preparation_time" name="preparation_time_minutes" class="form-control @error('preparation_time_minutes') is-invalid @enderror" 
          value="{{ old('preparation_time_minutes', 0) }}" min="0" required>
        @error('preparation_time_minutes') <span class="error-message">{{ $message }}</span> @enderror
      </div>

      <div class="mb-0">
        <label class="form-label" for="instructions">Instructions de Préparation</label>
        <textarea id="instructions" name="instructions" class="form-control @error('instructions') is-invalid @enderror" 
          rows="4" placeholder="Décrivez les étapes...">{{ old('instructions') }}</textarea>
        @error('instructions') <span class="error-message">{{ $message }}</span> @enderror
      </div>
    </div>

    <!-- Section: Ingrédients -->
    <div class="form-section">
      <h2 class="section-title"><i class="fa-solid fa-list"></i> Ingrédients *</h2>

      @if($receivedMaterials->isEmpty())
        <div class="alert alert-warning" role="alert">
          <i class="fa-solid fa-exclamation-triangle"></i>
          <strong>Aucune matière brute reçue</strong><br>
          Vous devez d'abord recevoir des matières brutes depuis <a href="{{ route('pro.processor.raw-materials.index') }}">Réception des Matières Brutes</a>.
        </div>
      @endif

      <div id="ingredientsContainer">
        @if (old('items'))
          @foreach (old('items') as $index => $item)
            <div class="ingredient-item">
              <div class="ingredient-grid">
                <div>
                  <select name="items[{{ $index }}][raw_material_name]" class="form-select" required>
                    <option value="">-- Sélectionner --</option>
                    @foreach($receivedMaterials as $material)
                      <option value="{{ $material->product_name }}" @selected($item['raw_material_name'] === $material->product_name)>
                        {{ $material->product_name }}
                      </option>
                    @endforeach
                  </select>
                </div>
                <div>
                  <input type="number" name="items[{{ $index }}][quantity_required]" class="form-control" step="0.01" value="{{ $item['quantity_required'] }}" placeholder="Qté" required>
                </div>
                <div>
                  <select name="items[{{ $index }}][unit]" class="form-select" required>
                    <option value="kg" @selected($item['unit'] === 'kg')>kg</option>
                    <option value="g" @selected($item['unit'] === 'g')>g</option>
                    <option value="quintal" @selected($item['unit'] === 'quintal')>q</option>
                    <option value="tonne" @selected($item['unit'] === 'tonne')>t</option>
                    <option value="L" @selected($item['unit'] === 'L')>L</option>
                    <option value="mL" @selected($item['unit'] === 'mL')>mL</option>
                    <option value="unités" @selected($item['unit'] === 'unités')>unités</option>
                  </select>
                </div>
                <button type="button" class="btn-remove-item" onclick="removeIngredient(this)" title="Supprimer">
                  <i class="fa-solid fa-trash"></i>
                </button>
              </div>
              <div style="margin-top: 8px;">
                <input type="text" name="items[{{ $index }}][notes]" class="form-control" placeholder="Notes (optionnel)" value="{{ $item['notes'] ?? '' }}">
              </div>
            </div>
          @endforeach
        @else
          <div class="ingredient-item">
            <div class="ingredient-grid">
              <div>
                <select name="items[0][raw_material_name]" class="form-select" required>
                  <option value="">-- Sélectionner --</option>
                  @foreach($receivedMaterials as $material)
                    <option value="{{ $material->product_name }}">{{ $material->product_name }}</option>
                  @endforeach
                </select>
              </div>
              <div>
                <input type="number" name="items[0][quantity_required]" class="form-control" step="0.01" placeholder="Qté" required>
              </div>
              <div>
                <select name="items[0][unit]" class="form-select" required>
                  <option value="kg">kg</option>
                  <option value="g">g</option>
                  <option value="quintal">q</option>
                  <option value="tonne">t</option>
                  <option value="L">L</option>
                  <option value="mL">mL</option>
                  <option value="unités">unités</option>
                </select>
              </div>
              <button type="button" class="btn-remove-item" onclick="removeIngredient(this)" title="Supprimer">
                <i class="fa-solid fa-trash"></i>
              </button>
            </div>
            <div style="margin-top: 8px;">
              <input type="text" name="items[0][notes]" class="form-control" placeholder="Notes (optionnel)">
            </div>
          </div>
        @endif
      </div>

      <button type="button" class="btn-add-item" onclick="addIngredient()" @disabled($receivedMaterials->isEmpty())>
        <i class="fa-solid fa-plus"></i> Ajouter un Ingrédient
      </button>
    </div>

    <!-- Actions -->
    <div class="form-actions">
      <button type="submit" class="btn-submit">
        <i class="fa-solid fa-save"></i> Créer
      </button>
      <a href="{{ route('pro.processor.recipes.index') }}" class="btn-cancel">
        <i class="fa-solid fa-times"></i> Annuler
      </a>
    </div>
  </form>
</div>

<script>
let ingredientCount = document.querySelectorAll('.ingredient-item').length;
const receivedMaterials = @json($receivedMaterials);

function addIngredient() {
  const container = document.getElementById('ingredientsContainer');
  let optionsHTML = '<option value="">-- Sélectionner --</option>';
  receivedMaterials.forEach(m => {
    optionsHTML += `<option value="${m.product_name}">${m.product_name}</option>`;
  });

  const div = document.createElement('div');
  div.className = 'ingredient-item';
  div.innerHTML = `
    <div class="ingredient-grid">
      <div>
        <select name="items[${ingredientCount}][raw_material_name]" class="form-select" required>${optionsHTML}</select>
      </div>
      <div>
        <input type="number" name="items[${ingredientCount}][quantity_required]" class="form-control" step="0.01" placeholder="Qté" required>
      </div>
      <div>
        <select name="items[${ingredientCount}][unit]" class="form-select" required>
          <option value="kg">kg</option>
          <option value="g">g</option>
          <option value="quintal">q</option>
          <option value="tonne">t</option>
          <option value="L">L</option>
          <option value="mL">mL</option>
          <option value="unités">unités</option>
        </select>
      </div>
      <button type="button" class="btn-remove-item" onclick="removeIngredient(this)" title="Supprimer">
        <i class="fa-solid fa-trash"></i>
      </button>
    </div>
    <div style="margin-top: 8px;">
      <input type="text" name="items[${ingredientCount}][notes]" class="form-control" placeholder="Notes (optionnel)">
    </div>
  `;
  container.appendChild(div);
  ingredientCount++;
}

function removeIngredient(btn) {
  if (document.querySelectorAll('.ingredient-item').length > 1) {
    btn.closest('.ingredient-item').remove();
  } else {
    alert('Au moins un ingrédient est obligatoire!');
  }
}
</script>

@endsection
