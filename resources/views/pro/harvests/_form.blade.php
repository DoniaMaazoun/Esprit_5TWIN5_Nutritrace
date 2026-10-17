@php
  use App\Models\Harvest;
  $err = fn ($f) => $errors->has($f) ? ' is-invalid' : '';
  $date = fn ($f) => old($f, optional($harvest->{$f})->format('Y-m-d'));
@endphp

<form method="POST" action="{{ $action }}" data-validate class="p-card" enctype="multipart/form-data">
  @csrf
  @if($harvest->exists) @method('PUT') @endif

  @if($errors->any())
    <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i> Le formulaire contient {{ $errors->count() }} erreur(s). Corrigez les champs en rouge.</div>
  @endif
  <div class="alert alert-danger d-none js-form-alert"></div>

  {{-- Origine --}}
  <div class="form-section">
    <div class="form-section__title"><i class="fa-solid fa-location-dot"></i> Origine</div>
    <div class="row g-3">
      <div class="col-md-6">
        <label for="farm_id" class="form-label">Exploitation <span class="req">*</span></label>
        <select id="farm_id" name="farm_id" class="form-select{{ $err('farm_id') }}" required>
          <option value="">— Choisir —</option>
          @foreach($farms as $f)
            <option value="{{ $f->id }}" data-area="{{ $f->total_area }}" @selected((int) old('farm_id', $harvest->farm_id) === $f->id)>
              {{ $f->name }} — {{ $f->city }} ({{ $f->total_area }} ha){{ $f->status !== 'active' ? ' · ' . $f->statusLabel() : '' }}
            </option>
          @endforeach
        </select>
        @error('farm_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
      <div class="col-md-3">
        <label for="plot_name" class="form-label">Parcelle</label>
        <input type="text" id="plot_name" name="plot_name" value="{{ old('plot_name', $harvest->plot_name) }}" class="form-control{{ $err('plot_name') }}" maxlength="100" placeholder="Ex. : Parcelle Nord">
        @error('plot_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
      <div class="col-md-3">
        <label for="plot_area" class="form-label">Surface parcelle (ha)</label>
        <input type="number" id="plot_area" name="plot_area" value="{{ old('plot_area', $harvest->plot_area) }}" class="form-control{{ $err('plot_area') }}"
               step="0.01" min="0.01" data-rules="lte-farm-area:farm_id" data-min-msg="La surface de la parcelle doit être supérieure à 0 ha.">
        @error('plot_area')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
    </div>
  </div>

  {{-- Produit --}}
  <div class="form-section">
    <div class="form-section__title"><i class="fa-solid fa-carrot"></i> Produit récolté</div>
    <div class="row g-3">
      <div class="col-md-4 order-md-last">
        @include('shared.image-upload', ['current' => $harvest->imageUrl()])
      </div>
      <div class="col-md-8">
      <div class="row g-3">
      <div class="col-md-6">
        <label for="product_name" class="form-label">Produit <span class="req">*</span></label>
        <input type="text" id="product_name" name="product_name" value="{{ old('product_name', $harvest->product_name) }}" class="form-control{{ $err('product_name') }}"
               required minlength="2" maxlength="100" data-pattern="[\p{L}\p{N}\s'\-]+"
               data-pattern-msg="Le nom du produit ne peut contenir que des lettres, chiffres, espaces, tirets et apostrophes." placeholder="Ex. : Tomate" list="product-suggestions">
        <datalist id="product-suggestions">
          @foreach(['Tomate', 'Piment', 'Pomme de terre', 'Olive', 'Datte Deglet Nour', 'Orange Maltaise', 'Blé dur', 'Orge', 'Fraise', 'Pastèque', 'Melon', 'Fève', 'Pois chiche', 'Grenade', 'Abricot'] as $p)
            <option value="{{ $p }}">
          @endforeach
        </datalist>
        @error('product_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
      <div class="col-md-6">
        <label for="category_id" class="form-label">Catégorie <span class="req">*</span></label>
        <select id="category_id" name="category_id" class="form-select{{ $err('category_id') }}" required>
          <option value="">— Choisir —</option>
          @foreach($categories as $c)
            <option value="{{ $c->id }}" @selected((int) old('category_id', $harvest->category_id) === $c->id)>{{ $c->name }}{{ $c->is_active ? '' : ' (désactivée)' }}</option>
          @endforeach
        </select>
        @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
      <div class="col-12">
        <label for="variety" class="form-label">Variété</label>
        <input type="text" id="variety" name="variety" value="{{ old('variety', $harvest->variety) }}" class="form-control{{ $err('variety') }}" maxlength="100" placeholder="Ex. : Chemlali, Rio Grande…">
        @error('variety')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
      <div class="col-12">
        <div class="alert alert-light border small mb-0">
          <i class="fa-solid fa-lightbulb text-warning"></i> Une photo nette du produit, prise à la lumière du jour, sera affichée dans le catalogue et sur la page d'origine scannée par le consommateur.
        </div>
      </div>
      </div>
      </div>
    </div>
  </div>

  {{-- Dates & quantités --}}
  <div class="form-section">
    <div class="form-section__title"><i class="fa-solid fa-calendar-days"></i> Dates & quantités</div>
    <div class="row g-3">
      <div class="col-md-3">
        <label for="planting_date" class="form-label">Date de plantation</label>
        <input type="date" id="planting_date" name="planting_date" value="{{ $date('planting_date') }}" class="form-control{{ $err('planting_date') }}"
               max="{{ now()->format('Y-m-d') }}" data-rules="before:harvest_date">
        @error('planting_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
      <div class="col-md-3">
        <label for="harvest_date" class="form-label">Date de récolte <span class="req">*</span></label>
        <input type="date" id="harvest_date" name="harvest_date" value="{{ $date('harvest_date') }}" class="form-control{{ $err('harvest_date') }}"
               required max="{{ now()->format('Y-m-d') }}" data-rules="not-future">
        @error('harvest_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
      <div class="col-md-3">
        <label for="quantity" class="form-label">Quantité <span class="req">*</span></label>
        <input type="number" id="quantity" name="quantity" value="{{ old('quantity', $harvest->quantity) }}" class="form-control{{ $err('quantity') }}"
               required step="0.01" min="0" max="1000000" data-gt="0" data-min-msg="La quantité doit être strictement positive." placeholder="Ex. : 850">
        @error('quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
      <div class="col-md-3">
        <label for="unit" class="form-label">Unité <span class="req">*</span></label>
        <select id="unit" name="unit" class="form-select{{ $err('unit') }}" required>
          @foreach(Harvest::UNITS as $k => $u)
            <option value="{{ $k }}" data-factor="{{ $u['factor'] }}" @selected(old('unit', $harvest->unit) === $k)>{{ $u['label'] }}</option>
          @endforeach
        </select>
        @error('unit')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
      <div class="col-12">
        <div class="alert alert-light border mb-0 small" id="harvest-preview">
          <i class="fa-solid fa-calculator text-success"></i> Renseignez la quantité pour calculer l'équivalent en kg, le rendement et la durée du cycle.
        </div>
      </div>
    </div>
  </div>

  {{-- Qualité & suivi --}}
  <div class="form-section">
    <div class="form-section__title"><i class="fa-solid fa-award"></i> Qualité & suivi</div>
    <div class="row g-3">
      <div class="col-md-4">
        <label for="quality_grade" class="form-label">Qualité <span class="req">*</span></label>
        <select id="quality_grade" name="quality_grade" class="form-select{{ $err('quality_grade') }}" required>
          @foreach(Harvest::QUALITY_GRADES as $k => $label)
            <option value="{{ $k }}" @selected(old('quality_grade', $harvest->quality_grade) === $k)>{{ $label }}</option>
          @endforeach
        </select>
        @error('quality_grade')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
      <div class="col-md-4">
        <label for="status" class="form-label">Statut du lot <span class="req">*</span></label>
        <select id="status" name="status" class="form-select{{ $err('status') }}" required>
          @foreach(Harvest::STATUSES as $k => $label)
            <option value="{{ $k }}" @selected(old('status', $harvest->status) === $k)>{{ $label }}</option>
          @endforeach
        </select>
        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
      <div class="col-md-4">
        <label for="storage_conditions" class="form-label">Conditions de stockage</label>
        <input type="text" id="storage_conditions" name="storage_conditions" value="{{ old('storage_conditions', $harvest->storage_conditions) }}" class="form-control{{ $err('storage_conditions') }}" maxlength="255" placeholder="Ex. : chambre froide 8 °C">
        @error('storage_conditions')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
      <div class="col-md-6">
        <label for="treatments" class="form-label">Traitements appliqués <span class="char-count" id="treatments-count"></span></label>
        <textarea id="treatments" name="treatments" rows="3" maxlength="1000" data-count="treatments-count" class="form-control{{ $err('treatments') }}"
                  placeholder="Produit, dose, date… (ex. : bouillie bordelaise 2 %, 12/03)">{{ old('treatments', $harvest->treatments) }}</textarea>
        @error('treatments')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
      <div class="col-md-6">
        <label for="notes" class="form-label">Notes <span class="char-count" id="notes-count"></span></label>
        <textarea id="notes" name="notes" rows="3" maxlength="2000" data-count="notes-count" class="form-control{{ $err('notes') }}" placeholder="Observations, météo, main-d'œuvre…">{{ old('notes', $harvest->notes) }}</textarea>
        @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
    </div>
  </div>

  {{-- Fiche consommateur (publique) + IA --}}
  <div class="form-section">
    <div class="form-section__title justify-content-between flex-wrap">
      <span><i class="fa-solid fa-store"></i> Fiche consommateur <small class="text-muted fw-normal">— affichée sur la page d'origine (QR code)</small></span>
      <button type="button" class="btn btn-sm ai-btn" data-ai-harvest data-target="public_description" data-status="harvest-ai-status">
        <i class="fa-solid fa-wand-magic-sparkles"></i> Rédiger avec l'IA
      </button>
    </div>
    <label for="public_description" class="form-label">Présentation du lot <span class="char-count" id="public_description-count"></span></label>
    <textarea id="public_description" name="public_description" rows="4" minlength="20" maxlength="1500" data-count="public_description-count"
              class="form-control{{ $err('public_description') }}" placeholder="Goût, usages, conseils de conservation… ou laissez l'IA rédiger à partir des champs ci-dessus.">{{ old('public_description', $harvest->public_description) }}</textarea>
    @error('public_description')<div class="invalid-feedback">{{ $message }}</div>@enderror
    <div class="ai-source mt-1" id="harvest-ai-status"></div>
  </div>

  <div class="form-actions">
    <a href="{{ $harvest->exists ? route('pro.harvests.show', $harvest) : route('pro.harvests.index') }}" class="btn btn-light">Annuler</a>
    <button type="submit" class="btn btn-success btn-lg"><i class="fa-solid fa-floppy-disk"></i> {{ $harvest->exists ? 'Enregistrer les modifications' : 'Enregistrer la récolte' }}</button>
  </div>
</form>

@push('scripts')
  <script>
    // Calculs métier en direct : conversion en kg, rendement/ha, durée du cycle
    (function () {
      const $ = (id) => document.getElementById(id);
      const box = $('harvest-preview');
      const fmt = (n) => n.toLocaleString('fr-FR', { maximumFractionDigits: 1 });

      function update() {
        const qty = parseFloat($('quantity').value);
        if (!(qty > 0)) return;
        const factor = +$('unit').selectedOptions[0].dataset.factor;
        const kg = qty * factor;
        const parts = [`<strong>${fmt(kg)} kg</strong> récoltés`];

        const plot = parseFloat($('plot_area').value);
        if (plot > 0) parts.push(`rendement <strong>${fmt(kg / plot)} kg/ha</strong>`);

        const p = $('planting_date').value, h = $('harvest_date').value;
        if (p && h && p < h) parts.push(`cycle de <strong>${Math.round((new Date(h) - new Date(p)) / 86400000)} jours</strong>`);

        box.innerHTML = '<i class="fa-solid fa-calculator text-success"></i> ' + parts.join(' · ');
      }

      ['quantity', 'unit', 'plot_area', 'planting_date', 'harvest_date'].forEach((id) => {
        $(id).addEventListener('input', update);
        $(id).addEventListener('change', update);
      });
      // La surface de la parcelle dépend de l'exploitation choisie
      $('farm_id').addEventListener('change', () => { if ($('plot_area').value) $('plot_area').dispatchEvent(new Event('blur')); });
      update();
    })();
  </script>
@endpush
