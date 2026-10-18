@extends('backend.layouts.app')

@section('title', 'Modifier ' . $farm->name . ' - Farm to Fork Admin')

@php
  use App\Models\Farm;
  $err = fn ($f) => $errors->has($f) ? ' is-invalid' : '';
@endphp

@section('extra-css')
  @include('backend.pages.partials.module-styles')
@endsection

@section('content')
<div class="m1-head">
  <div>
    <div class="m1-crumb"><a href="{{ route('backend.dashboard') }}">Back-office</a> / <a href="{{ route('backend.farms.index') }}">Fermes</a> / Modifier</div>
    <h3><i class="mdi mdi-pencil"></i> Modifier « {{ $farm->name }} »</h3>
    <p>Producteur : <strong>{{ $farm->user->company_name }}</strong> ({{ $farm->user->email }}) — les mêmes règles de saisie que côté producteur s'appliquent.</p>
  </div>
  <div class="m1-head__actions">
    <a href="{{ route('backend.products.index', ['farm_id' => $farm->id]) }}" class="btn btn-outline-secondary"><i class="mdi mdi-basket"></i> Produits de la ferme</a>
  </div>
</div>

<form method="POST" action="{{ route('backend.farms.update', $farm) }}" data-validate class="card m1-panel">
  @csrf @method('PUT')
  <div class="card-body">
    @if($errors->any())
      <div class="alert alert-danger"><i class="mdi mdi-alert-circle"></i> {{ $errors->count() }} erreur(s) à corriger.</div>
    @endif
    <div class="alert alert-danger d-none js-form-alert"></div>

    <div class="form-row">
      <div class="form-group col-md-6">
        <label for="name">Nom de l'exploitation <span class="req">*</span></label>
        <input type="text" id="name" name="name" value="{{ old('name', $farm->name) }}" class="form-control{{ $err('name') }}"
               required minlength="3" maxlength="120" data-pattern="[\p{L}\p{N}\s'\-.]+" data-pattern-msg="Caractères autorisés : lettres, chiffres, espaces, tirets, points, apostrophes.">
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
      <div class="form-group col-md-3">
        <label for="status">Statut <span class="req">*</span></label>
        <select id="status" name="status" class="form-control{{ $err('status') }}" required>
          @foreach(Farm::STATUSES as $k => $label)
            <option value="{{ $k }}" @selected(old('status', $farm->status) === $k)>{{ $label }}</option>
          @endforeach
        </select>
        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
      <div class="form-group col-md-3">
        <label for="established_year">Année de création</label>
        <input type="number" id="established_year" name="established_year" value="{{ old('established_year', $farm->established_year) }}" class="form-control{{ $err('established_year') }}"
               min="1900" max="{{ now()->year }}" data-max-msg="L'année de création ne peut pas être dans le futur.">
        @error('established_year')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>

      <div class="form-group col-md-4">
        <label for="governorate">Gouvernorat <span class="req">*</span></label>
        <select id="governorate" name="governorate" class="form-control{{ $err('governorate') }}" required>
          <option value="">— Choisir —</option>
          @foreach(Farm::GOVERNORATES as $g)
            <option value="{{ $g }}" @selected(old('governorate', $farm->governorate) === $g)>{{ $g }}</option>
          @endforeach
        </select>
        @error('governorate')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
      <div class="form-group col-md-4">
        <label for="city">Ville <span class="req">*</span></label>
        <input type="text" id="city" name="city" value="{{ old('city', $farm->city) }}" class="form-control{{ $err('city') }}"
               required minlength="2" maxlength="80" data-pattern="[\p{L}\s'\-]+" data-pattern-msg="Lettres, espaces, tirets et apostrophes uniquement.">
        @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
      <div class="form-group col-md-4">
        <label for="address">Adresse</label>
        <input type="text" id="address" name="address" value="{{ old('address', $farm->address) }}" class="form-control{{ $err('address') }}" maxlength="255">
        @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>

      <div class="form-group col-md-3">
        <label for="latitude">Latitude</label>
        <input type="number" id="latitude" name="latitude" value="{{ old('latitude', $farm->latitude) }}" class="form-control{{ $err('latitude') }}"
               step="0.0000001" min="30.2" max="37.6" data-rules="with:longitude" data-min-msg="Latitude hors Tunisie (30.2 à 37.6)." data-max-msg="Latitude hors Tunisie (30.2 à 37.6).">
        @error('latitude')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
      <div class="form-group col-md-3">
        <label for="longitude">Longitude</label>
        <input type="number" id="longitude" name="longitude" value="{{ old('longitude', $farm->longitude) }}" class="form-control{{ $err('longitude') }}"
               step="0.0000001" min="7.5" max="11.6" data-rules="with:latitude" data-min-msg="Longitude hors Tunisie (7.5 à 11.6)." data-max-msg="Longitude hors Tunisie (7.5 à 11.6).">
        @error('longitude')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
      <div class="form-group col-md-3">
        <label for="total_area">Surface totale (ha) <span class="req">*</span></label>
        <input type="number" id="total_area" name="total_area" value="{{ old('total_area', $farm->total_area) }}" class="form-control{{ $err('total_area') }}"
               required step="0.01" min="0.01" max="100000" data-min-msg="La surface doit être supérieure à 0 ha.">
        @error('total_area')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
      <div class="form-group col-md-3">
        <label for="soil_type">Type de sol</label>
        <select id="soil_type" name="soil_type" class="form-control{{ $err('soil_type') }}">
          <option value="">— Non renseigné —</option>
          @foreach(Farm::SOIL_TYPES as $k => $label)
            <option value="{{ $k }}" @selected(old('soil_type', $farm->soil_type) === $k)>{{ $label }}</option>
          @endforeach
        </select>
        @error('soil_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>

      <div class="form-group col-md-4">
        <label for="farming_type">Type d'agriculture <span class="req">*</span></label>
        <select id="farming_type" name="farming_type" class="form-control{{ $err('farming_type') }}" required>
          @foreach(Farm::FARMING_TYPES as $k => $label)
            <option value="{{ $k }}" @selected(old('farming_type', $farm->farming_type) === $k)>{{ $label }}</option>
          @endforeach
        </select>
        @error('farming_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
      <div class="form-group col-md-4">
        <label for="irrigation_type">Irrigation <span class="req">*</span></label>
        <select id="irrigation_type" name="irrigation_type" class="form-control{{ $err('irrigation_type') }}" required>
          @foreach(Farm::IRRIGATION_TYPES as $k => $label)
            <option value="{{ $k }}" @selected(old('irrigation_type', $farm->irrigation_type) === $k)>{{ $label }}</option>
          @endforeach
        </select>
        @error('irrigation_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
      <div class="form-group col-md-4">
        <label for="certification">Certification</label>
        <input type="text" id="certification" name="certification" value="{{ old('certification', $farm->certification) }}" class="form-control{{ $err('certification') }}"
               maxlength="100" data-required-if="farming_type=organic" data-required-if-msg="Une exploitation biologique doit indiquer son organisme certificateur.">
        @error('certification')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>

      <div class="form-group col-md-12">
        <label for="main_crops">Cultures principales</label>
        <input type="text" id="main_crops" name="main_crops" value="{{ old('main_crops', $farm->main_crops) }}" class="form-control{{ $err('main_crops') }}" maxlength="255">
        @error('main_crops')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
      <div class="form-group col-md-12">
        <label for="description">Description</label>
        <textarea id="description" name="description" rows="4" maxlength="2000" class="form-control{{ $err('description') }}">{{ old('description', $farm->description) }}</textarea>
        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
    </div>
  </div>
  <div class="card-footer d-flex justify-content-between">
    <a href="{{ route('backend.farms.index') }}" class="btn btn-light">Annuler</a>
    <button type="submit" class="btn btn-success"><i class="mdi mdi-content-save"></i> Enregistrer</button>
  </div>
</form>
@endsection

@section('extra-js')
  <script>window.AGRI = { csrf: @json(csrf_token()) };</script>
  <script src="{{ asset('frontend/js/producer.js') }}"></script>
@endsection
