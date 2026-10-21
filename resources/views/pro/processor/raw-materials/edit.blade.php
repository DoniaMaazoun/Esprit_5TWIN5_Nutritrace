@extends('pro.layouts.processor')

@section('title', 'Modifier Réception - Transformateur | Farm to Fork')

@section('processor-content')

<style>
  .quality-options {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.75rem;
  }

  .quality-option {
    position: relative;
  }

  .quality-option input[type="radio"] {
    display: none;
  }

  .quality-label {
    display: block;
    padding: 1rem;
    border: 2px solid #ddd;
    border-radius: 0.5rem;
    cursor: pointer;
    text-align: center;
    transition: all 0.3s ease;
  }

  .quality-option input[type="radio"]:checked + .quality-label {
    border-color: var(--processor-primary);
    background: #f0f9f4;
  }

  .quality-label i {
    display: block;
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
  }

  .quality-label span {
    display: block;
    font-weight: 600;
    color: var(--dark);
  }

  .reception-preview {
    background: #f0f9f4;
    padding: 1.5rem;
    border-radius: 12px;
    margin-bottom: 2rem;
    border-left: 4px solid var(--processor-primary);
  }

  .reception-preview h3 {
    color: var(--dark);
    margin-top: 0;
    margin-bottom: 1rem;
  }

  .preview-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.75rem;
    font-size: 0.95rem;
  }

  .preview-label {
    font-weight: 600;
    color: var(--gray);
  }

  .preview-value {
    color: var(--dark);
    font-weight: 500;
  }

  .form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
  }

  @media (max-width: 768px) {
    .form-row { grid-template-columns: 1fr; }
    .quality-options { grid-template-columns: 1fr; }
  }
</style>

<div class="page-head">
  <div>
    <a href="{{ route('pro.processor.raw-materials.show', $reception) }}" class="small"><i class="fa-solid fa-arrow-left"></i> Retour</a>
    <h1><i class="fa-solid fa-pen"></i> Modifier la Réception</h1>
    <p>Mettez à jour les détails de la réception</p>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-8">
    <div class="p-card">
      <!-- Warning if not pending -->
      @if(!$reception->isPending())
        <div class="alert alert-warning">
          <strong><i class="fa-solid fa-triangle-exclamation"></i> Attention :</strong>
          Cette réception n'est plus en attente. Les modifications ne sont pas autorisées.
        </div>
        <div style="text-align: center;">
          <a href="{{ route('pro.processor.raw-materials.show', $reception) }}" class="btn btn-outline-secondary">
            <i class="fa-solid fa-arrow-left"></i> Retour aux Détails
          </a>
        </div>
      @else
        <!-- Error Messages -->
        @if ($errors->any())
          <div class="alert alert-danger">
            <strong><i class="fa-solid fa-circle-xmark"></i> Erreurs dans le formulaire :</strong>
            <ul style="margin: 0.5rem 0 0 1.5rem;">
              @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        <!-- Info Banner -->
        <div class="alert alert-info">
          <i class="fa-solid fa-info-circle"></i>
          Vous pouvez modifier cette réception tant qu'elle est en attente. Une fois approuvée ou rejetée, aucune modification ne sera possible.
        </div>

        <!-- Reception Preview -->
        <div class="reception-preview">
          <h3><i class="fa-solid fa-cube"></i> Détails du Lot</h3>
          <div class="preview-row">
            <span class="preview-label">Produit :</span>
            <span class="preview-value">{{ $reception->harvest->product_name }}</span>
          </div>
          <div class="preview-row">
            <span class="preview-label">Producteur :</span>
            <span class="preview-value">{{ $reception->harvest->farm->user->company_name }}</span>
          </div>
          <div class="preview-row">
            <span class="preview-label">Date Réception :</span>
            <span class="preview-value">{{ $reception->reception_date->format('d/m/Y H:i') }}</span>
          </div>
        </div>

        <!-- Edit Form -->
        <form method="POST" action="{{ route('pro.processor.raw-materials.update', $reception) }}">
          @csrf
          @method('PUT')

          <h2 class="p-card__title" style="margin-top: 0; margin-bottom: 1rem;"><i class="fa-solid fa-file-import"></i> Détails Réception</h2>

          <!-- Quantity Received -->
          <div>
            <label for="quantity_received" class="form-label required">Quantité Reçue (kg)</label>
            <input 
              type="number" 
              id="quantity_received" 
              name="quantity_received" 
              step="0.01" 
              min="0.01"
              value="{{ old('quantity_received', $reception->quantity_received) }}"
              class="form-control"
              required
            >
            <small class="form-text text-muted">Quantité reçue à votre usine</small>
          </div>

          <!-- Quality Status -->
          <div style="margin-top: 1.5rem;">
            <label class="form-label required">État de Qualité</label>
            <div class="quality-options">
              <div class="quality-option">
                <input 
                  type="radio" 
                  id="quality_good" 
                  name="quality_status" 
                  value="good"
                  {{ old('quality_status', $reception->quality_status) === 'good' ? 'checked' : '' }}
                >
                <label for="quality_good" class="quality-label">
                  <i class="fa-solid fa-circle-check" style="color: var(--processor-primary);"></i>
                  <span>Bon</span>
                </label>
              </div>
              <div class="quality-option">
                <input 
                  type="radio" 
                  id="quality_acceptable" 
                  name="quality_status" 
                  value="acceptable"
                  {{ old('quality_status', $reception->quality_status) === 'acceptable' ? 'checked' : '' }}
                >
                <label for="quality_acceptable" class="quality-label">
                  <i class="fa-solid fa-triangle-exclamation" style="color: #ffc107;"></i>
                  <span>Acceptable</span>
                </label>
              </div>
              <div class="quality-option">
                <input 
                  type="radio" 
                  id="quality_damaged" 
                  name="quality_status" 
                  value="damaged"
                  {{ old('quality_status', $reception->quality_status) === 'damaged' ? 'checked' : '' }}
                >
                <label for="quality_damaged" class="quality-label">
                  <i class="fa-solid fa-circle-xmark" style="color: #dc3545;"></i>
                  <span>Endommagé</span>
                </label>
              </div>
            </div>
          </div>

          <!-- Quality Notes -->
          <div style="margin-top: 1.5rem;">
            <label for="quality_notes" class="form-label">Notes Qualité</label>
            <textarea 
              id="quality_notes" 
              name="quality_notes"
              class="form-control"
              rows="3"
            >{{ old('quality_notes', $reception->quality_notes) }}</textarea>
            <small class="form-text text-muted">Observations sur la qualité du lot reçu</small>
          </div>

          <!-- Storage Location & Temperature -->
          <div class="form-row" style="margin-top: 1.5rem;">
            <div>
              <label for="storage_location" class="form-label">Localisation de Stockage</label>
              <input 
                type="text" 
                id="storage_location" 
                name="storage_location" 
                value="{{ old('storage_location', $reception->storage_location) }}"
                class="form-control"
              >
              <small class="form-text text-muted">Où est stocké le produit dans votre usine</small>
            </div>

            <div>
              <label for="temperature" class="form-label">Température de Stockage (°C)</label>
              <input 
                type="number" 
                id="temperature" 
                name="temperature" 
                step="0.1"
                min="-50"
                max="50"
                value="{{ old('temperature', $reception->temperature) }}"
                class="form-control"
              >
              <small class="form-text text-muted">Température de stockage (ex: 4°C)</small>
            </div>
          </div>

          <!-- Internal Notes -->
          <div style="margin-top: 1.5rem;">
            <label for="internal_notes" class="form-label">Notes Internes</label>
            <textarea 
              id="internal_notes" 
              name="internal_notes"
              class="form-control"
              rows="3"
            >{{ old('internal_notes', $reception->internal_notes) }}</textarea>
            <small class="form-text text-muted">Notes internes (non visibles au producteur)</small>
          </div>

          <!-- Actions -->
          <div style="display: flex; gap: 1rem; margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--gray-light);">
            <button type="submit" class="btn btn-success flex-grow-1">
              <i class="fa-solid fa-save"></i> Enregistrer les Modifications
            </button>
            <a href="{{ route('pro.processor.raw-materials.show', $reception) }}" class="btn btn-outline-secondary flex-grow-1">
              <i class="fa-solid fa-times"></i> Annuler
            </a>
          </div>
        </form>
      @endif
    </div>
  </div>

  <div class="col-lg-4">
    <div class="p-card" style="background: linear-gradient(135deg, var(--processor-primary-dark), var(--processor-primary)); color: #fff; text-align: center;">
      <div style="padding: 20px;">
        <i class="fa-solid fa-edit" style="font-size: 3rem; opacity: .3; margin-bottom: 12px; display: block;"></i>
        <h3 style="margin: 0 0 6px; font-size: 1.2rem; font-weight: 700;">{{ $reception->harvest->product_name }}</h3>
        <p style="margin: 0; opacity: .9; font-size: .92rem;">Réception #{{ $reception->id }}</p>
        <hr style="border-color: rgba(255,255,255,.2); margin: 12px 0;">
        <dl style="margin: 0; font-size: .85rem; text-align: left;">
          <dt style="font-weight: 600; opacity: .8;">Statut</dt>
          <dd style="margin: 0 0 8px; font-weight: 500;"><span class="tag tag--{{ $reception->status }}">{{ $reception->getStatusLabel() }}</span></dd>
          <dt style="font-weight: 600; opacity: .8;">Qualité</dt>
          <dd style="margin: 0; font-weight: 500;">{{ $reception->getQualityLabel() }}</dd>
        </dl>
      </div>
    </div>

    <div class="p-card">
      <h3 class="p-card__title"><i class="fa-solid fa-info-circle"></i> Information</h3>
      <dl style="margin: 0;">
        <dt style="font-size: .85rem; font-weight: 600; color: var(--gray); text-transform: uppercase; margin-bottom: 4px;">Créée le</dt>
        <dd style="margin: 0 0 12px;">{{ $reception->created_at->format('d/m/Y à H:i') }}</dd>
        <dt style="font-size: .85rem; font-weight: 600; color: var(--gray); text-transform: uppercase; margin-bottom: 4px;">Modifiée le</dt>
        <dd style="margin: 0;">{{ $reception->updated_at->format('d/m/Y à H:i') }}</dd>
      </dl>
    </div>
  </div>
</div>

@endsection
