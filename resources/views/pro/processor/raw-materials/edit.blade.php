@extends('frontend.layouts.app')

@section('title', 'Modifier Réception - Transformateur | Farm to Fork')

@section('extra-css')
<style>
  .form-section {
    background: linear-gradient(135deg, #f0f9f7 0%, #e8f5f3 100%);
    padding: 40px 0;
    min-height: 100vh;
  }

  .form-card {
    background: white;
    border-radius: 1rem;
    padding: 2rem;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    max-width: 700px;
    margin: 0 auto;
  }

  .form-header {
    background: linear-gradient(135deg, #0dcaf0 0%, #31d5f8 100%);
    color: white;
    padding: 2rem;
    border-radius: 1rem;
    margin-bottom: 2rem;
    text-align: center;
  }

  .form-header h1 {
    font-size: 1.8rem;
    font-weight: 700;
    margin: 0;
  }

  .reception-preview {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 0.75rem;
    margin-bottom: 2rem;
    border-left: 4px solid #0dcaf0;
  }

  .reception-preview h3 {
    color: #1e3a2b;
    margin-top: 0;
  }

  .preview-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.75rem;
    font-size: 0.95rem;
  }

  .preview-label {
    font-weight: 600;
    color: #666;
  }

  .preview-value {
    color: #1e3a2b;
  }

  .form-group {
    margin-bottom: 1.5rem;
  }

  .form-group label {
    display: block;
    font-weight: 600;
    color: #1e3a2b;
    margin-bottom: 0.5rem;
  }

  .form-group input,
  .form-group select,
  .form-group textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 0.5rem;
    font-size: 1rem;
    transition: border-color 0.3s ease;
  }

  .form-group input:focus,
  .form-group select:focus,
  .form-group textarea:focus {
    outline: none;
    border-color: #0dcaf0;
    box-shadow: 0 0 0 3px rgba(13, 202, 240, 0.1);
  }

  .form-group textarea {
    resize: vertical;
    min-height: 100px;
  }

  .required::after {
    content: ' *';
    color: #dc3545;
  }

  .form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid #e0e0e0;
  }

  .btn-submit {
    flex: 1;
    background: #0dcaf0;
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 0.5rem;
    cursor: pointer;
    font-weight: 600;
    font-size: 1rem;
    transition: background 0.3s ease;
  }

  .btn-submit:hover {
    background: #31d5f8;
  }

  .btn-cancel {
    flex: 1;
    background: #6c757d;
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 0.5rem;
    cursor: pointer;
    font-weight: 600;
    font-size: 1rem;
    text-decoration: none;
    transition: background 0.3s ease;
    text-align: center;
  }

  .btn-cancel:hover {
    background: #5a6268;
  }

  .error-message {
    background: #f8d7da;
    color: #721c24;
    padding: 1rem;
    border-radius: 0.5rem;
    margin-bottom: 1.5rem;
  }

  .warning-box {
    background: #fff3cd;
    color: #856404;
    padding: 1rem;
    border-radius: 0.5rem;
    margin-bottom: 1.5rem;
    border-left: 4px solid #ffc107;
  }

  .help-text {
    font-size: 0.85rem;
    color: #666;
    margin-top: 0.25rem;
  }

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
    border-color: #0dcaf0;
    background: #e0f7ff;
  }

  .quality-label i {
    display: block;
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
  }

  .quality-label span {
    display: block;
    font-weight: 600;
    color: #1e3a2b;
  }

  .info-banner {
    background: #e7f3ff;
    border-left: 4px solid #0d6efd;
    padding: 1rem;
    border-radius: 0.5rem;
    margin-bottom: 1.5rem;
    font-size: 0.95rem;
    color: #0c5fc7;
  }
</style>
@endsection

@section('content')

<div class="form-section">
  <div class="container">
    <!-- Form Header -->
    <div class="form-header">
      <h1><i class="fa-solid fa-edit"></i> Modifier la Réception</h1>
    </div>

    <div class="form-card">
      <!-- Warning if not pending -->
      @if(!$reception->isPending())
        <div class="warning-box">
          <strong><i class="fa-solid fa-triangle-exclamation"></i> Attention :</strong>
          Cette réception n'est plus en attente. Les modifications ne sont pas autorisées.
        </div>
        <div style="text-align: center; margin-bottom: 2rem;">
          <a href="{{ route('pro.processor.raw-materials.show', $reception) }}" style="display: inline-block; background: #6c757d; color: white; padding: 0.75rem 1.5rem; border-radius: 0.5rem; text-decoration: none;">
            <i class="fa-solid fa-arrow-left"></i> Retour aux Détails
          </a>
        </div>
      @else
        <!-- Error Messages -->
        @if ($errors->any())
          <div class="error-message">
            <strong>Erreurs dans le formulaire :</strong>
            <ul style="margin: 0.5rem 0 0 1.5rem;">
              @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        <!-- Info Banner -->
        <div class="info-banner">
          <i class="fa-solid fa-info-circle"></i>
          Vous pouvez modifier cette réception tant qu'elle est en attente. Une fois approuvée ou rejetée, aucune modification ne sera possible.
        </div>

        <!-- Reception Preview -->
        <div class="reception-preview">
          <h3>📦 Lot de Récolte</h3>
          <div class="preview-row">
            <span class="preview-label">Produit :</span>
            <span class="preview-value">{{ $reception->harvest->name }}</span>
          </div>
          <div class="preview-row">
            <span class="preview-label">Producteur :</span>
            <span class="preview-value">{{ $reception->harvest->farm->user->name }}</span>
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

          <!-- Quantity Received -->
          <div class="form-group">
            <label for="quantity_received" class="required">Quantité Reçue (kg)</label>
            <input 
              type="number" 
              id="quantity_received" 
              name="quantity_received" 
              step="0.01" 
              min="0.01"
              value="{{ old('quantity_received', $reception->quantity_received) }}"
              required
            >
            <div class="help-text">Quantité reçue à votre usine</div>
          </div>

          <!-- Quality Status -->
          <div class="form-group">
            <label class="required">État de Qualité</label>
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
                  <i class="fa-solid fa-circle-check" style="color: #28a745;"></i>
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
          <div class="form-group">
            <label for="quality_notes">Notes Qualité</label>
            <textarea 
              id="quality_notes" 
              name="quality_notes"
            >{{ old('quality_notes', $reception->quality_notes) }}</textarea>
            <div class="help-text">Observations sur la qualité du lot reçu</div>
          </div>

          <!-- Storage Location -->
          <div class="form-group">
            <label for="storage_location">Localisation de Stockage</label>
            <input 
              type="text" 
              id="storage_location" 
              name="storage_location" 
              value="{{ old('storage_location', $reception->storage_location) }}"
            >
            <div class="help-text">Où est stocké le produit dans votre usine</div>
          </div>

          <!-- Temperature -->
          <div class="form-group">
            <label for="temperature">Température de Stockage (°C)</label>
            <input 
              type="number" 
              id="temperature" 
              name="temperature" 
              step="0.1"
              min="-50"
              max="50"
              value="{{ old('temperature', $reception->temperature) }}"
            >
            <div class="help-text">Température de stockage (ex: 4°C pour produits frais)</div>
          </div>

          <!-- Internal Notes -->
          <div class="form-group">
            <label for="internal_notes">Notes Internes</label>
            <textarea 
              id="internal_notes" 
              name="internal_notes"
            >{{ old('internal_notes', $reception->internal_notes) }}</textarea>
            <div class="help-text">Notes internes (non visibles au producteur)</div>
          </div>

          <!-- Actions -->
          <div class="form-actions">
            <button type="submit" class="btn-submit">
              <i class="fa-solid fa-save"></i> Enregistrer les Modifications
            </button>
            <a href="{{ route('pro.processor.raw-materials.show', $reception) }}" class="btn-cancel">
              <i class="fa-solid fa-times"></i> Annuler
            </a>
          </div>
        </form>
      @endif
    </div>
  </div>
</div>

@endsection
