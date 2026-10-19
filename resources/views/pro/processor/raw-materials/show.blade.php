@extends('frontend.layouts.app')

@section('title', 'Détails Réception - Transformateur | Farm to Fork')

@section('extra-css')
<style>
  .detail-section {
    background: linear-gradient(135deg, #f0f9f7 0%, #e8f5f3 100%);
    padding: 40px 0;
    min-height: 100vh;
  }

  .detail-card {
    background: white;
    border-radius: 1rem;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    margin-bottom: 2rem;
  }

  .detail-header {
    background: linear-gradient(135deg, #fd7e14 0%, #ff9a3d 100%);
    color: white;
    padding: 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .detail-header h1 {
    font-size: 1.8rem;
    font-weight: 700;
    margin: 0;
  }

  .status-large {
    font-size: 1.2rem;
    padding: 0.75rem 1.5rem;
    border-radius: 0.5rem;
    font-weight: 700;
  }

  .status-pending-lg {
    background: #fff3cd;
    color: #856404;
  }

  .status-received-lg {
    background: #d4edda;
    color: #155724;
  }

  .status-rejected-lg {
    background: #f8d7da;
    color: #721c24;
  }

  .detail-content {
    padding: 2rem;
  }

  .info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
    margin-bottom: 2rem;
  }

  .info-block {
    border-left: 4px solid #fd7e14;
    padding-left: 1rem;
  }

  .info-label {
    font-size: 0.85rem;
    color: #666;
    font-weight: 600;
    text-transform: uppercase;
    margin-bottom: 0.5rem;
  }

  .info-value {
    font-size: 1.2rem;
    color: #1e3a2b;
    font-weight: 600;
  }

  .divider {
    border-top: 1px solid #e0e0e0;
    margin: 2rem 0;
  }

  .section-title {
    font-size: 1.3rem;
    font-weight: 700;
    color: #1e3a2b;
    margin-bottom: 1.5rem;
    padding-bottom: 0.75rem;
    border-bottom: 2px solid #fd7e14;
  }

  .badge {
    display: inline-block;
    padding: 0.4rem 0.8rem;
    border-radius: 0.25rem;
    font-size: 0.85rem;
    font-weight: 600;
    margin-right: 0.5rem;
  }

  .badge-success {
    background: #d4edda;
    color: #155724;
  }

  .badge-warning {
    background: #fff3cd;
    color: #856404;
  }

  .badge-danger {
    background: #f8d7da;
    color: #721c24;
  }

  .notes-box {
    background: #f8f9fa;
    padding: 1rem;
    border-left: 4px solid #0d6efd;
    border-radius: 0.5rem;
    margin-bottom: 1rem;
    line-height: 1.6;
  }

  .notes-label {
    font-weight: 700;
    color: #1e3a2b;
    margin-bottom: 0.5rem;
  }

  .action-buttons {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
    flex-wrap: wrap;
  }

  .btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 0.5rem;
    cursor: pointer;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
  }

  .btn-primary {
    background: #fd7e14;
    color: white;
  }

  .btn-primary:hover {
    background: #e67e0f;
  }

  .btn-success {
    background: #28a745;
    color: white;
  }

  .btn-success:hover {
    background: #218838;
  }

  .btn-danger {
    background: #dc3545;
    color: white;
  }

  .btn-danger:hover {
    background: #c82333;
  }

  .btn-secondary {
    background: #6c757d;
    color: white;
  }

  .btn-secondary:hover {
    background: #5a6268;
  }

  .modal-backdrop {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 999;
  }

  .modal-backdrop.active {
    display: flex;
    justify-content: center;
    align-items: center;
  }

  .modal-dialog {
    background: white;
    border-radius: 1rem;
    padding: 2rem;
    max-width: 500px;
    width: 90%;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
  }

  .modal-header {
    font-size: 1.3rem;
    font-weight: 700;
    color: #1e3a2b;
    margin-bottom: 1rem;
  }

  .form-group {
    margin-bottom: 1rem;
  }

  .form-group label {
    display: block;
    font-weight: 600;
    color: #1e3a2b;
    margin-bottom: 0.5rem;
  }

  .form-group textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 0.5rem;
    min-height: 100px;
  }

  .modal-actions {
    display: flex;
    gap: 1rem;
    margin-top: 1.5rem;
  }

  .modal-actions button {
    flex: 1;
    padding: 0.75rem;
    border: none;
    border-radius: 0.5rem;
    cursor: pointer;
    font-weight: 600;
  }

  .modal-actions .btn-confirm {
    background: #dc3545;
    color: white;
  }

  .modal-actions .btn-cancel {
    background: #6c757d;
    color: white;
  }

  .timeline {
    position: relative;
    padding-left: 2rem;
  }

  .timeline::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e0e0e0;
  }

  .timeline-item {
    position: relative;
    margin-bottom: 1.5rem;
  }

  .timeline-item::before {
    content: '';
    position: absolute;
    left: -2.5rem;
    top: 0.25rem;
    width: 12px;
    height: 12px;
    background: #fd7e14;
    border-radius: 50%;
    border: 3px solid white;
  }

  .timeline-date {
    font-size: 0.85rem;
    color: #666;
    font-weight: 600;
  }

  .timeline-text {
    color: #1e3a2b;
  }
</style>
@endsection

@section('content')

<div class="detail-section">
  <div class="container">
    <!-- Header -->
    <div class="detail-card">
      <div class="detail-header">
        <div>
          <h1><i class="fa-solid fa-inbox"></i> {{ $reception->harvest->name }}</h1>
          <p style="margin: 0.5rem 0 0 0; opacity: 0.9;">Réception #{{ $reception->id }}</p>
        </div>
        <div class="status-large status-{{ $reception->status }}-lg">
          {{ $reception->getStatusLabel() }}
        </div>
      </div>
    </div>

    <!-- Harvest Information -->
    <div class="detail-card">
      <div class="detail-content">
        <h2 class="section-title"><i class="fa-solid fa-farm"></i> Information du Lot</h2>
        
        <div class="info-grid">
          <div class="info-block">
            <div class="info-label">Producteur</div>
            <div class="info-value">{{ $reception->harvest->farm->user->name }}</div>
          </div>
          <div class="info-block">
            <div class="info-label">Ferme</div>
            <div class="info-value">{{ $reception->harvest->farm->name }}</div>
          </div>
          <div class="info-block">
            <div class="info-label">Catégorie</div>
            <div class="info-value">{{ $reception->harvest->category->name ?? 'N/A' }}</div>
          </div>
          <div class="info-block">
            <div class="info-label">Date Récolte</div>
            <div class="info-value">{{ $reception->harvest->harvest_date?->format('d/m/Y') ?? 'N/A' }}</div>
          </div>
          <div class="info-block">
            <div class="info-label">Quantité Produite</div>
            <div class="info-value">{{ $reception->harvest->quantity_kg ?? 0 }} kg</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Reception Details -->
    <div class="detail-card">
      <div class="detail-content">
        <h2 class="section-title"><i class="fa-solid fa-file-import"></i> Détails Réception</h2>
        
        <div class="info-grid">
          <div class="info-block">
            <div class="info-label">Quantité Reçue</div>
            <div class="info-value">{{ number_format($reception->quantity_received, 2) }} {{ $reception->unit }}</div>
          </div>
          <div class="info-block">
            <div class="info-label">Date Réception</div>
            <div class="info-value">{{ $reception->reception_date->format('d/m/Y H:i') }}</div>
          </div>
          <div class="info-block">
            <div class="info-label">État Qualité</div>
            <div class="info-value">
              <span class="badge badge-{{ $reception->quality_status === 'good' ? 'success' : ($reception->quality_status === 'acceptable' ? 'warning' : 'danger') }}">
                {{ $reception->getQualityLabel() }}
              </span>
            </div>
          </div>
          <div class="info-block">
            <div class="info-label">Localisation Stockage</div>
            <div class="info-value">{{ $reception->storage_location ?? 'Non spécifiée' }}</div>
          </div>
          <div class="info-block">
            <div class="info-label">Température</div>
            <div class="info-value">{{ $reception->temperature ?? 'Non enregistrée' }}{{ $reception->temperature ? '°C' : '' }}</div>
          </div>
        </div>

        @if($reception->quality_notes)
          <div class="divider"></div>
          <div class="notes-box">
            <div class="notes-label"><i class="fa-solid fa-clipboard-check"></i> Notes Qualité</div>
            <p>{{ $reception->quality_notes }}</p>
          </div>
        @endif

        @if($reception->internal_notes)
          <div class="notes-box">
            <div class="notes-label"><i class="fa-solid fa-sticky-note"></i> Notes Internes</div>
            <p>{{ $reception->internal_notes }}</p>
          </div>
        @endif

        @if($reception->rejection_reason)
          <div class="notes-box" style="border-left-color: #dc3545; background: #fff5f5;">
            <div class="notes-label"><i class="fa-solid fa-circle-xmark" style="color: #dc3545;"></i> Raison du Rejet</div>
            <p>{{ $reception->rejection_reason }}</p>
            <small style="color: #666;">Rejeté le : {{ $reception->rejection_date?->format('d/m/Y H:i') }}</small>
          </div>
        @endif
      </div>
    </div>

    <!-- Timeline -->
    <div class="detail-card">
      <div class="detail-content">
        <h2 class="section-title"><i class="fa-solid fa-history"></i> Historique</h2>
        
        <div class="timeline">
          <div class="timeline-item">
            <div class="timeline-date">{{ $reception->created_at->format('d/m/Y H:i') }}</div>
            <div class="timeline-text">Réception créée</div>
          </div>

          @if($reception->verified_at)
            <div class="timeline-item">
              <div class="timeline-date">{{ $reception->verified_at->format('d/m/Y H:i') }}</div>
              <div class="timeline-text">Réception approuvée ✅</div>
            </div>
          @endif

          @if($reception->rejection_date)
            <div class="timeline-item">
              <div class="timeline-date">{{ $reception->rejection_date->format('d/m/Y H:i') }}</div>
              <div class="timeline-text">Réception rejetée ❌</div>
            </div>
          @endif
        </div>
      </div>
    </div>

    <!-- Action Buttons -->
    <div style="display: flex; gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap;">
      @if($reception->isPending())
        <button onclick="openApproveModal()" class="btn btn-success">
          <i class="fa-solid fa-check-circle"></i> Approuver la Réception
        </button>
        <button onclick="openRejectModal()" class="btn btn-danger">
          <i class="fa-solid fa-times-circle"></i> Rejeter la Réception
        </button>
        <a href="{{ route('pro.processor.raw-materials.edit', $reception) }}" class="btn btn-primary">
          <i class="fa-solid fa-edit"></i> Modifier
        </a>
      @endif
      <a href="{{ route('pro.processor.raw-materials.index') }}" class="btn btn-secondary">
        <i class="fa-solid fa-arrow-left"></i> Retour
      </a>
    </div>
  </div>
</div>

<!-- Approve Modal -->
<div id="approveModal" class="modal-backdrop">
  <div class="modal-dialog">
    <div class="modal-header">
      <i class="fa-solid fa-check-circle" style="color: #28a745;"></i> Approuver la Réception
    </div>
    <p>Êtes-vous certain de vouloir approuver cette réception?</p>
    <form method="POST" action="{{ route('pro.processor.raw-materials.approve', $reception) }}">
      @csrf
      <div class="modal-actions">
        <button type="submit" class="btn-confirm">
          <i class="fa-solid fa-check"></i> Approuver
        </button>
        <button type="button" onclick="closeApproveModal()" class="btn-cancel">
          <i class="fa-solid fa-times"></i> Annuler
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="modal-backdrop">
  <div class="modal-dialog">
    <div class="modal-header">
      <i class="fa-solid fa-times-circle" style="color: #dc3545;"></i> Rejeter la Réception
    </div>
    <form method="POST" action="{{ route('pro.processor.raw-materials.reject', $reception) }}">
      @csrf
      <div class="form-group">
        <label for="rejection_reason">Raison du Rejet *</label>
        <textarea 
          id="rejection_reason" 
          name="rejection_reason" 
          required 
          placeholder="Décrivez la raison du rejet..."
        ></textarea>
      </div>
      <div class="modal-actions">
        <button type="submit" class="btn-confirm">
          <i class="fa-solid fa-times"></i> Rejeter
        </button>
        <button type="button" onclick="closeRejectModal()" class="btn-cancel">
          <i class="fa-solid fa-arrow-left"></i> Annuler
        </button>
      </div>
    </form>
  </div>
</div>

<script>
  function openApproveModal() {
    document.getElementById('approveModal').classList.add('active');
  }

  function closeApproveModal() {
    document.getElementById('approveModal').classList.remove('active');
  }

  function openRejectModal() {
    document.getElementById('rejectModal').classList.add('active');
  }

  function closeRejectModal() {
    document.getElementById('rejectModal').classList.remove('active');
  }

  // Close modals when clicking backdrop
  document.getElementById('approveModal').addEventListener('click', function(e) {
    if (e.target === this) closeApproveModal();
  });

  document.getElementById('rejectModal').addEventListener('click', function(e) {
    if (e.target === this) closeRejectModal();
  });
</script>

@endsection
