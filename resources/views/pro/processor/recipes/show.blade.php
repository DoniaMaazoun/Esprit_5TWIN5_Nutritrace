@extends('pro.layouts.processor')

@section('title', $recipe->name . ' - Détails Recette | Farm to Fork')

@section('processor-content')

<div class="page-head">
  <div>
    <a href="{{ route('pro.processor.recipes.index') }}" class="small"><i class="fa-solid fa-arrow-left"></i> Mes recettes</a>
    <h1>{{ $recipe->name }}</h1>
    <p>
      Recette créée par {{ $recipe->processor->name }}
      &nbsp;<span class="tag tag--active">{{ $recipe->is_active ? '✅ Actif' : '❌ Inactif' }}</span>
    </p>
  </div>
  <div class="page-head__actions">
    <a href="{{ route('pro.processor.recipes.edit', $recipe) }}" class="btn btn-outline-success"><i class="fa-solid fa-pen"></i> Modifier</a>
    <form method="POST" action="{{ route('pro.processor.recipes.destroy', $recipe) }}" data-confirm="Supprimer la recette « {{ $recipe->name }} » ? Cette action est irréversible.">
      @csrf @method('DELETE')
      <button type="submit" class="btn btn-outline-danger"><i class="fa-solid fa-trash"></i> Supprimer</button>
    </form>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-8">
    <div class="stat-grid">
      <div class="stat"><div class="stat__icon"><i class="fa-solid fa-box"></i></div><div><div class="stat__value">{{ $recipe->getYieldDisplay() }}</div><div class="stat__label">Rendement</div></div></div>
      <div class="stat"><div class="stat__icon"><i class="fa-solid fa-hourglass-half"></i></div><div><div class="stat__value">{{ $recipe->preparation_time_minutes }} min</div><div class="stat__label">Temps de préparation</div></div></div>
      <div class="stat"><div class="stat__icon"><i class="fa-solid fa-cubes"></i></div><div><div class="stat__value">{{ $recipe->itemsCount() }}</div><div class="stat__label">Ingrédient{{ $recipe->itemsCount() !== 1 ? 's' : '' }}</div></div></div>
      <div class="stat"><div class="stat__icon"><i class="fa-solid fa-percent"></i></div><div><div class="stat__value">{{ $recipe->getYieldRatio() }}x</div><div class="stat__label">Ratio rendement</div></div></div>
    </div>

    @if($recipe->description)
      <div class="p-card">
        <h2 class="p-card__title"><i class="fa-solid fa-info-circle"></i> Description</h2>
        <p style="margin: 0; line-height: 1.6;">{{ $recipe->description }}</p>
      </div>
    @endif

    @if($recipe->instructions)
      <div class="p-card">
        <h2 class="p-card__title"><i class="fa-solid fa-tasks"></i> Instructions de Préparation</h2>
        <p style="margin: 0; line-height: 1.6; white-space: pre-wrap;">{{ $recipe->instructions }}</p>
      </div>
    @endif

    <div class="p-card">
      <h2 class="p-card__title"><i class="fa-solid fa-list"></i> Ingrédients Requis</h2>

      @if($recipe->items->isEmpty())
        <div class="empty-state" style="padding: 40px 20px;">
          <i class="fa-solid fa-inbox" style="font-size: 2rem; margin-bottom: 1rem; opacity: .4;"></i>
          <p>Aucun ingrédient ajouté</p>
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
              @foreach($recipe->items as $item)
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
              <strong style="display: block; margin-bottom: 4px;">Résumé des Ingrédients</strong>
              <div style="font-size: .88rem; color: var(--gray-dark);">
                <p style="margin: 2px 0;"><i class="fa-solid fa-cubes"></i> <strong>Total :</strong> {{ $recipe->itemsCount() }} ingrédient{{ $recipe->itemsCount() !== 1 ? 's' : '' }}</p>
                <p style="margin: 2px 0;"><i class="fa-solid fa-weight-hanging"></i> <strong>Quantité totale entrée :</strong> {{ number_format($recipe->getTotalInputQuantityInKg(), 2, ',', ' ') }} kg</p>
                <p style="margin: 2px 0;"><i class="fa-solid fa-box"></i> <strong>Rendement :</strong> {{ number_format($recipe->getYieldInKg(), 2, ',', ' ') }} kg</p>
              </div>
            </div>
          </div>
        </div>
      @endif
    </div>
  </div>

  <div class="col-lg-4">
    <div class="p-card" style="background: linear-gradient(135deg, var(--processor-primary-dark), var(--processor-primary)); color: #fff; text-align: center;">
      <div style="padding: 20px;">
        <i class="fa-solid fa-flask-vial" style="font-size: 3rem; opacity: .3; margin-bottom: 12px; display: block;"></i>
        <h3 style="margin: 0 0 6px; font-size: 1.4rem; font-weight: 700;">{{ $recipe->name }}</h3>
        <p style="margin: 0; opacity: .9; font-size: .92rem;">Recette de transformation</p>
      </div>
    </div>

    <div class="p-card">
      <h3 class="p-card__title"><i class="fa-solid fa-info-circle"></i> Statut</h3>
      @if($recipe->is_active)
        <div class="tag tag--active" style="display: inline-flex; gap: 6px;"><i class="fa-solid fa-check-circle"></i> Actif</div>
      @else
        <div class="tag" style="background: #e2e3e5; color: #495057; display: inline-flex; gap: 6px;"><i class="fa-solid fa-pause-circle"></i> Inactif</div>
      @endif
    </div>

    <div class="p-card">
      <h3 class="p-card__title"><i class="fa-solid fa-calendar"></i> Informations</h3>
      <dl class="kv">
        <dt>Créée le</dt>
        <dd>{{ $recipe->created_at->format('d/m/Y à H:i') }}</dd>
        <dt>Modifiée le</dt>
        <dd>{{ $recipe->updated_at->format('d/m/Y à H:i') }}</dd>
      </dl>
    </div>
  </div>
</div>

@endsection
