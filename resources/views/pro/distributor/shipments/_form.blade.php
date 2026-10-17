<div class="row g-3">
  <div class="col-md-8">
    <label for="product_name" class="form-label">Produit transporté *</label>
    <input type="text" id="product_name" name="product_name" class="form-control @error('product_name') is-invalid @enderror"
           value="{{ old('product_name', $shipment->product_name) }}" required>
    @error('product_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>
  <div class="col-md-4">
    <label for="batch_id" class="form-label">N° de lot (modules 1/2)</label>
    <input type="number" id="batch_id" name="batch_id" class="form-control @error('batch_id') is-invalid @enderror"
           value="{{ old('batch_id', $shipment->batch_id) }}">
    <div class="form-text">Optionnel, sera relié aux lots du module Producteur.</div>
    @error('batch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>
  <div class="col-md-4">
    <label for="weight_kg" class="form-label">Poids total (kg) *</label>
    <input type="number" step="0.01" min="0.01" id="weight_kg" name="weight_kg" class="form-control @error('weight_kg') is-invalid @enderror"
           value="{{ old('weight_kg', $shipment->weight_kg) }}" required>
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
        <option value="{{ $pointOfSale->id }}" @selected((string) old('point_of_sale_id', $shipment->point_of_sale_id) === (string) $pointOfSale->id)>
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
