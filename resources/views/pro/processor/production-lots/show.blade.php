@extends('pro.layouts.processor')

@section('title', $lot->getProductName() . ' - Détails Lot | Farm to Fork')

@section('processor-content')

<style>
  .prod-page-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 1rem;
  }

  /* En-tête */
  .prod-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 2rem;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e5e7eb;
  }

  .prod-header-left {
    flex: 1;
  }

  .prod-header-left h1 {
    font-size: 2rem;
    font-weight: 700;
    color: #1e3a2b;
    margin: 0 0 0.5rem 0;
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .prod-header-left .subtitle {
    font-size: 0.95rem;
    color: #666;
    margin: 0;
  }

  .prod-header-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: linear-gradient(135deg, #28a745, #1e7e34);
    color: white;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 600;
    margin-top: 8px;
  }

  .prod-header-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
  }

  .prod-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 16px;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    border: none;
    transition: all 0.3s;
    font-size: 0.9rem;
  }

  .prod-btn-primary {
    background: #28a745;
    color: white;
  }

  .prod-btn-primary:hover {
    background: #1e7e34;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.2);
  }

  .prod-btn-danger {
    background: #f8f9fa;
    color: #dc3545;
    border: 2px solid #dc3545;
  }

  .prod-btn-danger:hover {
    background: #dc3545;
    color: white;
    transform: translateY(-2px);
  }

  /* KPI Grid */
  .prod-kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
  }

  .prod-kpi-card {
    background: white;
    border-radius: 10px;
    padding: 1.25rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
    border: 1px solid #f0f0f0;
    transition: all 0.3s;
  }

  .prod-kpi-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    border-color: #e0e0e0;
  }

  .prod-kpi-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    background: linear-gradient(135deg, rgba(40, 167, 69, 0.1), rgba(40, 167, 69, 0.05));
    color: #28a745;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 8px;
    font-size: 1.2rem;
  }

  .prod-kpi-label {
    font-size: 0.8rem;
    color: #999;
    text-transform: uppercase;
    font-weight: 600;
    margin-bottom: 4px;
  }

  .prod-kpi-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e3a2b;
  }

  /* Layout Grid */
  .prod-main-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 2rem;
    margin-top: 2rem;
  }

  @media (max-width: 1024px) {
    .prod-main-grid {
      grid-template-columns: 1fr;
      gap: 1.5rem;
    }

    .prod-header {
      flex-direction: column;
      gap: 1rem;
    }

    .prod-header-actions {
      width: 100%;
    }

    .prod-btn {
      flex: 1;
    }
  }

  /* Card Styles */
  .prod-card {
    background: white;
    border-radius: 10px;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
    border: 1px solid #f0f0f0;
  }

  .prod-card-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: #1e3a2b;
    margin: 0 0 1rem 0;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .prod-card-title i {
    color: #28a745;
    font-size: 1.2rem;
  }

  /* Alert Box */
  .prod-alert {
    padding: 1rem;
    border-radius: 8px;
    border-left: 4px solid;
    margin-bottom: 1.5rem;
    display: flex;
    gap: 12px;
  }

  .prod-alert-success {
    background: #e8f5e9;
    border-color: #2e7d32;
    color: #1b5e20;
  }

  .prod-alert-warning {
    background: #fff3cd;
    border-color: #ffc107;
    color: #856404;
  }

  .prod-alert-danger {
    background: #f8d7da;
    border-color: #dc3545;
    color: #721c24;
  }

  .prod-alert-icon {
    flex-shrink: 0;
    font-size: 1.2rem;
    margin-top: 2px;
  }

  .prod-alert-content strong {
    display: block;
    margin-bottom: 4px;
  }

  .prod-alert-content p {
    margin: 0;
    font-size: 0.9rem;
  }

  /* Table */
  .prod-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 1rem;
  }

  .prod-table thead {
    background: #f8fafb;
    border-bottom: 2px solid #e0e0e0;
  }

  .prod-table th {
    padding: 1rem;
    text-align: left;
    font-size: 0.85rem;
    font-weight: 700;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }

  .prod-table tbody tr {
    border-bottom: 1px solid #f0f0f0;
    transition: all 0.2s;
  }

  .prod-table tbody tr:hover {
    background: #f8fafb;
  }

  .prod-table td {
    padding: 1rem;
    color: #333;
    font-size: 0.95rem;
  }

  .prod-table td strong {
    color: #1e3a2b;
  }

  .prod-tag {
    display: inline-block;
    background: #f0f9f4;
    color: #1B4332;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.85rem;
    font-weight: 600;
  }

  /* Recipe Card */
  .prod-recipe-card {
    background: #f1f8f3;
    border-left: 4px solid #28a745;
    padding: 1.25rem;
    border-radius: 8px;
    margin-top: 1.5rem;
  }

  .prod-recipe-card strong {
    display: block;
    margin-bottom: 0.75rem;
    color: #1e3a2b;
  }

  .prod-recipe-list {
    font-size: 0.9rem;
    color: #555;
  }

  .prod-recipe-list p {
    margin: 6px 0;
    display: flex;
    gap: 8px;
    align-items: center;
  }

  .prod-recipe-list i {
    color: #28a745;
    min-width: 16px;
  }

  /* QR Code Section */
  .prod-qr-container {
    background: white;
    border-radius: 10px;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
    border: 1px solid #f0f0f0;
    text-align: center;
  }

  .prod-qr-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: #1e3a2b;
    margin: 0 0 1rem 0;
  }

  .prod-qr-content {
    background: linear-gradient(135deg, #f8fafb, #f0f5f2);
    border-radius: 10px;
    padding: 1.5rem;
    margin-bottom: 1rem;
  }

  .prod-qr-image {
    display: flex;
    justify-content: center;
    margin-bottom: 1rem;
  }

  .prod-qr-image img {
    max-width: 200px;
    height: auto;
    background: white;
    padding: 1rem;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  }

  .prod-qr-code-display {
    font-size: 0.75rem;
    color: #999;
    word-break: break-all;
    font-family: monospace;
    margin-bottom: 1rem;
  }

  .prod-qr-buttons {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
  }

  .prod-qr-btn {
    flex: 1;
    min-width: 100px;
    padding: 10px 12px;
    border-radius: 6px;
    border: none;
    font-weight: 600;
    cursor: pointer;
    font-size: 0.9rem;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
  }

  .prod-qr-btn-primary {
    background: #28a745;
    color: white;
  }

  .prod-qr-btn-primary:hover {
    background: #1e7e34;
  }

  .prod-qr-btn-secondary {
    background: #f0f0f0;
    color: #333;
    border: 1px solid #ddd;
  }

  .prod-qr-btn-secondary:hover {
    background: #e8e8e8;
  }

  /* Space utilities */
  .space-y-4 > * + * {
    margin-top: 1rem;
  }

  .space-y-6 > * + * {
    margin-top: 1.5rem;
  }

  /* Breadcrumb */
  .prod-breadcrumb {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.9rem;
    color: #666;
    margin-bottom: 1.5rem;
  }

  .prod-breadcrumb a {
    color: #28a745;
    text-decoration: none;
    transition: all 0.3s;
  }

  .prod-breadcrumb a:hover {
    color: #1e7e34;
  }

  .prod-breadcrumb i {
    font-size: 0.8rem;
  }
</style>

<!-- Main Container -->
<div class="prod-page-container">

  <!-- Breadcrumb -->
  <div class="prod-breadcrumb">
    <a href="{{ route('pro.processor.production-lots.index') }}">
      <i class="fa-solid fa-list"></i> Mes lots
    </a>
    <i class="fa-solid fa-chevron-right"></i>
    <span>Détails lot</span>
  </div>

  <!-- Header Section -->
  <div class="prod-header">
    <div class="prod-header-left">
      <h1>{{ $lot->getProductName() }}</h1>
      <p class="subtitle">Lot de production créé par <strong>{{ $lot->processor->name }}</strong></p>
      <div class="prod-header-badge">
        @if($lot->status === 'produced')
          <i class="fa-solid fa-check-circle"></i> Produit
        @elseif($lot->status === 'packaged')
          <i class="fa-solid fa-box"></i> Emballé
        @elseif($lot->status === 'shipped')
          <i class="fa-solid fa-truck"></i> Expédié
        @else
          <i class="fa-solid fa-archive"></i> Archivé
        @endif
      </div>
    </div>
    <div class="prod-header-actions">
      <a href="{{ route('pro.processor.production-lots.edit', $lot) }}" class="prod-btn prod-btn-primary">
        <i class="fa-solid fa-pen"></i> Modifier
      </a>
      <form method="POST" action="{{ route('pro.processor.production-lots.destroy', $lot) }}" style="display: contents;" data-confirm="Supprimer le lot « {{ $lot->getProductName() }} » ? Cette action est irréversible.">
        @csrf @method('DELETE')
        <button type="submit" class="prod-btn prod-btn-danger">
          <i class="fa-solid fa-trash"></i> Supprimer
        </button>
      </form>
    </div>
  </div>

  <!-- KPI Cards Grid -->
  <div class="prod-kpi-grid">
    <!-- Quantité Produite -->
    <div class="prod-kpi-card">
      <div class="prod-kpi-icon"><i class="fa-solid fa-weight-hanging"></i></div>
      <div class="prod-kpi-label">Quantité Produite</div>
      <div class="prod-kpi-value">{{ $lot->getQuantityDisplay() }}</div>
    </div>

    <!-- Date Production -->
    <div class="prod-kpi-card">
      <div class="prod-kpi-icon"><i class="fa-solid fa-calendar-check"></i></div>
      <div class="prod-kpi-label">Date Production</div>
      <div class="prod-kpi-value">{{ $lot->production_date->format('d/m/Y') }}</div>
    </div>

    <!-- Date Péremption -->
    <div class="prod-kpi-card">
      <div class="prod-kpi-icon"><i class="fa-solid fa-hourglass-end"></i></div>
      <div class="prod-kpi-label">Date Péremption</div>
      <div class="prod-kpi-value">{{ $lot->expiry_date->format('d/m/Y') }}</div>
    </div>

    <!-- Nombre d'Ingrédients -->
    <div class="prod-kpi-card">
      <div class="prod-kpi-icon"><i class="fa-solid fa-cubes"></i></div>
      <div class="prod-kpi-label">Ingrédient{{ $lot->getRecipeItems()->count() !== 1 ? 's' : '' }}</div>
      <div class="prod-kpi-value">{{ $lot->getRecipeItems()->count() }}</div>
    </div>
  </div>

  <!-- Main Content Grid -->
  <div class="prod-main-grid">
    <!-- Left Column (2/3) -->
    <div class="space-y-6">
      
      <!-- Validity Status Alert -->
      @if($lot->isExpired())
        <div class="prod-alert prod-alert-danger">
          <div class="prod-alert-icon"><i class="fa-solid fa-exclamation-triangle"></i></div>
          <div class="prod-alert-content">
            <strong>⚠️ Ce lot est périmé</strong>
            <p>Ce lot ne doit pas être distribué. Date limite dépassée le <strong>{{ $lot->expiry_date->format('d/m/Y') }}</strong>.</p>
          </div>
        </div>
      @elseif($lot->isCloseToExpiry())
        <div class="prod-alert prod-alert-warning">
          <div class="prod-alert-icon"><i class="fa-solid fa-hourglass-half"></i></div>
          <div class="prod-alert-content">
            <strong>⏳ Péremption proche</strong>
            <p>Ce lot expire le <strong>{{ $lot->expiry_date->format('d/m/Y') }}</strong>. À écouler en priorité.</p>
          </div>
        </div>
      @else
        <div class="prod-alert prod-alert-success">
          <div class="prod-alert-icon"><i class="fa-solid fa-check-circle"></i></div>
          <div class="prod-alert-content">
            <strong>✓ Lot valide</strong>
            <p>Ce lot est en bon état. Valide jusqu'au <strong>{{ $lot->expiry_date->format('d/m/Y') }}</strong>.</p>
          </div>
        </div>
      @endif

      <!-- Production Notes -->
      @if($lot->production_notes)
        <div class="prod-card">
          <div class="prod-card-title"><i class="fa-solid fa-sticky-note"></i> Notes de Production</div>
          <p style="margin: 0; line-height: 1.6; white-space: pre-wrap; color: #555;">{{ $lot->production_notes }}</p>
        </div>
      @endif

      <!-- Ingredients Used -->
      <div class="prod-card">
        <div class="prod-card-title"><i class="fa-solid fa-list-check"></i> Ingrédients Utilisés</div>

        @if($lot->getRecipeItems()->isEmpty())
          <div style="text-align: center; padding: 2rem; color: #999;">
            <i class="fa-solid fa-inbox" style="font-size: 2rem; margin-bottom: 0.5rem; opacity: 0.5;"></i>
            <p>Aucun ingrédient</p>
          </div>
        @else
          <div style="overflow-x: auto;">
            <table class="prod-table">
              <thead>
                <tr>
                  <th>Ingrédient</th>
                  <th style="text-align: right;">Quantité</th>
                  <th>Notes</th>
                </tr>
              </thead>
              <tbody>
                @foreach($lot->getRecipeItems() as $item)
                  <tr>
                    <td><strong>{{ $item->raw_material_name }}</strong></td>
                    <td style="text-align: right;">
                      <span class="prod-tag">
                        <i class="fa-solid fa-weight-hanging"></i> {{ $item->getQuantityDisplay() }}
                      </span>
                    </td>
                    <td style="color: #999; font-size: 0.9rem;">{{ $item->notes ?? '—' }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>

          <!-- Recipe Card -->
          <div class="prod-recipe-card">
            <strong><i class="fa-solid fa-flask-vial" style="color: #28a745; margin-right: 6px;"></i>Recette Utilisée</strong>
            <div class="prod-recipe-list">
              <p>
                <i class="fa-solid fa-book"></i>
                <span><strong>Recette:</strong> {{ $lot->recipe->name }}</span>
              </p>
              <p>
                <i class="fa-solid fa-cubes"></i>
                <span><strong>Ingrédients:</strong> {{ $lot->getRecipeItems()->count() }}</span>
              </p>
              <p>
                <i class="fa-solid fa-box"></i>
                <span><strong>Rendement:</strong> {{ $lot->recipe->getYieldDisplay() }}</span>
              </p>
            </div>
          </div>
        @endif
      </div>
    </div>

    <!-- Right Column (1/3) - QR Code -->
    <div>
      <div class="prod-qr-container">
        <div class="prod-qr-title"><i class="fa-solid fa-qrcode"></i> QR Code</div>
        
        <!-- QR Content -->
        <div class="prod-qr-content">
          <div class="prod-qr-image">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data={{ urlencode(route('traceability.show', ['code' => $lot->qr_code_data])) }}" alt="QR Code Traçabilité">
          </div>
          <div class="prod-qr-code-display">{{ $lot->qr_code_data }}</div>
        </div>

        <!-- Action Buttons -->
        <div class="prod-qr-buttons">
          <a href="{{ route('pro.processor.production-lots.qr-code', $lot) }}" target="_blank" class="prod-qr-btn prod-qr-btn-primary">
            <i class="fa-solid fa-external-link"></i> Ouvrir
          </a>
          <button class="prod-qr-btn prod-qr-btn-secondary" onclick="window.print()">
            <i class="fa-solid fa-print"></i> Imprimer
          </button>
        </div>

        <!-- QR Description -->
        <p style="font-size: 0.8rem; color: #999; margin-top: 1rem; margin-bottom: 0;">
          Scannez ce code pour accéder à la page de traçabilité
        </p>
      </div>
    </div>
  </div>

</div>

@endsection
