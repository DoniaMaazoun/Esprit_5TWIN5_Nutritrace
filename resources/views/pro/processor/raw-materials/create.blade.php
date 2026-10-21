@extends('pro.layouts.processor')

@section('title', 'Enregistrer une Réception - Transformateur | Farm to Fork')

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

  .harvest-preview {
    background: #f0f9f4;
    padding: 1.5rem;
    border-radius: 12px;
    margin-bottom: 2rem;
    border-left: 4px solid var(--processor-primary);
  }

  .harvest-preview h3 {
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
    <a href="{{ route('pro.processor.raw-materials.index') }}" class="small"><i class="fa-solid fa-arrow-left"></i> Retour</a>
    <h1><i class="fa-solid fa-inbox"></i> Enregistrer une Réception</h1>
    <p>Enregistrez la réception d'un nouveau lot de matière brute</p>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-8">
    <div class="p-card">
      <!-- Error Messages -->
      @if ($errors->any())
        <div class="alert alert-danger" style="margin-bottom: 1.5rem;">
          <strong><i class="fa-solid fa-circle-xmark"></i> Erreurs dans le formulaire :</strong>
          <ul style="margin: 0.5rem 0 0 1.5rem;">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <!-- Harvest Preview -->
      <div class="harvest-preview">
        <h3><i class="fa-solid fa-cube"></i> Lot de Récolte</h3>
        <div class="preview-row">
          <span class="preview-label">Produit :</span>
          <span class="preview-value">{{ $harvest->product_name }}</span>
        </div>
        <div class="preview-row">
          <span class="preview-label">Catégorie :</span>
          <span class="preview-value">{{ $harvest->category->name ?? 'N/A' }}</span>
        </div>
        <div class="preview-row">
          <span class="preview-label">Producteur :</span>
          <span class="preview-value">{{ $harvest->farm->user->company_name }}</span>
        </div>
        <div class="preview-row">
          <span class="preview-label">Ferme :</span>
          <span class="preview-value">{{ $harvest->farm->name }}</span>
        </div>
        <div class="preview-row">
          <span class="preview-label">Date Récolte :</span>
          <span class="preview-value">{{ $harvest->harvest_date?->format('d/m/Y') ?? 'N/A' }}</span>
        </div>
        <div class="preview-row">
          <span class="preview-label">Quantité Produite :</span>
          <span class="preview-value">{{ $harvest->quantityInKg() }} kg</span>
        </div>
      </div>

      <!-- Reception Form -->
      <form method="POST" action="{{ route('pro.processor.raw-materials.store', $harvest) }}">
        @csrf

        <h2 class="p-card__title" style="margin-top: 2rem; margin-bottom: 1rem;"><i class="fa-solid fa-file-import"></i> Détails Réception</h2>

        <!-- Quantity Received & Reception Date -->
        <div class="form-row">
          <div>
            <label for="quantity_received" class="form-label required">Quantité Reçue (kg)</label>
            <input 
              type="number" 
              id="quantity_received" 
              name="quantity_received" 
              step="0.01" 
              min="0.01"
              max="{{ $harvest->quantityInKg() }}"
              value="{{ old('quantity_received') }}"
              placeholder="Ex: 450.50"
              class="form-control"
              required
            >
            <small class="form-text text-muted">Quantité reçue à votre usine</small>
          </div>

          <div>
            <label for="reception_date" class="form-label required">Date de Réception</label>
            <input 
              type="date" 
              id="reception_date" 
              name="reception_date" 
              value="{{ old('reception_date', now()->format('Y-m-d')) }}"
              class="form-control"
              required
            >
            <small class="form-text text-muted">Date d'arrivée du lot à l'usine</small>
          </div>
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
                {{ old('quality_status') === 'good' ? 'checked' : '' }}
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
                {{ old('quality_status') === 'acceptable' ? 'checked' : '' }}
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
                {{ old('quality_status') === 'damaged' ? 'checked' : '' }}
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
            placeholder="Décrivez l'état du produit, dommages observés, etc."
            class="form-control"
            rows="3"
          >{{ old('quality_notes') }}</textarea>
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
              placeholder="Ex: Entrepôt A - Étagère 3"
              value="{{ old('storage_location') }}"
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
              placeholder="Ex: 4"
              value="{{ old('temperature') }}"
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
            placeholder="Notes pour votre équipe..."
            class="form-control"
            rows="3"
          >{{ old('internal_notes') }}</textarea>
          <small class="form-text text-muted">Notes internes (non visibles au producteur)</small>
        </div>

        <!-- Actions -->
        <div style="display: flex; gap: 1rem; margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--gray-light);">
          <button type="submit" class="btn btn-success flex-grow-1">
            <i class="fa-solid fa-check"></i> Enregistrer la Réception
          </button>
          <a href="{{ route('pro.processor.raw-materials.index') }}" class="btn btn-outline-secondary flex-grow-1">
            <i class="fa-solid fa-times"></i> Annuler
          </a>
        </div>
      </form>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="p-card" style="background: linear-gradient(135deg, var(--processor-primary-dark), var(--processor-primary)); color: #fff; text-align: center;">
      <div style="padding: 20px;">
        <i class="fa-solid fa-inbox" style="font-size: 3rem; opacity: .3; margin-bottom: 12px; display: block;"></i>
        <h3 style="margin: 0 0 6px; font-size: 1.2rem; font-weight: 700;">{{ $harvest->product_name }}</h3>
        <p style="margin: 0; opacity: .9; font-size: .92rem;">{{ $harvest->category->name ?? 'N/A' }}</p>
        <hr style="border-color: rgba(255,255,255,.2); margin: 12px 0;">
        <dl style="margin: 0; font-size: .85rem; text-align: left;">
          <dt style="font-weight: 600; opacity: .8;">Producteur</dt>
          <dd style="margin: 0 0 8px; font-weight: 500;">{{ $harvest->farm->user->company_name }}</dd>
          <dt style="font-weight: 600; opacity: .8;">Quantité</dt>
          <dd style="margin: 0; font-weight: 500;">{{ $harvest->quantityInKg() }} kg</dd>
        </dl>
      </div>
    </div>

    <div class="p-card">
      <h3 class="p-card__title"><i class="fa-solid fa-info-circle"></i> Aide</h3>
      <ul style="margin: 0; padding-left: 1.5rem; color: var(--gray-dark); font-size: .9rem;">
        <li style="margin-bottom: 8px;">Vérifiez la quantité reçue</li>
        <li style="margin-bottom: 8px;">Évaluez l'état de qualité</li>
        <li style="margin-bottom: 8px;">Notez le lieu de stockage</li>
        <li>Conservez les conditions de température</li>
      </ul>
    </div>
  </div>
</div>

@endsection
