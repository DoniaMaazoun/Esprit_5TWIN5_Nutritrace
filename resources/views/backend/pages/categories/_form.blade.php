{{-- Formulaire catégorie (création & modification). Variables : $category, $action --}}
@php
  use App\Models\Category;
  $err = fn ($f) => $errors->has($f) ? ' is-invalid' : '';
  $icon = old('icon', $category->icon);
  $color = old('color', $category->color);
@endphp

<form method="POST" action="{{ $action }}" data-validate>
  @csrf
  @if($category->exists) @method('PUT') @endif

  @if($errors->any())
    <div class="alert alert-danger py-2"><i class="mdi mdi-alert-circle"></i> {{ $errors->count() }} erreur(s) à corriger.</div>
  @endif
  <div class="alert alert-danger d-none js-form-alert py-2"></div>

  <div class="cat-preview mb-3">
    <span class="cat-preview__icon" id="cat-preview-icon" style="background: {{ $color }}"><i class="fa-solid {{ $icon }}"></i></span>
    <div>
      <strong id="cat-preview-name">{{ old('name', $category->name) ?: 'Nouvelle catégorie' }}</strong>
      <small class="d-block text-muted">Aperçu dans le catalogue</small>
    </div>
  </div>

  <div class="form-group">
    <label for="name">Nom <span class="req">*</span></label>
    <input type="text" id="name" name="name" value="{{ old('name', $category->name) }}" class="form-control{{ $err('name') }}"
           required minlength="2" maxlength="60" data-pattern="[\p{L}\s'\-]+" data-pattern-msg="Lettres, espaces, tirets et apostrophes uniquement." placeholder="Ex. : Fruits secs">
    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>

  <div class="form-group">
    <label for="description">Description</label>
    <textarea id="description" name="description" rows="2" maxlength="500" class="form-control{{ $err('description') }}" placeholder="Exemples de produits de cette catégorie…">{{ old('description', $category->description) }}</textarea>
    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>

  <div class="form-group">
    <label>Icône <span class="req">*</span></label>
    <div class="icon-picker">
      @foreach(Category::ICONS as $value => $label)
        <label class="icon-picker__item" title="{{ $label }}">
          <input type="radio" name="icon" value="{{ $value }}" @checked($icon === $value)>
          <span><i class="fa-solid {{ $value }}"></i></span>
        </label>
      @endforeach
    </div>
    @error('icon')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
  </div>

  <div class="form-row">
    <div class="form-group col-7">
      <label for="color">Couleur <span class="req">*</span></label>
      <div class="d-flex" style="gap:8px">
        <input type="color" id="color-picker" value="{{ $color }}" class="color-swatch" aria-label="Choisir la couleur">
        <input type="text" id="color" name="color" value="{{ $color }}" class="form-control{{ $err('color') }}" required maxlength="7"
               data-pattern="#[0-9A-Fa-f]{6}" data-pattern-msg="Format attendu : #RRGGBB (ex. : #2D6A4F).">
      </div>
      @error('color')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    <div class="form-group col-5">
      <label>Statut</label>
      <input type="hidden" name="is_active" value="0">
      <label class="switch-line"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active))> Active</label>
      @error('is_active')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
  </div>

  <div class="d-flex justify-content-between">
    @if($category->exists)
      <a href="{{ route('backend.categories.index') }}" class="btn btn-light">Annuler</a>
    @else
      <span></span>
    @endif
    <button type="submit" class="btn btn-success"><i class="mdi mdi-content-save"></i> {{ $category->exists ? 'Enregistrer' : 'Créer la catégorie' }}</button>
  </div>
</form>

@push('category-scripts')
<script>
  (function () {
    const name = document.getElementById('name');
    const color = document.getElementById('color');
    const picker = document.getElementById('color-picker');
    const icon = document.getElementById('cat-preview-icon');
    name.addEventListener('input', () => { document.getElementById('cat-preview-name').textContent = name.value || 'Nouvelle catégorie'; });
    picker.addEventListener('input', () => { color.value = picker.value.toUpperCase(); color.dispatchEvent(new Event('input')); });
    color.addEventListener('input', () => { if (/^#[0-9A-Fa-f]{6}$/.test(color.value)) { picker.value = color.value; icon.style.background = color.value; } });
    document.querySelectorAll('input[name=icon]').forEach((r) => r.addEventListener('change', () => { icon.innerHTML = `<i class="fa-solid ${r.value}"></i>`; }));
  })();
</script>
@endpush
