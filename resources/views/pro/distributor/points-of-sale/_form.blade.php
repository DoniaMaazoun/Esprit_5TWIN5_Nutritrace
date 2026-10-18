<div class="row g-3">
  <div class="col-md-8">
    <label for="name" class="form-label">Nom *</label>
    <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror"
           value="{{ old('name', $pointOfSale->name) }}" required>
    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>
  <div class="col-md-4">
    <label for="type" class="form-label">Type *</label>
    <select id="type" name="type" class="form-select @error('type') is-invalid @enderror" required>
      @foreach(\App\Models\PointOfSale::TYPES as $value => $label)
        <option value="{{ $value }}" @selected(old('type', $pointOfSale->type) === $value)>{{ $label }}</option>
      @endforeach
    </select>
    @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>
  <div class="col-md-8">
    <label for="address" class="form-label">Adresse *</label>
    <input type="text" id="address" name="address" class="form-control @error('address') is-invalid @enderror"
           value="{{ old('address', $pointOfSale->address) }}" required>
    @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>
  <div class="col-md-4">
    <label for="city" class="form-label">Ville *</label>
    <input type="text" id="city" name="city" class="form-control @error('city') is-invalid @enderror"
           value="{{ old('city', $pointOfSale->city) }}" required>
    @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>
  <div class="col-md-6">
    <label for="latitude" class="form-label">Latitude</label>
    <input type="number" step="0.0000001" id="latitude" name="latitude" class="form-control @error('latitude') is-invalid @enderror"
           value="{{ old('latitude', $pointOfSale->latitude) }}">
    @error('latitude')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>
  <div class="col-md-6">
    <label for="longitude" class="form-label">Longitude</label>
    <input type="number" step="0.0000001" id="longitude" name="longitude" class="form-control @error('longitude') is-invalid @enderror"
           value="{{ old('longitude', $pointOfSale->longitude) }}">
    @error('longitude')<div class="invalid-feedback">{{ $message }}</div>@enderror
  </div>
</div>
