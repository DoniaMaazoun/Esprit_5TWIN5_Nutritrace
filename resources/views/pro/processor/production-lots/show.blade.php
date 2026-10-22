@extends('pro.layouts.processor')

@section('title', $lot->getProductName() . ' - Détails Lot | Farm to Fork')

@section('processor-content')

<div class="page-head">
  <div>
    <a href="{{ route('pro.processor.production-lots.index') }}" class="small"><i class="fa-solid fa-arrow-left"></i> Mes lots</a>
    <h1>{{ $lot->getProductName() }}</h1>
    <p>
      Lot de production créé par {{ $lot->processor->name }}
      &nbsp;<span class="tag" style="background: linear-gradient(135deg, var(--processor-primary), #2D6A4F); color: #fff;">{{ $lot->getStatusLabel() }}</span>
    </p>
  </div>
  <div class="page-head__actions">
    <a href="{{ route('pro.processor.production-lots.edit', $lot) }}" class="btn btn-outline-success"><i class="fa-solid fa-pen"></i> Modifier</a>
    <form method="POST" action="{{ route('pro.processor.production-lots.destroy', $lot) }}" data-confirm="Supprimer le lot « {{ $lot->getProductName() }} » ? Cette action est irréversible.">
      @csrf @method('DELETE')
      <button type="submit" class="btn btn-outline-danger"><i class="fa-solid fa-trash"></i> Supprimer</button>
    </form>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-8">
    <div class="stat-grid">
      <div class="stat"><div class="stat__icon"><i class="fa-solid fa-weight-hanging"></i></div><div><div class="stat__value">{{ $lot->getQuantityDisplay() }}</div><div class="stat__label">Quantité Produite</div></div></div>
      <div class="stat"><div class="stat__icon"><i class="fa-solid fa-calendar"></i></div><div><div class="stat__value">{{ $lot->production_date->format('d/m/Y') }}</div><div class="stat__label">Date Production</div></div></div>
      <div class="stat"><div class="stat__icon"><i class="fa-solid fa-hourglass-end"></i></div><div><div class="stat__value">{{ $lot->expiry_date->format('d/m/Y') }}</div><div class="stat__label">Date Péremption</div></div></div>
      <div class="stat"><div class="stat__icon"><i class="fa-solid fa-cubes"></i></div><div><div class="stat__value">{{ $lot->getRecipeItems()->count() }}</div><div class="stat__label">Ingrédient{{ $lot->getRecipeItems()->count() !== 1 ? 's' : '' }}</div></div></div>
    </div>

    @if($lot->production_notes)
      <div class="p-card">
        <h2 class="p-card__title"><i class="fa-solid fa-info-circle"></i> Notes de Production</h2>
        <p style="margin: 0; line-height: 1.6; white-space: pre-wrap;">{{ $lot->production_notes }}</p>
      </div>
    @endif

    <!-- Alertes expiration -->
    @if($lot->isExpired())
      <div class="p-card" style="background: #f8d7da; border-left: 4px solid #dc3545; color: #721c24;">
        <div style="display: flex; gap: 12px; align-items: flex-start;">
          <i class="fa-solid fa-exclamation-triangle" style="font-size: 1.2rem; margin-top: 4px;"></i>
          <div>
            <strong style="display: block; margin-bottom: 4px;">⚠️ Ce lot est périmé</strong>
            <p style="margin: 0; font-size: .9rem;">Ce lot ne doit pas être distribué. Date limite dépassée le {{ $lot->expiry_date->format('d/m/Y') }}.</p>
          </div>
        </div>
      </div>
    @elseif($lot->isCloseToExpiry())
      <div class="p-card" style="background: #fff3cd; border-left: 4px solid #ffc107; color: #856404;">
        <div style="display: flex; gap: 12px; align-items: flex-start;">
          <i class="fa-solid fa-hourglass-half" style="font-size: 1.2rem; margin-top: 4px;"></i>
          <div>
            <strong style="display: block; margin-bottom: 4px;">⏳ Péremption proche</strong>
            <p style="margin: 0; font-size: .9rem;">Ce lot expire le {{ $lot->expiry_date->format('d/m/Y') }}. À écouler en priorité.</p>
          </div>
        </div>
      </div>
    @else
      <div class="p-card" style="background: #e8f5e9; border-left: 4px solid #2e7d32; color: #1b5e20;">
        <div style="display: flex; gap: 12px; align-items: flex-start;">
          <i class="fa-solid fa-check-circle" style="font-size: 1.2rem; margin-top: 4px;"></i>
          <div>
            <strong style="display: block; margin-bottom: 4px;">✅ Lot valide</strong>
            <p style="margin: 0; font-size: .9rem;">Ce lot est en bon état. Valide jusqu'au {{ $lot->expiry_date->format('d/m/Y') }}.</p>
          </div>
        </div>
      </div>
    @endif

    <div class="p-card">
      <h2 class="p-card__title"><i class="fa-solid fa-list"></i> Ingrédients Utilisés</h2>

      @if($lot->getRecipeItems()->isEmpty())
        <div class="empty-state" style="padding: 40px 20px;">
          <i class="fa-solid fa-inbox" style="font-size: 2rem; margin-bottom: 1rem; opacity: .4;"></i>
          <p>Aucun ingrédient</p>
        </div>
      @else
        <div class="table-responsive">
          <table class="p-table">
            <thead>
              <tr>
                <th>Ingrédient</th>
                <th>Quantité Requise</th>
                <th>Notes</th>
              </tr>
            </thead>
            <tbody>
              @foreach($lot->getRecipeItems() as $item)
                <tr>
                  <td><strong>{{ $item->raw_material_name }}</strong></td>
                  <td class="text-nowrap"><span class="tag" style="background: #f0f9f4; color: #1B4332; display: inline-block;"><i class="fa-solid fa-weight-hanging"></i> {{ $item->getQuantityDisplay() }}</span></td>
                  <td style="color: var(--gray); font-size: .88rem;">{{ $item->notes ?? '—' }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        <div class="p-card" style="background: #f1f8f3; border-left: 4px solid var(--processor-primary); margin-top: 1rem;">
          <div style="display: flex; gap: 12px; align-items: flex-start;">
            <i class="fa-solid fa-chart-pie" style="color: var(--processor-primary); font-size: 1.2rem; margin-top: 4px;"></i>
            <div>
              <strong style="display: block; margin-bottom: 4px;">Recette utilisée</strong>
              <div style="font-size: .88rem; color: var(--gray-dark);">
                <p style="margin: 2px 0;"><i class="fa-solid fa-flask-vial"></i> <strong>Recette :</strong> {{ $lot->recipe->name }}</p>
                <p style="margin: 2px 0;"><i class="fa-solid fa-cubes"></i> <strong>Ingrédients :</strong> {{ $lot->getRecipeItems()->count() }}</p>
                <p style="margin: 2px 0;"><i class="fa-solid fa-box"></i> <strong>Rendement recette :</strong> {{ $lot->recipe->getYieldDisplay() }}</p>
              </div>
            </div>
          </div>
        </div>
      @endif
    </div>
  </div>

  <div class="col-lg-4">
    <!-- QR Code Card -->
    <div class="p-card" style="background: linear-gradient(135deg, var(--processor-primary-dark), var(--processor-primary)); color: #fff; text-align: center;">
      <div style="padding: 20px;">
        <i class="fa-solid fa-qrcode" style="font-size: 3rem; opacity: .3; margin-bottom: 12px; display: block;"></i>
        <h3 style="margin: 0 0 6px; font-size: 1.2rem; font-weight: 700;">QR Code Traçabilité</h3>
        <p style="margin: 0; opacity: .9; font-size: .85rem;">Scannez pour vérifier l'origine</p>

        <div style="background: rgba(255,255,255,.15); padding: 1rem; border-radius: 8px; margin-top: 1rem;">
          <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode(route('traceability.show', ['code' => $lot->qr_code_data])) }}" alt="QR Code" style="max-width: 100%; border-radius: 4px;">
        </div>

        <p style="margin: 1rem 0 0; font-size: .75rem; opacity: .8;">{{ $lot->qr_code_data }}</p>
      </div>
    </div>

    <!-- Status Card -->
    <div class="p-card">
      <h3 class="p-card__title"><i class="fa-solid fa-info-circle"></i> Statut</h3>
      <div class="tag" style="background: linear-gradient(135deg, var(--processor-primary), #2D6A4F); color: #fff; display: inline-flex; gap: 6px; padding: 8px 12px; border-radius: 6px;">
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

    <!-- Informations Card -->
    <div class="p-card">
      <h3 class="p-card__title"><i class="fa-solid fa-calendar"></i> Informations</h3>
      <dl class="kv">
        <dt>Créé le</dt>
        <dd>{{ $lot->created_at->format('d/m/Y à H:i') }}</dd>
        <dt>Modifié le</dt>
        <dd>{{ $lot->updated_at->format('d/m/Y à H:i') }}</dd>
        <dt>Recette</dt>
        <dd>{{ $lot->recipe->name }}</dd>
        <dt>Processeur</dt>
        <dd>{{ $lot->processor->name }}</dd>
      </dl>
    </div>

    <!-- Traceability Info -->
    <div class="p-card" style="background: #f1f8f3; border-left: 4px solid var(--processor-primary);">
      <h3 class="p-card__title"><i class="fa-solid fa-link"></i> Traçabilité</h3>
      <p style="font-size: .88rem; margin: 0 0 1rem; color: var(--gray-dark);">
        Ce lot peut être scanné par les distributeurs et consommateurs pour vérifier l'origine et la chaîne de transformation.
      </p>
      <p style="font-size: .75rem; color: var(--gray); margin: 0;">
        <strong>URL publique:</strong><br>
        <code style="display: block; word-break: break-all; margin-top: 4px; background: rgba(45,106,79,.1); padding: 4px; border-radius: 3px;">{{ route('traceability.show', ['code' => $lot->qr_code_data]) }}</code>
      </p>
    </div>
  </div>
</div>

@endsection
