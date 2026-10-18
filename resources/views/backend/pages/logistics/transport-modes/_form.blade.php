<div class="form-group">
  <label for="name">Nom *</label>
  <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $transportMode->name) }}" required>
</div>
<div class="form-group">
  <label for="code">Code *</label>
  <input type="text" id="code" name="code" class="form-control" value="{{ old('code', $transportMode->code) }}" placeholder="truck_heavy" required>
</div>
<div class="form-group">
  <label for="emission_factor">Facteur d'émission (kg CO2e / tonne.km) *</label>
  <input type="number" step="0.00001" min="0" id="emission_factor" name="emission_factor" class="form-control"
         value="{{ old('emission_factor', $transportMode->emission_factor) }}" required>
  <small class="form-text text-muted">Les étapes déjà enregistrées gardent l'ancien facteur.</small>
</div>
<div class="form-group">
  <label for="source">Source</label>
  <input type="text" id="source" name="source" class="form-control" value="{{ old('source', $transportMode->source) }}" placeholder="ADEME Base Carbone">
</div>
<div class="form-check mb-3">
  <label class="form-check-label">
    <input type="checkbox" name="is_active" value="1" class="form-check-input" @checked(old('is_active', $transportMode->is_active))>
    Actif (proposé aux distributeurs)
  </label>
</div>
