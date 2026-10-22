{{-- Formulaire d'expédition en une page (création et modification) : infos, étapes de transport, recommandation IA --}}
@php
  $legRows = old('legs', $shipment->relationLoaded('legs')
      ? $shipment->legs->map(fn ($leg) => [
          'id' => $leg->id,
          'transport_mode_id' => $leg->transport_mode_id,
          'from_location' => $leg->from_location,
          'to_location' => $leg->to_location,
          'distance_km' => (float) $leg->distance_km,
          'to_latitude' => $leg->to_latitude,
          'to_longitude' => $leg->to_longitude,
          'from_recommendation' => $leg->from_recommendation,
      ])->all()
      : []);
@endphp

<!-- a) Infos de l'expédition -->
<h5 class="mb-3"><span class="badge bg-success me-1">1</span> Infos de l'expédition</h5>
<div class="row g-3">
  <div class="col-md-6">
    <label for="production_lot_id" class="form-label">Lot de production transporté (module Transformateur)</label>
    <select id="production_lot_id" name="production_lot_id" class="form-select @error('production_lot_id') is-invalid @enderror">
      <option value="">— Aucun lot relié —</option>
      @foreach($productionLots as $lot)
        <option value="{{ $lot->id }}" @selected((string) old('production_lot_id', $shipment->production_lot_id) === (string) $lot->id)>
          {{ $lot->qr_code_data ?? 'Lot #'.$lot->id }} · {{ $lot->getProductName() }} · {{ $lot->getQuantityDisplay() }} · produit le {{ $lot->production_date->format('d/m/Y') }}
          ({{ $lot->processor->company_name ?: $lot->processor->name }})
        </option>
      @endforeach
    </select>
    <div class="form-text">Optionnel : relie l'expédition à un produit fini déclaré par un transformateur.</div>
    @error('production_lot_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>
  <div class="col-md-6">
    <label for="product_name" class="form-label">Produit transporté *</label>
    <input type="text" id="product_name" name="product_name" class="form-control @error('product_name') is-invalid @enderror"
           value="{{ old('product_name', $shipment->product_name) }}">
    <div class="form-text">Laissez vide pour reprendre le nom du produit du lot choisi.</div>
    @error('product_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>
  <div class="col-md-4">
    <label for="weight_kg" class="form-label">Poids total (kg) *</label>
    <input type="number" step="0.01" min="0.01" id="weight_kg" name="weight_kg" class="form-control @error('weight_kg') is-invalid @enderror"
           value="{{ old('weight_kg', $shipment->weight_kg) }}" required>
    <div class="form-text">Utilisé pour le CO2 de chaque étape.</div>
    @error('weight_kg')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>
  <div class="col-md-8">
    <label for="origin_address" class="form-label">Lieu de départ *</label>
    <input type="text" id="origin_address" name="origin_address" class="form-control @error('origin_address') is-invalid @enderror"
           value="{{ old('origin_address', $shipment->origin_address) }}" required>
    @error('origin_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>
  <div class="col-md-6">
    <label for="point_of_sale_id" class="form-label">Point de vente de destination</label>
    <select id="point_of_sale_id" name="point_of_sale_id" class="form-select @error('point_of_sale_id') is-invalid @enderror">
      <option value="">— Aucun —</option>
      @foreach($pointsOfSale as $pointOfSale)
        <option value="{{ $pointOfSale->id }}" data-label="{{ $pointOfSale->name }}, {{ $pointOfSale->city }}"
                data-lat="{{ $pointOfSale->latitude }}" data-lng="{{ $pointOfSale->longitude }}"
                @selected((string) old('point_of_sale_id', $shipment->point_of_sale_id) === (string) $pointOfSale->id)>
          {{ $pointOfSale->name }} ({{ $pointOfSale->city }})
        </option>
      @endforeach
    </select>
    @error('point_of_sale_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>
  <div class="col-md-6">
    <label for="status" class="form-label">Statut *</label>
    <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
      @foreach(\App\Models\Shipment::STATUSES as $value => $label)
        <option value="{{ $value }}" @selected(old('status', $shipment->status) === $value)>{{ $label }}</option>
      @endforeach
    </select>
    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>
  <div class="col-md-6">
    <label for="departed_at" class="form-label">Date de départ</label>
    <input type="datetime-local" id="departed_at" name="departed_at" class="form-control @error('departed_at') is-invalid @enderror"
           value="{{ old('departed_at', $shipment->departed_at?->format('Y-m-d\TH:i')) }}">
    @error('departed_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>
  <div class="col-md-6">
    <label for="delivered_at" class="form-label">Date de livraison</label>
    <input type="datetime-local" id="delivered_at" name="delivered_at" class="form-control @error('delivered_at') is-invalid @enderror"
           value="{{ old('delivered_at', $shipment->delivered_at?->format('Y-m-d\TH:i')) }}">
    @error('delivered_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>
</div>

<!-- b) Étapes de transport -->
<hr class="my-4">
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <h5 class="mb-0"><span class="badge bg-success me-1">2</span> Étapes de transport</h5>
  <button type="button" class="btn btn-outline-success btn-sm" id="leg-add">
    <i class="fa-solid fa-plus"></i> Ajouter une étape
  </button>
</div>

<div id="legs-container">
  @foreach($legRows as $index => $row)
    @include('pro.distributor.shipments._leg_row', ['index' => $index, 'row' => $row])
  @endforeach
</div>
<div class="alert alert-info {{ count($legRows) ? 'd-none' : '' }}" id="legs-empty">
  Aucune étape : ajoutez les trajets un par un, ou utilisez la recommandation IA ci-dessous pour les remplir automatiquement.
</div>

<template id="leg-template">
  @include('pro.distributor.shipments._leg_row', ['index' => '__INDEX__', 'row' => []])
</template>

<div class="d-flex flex-wrap justify-content-end gap-4 text-end">
  <div><div class="small text-muted">Distance totale</div><div class="fw-bold" id="legs-total-distance">0 km</div></div>
  <div><div class="small text-muted">CO2 total (aperçu)</div><div class="fw-bold text-success" id="legs-total-co2">0 kg CO2e</div></div>
</div>
<p class="form-text mb-0 text-end">CO2 (kg) = distance (km) × poids (tonnes) × facteur d'émission ADEME. Aperçu : le calcul définitif est refait à l'enregistrement.</p>

<!-- c) Recommandation IA -->
<hr class="my-4">
<h5 class="mb-3"><span class="badge bg-success me-1">3</span> Recommandation IA</h5>
<div class="border rounded-3 p-3" id="ai-recommendation" style="background: #f6fbf9;">
  <div class="row g-3 align-items-end">
    <div class="col-md-3">
      <label for="ai_distance_km" class="form-label mb-1">Distance totale (km)</label>
      <input type="number" step="0.1" min="0.1" id="ai_distance_km" class="form-control form-control-sm" placeholder="ex. 450">
    </div>
    <div class="col-md-3">
      <label for="ai_max_hours" class="form-label mb-1">Délai souhaité (heures)</label>
      <input type="number" step="1" min="1" id="ai_max_hours" class="form-control form-control-sm" placeholder="Optionnel">
    </div>
    <div class="col-md-6">
      <button type="button" class="btn btn-outline-success btn-sm" id="ai-recommend-btn">
        <i class="fa-solid fa-wand-magic-sparkles"></i> Recommandation IA
      </button>
      <div class="form-text m-0">Utilise le lot de production, le poids et la distance totale (reprise des étapes si elles existent).</div>
    </div>
  </div>

  <div id="ai-recommend-error" class="alert alert-warning mt-3 mb-0 d-none"></div>

  <div id="ai-recommend-result" class="mt-3 d-none">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
      <strong><i class="fa-solid fa-leaf text-success"></i> <span data-field="name"></span></strong>
      <span class="text-end">
        <span class="badge bg-secondary" data-field="source"></span>
        <small class="d-block text-muted" data-field="source-reason"></small>
      </span>
    </div>
    <div class="small mt-1">
      <span class="badge bg-light text-dark border text-wrap text-start" data-field="product"></span>
      <span class="badge bg-info text-dark d-none" data-field="cold"><i class="fa-solid fa-snowflake"></i> Transport réfrigéré conseillé</span>
    </div>
    <p class="mb-2 mt-2" data-field="explanation"></p>

    <ol class="small mb-2" data-field="legs"></ol>

    <div class="row text-center g-2 mb-2">
      <div class="col-sm-4"><div class="fw-bold" data-field="co2"></div><div class="small text-muted">CO2 estimé</div></div>
      <div class="col-sm-4"><div class="fw-bold" data-field="hours"></div><div class="small text-muted">Durée estimée</div></div>
      <div class="col-sm-4"><div class="fw-bold text-success" data-field="saving"></div><div class="small text-muted" data-field="saving-label">Gain vs camion</div></div>
    </div>

    <div class="d-none" data-field="tips-box">
      <div class="small fw-bold"><i class="fa-solid fa-lightbulb text-warning"></i> Conseils</div>
      <ul class="small mb-2" data-field="tips"></ul>
    </div>

    <button type="button" class="btn btn-success btn-sm" id="ai-recommend-apply">
      <i class="fa-solid fa-check"></i> Utiliser ce plan
    </button>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('legs-container');
    const template = document.getElementById('leg-template');
    const emptyBox = document.getElementById('legs-empty');
    const weightInput = document.getElementById('weight_kg');
    const aiDistance = document.getElementById('ai_distance_km');
    let aiDistanceTyped = false;

    const number = (value, digits = 2) => Number(value).toLocaleString('fr-FR', { maximumFractionDigits: digits });
    const kg = (value) => number(value) + ' kg CO2e';
    const rows = () => [...container.querySelectorAll('[data-leg-row]')];

    // Les champs sont renumérotés (legs[0], legs[1]...) pour suivre l'ordre affiché
    const renumber = () => {
      rows().forEach((row, index) => {
        row.querySelector('[data-leg-number]').textContent = index + 1;
        row.querySelectorAll('[name^="legs["]').forEach((input) => {
          input.name = input.name.replace(/^legs\[[^\]]*\]/, `legs[${index}]`);
        });
      });
      emptyBox.classList.toggle('d-none', rows().length > 0);
    };

    // Aperçu du CO2 de chaque étape et du total, avec le poids de l'expédition
    const refreshTotals = () => {
      const tonnes = (parseFloat(weightInput.value) || 0) / 1000;
      let totalDistance = 0;
      let totalCo2 = 0;

      rows().forEach((row) => {
        const option = row.querySelector('[data-leg-mode]').selectedOptions[0];
        const factor = parseFloat(option?.dataset.factor);
        const distance = parseFloat(row.querySelector('[data-leg-distance]').value) || 0;
        totalDistance += distance;

        if (!isNaN(factor) && distance > 0 && tonnes > 0) {
          const co2 = distance * tonnes * factor;
          totalCo2 += co2;
          row.querySelector('[data-leg-co2]').textContent = kg(co2);
        } else {
          row.querySelector('[data-leg-co2]').textContent = '— kg CO2e';
        }
      });

      document.getElementById('legs-total-distance').textContent = number(totalDistance, 1) + ' km';
      document.getElementById('legs-total-co2').textContent = kg(totalCo2);
      if (!aiDistanceTyped && totalDistance > 0) {
        aiDistance.value = Math.round(totalDistance * 10) / 10;
      }
    };

    const addRow = (values = {}) => {
      const fragment = template.content.cloneNode(true);
      const row = fragment.querySelector('[data-leg-row]');
      const set = (selector, value) => {
        if (value !== undefined && value !== null) {
          row.querySelector(selector).value = value;
        }
      };
      set('[data-leg-mode]', values.transport_mode_id);
      set('[data-leg-from]', values.from_location);
      set('[data-leg-to]', values.to_location);
      set('[data-leg-distance]', values.distance_km);
      set('[data-leg-lat]', values.to_latitude);
      set('[data-leg-lng]', values.to_longitude);
      row.querySelector('[data-leg-flag]').value = values.from_recommendation ? 1 : 0;
      row.querySelector('[data-leg-ai-badge]').classList.toggle('d-none', !values.from_recommendation);
      container.appendChild(fragment);
      renumber();
      refreshTotals();
    };

    document.getElementById('leg-add').addEventListener('click', () => {
      // La nouvelle étape part de l'arrivée de la précédente, ou du lieu de départ de l'expédition
      const last = rows().at(-1);
      addRow({
        from_location: last ? last.querySelector('[data-leg-to]').value : document.getElementById('origin_address').value,
      });
    });

    container.addEventListener('click', (event) => {
      const button = event.target.closest('[data-leg-remove]');
      if (button) {
        button.closest('[data-leg-row]').remove();
        renumber();
        refreshTotals();
      }
    });

    container.addEventListener('input', refreshTotals);
    container.addEventListener('change', (event) => {
      // Si le distributeur choisit lui-même un autre mode, l'étape ne suit plus la recommandation
      if (event.target.matches('[data-leg-mode]') && event.isTrusted) {
        const row = event.target.closest('[data-leg-row]');
        row.querySelector('[data-leg-flag]').value = 0;
        row.querySelector('[data-leg-ai-badge]').classList.add('d-none');
      }
      refreshTotals();
    });
    weightInput.addEventListener('input', refreshTotals);
    aiDistance.addEventListener('input', () => { aiDistanceTyped = aiDistance.value !== ''; });
    aiDistance.addEventListener('keydown', (event) => { if (event.key === 'Enter') event.preventDefault(); });
    document.getElementById('ai_max_hours').addEventListener('keydown', (event) => { if (event.key === 'Enter') event.preventDefault(); });

    // ==================== Recommandation IA ====================
    const button = document.getElementById('ai-recommend-btn');
    const applyButton = document.getElementById('ai-recommend-apply');
    const errorBox = document.getElementById('ai-recommend-error');
    const result = document.getElementById('ai-recommend-result');
    const field = (name) => result.querySelector(`[data-field="${name}"]`);
    const fillList = (list, items) => {
      list.replaceChildren(...items.map((text) => Object.assign(document.createElement('li'), { textContent: text })));
    };
    const showError = (message) => {
      errorBox.textContent = message;
      errorBox.classList.remove('d-none');
    };
    let recommended = null;

    button.addEventListener('click', async () => {
      errorBox.classList.add('d-none');
      if (!weightInput.value) {
        return showError('Indiquez le poids total de l\'expédition pour obtenir une recommandation.');
      }
      if (!aiDistance.value) {
        return showError('Indiquez la distance totale (ou ajoutez des étapes avec leur distance).');
      }

      button.disabled = true;
      button.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Analyse en cours…';

      try {
        const response = await fetch(@json(route('pro.distributor.shipments.transport-recommendation')), {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': @json(csrf_token()) },
          body: JSON.stringify({
            distance_km: aiDistance.value,
            weight_kg: weightInput.value,
            max_hours: document.getElementById('ai_max_hours').value || null,
            production_lot_id: document.getElementById('production_lot_id').value || null,
            product_name: document.getElementById('product_name').value || null,
            point_of_sale_id: document.getElementById('point_of_sale_id').value || null,
            shipment_id: @json($shipment->exists ? $shipment->id : null),
          }),
        });
        const data = await response.json();
        if (!response.ok) {
          throw new Error(data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || 'Recommandation indisponible.'));
        }

        recommended = data.recommended;
        const isPlan = recommended.type === 'plan';

        field('name').textContent = (isPlan ? 'Plan multimodal : ' : '') + recommended.name;
        const fromAi = data.source === 'ai';
        field('source').textContent = fromAi ? `IA (${data.provider})` : 'Mode secours (hors-ligne)';
        field('source').classList.toggle('bg-success', fromAi);
        field('source').classList.toggle('bg-secondary', !fromAi);
        field('source-reason').textContent = fromAi ? '' : (data.fallback_reason || '');
        const origins = data.product.origins || [];
        field('product').textContent = (data.product.name || 'Produit') + ' · ' + data.product.label
          + (data.product.expiry_date ? ` · DLC ${data.product.expiry_date}` : '')
          + (data.product.days_to_expiry !== null ? ` (${data.product.days_to_expiry} j restants)` : '')
          + (origins.length ? ' · issu de ' + origins.map((o) => `${o.product_name} récolté le ${o.harvest_date}`).join(', ') : '')
          + (data.max_hours ? ` · délai retenu ${data.max_hours} h` : '');
        field('cold').classList.toggle('d-none', !data.product.refrigerated);
        field('explanation').textContent = data.explanation;
        fillList(field('legs'), recommended.legs.map((leg) => `${leg.name} : ${number(leg.distance_km, 1)} km, ${kg(leg.co2_kg)}, ≈ ${leg.hours} h`));

        field('co2').textContent = kg(recommended.co2_kg);
        field('hours').textContent = '≈ ' + recommended.hours + ' h';
        // Gain négatif : l'option émet plus que le camion de référence, affichée en surcoût
        const extra = data.reference && data.saving_kg < 0;
        field('saving').classList.toggle('text-success', !extra);
        field('saving').classList.toggle('text-danger', extra);
        if (data.reference) {
          field('saving').textContent = (extra ? '+' + kg(-data.saving_kg) : kg(data.saving_kg))
            + (data.saving_percent !== null ? ` (${extra ? '+' + number(-data.saving_percent, 1) : number(data.saving_percent, 1)} %)` : '');
          field('saving-label').textContent = (extra ? 'Surcoût vs ' : 'Gain vs ') + data.reference.name;
        } else {
          field('saving').textContent = '—';
          field('saving-label').textContent = 'Gain vs camion';
        }

        fillList(field('tips'), data.tips);
        field('tips-box').classList.toggle('d-none', data.tips.length === 0);
        result.classList.remove('d-none');
      } catch (error) {
        showError(error.message);
      } finally {
        button.disabled = false;
        button.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles"></i> Recommandation IA';
      }
    });

    // « Utiliser ce plan » : une ligne d'étape par tronçon du plan, marquée « recommandée par l'IA »
    applyButton.addEventListener('click', () => {
      if (!recommended) {
        return;
      }
      if (rows().length > 0 && !confirm('Remplacer les étapes déjà saisies par celles du plan recommandé ?')) {
        return;
      }

      const origin = document.getElementById('origin_address').value;
      const destination = document.getElementById('point_of_sale_id').selectedOptions[0];
      const hasDestination = destination && destination.value !== '';
      const main = recommended.legs.length > 1 ? recommended.legs[1].code : null;
      const hub = main === 'rail' ? 'Gare' : (main === 'sea' || main === 'river' ? 'Port' : 'Point de transfert');
      const hubNames = [`${hub} de départ`, `${hub} d'arrivée`];

      container.replaceChildren();
      let from = origin;
      recommended.legs.forEach((leg, index) => {
        const isLast = index === recommended.legs.length - 1;
        const to = isLast
          ? (hasDestination ? destination.dataset.label : 'Destination')
          : (hubNames[index] ?? `${hub} ${index + 1}`);
        addRow({
          transport_mode_id: leg.id,
          from_location: from,
          to_location: to,
          distance_km: leg.distance_km,
          to_latitude: isLast && hasDestination ? destination.dataset.lat : null,
          to_longitude: isLast && hasDestination ? destination.dataset.lng : null,
          from_recommendation: true,
        });
        from = to;
      });
      container.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    renumber();
    refreshTotals();
  });
</script>
