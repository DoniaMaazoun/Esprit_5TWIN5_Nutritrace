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
          value="{{ old('name') }}" placeholder="Ex: Sauce Tomate Bio 1L">
        @error('name') <span class="error-message">{{ $message }}</span> @enderror
      </div>

      <div class="mb-3">
        <label class="form-label" for="description">Description</label>
        <div style="display: flex; gap: 8px; align-items: flex-start;">
          <textarea id="description" name="description" class="form-control @error('description') is-invalid @enderror" 
            rows="3" placeholder="Décrivez votre recette..." style="flex: 1;">{{ old('description') }}</textarea>
          <button type="button" id="aiGenerateBtn" class="btn btn-outline-success" title="Générer avec l'IA" style="height: 80px; white-space: nowrap;">
            <i class="fa-solid fa-wand-magic-sparkles"></i> IA
          </button>
        </div>
        @error('description') <span class="error-message">{{ $message }}</span> @enderror
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label class="form-label" for="yield_quantity">Quantité Produite *</label>
          <input type="number" id="yield_quantity" name="yield_quantity" class="form-control @error('yield_quantity') is-invalid @enderror" 
            step="0.01" value="{{ old('yield_quantity') }}" placeholder="500">
          @error('yield_quantity') <span class="error-message">{{ $message }}</span> @enderror
        </div>
        <div class="col-md-6 mb-3">
          <label class="form-label" for="yield_unit">Unité *</label>
          <select id="yield_unit" name="yield_unit" class="form-select @error('yield_unit') is-invalid @enderror">
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
          value="{{ old('preparation_time_minutes', 0) }}">
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
                  <select name="items[{{ $index }}][raw_material_name]" class="form-select">
                    <option value="">-- Sélectionner --</option>
                    @foreach($receivedMaterials as $material)
                      <option value="{{ $material->product_name }}" @selected($item['raw_material_name'] === $material->product_name)>
                        {{ $material->product_name }}
                      </option>
                    @endforeach
                  </select>
                </div>
                <div>
                  <input type="number" name="items[{{ $index }}][quantity_required]" class="form-control" step="0.01" value="{{ $item['quantity_required'] }}" placeholder="Qté">
                </div>
                <div>
                  <select name="items[{{ $index }}][unit]" class="form-select">
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
                <select name="items[0][raw_material_name]" class="form-select">
                  <option value="">-- Sélectionner --</option>
                  @foreach($receivedMaterials as $material)
                    <option value="{{ $material->product_name }}">{{ $material->product_name }}</option>
                  @endforeach
                </select>
              </div>
              <div>
                <input type="number" name="items[0][quantity_required]" class="form-control" step="0.01" placeholder="Qté">
              </div>
              <div>
                <select name="items[0][unit]" class="form-select">
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
const form = document.getElementById('recipeForm');

// Validate form on submit
form.addEventListener('submit', function(e) {
  if (!validateForm()) {
    e.preventDefault();
    return false;
  }
});

function validateForm() {
  // Clear previous errors
  document.querySelectorAll('.form-error-highlight').forEach(el => el.classList.remove('form-error-highlight'));
  
  let isValid = true;
  
  // Validate name
  const name = document.getElementById('name').value.trim();
  if (!name) {
    showFieldError('name', 'Le nom de la recette est obligatoire');
    isValid = false;
  } else if (name.length > 100) {
    showFieldError('name', 'Le nom ne peut pas dépasser 100 caractères');
    isValid = false;
  }
  
  // Validate yield quantity
  const yieldQty = parseFloat(document.getElementById('yield_quantity').value);
  if (!yieldQty || yieldQty <= 0) {
    showFieldError('yield_quantity', 'La quantité produite doit être supérieure à 0');
    isValid = false;
  }
  
  // Validate yield unit
  const yieldUnit = document.getElementById('yield_unit').value;
  if (!yieldUnit) {
    showFieldError('yield_unit', 'L\'unité est obligatoire');
    isValid = false;
  }
  
  // Validate preparation time
  const prepTime = parseInt(document.getElementById('preparation_time').value);
  if (prepTime === null || prepTime === '' || prepTime < 0) {
    showFieldError('preparation_time', 'Le temps de préparation doit être 0 ou plus');
    isValid = false;
  }
  
  // Validate ingredients
  const ingredients = document.querySelectorAll('.ingredient-item');
  if (ingredients.length === 0) {
    alert('Au moins un ingrédient est obligatoire');
    isValid = false;
  } else {
    ingredients.forEach((ingredient, index) => {
      const materialSelect = ingredient.querySelector('select[name*="raw_material_name"]');
      const qtyInput = ingredient.querySelector('input[name*="quantity_required"]');
      const unitSelect = ingredient.querySelector('select[name*="unit"]');
      
      if (!materialSelect.value) {
        materialSelect.classList.add('is-invalid');
        isValid = false;
      }
      
      const qty = parseFloat(qtyInput.value);
      if (!qty || qty <= 0) {
        qtyInput.classList.add('is-invalid');
        isValid = false;
      }
      
      if (!unitSelect.value) {
        unitSelect.classList.add('is-invalid');
        isValid = false;
      }
    });
  }
  
  return isValid;
}

function showFieldError(fieldId, message) {
  const field = document.getElementById(fieldId);
  if (field) {
    field.classList.add('is-invalid');
    const errorDiv = field.parentElement.querySelector('.error-message');
    if (!errorDiv) {
      const error = document.createElement('span');
      error.className = 'error-message';
      error.textContent = message;
      field.parentElement.appendChild(error);
    } else {
      errorDiv.textContent = message;
    }
  }
}

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
        <select name="items[${ingredientCount}][raw_material_name]" class="form-select">${optionsHTML}</select>
      </div>
      <div>
        <input type="number" name="items[${ingredientCount}][quantity_required]" class="form-control" step="0.01" placeholder="Qté">
      </div>
      <div>
        <select name="items[${ingredientCount}][unit]" class="form-select">
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

// ========== AI Recipe Generation ==========
document.getElementById('aiGenerateBtn').addEventListener('click', async function() {
  const recipeName = document.getElementById('name').value.trim();
  const ingredients = extractIngredients();
  
  if (!recipeName) {
    alert('Veuillez d\'abord entrer un nom pour la recette.');
    return;
  }

  if (ingredients.length === 0) {
    alert('Veuillez d\'abord ajouter au moins un ingrédient.');
    return;
  }

  const btn = this;
  btn.disabled = true;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Génération intelligente...';

  try {
    const response = await fetch('{{ route("pro.processor.recipes.ai.generate") }}', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
      },
      body: JSON.stringify({ 
        recipe_name: recipeName,
        ingredients 
      })
    });

    if (!response.ok) throw new Error('Erreur API');
    
    const data = await response.json();
    
    // Remplir les champs
    document.getElementById('description').value = data.description || '';
    document.getElementById('instructions').value = data.instructions || '';
    
    // Afficher le résultat
    if (data.description && data.instructions) {
      alert(`✅ Recette générée intelligemment!\n\n📊 Éco-score estimé: ${data.eco_score}`);
    } else {
      alert('Contenu généré. Vérifiez et ajustez selon vos besoins.');
    }

  } catch (error) {
    console.error('AI Error:', error);
    alert('Erreur lors de la génération. Veuillez réessayer.');
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles"></i> IA';
  }
});

function extractIngredients() {
  const ingredients = [];
  document.querySelectorAll('.ingredient-item').forEach(item => {
    const nameSelect = item.querySelector('select[name*="[raw_material_name]"]');
    const qtyInput = item.querySelector('input[name*="[quantity_required]"]');
    const unitSelect = item.querySelector('select[name*="[unit]"]');
    
    if (nameSelect && nameSelect.value && qtyInput && qtyInput.value && unitSelect && unitSelect.value) {
      ingredients.push({
        name: nameSelect.value,
        quantity: parseFloat(qtyInput.value),
        unit: unitSelect.value
      });
    }
  });
  return ingredients;
}
</script>

@endsection
