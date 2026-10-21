@extends('pro.layouts.processor')

@section('title', 'Détails Réception - Transformateur | Farm to Fork')

@section('processor-content')

<div class="page-head">
  <div>
    <a href="{{ route('pro.processor.raw-materials.index') }}" class="small"><i class="fa-solid fa-arrow-left"></i> Réceptions</a>
    <h1><i class="fa-solid fa-inbox"></i> {{ $reception->harvest->product_name }}</h1>
    <p>Réception #{{ $reception->id }} &nbsp;<span class="tag tag--{{ $reception->status }}">{{ $reception->getStatusLabel() }}</span></p>
  </div>
  <div class="page-head__actions">
    @if($reception->isPending())
      <button onclick="openApproveModal()" class="btn btn-success"><i class="fa-solid fa-check-circle"></i> Approuver</button>
      <button onclick="openRejectModal()" class="btn btn-outline-danger"><i class="fa-solid fa-times-circle"></i> Rejeter</button>
      <a href="{{ route('pro.processor.raw-materials.edit', $reception) }}" class="btn btn-outline-secondary"><i class="fa-solid fa-pen"></i> Modifier</a>
    @endif
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-8">
    <div class="stat-grid">
      <div class="stat"><div class="stat__icon"><i class="fa-solid fa-tractor"></i></div><div><div class="stat__value">{{ $reception->harvest->farm->name }}</div><div class="stat__label">Exploitation</div></div></div>
      <div class="stat"><div class="stat__icon"><i class="fa-solid fa-user"></i></div><div><div class="stat__value">{{ $reception->harvest->farm->user->company_name }}</div><div class="stat__label">Producteur</div></div></div>
      <div class="stat"><div class="stat__icon"><i class="fa-solid fa-weight-hanging"></i></div><div><div class="stat__value">{{ number_format($reception->quantity_received, 2) }}</div><div class="stat__label">{{ $reception->unit }} reçus</div></div></div>
      <div class="stat"><div class="stat__icon"><i class="fa-solid fa-calendar-check"></i></div><div><div class="stat__value">{{ $reception->reception_date->format('d/m/Y') }}</div><div class="stat__label">Date réception</div></div></div>
    </div>

    <div class="p-card">
      <h2 class="p-card__title"><i class="fa-solid fa-cube"></i> Information du Lot</h2>
      <dl class="kv">
        <dt>Producteur</dt>
        <dd>{{ $reception->harvest->farm->user->company_name }}</dd>
        <dt>Exploitation</dt>
        <dd>{{ $reception->harvest->farm->name }}, {{ $reception->harvest->farm->city }}</dd>
        <dt>Catégorie</dt>
        <dd>{{ $reception->harvest->category->name ?? 'N/A' }}</dd>
        <dt>Variété</dt>
        <dd>{{ $reception->harvest->variety ?? '—' }}</dd>
        <dt>Date Récolte</dt>
        <dd>{{ $reception->harvest->harvest_date?->format('d/m/Y') ?? 'N/A' }}</dd>
        <dt>Quantité Produite</dt>
        <dd>{{ number_format($reception->harvest->quantityInKg(), 0, ',', ' ') }} kg</dd>
        <dt>Agriculture</dt>
        <dd>{{ $reception->harvest->farm->farmingTypeLabel() }}{{ $reception->harvest->farm->certification ? ' · ' . $reception->harvest->farm->certification : '' }}</dd>
      </dl>
    </div>

    <div class="p-card">
      <h2 class="p-card__title"><i class="fa-solid fa-file-import"></i> Détails Réception</h2>
      <dl class="kv">
        <dt>Quantité Reçue</dt>
        <dd>{{ number_format($reception->quantity_received, 2) }} {{ $reception->unit }}</dd>
        <dt>Date Réception</dt>
        <dd>{{ $reception->reception_date->format('d/m/Y H:i') }}</dd>
        <dt>État Qualité</dt>
        <dd>
          <span class="tag" style="background: {{ $reception->quality_status === 'good' ? '#d4edda' : ($reception->quality_status === 'acceptable' ? '#fff3cd' : '#f8d7da') }}; color: {{ $reception->quality_status === 'good' ? '#155724' : ($reception->quality_status === 'acceptable' ? '#856404' : '#721c24') }}; display: inline-block;">
            {{ $reception->getQualityLabel() }}
          </span>
        </dd>
        <dt>Localisation Stockage</dt>
        <dd>{{ $reception->storage_location ?? '—' }}</dd>
        <dt>Température</dt>
        <dd>{{ $reception->temperature ? $reception->temperature . '°C' : '—' }}</dd>
      </dl>
    </div>

    @if($reception->quality_notes || $reception->internal_notes || $reception->rejection_reason)
      <div class="p-card">
        <h2 class="p-card__title"><i class="fa-solid fa-clipboard-check"></i> Notes & Observations</h2>

        @if($reception->quality_notes)
          <div style="margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid var(--gray-light);">
            <strong>Notes Qualité:</strong>
            <p style="margin: 6px 0 0; color: var(--gray-dark);">{{ $reception->quality_notes }}</p>
          </div>
        @endif

        @if($reception->internal_notes)
          <div style="margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid var(--gray-light);">
            <strong>Notes Internes:</strong>
            <p style="margin: 6px 0 0; color: var(--gray-dark);">{{ $reception->internal_notes }}</p>
          </div>
        @endif

        @if($reception->rejection_reason)
          <div style="background: #fff5f5; padding: 1rem; border-radius: 8px; border-left: 4px solid #dc3545;">
            <strong style="color: #dc3545;"><i class="fa-solid fa-circle-xmark"></i> Raison du Rejet:</strong>
            <p style="margin: 6px 0 0; color: var(--gray-dark);">{{ $reception->rejection_reason }}</p>
            <small style="color: var(--gray); margin-top: 6px; display: block;">Rejeté le {{ $reception->rejection_date?->format('d/m/Y H:i') }}</small>
          </div>
        @endif
      </div>
    @endif

    <div class="p-card">
      <h2 class="p-card__title"><i class="fa-solid fa-history"></i> Historique</h2>
      <div style="position: relative; padding-left: 2rem;">
        <div style="position: absolute; left: 0; top: 0; bottom: 0; width: 2px; background: var(--gray-light);"></div>
        
        <div style="position: relative; margin-bottom: 1.5rem;">
          <div style="position: absolute; left: -12px; top: 4px; width: 10px; height: 10px; background: var(--processor-primary); border-radius: 50%; border: 3px solid white;"></div>
          <div style="font-size: .85rem; color: var(--gray); font-weight: 600;">{{ $reception->created_at->format('d/m/Y H:i') }}</div>
          <div style="color: var(--dark);">Réception créée</div>
        </div>

        @if($reception->verified_at)
          <div style="position: relative; margin-bottom: 1.5rem;">
            <div style="position: absolute; left: -12px; top: 4px; width: 10px; height: 10px; background: var(--processor-primary); border-radius: 50%; border: 3px solid white;"></div>
            <div style="font-size: .85rem; color: var(--gray); font-weight: 600;">{{ $reception->verified_at->format('d/m/Y H:i') }}</div>
            <div style="color: var(--dark);"><i class="fa-solid fa-check"></i> Réception approuvée</div>
          </div>
        @endif

        @if($reception->rejection_date)
          <div style="position: relative;">
            <div style="position: absolute; left: -12px; top: 4px; width: 10px; height: 10px; background: #dc3545; border-radius: 50%; border: 3px solid white;"></div>
            <div style="font-size: .85rem; color: var(--gray); font-weight: 600;">{{ $reception->rejection_date->format('d/m/Y H:i') }}</div>
            <div style="color: var(--dark);"><i class="fa-solid fa-times"></i> Réception rejetée</div>
          </div>
        @endif
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="p-card" style="background: linear-gradient(135deg, var(--processor-primary-dark), var(--processor-primary)); color: #fff; text-align: center;">
      <div style="padding: 20px;">
        <i class="fa-solid fa-inbox" style="font-size: 3rem; opacity: .3; margin-bottom: 12px; display: block;"></i>
        <h3 style="margin: 0 0 6px; font-size: 1.2rem; font-weight: 700;">{{ $reception->harvest->product_name }}</h3>
        <p style="margin: 0; opacity: .9; font-size: .92rem;">Réception #{{ $reception->id }}</p>
      </div>
    </div>

    <div class="p-card">
      <h3 class="p-card__title"><i class="fa-solid fa-check-circle"></i> Statut</h3>
      <span class="tag tag--{{ $reception->status }}" style="display: inline-block; font-size: 1rem; padding: 6px 12px;">{{ $reception->getStatusLabel() }}</span>
    </div>

    <div class="p-card">
      <h3 class="p-card__title"><i class="fa-solid fa-info-circle"></i> Informations</h3>
      <dl style="margin: 0;">
        <dt style="font-size: .85rem; font-weight: 600; color: var(--gray); text-transform: uppercase; margin-bottom: 4px;">Créée le</dt>
        <dd style="margin: 0 0 12px;">{{ $reception->created_at->format('d/m/Y à H:i') }}</dd>
        <dt style="font-size: .85rem; font-weight: 600; color: var(--gray); text-transform: uppercase; margin-bottom: 4px;">Modifiée le</dt>
        <dd style="margin: 0;">{{ $reception->updated_at->format('d/m/Y à H:i') }}</dd>
      </dl>
    </div>
  </div>
</div>

<!-- Approve Modal -->
<div id="approveModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,.5); z-index: 999; align-items: center; justify-content: center;">
  <div style="background: white; border-radius: 12px; padding: 24px; max-width: 400px; width: 90%; box-shadow: 0 10px 40px rgba(0,0,0,.2);">
    <h3 style="margin: 0 0 12px; font-size: 1.1rem; font-weight: 700;"><i class="fa-solid fa-check-circle" style="color: var(--processor-primary);"></i> Approuver la Réception</h3>
    <p style="margin: 0 0 20px; color: var(--gray);">Êtes-vous certain de vouloir approuver cette réception?</p>
    <div style="display: flex; gap: 12px;">
      <form method="POST" action="{{ route('pro.processor.raw-materials.approve', $reception) }}" style="flex: 1;">
        @csrf
        <button type="submit" style="width: 100%; padding: 10px; border: none; border-radius: 8px; background: var(--processor-primary); color: white; cursor: pointer; font-weight: 600;">
          <i class="fa-solid fa-check"></i> Approuver
        </button>
      </form>
      <button type="button" onclick="closeApproveModal()" style="flex: 1; padding: 10px; border: 1px solid var(--gray-light); border-radius: 8px; background: white; cursor: pointer; font-weight: 600;">
        <i class="fa-solid fa-times"></i> Annuler
      </button>
    </div>
  </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,.5); z-index: 999; align-items: center; justify-content: center;">
  <div style="background: white; border-radius: 12px; padding: 24px; max-width: 400px; width: 90%; box-shadow: 0 10px 40px rgba(0,0,0,.2);">
    <h3 style="margin: 0 0 12px; font-size: 1.1rem; font-weight: 700;"><i class="fa-solid fa-times-circle" style="color: #dc3545;"></i> Rejeter la Réception</h3>
    <form method="POST" action="{{ route('pro.processor.raw-materials.reject', $reception) }}">
      @csrf
      <div style="margin-bottom: 16px;">
        <label for="rejection_reason" style="display: block; font-weight: 600; margin-bottom: 6px;">Raison du Rejet *</label>
        <textarea id="rejection_reason" name="rejection_reason" required placeholder="Décrivez la raison du rejet..." class="form-control" rows="4"></textarea>
      </div>
      <div style="display: flex; gap: 12px;">
        <button type="submit" style="flex: 1; padding: 10px; border: none; border-radius: 8px; background: #dc3545; color: white; cursor: pointer; font-weight: 600;">
          <i class="fa-solid fa-times"></i> Rejeter
        </button>
        <button type="button" onclick="closeRejectModal()" style="flex: 1; padding: 10px; border: 1px solid var(--gray-light); border-radius: 8px; background: white; cursor: pointer; font-weight: 600;">
          <i class="fa-solid fa-arrow-left"></i> Annuler
        </button>
      </div>
    </form>
  </div>
</div>

<script>
  function openApproveModal() {
    document.getElementById('approveModal').style.display = 'flex';
  }
  function closeApproveModal() {
    document.getElementById('approveModal').style.display = 'none';
  }
  function openRejectModal() {
    document.getElementById('rejectModal').style.display = 'flex';
  }
  function closeRejectModal() {
    document.getElementById('rejectModal').style.display = 'none';
  }
  document.getElementById('approveModal').addEventListener('click', (e) => {
    if (e.target.id === 'approveModal') closeApproveModal();
  });
  document.getElementById('rejectModal').addEventListener('click', (e) => {
    if (e.target.id === 'rejectModal') closeRejectModal();
  });
</script>

@endsection
