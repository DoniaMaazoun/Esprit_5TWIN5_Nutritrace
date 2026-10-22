{{-- Une ligne d'étape de transport du formulaire d'expédition ($index peut valoir __INDEX__ dans le modèle JavaScript) --}}
@php
  $field = fn (string $name) => "legs[{$index}][{$name}]";
  $invalid = fn (string $name) => $errors->has("legs.{$index}.{$name}") ? 'is-invalid' : '';
@endphp
<div class="leg-row border rounded-3 p-3 mb-3" data-leg-row>
  <div class="d-flex justify-content-between align-items-center mb-2">
    <strong>
      Étape <span data-leg-number>{{ is_numeric($index) ? $index + 1 : '' }}</span>
      <span class="badge bg-success ms-1 {{ ! empty($row['from_recommendation']) ? '' : 'd-none' }}" data-leg-ai-badge>
        <i class="fa-solid fa-wand-magic-sparkles"></i> Recommandée par l'IA
      </span>
    </strong>
    <div class="d-flex align-items-center gap-2">
      <span class="badge co2-badge" data-leg-co2>— kg CO2e</span>
      <button type="button" class="btn btn-sm btn-outline-danger" data-leg-remove title="Supprimer l'étape">
        <i class="fa-solid fa-trash"></i>
      </button>
    </div>
  </div>

  <input type="hidden" name="{{ $field('id') }}" value="{{ $row['id'] ?? '' }}">
  <input type="hidden" name="{{ $field('from_recommendation') }}" value="{{ ! empty($row['from_recommendation']) ? 1 : 0 }}" data-leg-flag>

  <div class="row g-2">
    <div class="col-md-4">
      <label class="form-label small mb-1">Mode de transport *</label>
      <select name="{{ $field('transport_mode_id') }}" class="form-select form-select-sm {{ $invalid('transport_mode_id') }}" data-leg-mode>
        <option value="">— Choisir —</option>
        @foreach($transportModes as $mode)
          <option value="{{ $mode->id }}" data-factor="{{ $mode->emission_factor }}"
                  @selected((string) ($row['transport_mode_id'] ?? '') === (string) $mode->id)>
            {{ $mode->name }} ({{ rtrim(rtrim(number_format($mode->emission_factor, 5, ',', ''), '0'), ',') }} kg CO2e/t.km)
          </option>
        @endforeach
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label small mb-1">Départ *</label>
      <input type="text" name="{{ $field('from_location') }}" class="form-control form-control-sm {{ $invalid('from_location') }}"
             value="{{ $row['from_location'] ?? '' }}" data-leg-from>
    </div>
    <div class="col-md-3">
      <label class="form-label small mb-1">Arrivée *</label>
      <input type="text" name="{{ $field('to_location') }}" class="form-control form-control-sm {{ $invalid('to_location') }}"
             value="{{ $row['to_location'] ?? '' }}" data-leg-to>
    </div>
    <div class="col-md-2">
      <label class="form-label small mb-1">Distance (km) *</label>
      <input type="number" step="0.01" min="0.1" name="{{ $field('distance_km') }}" class="form-control form-control-sm {{ $invalid('distance_km') }}"
             value="{{ $row['distance_km'] ?? '' }}" data-leg-distance>
    </div>
    <div class="col-md-3">
      <label class="form-label small mb-1">Latitude d'arrivée</label>
      <input type="number" step="0.0000001" min="-90" max="90" name="{{ $field('to_latitude') }}" class="form-control form-control-sm {{ $invalid('to_latitude') }}"
             value="{{ $row['to_latitude'] ?? '' }}" placeholder="ex. 36.8065" data-leg-lat>
    </div>
    <div class="col-md-3">
      <label class="form-label small mb-1">Longitude d'arrivée</label>
      <input type="number" step="0.0000001" min="-180" max="180" name="{{ $field('to_longitude') }}" class="form-control form-control-sm {{ $invalid('to_longitude') }}"
             value="{{ $row['to_longitude'] ?? '' }}" placeholder="ex. 10.1815" data-leg-lng>
    </div>
    <div class="col-md-6 d-flex align-items-end">
      <span class="form-text m-0">Coordonnées facultatives : elles placent l'étape sur la carte du trajet.</span>
    </div>
  </div>
</div>
