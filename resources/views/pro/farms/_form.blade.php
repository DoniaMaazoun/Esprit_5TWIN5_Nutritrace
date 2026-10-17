@php
  use App\Models\Farm;
  $err = fn ($f) => $errors->has($f) ? ' is-invalid' : '';
@endphp

<form method="POST" action="{{ $action }}" data-validate class="p-card">
  @csrf
  @if($farm->exists) @method('PUT') @endif

  @if($errors->any())
    <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i> Le formulaire contient {{ $errors->count() }} erreur(s). Corrigez les champs en rouge.</div>
  @endif
  <div class="alert alert-danger d-none js-form-alert"></div>

  {{-- Identité --}}
  <div class="form-section">
    <div class="form-section__title"><i class="fa-solid fa-id-card"></i> Identité de l'exploitation</div>
    <div class="row g-3">
      <div class="col-md-8">
        <label for="name" class="form-label">Nom de l'exploitation <span class="req">*</span></label>
        <input type="text" id="name" name="name" value="{{ old('name', $farm->name) }}" class="form-control{{ $err('name') }}"
               required minlength="3" maxlength="120" data-pattern="[\p{L}\p{N}\s'\-.]+"
               data-pattern-msg="Le nom ne peut contenir que des lettres, chiffres, espaces, tirets, points et apostrophes."
               placeholder="Ex. : Domaine des Oliviers">
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
      <div class="col-md-4">
        <label for="status" class="form-label">Statut <span class="req">*</span></label>
        <select id="status" name="status" class="form-select{{ $err('status') }}" required>
          @foreach(Farm::STATUSES as $k => $label)
            <option value="{{ $k }}" @selected(old('status', $farm->status) === $k)>{{ $label }}</option>
          @endforeach
        </select>
        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
      <div class="col-md-4">
        <label for="established_year" class="form-label">Année de création</label>
        <input type="number" id="established_year" name="established_year" value="{{ old('established_year', $farm->established_year) }}"
               class="form-control{{ $err('established_year') }}" min="1900" max="{{ now()->year }}" step="1"
               data-max-msg="L'année de création ne peut pas être dans le futur." placeholder="Ex. : 1998">
        @error('established_year')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
      <div class="col-md-8">
        <label for="main_crops" class="form-label">Cultures principales</label>
        <input type="text" id="main_crops" name="main_crops" value="{{ old('main_crops', $farm->main_crops) }}"
               class="form-control{{ $err('main_crops') }}" maxlength="255" placeholder="Ex. : olives Chemlali, tomates, piments">
        @error('main_crops')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
    </div>
  </div>

  {{-- Localisation --}}
  <div class="form-section">
    <div class="form-section__title"><i class="fa-solid fa-location-dot"></i> Localisation</div>
    <div class="row g-3">
      <div class="col-md-4">
        <label for="governorate" class="form-label">Gouvernorat <span class="req">*</span></label>
        <select id="governorate" name="governorate" class="form-select{{ $err('governorate') }}" required>
          <option value="">— Choisir —</option>
          @foreach(Farm::GOVERNORATES as $g)
            <option value="{{ $g }}" @selected(old('governorate', $farm->governorate) === $g)>{{ $g }}</option>
          @endforeach
        </select>
        @error('governorate')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
      <div class="col-md-4">
        <label for="city" class="form-label">Ville / Délégation <span class="req">*</span></label>
        <input type="text" id="city" name="city" value="{{ old('city', $farm->city) }}" class="form-control{{ $err('city') }}"
               required minlength="2" maxlength="80" data-pattern="[\p{L}\s'\-]+"
               data-pattern-msg="La ville ne peut contenir que des lettres, espaces, tirets et apostrophes." placeholder="Ex. : Menzel Temime">
        @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
      <div class="col-md-4">
        <label for="address" class="form-label">Adresse</label>
        <input type="text" id="address" name="address" value="{{ old('address', $farm->address) }}" class="form-control{{ $err('address') }}" maxlength="255" placeholder="Route, lieu-dit…">
        @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
      <div class="col-md-8">
        <div id="location-map" aria-label="Carte : cliquez pour placer l'exploitation"></div>
        <small class="text-muted"><i class="fa-solid fa-hand-pointer"></i> Cliquez sur la carte pour placer l'exploitation, ou utilisez votre position.</small>
      </div>
      <div class="col-md-4">
        <label for="latitude" class="form-label">Latitude</label>
        <input type="number" id="latitude" name="latitude" value="{{ old('latitude', $farm->latitude) }}" class="form-control{{ $err('latitude') }}"
               step="0.0000001" min="30.2" max="37.6" data-rules="with:longitude"
               data-min-msg="La latitude doit être située en Tunisie (30.2 à 37.6)." data-max-msg="La latitude doit être située en Tunisie (30.2 à 37.6).">
        @error('latitude')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <label for="longitude" class="form-label mt-2">Longitude</label>
        <input type="number" id="longitude" name="longitude" value="{{ old('longitude', $farm->longitude) }}" class="form-control{{ $err('longitude') }}"
               step="0.0000001" min="7.5" max="11.6" data-rules="with:latitude"
               data-min-msg="La longitude doit être située en Tunisie (7.5 à 11.6)." data-max-msg="La longitude doit être située en Tunisie (7.5 à 11.6).">
        @error('longitude')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="d-flex gap-2 mt-2">
          <button type="button" class="btn btn-sm btn-outline-success" id="locate-me"><i class="fa-solid fa-crosshairs"></i> Ma position</button>
          <button type="button" class="btn btn-sm btn-light" id="clear-location"><i class="fa-solid fa-eraser"></i> Effacer</button>
        </div>
      </div>
    </div>
  </div>

  {{-- Caractéristiques agronomiques --}}
  <div class="form-section">
    <div class="form-section__title"><i class="fa-solid fa-seedling"></i> Caractéristiques agronomiques</div>
    <div class="row g-3">
      <div class="col-md-3">
        <label for="total_area" class="form-label">Surface totale (ha) <span class="req">*</span></label>
        <input type="number" id="total_area" name="total_area" value="{{ old('total_area', $farm->total_area) }}" class="form-control{{ $err('total_area') }}"
               required step="0.01" min="0.01" max="100000" data-min-msg="La surface doit être supérieure à 0 ha." placeholder="Ex. : 12.5">
        @error('total_area')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
      <div class="col-md-3">
        <label for="farming_type" class="form-label">Type d'agriculture <span class="req">*</span></label>
        <select id="farming_type" name="farming_type" class="form-select{{ $err('farming_type') }}" required>
          <option value="">— Choisir —</option>
          @foreach(Farm::FARMING_TYPES as $k => $label)
            <option value="{{ $k }}" @selected(old('farming_type', $farm->farming_type) === $k)>{{ $label }}</option>
          @endforeach
        </select>
        @error('farming_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
      <div class="col-md-3">
        <label for="irrigation_type" class="form-label">Irrigation <span class="req">*</span></label>
        <select id="irrigation_type" name="irrigation_type" class="form-select{{ $err('irrigation_type') }}" required>
          <option value="">— Choisir —</option>
          @foreach(Farm::IRRIGATION_TYPES as $k => $label)
            <option value="{{ $k }}" @selected(old('irrigation_type', $farm->irrigation_type) === $k)>{{ $label }}</option>
          @endforeach
        </select>
        @error('irrigation_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
      <div class="col-md-3">
        <label for="soil_type" class="form-label">Type de sol</label>
        <select id="soil_type" name="soil_type" class="form-select{{ $err('soil_type') }}">
          <option value="">— Non renseigné —</option>
          @foreach(Farm::SOIL_TYPES as $k => $label)
            <option value="{{ $k }}" @selected(old('soil_type', $farm->soil_type) === $k)>{{ $label }}</option>
          @endforeach
        </select>
        @error('soil_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
      <div class="col-md-6">
        <label for="certification" class="form-label">Certification <span class="req d-none" id="cert-req">*</span></label>
        <input type="text" id="certification" name="certification" value="{{ old('certification', $farm->certification) }}" class="form-control{{ $err('certification') }}"
               maxlength="100" data-required-if="farming_type=organic"
               data-required-if-msg="Une exploitation biologique doit indiquer son organisme certificateur." placeholder="Ex. : Bio Tunisie (CNAB), Ecocert, GlobalG.A.P.">
        @error('certification')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
    </div>
  </div>

  {{-- Description + IA --}}
  <div class="form-section">
    <div class="form-section__title justify-content-between flex-wrap">
      <span><i class="fa-solid fa-align-left"></i> Présentation</span>
      <button type="button" class="btn btn-sm ai-btn" data-ai-description data-target="description" data-status="ai-status">
        <i class="fa-solid fa-wand-magic-sparkles"></i> Générer avec l'IA
      </button>
    </div>
    <label for="description" class="form-label">Description <span class="char-count" id="description-count"></span></label>
    <textarea id="description" name="description" rows="5" maxlength="2000" data-count="description-count"
              class="form-control{{ $err('description') }}" placeholder="Présentez votre exploitation aux consommateurs… ou laissez l'IA la rédiger à partir des champs ci-dessus.">{{ old('description', $farm->description) }}</textarea>
    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
    <div class="ai-source mt-1" id="ai-status"></div>
  </div>

  <div class="form-actions">
    <a href="{{ $farm->exists ? route('pro.farms.show', $farm) : route('pro.farms.index') }}" class="btn btn-light">Annuler</a>
    <button type="submit" class="btn btn-success btn-lg"><i class="fa-solid fa-floppy-disk"></i> {{ $farm->exists ? 'Enregistrer les modifications' : 'Créer l\'exploitation' }}</button>
  </div>
</form>

@push('styles')
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
@endpush

@push('scripts')
  <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
  <script>
    (function () {
      const lat = document.getElementById('latitude');
      const lng = document.getElementById('longitude');
      const map = L.map('location-map').setView([34.2, 9.6], 6);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(map);
      let marker = null;

      function place(la, ln, zoom) {
        la = +(+la).toFixed(7); ln = +(+ln).toFixed(7);
        if (marker) marker.setLatLng([la, ln]); else marker = L.marker([la, ln]).addTo(map);
        lat.value = la; lng.value = ln;
        [lat, lng].forEach((f) => f.dispatchEvent(new Event('blur')));
        if (zoom) map.setView([la, ln], zoom);
      }

      if (lat.value && lng.value) place(lat.value, lng.value, 12);
      map.on('click', (e) => place(e.latlng.lat, e.latlng.lng));
      [lat, lng].forEach((f) => f.addEventListener('change', () => { if (lat.value && lng.value) place(lat.value, lng.value, 12); }));

      document.getElementById('locate-me').addEventListener('click', () => {
        if (!navigator.geolocation) return alert('La géolocalisation n\'est pas disponible sur ce navigateur.');
        navigator.geolocation.getCurrentPosition(
          (pos) => place(pos.coords.latitude, pos.coords.longitude, 13),
          () => alert('Impossible de récupérer votre position.')
        );
      });
      document.getElementById('clear-location').addEventListener('click', () => {
        if (marker) { map.removeLayer(marker); marker = null; }
        lat.value = ''; lng.value = '';
        [lat, lng].forEach((f) => f.classList.remove('is-valid', 'is-invalid'));
      });

      // Astérisque dynamique : la certification devient obligatoire en bio
      const type = document.getElementById('farming_type');
      const toggleCert = () => document.getElementById('cert-req').classList.toggle('d-none', type.value !== 'organic');
      type.addEventListener('change', toggleCert);
      toggleCert();
    })();
  </script>
@endpush
