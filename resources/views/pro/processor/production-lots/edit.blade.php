@extends('pro.layouts.processor')

@section('title', 'Éditer - ' . $lot->getProductName() . ' | Farm to Fork')

@section('processor-content')

<style>
  .production-form { max-width: 900px; }
  .form-section { margin-bottom: 2rem; padding-bottom: 2rem; border-bottom: 1px solid var(--gray-light); }
  .form-section:last-of-type { border-bottom: none; }
  .section-title { font-size: 1.1rem; font-weight: 700; color: var(--processor-primary); margin-bottom: 1rem; display: flex; align-items: center; gap: 8px; }
  .info-box { background: #f0f9f4; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; border-left: 4px solid var(--processor-primary); }
  .info-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; }
  .info-row:last-child { margin-bottom: 0; }
  .info-label { font-weight: 600; color: var(--processor-primary); }
  .info-value { color: #333; }
  .form-actions { display: flex; gap: 1rem; margin-top: 2rem; justify-content: center; }
  .btn-submit { background: var(--processor-primary); color: white; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 1rem; }
  .btn-submit:hover { background: var(--processor-primary-dark); }
  .btn-cancel { background: #6c757d; color: white; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; }
  .btn-cancel:hover { background: #5a6268; }
  .error-message { color: #dc3545; font-size: 0.85rem; margin-top: 4px; }
  .is-invalid { border-color: #dc3545 !important; }
</style>

<div class="page-head">
  <div>
    <a href="{{ route('pro.processor.production-lots.show', $lot) }}" class="small"><i class="fa-solid fa-arrow-left"></i> Retour</a>
    <h1><i class="fa-solid fa-pen"></i> Éditer: {{ $lot->getProductName() }}</h1>
  </div>
</div>

<div class="p-card production-form">
  @if ($errors->any())
    <div class="alert alert-danger" role="alert">
      <strong><i class="fa-solid fa-circle-exclamation"></i> Erreurs de validation:</strong>
      <ul class="mb-0">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="alert alert-info" role="alert">
    <i class="fa-solid fa-info-circle"></i>
    <strong>Note :</strong> Vous pouvez modifier le statut et les notes. La recette et les quantités ne peuvent pas être modifiées après création.
  </div>

  <form action="{{ route('pro.processor.production-lots.update', $lot) }}" method="POST" id="editForm">
    @csrf
    @method('PUT')

    <!-- Section: Infos non modifiables -->
    <div class="form-section">
      <h2 class="section-title"><i class="fa-solid fa-lock"></i> Informations Générales (Non modifiables)</h2>

      <div class="info-box">
        <div class="info-row">
          <span class="info-label">Recette :</span>
          <span class="info-value"><strong>{{ $lot->recipe->name }}</strong></span>
        </div>
        <div class="info-row">
          <span class="info-label">Quantité :</span>
          <span class="info-value"><strong>{{ $lot->getQuantityDisplay() }}</strong></span>
        </div>
        <div class="info-row">
          <span class="info-label">Date Production :</span>
          <span class="info-value">{{ $lot->production_date->format('d/m/Y') }}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Date Péremption :</span>
          <span class="info-value">{{ $lot->expiry_date->format('d/m/Y') }}</span>
        </div>
        <div class="info-row">
          <span class="info-label">Code QR :</span>
          <span class="info-value"><code>{{ $lot->qr_code_data }}</code></span>
        </div>
      </div>
    </div>

    <!-- Section: Modification -->
    <div class="form-section">
      <h2 class="section-title"><i class="fa-solid fa-pencil"></i> Modification du Lot</h2>

      <div class="mb-3">
        <label class="form-label" for="status">Statut</label>
        <select id="status" name="status" class="form-select @error('status') is-invalid @enderror">
          <option value="">-- Pas de changement --</option>
          <option value="produced" @selected($lot->status === 'produced')>Produit ✅</option>
          <option value="packaged" @selected($lot->status === 'packaged')>Emballé 📦</option>
          <option value="shipped" @selected($lot->status === 'shipped')>Expédié 🚚</option>
          <option value="archived" @selected($lot->status === 'archived')>Archivé 📋</option>
        </select>
        <small class="text-muted">Laissez vide pour ne pas modifier le statut</small>
        @error('status') <span class="error-message">{{ $message }}</span> @enderror
      </div>

      <div class="mb-3">
        <label class="form-label" for="production_notes">Notes de Production</label>
        <textarea id="production_notes" name="production_notes" class="form-control @error('production_notes') is-invalid @enderror" 
          rows="5" placeholder="Observations, conditions spéciales, modifications, etc.">{{ $lot->production_notes }}</textarea>
        <small class="text-muted">Laissez vide pour ne pas modifier les notes</small>
        @error('production_notes') <span class="error-message">{{ $message }}</span> @enderror
      </div>
    </div>

    <!-- Actions -->
    <div class="form-actions">
      <button type="submit" class="btn-submit">
        <i class="fa-solid fa-save"></i> Mettre à Jour
      </button>
      <a href="{{ route('pro.processor.production-lots.show', $lot) }}" class="btn-cancel">
        <i class="fa-solid fa-times"></i> Annuler
      </a>
    </div>
  </form>
</div>

@endsection
