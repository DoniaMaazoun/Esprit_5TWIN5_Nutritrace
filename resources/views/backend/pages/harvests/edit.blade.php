@extends('backend.layouts.app')

@section('title', 'Modifier le lot ' . $harvest->batch_code . ' - Farm to Fork Admin')

@php
  use App\Models\Harvest;
  $err = fn ($f) => $errors->has($f) ? ' is-invalid' : '';
  $date = fn ($f) => old($f, optional($harvest->{$f})->format('Y-m-d'));
@endphp

@section('extra-css')
  @include('backend.pages.partials.module-styles')
@endsection

@section('content')
<div class="m1-head">
  <div>
    <div class="m1-crumb"><a href="{{ route('backend.dashboard') }}">Back-office</a> / <a href="{{ route('backend.products.index') }}">Produits</a> / Modifier</div>
    <h3><i class="mdi mdi-pencil"></i> {{ $harvest->product_name }} <code style="font-size:.9rem">{{ $harvest->batch_code }}</code></h3>
    <p>Producteur : <strong>{{ $harvest->farm->user->company_name }}</strong> — le lot reste rattaché aux exploitations de ce producteur.</p>
  </div>
</div>

<form method="POST" action="{{ route('backend.products.update', $harvest) }}" data-validate class="card m1-panel" enctype="multipart/form-data">
  @csrf @method('PUT')
  <div class="card-body">
    @if($errors->any())
      <div class="alert alert-danger"><i class="mdi mdi-alert-circle"></i> {{ $errors->count() }} erreur(s) à corriger.</div>
    @endif
    <div class="alert alert-danger d-none js-form-alert"></div>

    <div class="form-row">
      <div class="form-group col-md-4">
        @include('shared.image-upload', ['current' => $harvest->imageUrl()])
      </div>
      <div class="form-group col-md-8">
        <div class="m1-edit-summary">
          <div><span>Produit</span><strong>{{ $harvest->product_name }}</strong></div>
          <div><span>Exploitation</span><strong>{{ $harvest->farm->name }}</strong></div>
          <div><span>Producteur</span><strong>{{ $harvest->farm->user->company_name }}</strong></div>
          <div><span>Déclaré le</span><strong>{{ $harvest->created_at->format('d/m/Y') }}</strong></div>
        </div>
        <a href="{{ route('traceability.show', $harvest->batch_code) }}" target="_blank" class="btn btn-sm btn-light mt-2"><i class="mdi mdi-open-in-new"></i> Voir la page d'origine publique</a>

        <div class="m1-analysis mt-3">
          <div class="m1-analysis__head">
            <strong><i class="mdi mdi-shield-search"></i> Analyse de conformité</strong>
            <span class="m1-risk m1-risk--{{ $risk['level'] }}"><span class="m1-risk__bar"><span style="width: {{ $risk['score'] }}%"></span></span> {{ $risk['score'] }}/100 · {{ $risk['label'] }}</span>
          </div>
          @if($risk['reasons'])
            <ul class="m1-analysis__list">
              @foreach($risk['reasons'] as [$pts, $text])<li><span>+{{ $pts }}</span> {{ $text }}</li>@endforeach
            </ul>
          @else
            <p class="text-success mb-2"><i class="mdi mdi-check-decagram"></i> Aucune anomalie détectée.</p>
          @endif
          <div class="m1-analysis__meta">
            <span><i class="mdi mdi-leaf"></i> Fraîcheur : {{ $fresh['active'] ? $fresh['percent'] . ' % (DLC estimée ' . $fresh['best_before']->format('d/m/Y') . ')' : $fresh['label'] }}</span>
            <span><i class="mdi mdi-tag"></i> Prix conseillé : {{ number_format($price['min'], 2, ',', ' ') }}–{{ number_format($price['max'], 2, ',', ' ') }} TND/kg</span>
          </div>
          <button type="button" class="btn btn-sm btn-info mt-2" id="explain-risk"><i class="mdi mdi-robot"></i> Expliquer avec l'IA</button>
          <div id="risk-explanation" class="m1-analysis__ai" hidden></div>
        </div>
      </div>

      <div class="form-group col-md-6">
        <label for="farm_id">Exploitation <span class="req">*</span></label>
        <select id="farm_id" name="farm_id" class="form-control{{ $err('farm_id') }}" required>
          @foreach($farms as $f)
            <option value="{{ $f->id }}" data-area="{{ $f->total_area }}" @selected((int) old('farm_id', $harvest->farm_id) === $f->id)>{{ $f->name }} ({{ $f->total_area }} ha){{ $f->status !== 'active' ? ' · ' . $f->statusLabel() : '' }}</option>
          @endforeach
        </select>
        @error('farm_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
      <div class="form-group col-md-3">
        <label for="plot_name">Parcelle</label>
        <input type="text" id="plot_name" name="plot_name" value="{{ old('plot_name', $harvest->plot_name) }}" class="form-control{{ $err('plot_name') }}" maxlength="100">
        @error('plot_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
      <div class="form-group col-md-3">
        <label for="plot_area">Surface parcelle (ha)</label>
        <input type="number" id="plot_area" name="plot_area" value="{{ old('plot_area', $harvest->plot_area) }}" class="form-control{{ $err('plot_area') }}"
               step="0.01" min="0.01" data-rules="lte-farm-area:farm_id" data-min-msg="La surface doit être supérieure à 0 ha.">
        @error('plot_area')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>

      <div class="form-group col-md-4">
        <label for="product_name">Produit <span class="req">*</span></label>
        <input type="text" id="product_name" name="product_name" value="{{ old('product_name', $harvest->product_name) }}" class="form-control{{ $err('product_name') }}"
               required minlength="2" maxlength="100" data-pattern="[\p{L}\p{N}\s'\-]+" data-pattern-msg="Lettres, chiffres, espaces, tirets et apostrophes uniquement.">
        @error('product_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
      <div class="form-group col-md-4">
        <label for="category_id">Catégorie <span class="req">*</span></label>
        <select id="category_id" name="category_id" class="form-control{{ $err('category_id') }}" required>
          @foreach($categories as $c)
            <option value="{{ $c->id }}" @selected((int) old('category_id', $harvest->category_id) === $c->id)>{{ $c->name }}{{ $c->is_active ? '' : ' (désactivée)' }}</option>
          @endforeach
        </select>
        @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
      <div class="form-group col-md-4">
        <label for="variety">Variété</label>
        <input type="text" id="variety" name="variety" value="{{ old('variety', $harvest->variety) }}" class="form-control{{ $err('variety') }}" maxlength="100">
        @error('variety')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>

      <div class="form-group col-md-3">
        <label for="planting_date">Date de plantation</label>
        <input type="date" id="planting_date" name="planting_date" value="{{ $date('planting_date') }}" class="form-control{{ $err('planting_date') }}" data-rules="before:harvest_date">
        @error('planting_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
      <div class="form-group col-md-3">
        <label for="harvest_date">Date de récolte <span class="req">*</span></label>
        <input type="date" id="harvest_date" name="harvest_date" value="{{ $date('harvest_date') }}" class="form-control{{ $err('harvest_date') }}" required max="{{ now()->format('Y-m-d') }}" data-rules="not-future">
        @error('harvest_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
      <div class="form-group col-md-3">
        <label for="quantity">Quantité <span class="req">*</span></label>
        <input type="number" id="quantity" name="quantity" value="{{ old('quantity', $harvest->quantity) }}" class="form-control{{ $err('quantity') }}" required step="0.01" min="0" max="1000000" data-gt="0" data-min-msg="La quantité doit être strictement positive.">
        @error('quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
      <div class="form-group col-md-3">
        <label for="unit">Unité <span class="req">*</span></label>
        <select id="unit" name="unit" class="form-control{{ $err('unit') }}" required>
          @foreach(Harvest::UNITS as $k => $u)
            <option value="{{ $k }}" @selected(old('unit', $harvest->unit) === $k)>{{ $u['label'] }}</option>
          @endforeach
        </select>
        @error('unit')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>

      <div class="form-group col-md-4">
        <label for="quality_grade">Qualité <span class="req">*</span></label>
        <select id="quality_grade" name="quality_grade" class="form-control{{ $err('quality_grade') }}" required>
          @foreach(Harvest::QUALITY_GRADES as $k => $label)
            <option value="{{ $k }}" @selected(old('quality_grade', $harvest->quality_grade) === $k)>{{ $label }}</option>
          @endforeach
        </select>
        @error('quality_grade')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
      <div class="form-group col-md-4">
        <label for="status">Statut <span class="req">*</span></label>
        <select id="status" name="status" class="form-control{{ $err('status') }}" required>
          @foreach(Harvest::STATUSES as $k => $label)
            <option value="{{ $k }}" @selected(old('status', $harvest->status) === $k)>{{ $label }}</option>
          @endforeach
        </select>
        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
      <div class="form-group col-md-4">
        <label for="storage_conditions">Conditions de stockage</label>
        <input type="text" id="storage_conditions" name="storage_conditions" value="{{ old('storage_conditions', $harvest->storage_conditions) }}" class="form-control{{ $err('storage_conditions') }}" maxlength="255">
        @error('storage_conditions')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>

      <div class="form-group col-md-6">
        <label for="treatments">Traitements</label>
        <textarea id="treatments" name="treatments" rows="3" maxlength="1000" class="form-control{{ $err('treatments') }}">{{ old('treatments', $harvest->treatments) }}</textarea>
        @error('treatments')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
      <div class="form-group col-md-12">
        <label for="public_description">Fiche consommateur (publique)</label>
        <textarea id="public_description" name="public_description" rows="3" minlength="20" maxlength="1500" class="form-control{{ $err('public_description') }}">{{ old('public_description', $harvest->public_description) }}</textarea>
        @error('public_description')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
      <div class="form-group col-md-6">
        <label for="notes">Notes</label>
        <textarea id="notes" name="notes" rows="3" maxlength="2000" class="form-control{{ $err('notes') }}">{{ old('notes', $harvest->notes) }}</textarea>
        @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
      </div>
    </div>
  </div>
  <div class="card-footer d-flex justify-content-between">
    <a href="{{ route('backend.products.index') }}" class="btn btn-light">Annuler</a>
    <button type="submit" class="btn btn-success"><i class="mdi mdi-content-save"></i> Enregistrer</button>
  </div>
</form>
@endsection

@section('extra-js')
  <script>window.AGRI = { csrf: @json(csrf_token()) };</script>
  <script src="{{ asset('frontend/js/producer.js') }}"></script>
  <script>
    (function () {
      const btn = document.getElementById('explain-risk');
      const box = document.getElementById('risk-explanation');
      btn.addEventListener('click', async () => {
        btn.disabled = true;
        box.hidden = false;
        box.textContent = 'Analyse en cours…';
        try {
          const res = await fetch(@json(route('backend.products.risk', $harvest)), { headers: { Accept: 'application/json' } });
          if (!res.ok) throw new Error('Erreur ' + res.status);
          const data = await res.json();
          box.textContent = data.text;
          box.insertAdjacentHTML('beforeend', `<small class="d-block mt-2 text-muted">${data.source === 'ai' ? '🤖 Rédigé par l\'IA' : '⚙️ Synthèse du moteur de règles local (aucune clé IA configurée)'}</small>`);
        } catch (e) {
          box.textContent = 'Explication indisponible : ' + e.message;
        } finally {
          btn.disabled = false;
        }
      });
    })();
  </script>
@endsection
