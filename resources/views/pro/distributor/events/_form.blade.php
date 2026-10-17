{{-- Champs d'un événement du journal (ajout et modification) --}}
<div class="row g-3">
  <div class="col-md-4">
    <label for="type" class="form-label">Type *</label>
    <select id="type" name="type" class="form-select" required>
      @foreach(\App\Models\ShipmentEvent::TYPES as $value => $label)
        <option value="{{ $value }}" @selected(old('type', $event->type) === $value)>{{ $label }}</option>
      @endforeach
    </select>
  </div>
  <div class="col-md-4">
    <label for="occurred_at" class="form-label">Date et heure *</label>
    <input type="datetime-local" id="occurred_at" name="occurred_at" class="form-control"
           value="{{ old('occurred_at', ($event->occurred_at ?? now())->format('Y-m-d\TH:i')) }}" required>
  </div>
  <div class="col-md-4">
    <label for="location" class="form-label">Lieu</label>
    <input type="text" id="location" name="location" class="form-control"
           value="{{ old('location', $event->location) }}">
  </div>
  <div class="col-12">
    <label for="note" class="form-label">Note</label>
    <textarea id="note" name="note" rows="2" class="form-control">{{ old('note', $event->note) }}</textarea>
  </div>
</div>
