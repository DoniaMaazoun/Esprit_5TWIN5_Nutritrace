@extends('pro.layouts.producer')

@section('title', 'Lot ' . $harvest->batch_code . ' | Farm to Fork')

@section('producer-content')
<div class="page-head">
  <div>
    @can('update', $harvest)
    <a href="{{ route('pro.harvests.index') }}" class="small"><i class="fa-solid fa-arrow-left"></i> Mes récoltes</a>
  @else
    <a href="{{ route('frontend.products') }}" class="small"><i class="fa-solid fa-arrow-left"></i> Tous les produits</a>
  @endcan
    <h1>{{ $harvest->product_name }} @if($harvest->variety)<small class="text-muted fs-5">· {{ $harvest->variety }}</small>@endif</h1>
    <p>
      Lot <code>{{ $harvest->batch_code }}</code>
      &nbsp;<span class="tag tag--{{ $harvest->status }}">{{ $harvest->statusLabel() }}</span>
      <span class="grade grade--{{ $harvest->quality_grade }}">{{ $harvest->quality_grade }}</span>
    </p>
  </div>
  <div class="page-head__actions">
    @can('update', $harvest)
      <a href="{{ route('pro.harvests.label', $harvest) }}" target="_blank" class="btn btn-outline-success"><i class="fa-solid fa-print"></i> Étiquette</a>
      <a href="{{ route('pro.harvests.edit', $harvest) }}" class="btn btn-outline-secondary"><i class="fa-solid fa-pen"></i> Modifier</a>
      <form method="POST" action="{{ route('pro.harvests.destroy', $harvest) }}" data-confirm="Supprimer le lot {{ $harvest->batch_code }} ? Son QR code ne fonctionnera plus.">
        @csrf @method('DELETE')
        <button type="submit" class="btn btn-outline-danger"><i class="fa-solid fa-trash"></i> Supprimer</button>
      </form>
    @else
      <span class="tag tag--sold"><i class="fa-solid fa-lock"></i> Produit de {{ $harvest->farm->user->company_name }} — lecture seule</span>
    @endcan
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-8">
    <div class="stat-grid">
      <div class="stat"><div class="stat__icon"><i class="fa-solid fa-weight-hanging"></i></div><div><div class="stat__value">{{ number_format($harvest->quantityInKg(), 0, ',', ' ') }} kg</div><div class="stat__label">{{ number_format($harvest->quantity, 2, ',', ' ') }} {{ $harvest->unitShort() }} déclarés</div></div></div>
      <div class="stat"><div class="stat__icon"><i class="fa-solid fa-chart-line"></i></div><div><div class="stat__value">{{ $harvest->yieldPerHectare() !== null ? number_format($harvest->yieldPerHectare(), 0, ',', ' ') : '—' }}</div><div class="stat__label">Rendement (kg/ha)</div></div></div>
      <div class="stat"><div class="stat__icon"><i class="fa-solid fa-hourglass-half"></i></div><div><div class="stat__value">{{ $harvest->growingDays() ?? '—' }}</div><div class="stat__label">Jours de culture</div></div></div>
    </div>

    {{-- Analyse intelligente du lot --}}
    <div class="intel-grid">
      <div class="intel intel--{{ $fresh['level'] }}">
        <div class="intel__head"><i class="fa-solid fa-leaf"></i> Fraîcheur estimée</div>
        @if($fresh['active'])
          <div class="intel__value">{{ $fresh['percent'] }} %</div>
          <div class="intel__bar"><span style="width: {{ $fresh['percent'] }}%"></span></div>
          <p><strong>{{ $fresh['label'] }}</strong><br>
            À écouler avant le <strong>{{ $fresh['best_before']->translatedFormat('d F Y') }}</strong>
            ({{ $fresh['days_left'] >= 0 ? 'J-' . $fresh['days_left'] : 'dépassée de ' . abs($fresh['days_left']) . ' j' }}).</p>
          <small>Conservation {{ $fresh['cold'] ? 'au froid' : 'ambiante' }} : {{ $fresh['days'] }} jours pour cette catégorie.</small>
        @else
          <div class="intel__value intel__value--muted"><i class="fa-solid fa-check"></i></div>
          <p><strong>{{ $fresh['label'] }}</strong> ({{ \Illuminate\Support\Str::lower($harvest->statusLabel()) }}).</p>
          <small>Date limite estimée : {{ $fresh['best_before']->format('d/m/Y') }}.</small>
        @endif
      </div>

      <div class="intel intel--price">
        <div class="intel__head"><i class="fa-solid fa-tags"></i> Prix producteur conseillé</div>
        <div class="intel__value">{{ number_format($price['min'], 2, ',', ' ') }} – {{ number_format($price['max'], 2, ',', ' ') }} <small>TND/kg</small></div>
        <p>Valeur estimée du lot : <strong>{{ number_format($price['value'], 0, ',', ' ') }} TND</strong></p>
        <ul>@foreach($price['factors'] as $f)<li>{{ $f }}</li>@endforeach</ul>
        <small>Estimation indicative basée sur la catégorie, la qualité et le mode de culture.</small>
      </div>

      @can('update', $harvest)
        <div class="intel intel--risk-{{ $risk['level'] }}">
          <div class="intel__head"><i class="fa-solid fa-shield-halved"></i> Score de conformité</div>
          <div class="intel__value">{{ 100 - $risk['score'] }}<small>/100</small></div>
          <div class="intel__bar"><span style="width: {{ 100 - $risk['score'] }}%"></span></div>
          <p><strong>{{ $risk['label'] }}</strong></p>
          @if($risk['reasons'])
            <ul>@foreach(array_slice($risk['reasons'], 0, 3) as [$pts, $text])<li>{{ $text }}</li>@endforeach</ul>
            <small>Complétez ces points : l'administrateur contrôle en priorité les lots à faible score.</small>
          @else
            <small>Déclaration complète et cohérente avec les références de la catégorie.</small>
          @endif
        </div>
      @endcan
    </div>

    <div class="p-card">
      <h2 class="p-card__title"><i class="fa-solid fa-route text-success"></i> Origine du lot</h2>
      @if($harvest->public_description)
        <div class="insights__summary mb-3" style="border-left-color: var(--primary); background: #f1f8f3"><strong>Fiche consommateur :</strong> {{ $harvest->public_description }}</div>
      @endif
      <dl class="kv">
        <dt>Exploitation</dt><dd><a href="{{ route('pro.farms.show', $harvest->farm) }}">{{ $harvest->farm->name }}</a> — {{ $harvest->farm->city }}, {{ $harvest->farm->governorate }}</dd>
        <dt>Agriculture</dt><dd>{{ $harvest->farm->farmingTypeLabel() }}{{ $harvest->farm->certification ? ' · ' . $harvest->farm->certification : '' }}</dd>
        <dt>Parcelle</dt><dd>{{ $harvest->plot_name ?: '—' }}{{ $harvest->plot_area ? ' (' . $harvest->plot_area . ' ha)' : '' }}</dd>
        <dt>Catégorie</dt><dd>{{ $harvest->categoryLabel() }}</dd>
        <dt>Plantation</dt><dd>{{ $harvest->planting_date?->translatedFormat('d F Y') ?? '—' }}</dd>
        <dt>Récolte</dt><dd>{{ $harvest->harvest_date->translatedFormat('d F Y') }}</dd>
        <dt>Qualité</dt><dd>{{ \App\Models\Harvest::QUALITY_GRADES[$harvest->quality_grade] }}</dd>
        <dt>Stockage</dt><dd>{{ $harvest->storage_conditions ?: '—' }}</dd>
        <dt>Traitements</dt><dd style="white-space: pre-line">{{ $harvest->treatments ?: 'Aucun traitement déclaré' }}</dd>
        <dt>Notes</dt><dd style="white-space: pre-line">{{ $harvest->notes ?: '—' }}</dd>
        <dt>Enregistré le</dt><dd>{{ $harvest->created_at->format('d/m/Y à H:i') }}</dd>
      </dl>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="p-card p-0 overflow-hidden mb-4">
      <div style="position:relative">
        <img src="{{ $harvest->displayImageUrl() }}" alt="{{ $harvest->product_name }}" style="width:100%; height:240px; object-fit:cover; display:block">
        @unless($harvest->hasPhoto())
          <div style="position:absolute; inset:auto 12px 12px 12px; background:rgba(255,255,255,.92); border-radius:10px; padding:8px 12px; display:flex; justify-content:space-between; align-items:center; gap:8px; font-size:.82rem">
            <span><i class="fa-solid fa-image text-success"></i> Illustration de la catégorie</span>
            @can('update', $harvest)
              <a href="{{ route('pro.harvests.edit', $harvest) }}" class="btn btn-sm btn-success"><i class="fa-solid fa-camera"></i> Ajouter une photo</a>
            @endcan
          </div>
        @endunless
      </div>
    </div>

    <div class="p-card text-center">
      <h2 class="p-card__title justify-content-center"><i class="fa-solid fa-qrcode text-success"></i> QR code de traçabilité</h2>
      <div class="qr-box"><div id="qrcode"></div></div>
      <p class="small text-muted mt-2 mb-3">À imprimer sur l'emballage : le consommateur scanne et découvre l'origine du lot.</p>
      <div class="d-grid gap-2">
        <button type="button" class="btn btn-success" id="qr-download"><i class="fa-solid fa-download"></i> Télécharger (PNG)</button>
        <a href="{{ $harvest->traceabilityUrl() }}" target="_blank" rel="noopener" class="btn btn-outline-success"><i class="fa-solid fa-eye"></i> Voir la page consommateur</a>
        <button type="button" class="btn btn-light" id="copy-link"><i class="fa-solid fa-link"></i> Copier le lien</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
  <script>
    (function () {
      const url = @json($harvest->traceabilityUrl());
      const el = document.getElementById('qrcode');
      new QRCode(el, { text: url, width: 200, height: 200, colorDark: '#1B4332', colorLight: '#ffffff', correctLevel: QRCode.CorrectLevel.M });

      document.getElementById('qr-download').addEventListener('click', () => {
        const canvas = el.querySelector('canvas');
        const a = document.createElement('a');
        a.href = canvas ? canvas.toDataURL('image/png') : el.querySelector('img').src;
        a.download = @json('qr-' . $harvest->batch_code . '.png');
        a.click();
      });

      const copyBtn = document.getElementById('copy-link');
      copyBtn.addEventListener('click', async () => {
        try {
          await navigator.clipboard.writeText(url);
          copyBtn.innerHTML = '<i class="fa-solid fa-check"></i> Lien copié';
        } catch (e) {
          prompt('Copiez ce lien :', url);
        }
      });
    })();
  </script>
@endpush
