{{-- Champs d'une étape de transport (ajout et modification) --}}
<div class="row g-3">
  <div class="col-md-6">
    <label for="transport_mode_id" class="form-label">Mode de transport *</label>
    <select id="transport_mode_id" name="transport_mode_id" class="form-select" required>
      @foreach($transportModes as $mode)
        <option value="{{ $mode->id }}" @selected((string) old('transport_mode_id', $leg->transport_mode_id) === (string) $mode->id)>
          {{ $mode->name }} ({{ rtrim(rtrim(number_format($mode->emission_factor, 5, ',', ''), '0'), ',') }} kg CO2e/t.km)
        </option>
      @endforeach
    </select>
  </div>
  <div class="col-md-3">
    <label for="distance_km" class="form-label">Distance (km) *</label>
    <input type="number" step="0.01" min="0.1" id="distance_km" name="distance_km" class="form-control"
           value="{{ old('distance_km', $leg->distance_km) }}" required>
  </div>
  <div class="col-md-3">
    <label for="weight_kg" class="form-label">Poids (kg) *</label>
    <input type="number" step="0.01" min="0.01" id="weight_kg" name="weight_kg" class="form-control"
           value="{{ old('weight_kg', $leg->weight_kg ?? $shipment->weight_kg) }}" required>
  </div>
  <div class="col-md-6">
    <label for="from_location" class="form-label">Départ *</label>
    <input type="text" id="from_location" name="from_location" class="form-control"
           value="{{ old('from_location', $leg->from_location) }}" required>
  </div>
  <div class="col-md-6">
    <label for="to_location" class="form-label">Arrivée *</label>
    <input type="text" id="to_location" name="to_location" class="form-control"
           value="{{ old('to_location', $leg->to_location) }}" required>
  </div>
  <div class="col-md-3">
    <label for="to_latitude" class="form-label">Latitude d'arrivée</label>
    <input type="number" step="0.0000001" min="-90" max="90" id="to_latitude" name="to_latitude" class="form-control"
           value="{{ old('to_latitude', $leg->to_latitude) }}" placeholder="ex. 36.8065">
  </div>
  <div class="col-md-3">
    <label for="to_longitude" class="form-label">Longitude d'arrivée</label>
    <input type="number" step="0.0000001" min="-180" max="180" id="to_longitude" name="to_longitude" class="form-control"
           value="{{ old('to_longitude', $leg->to_longitude) }}" placeholder="ex. 10.1815">
  </div>
  <div class="col-12">
    <p class="form-text mb-0">Coordonnées facultatives : elles placent l'étape sur la carte du trajet.</p>
  </div>
  <input type="hidden" name="from_recommendation" id="from_recommendation"
         value="{{ old('from_recommendation', $leg->from_recommendation ? 1 : 0) }}">
</div>
