<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Rapport de traçabilité {{ $shipment->reference }}</title>
  {{-- Mise en page simple : dompdf ne gère ni flexbox ni grid --}}
  <style>
    @page { margin: 28px 32px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e3a2b; }
    h1 { font-size: 18px; margin: 0; }
    h2 { font-size: 13px; margin: 18px 0 6px; padding-bottom: 3px; border-bottom: 2px solid #28a745; }
    .header { background: #1e3a2b; color: #fff; padding: 12px 14px; }
    .header .sub { opacity: 0.8; font-size: 10px; margin-top: 3px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 5px 6px; border-bottom: 1px solid #e3ece8; text-align: left; vertical-align: top; }
    th { background: #f0f9f7; font-size: 10px; }
    .num { text-align: right; white-space: nowrap; }
    .info td { border: none; padding: 2px 6px; }
    .info td:first-child { width: 130px; font-weight: bold; }
    .totals td { width: 33%; text-align: center; border: 1px solid #e3ece8; padding: 8px; }
    .totals strong { display: block; font-size: 15px; }
    .total-row td { font-weight: bold; background: #f0f9f7; }
    .muted { color: #6c757d; }
    .footer { margin-top: 24px; font-size: 9px; color: #6c757d; }
  </style>
</head>
<body>
  @php($n = fn ($v, $d = 2) => number_format((float) $v, $d, ',', ' '))
  @php($originHarvests ??= $shipment->originHarvests())

  <div class="header">
    <h1>Rapport de traçabilité · {{ $shipment->reference }}</h1>
    <div class="sub">Farm to Fork · {{ $shipment->user->company_name }} · généré le {{ now()->format('d/m/Y H:i') }}</div>
  </div>

  <h2>Expédition</h2>
  <table class="info">
    <tr><td>Statut</td><td>{{ $shipment->getStatusLabel() }}</td></tr>
    <tr><td>Produit</td><td>{{ $shipment->product_name }} · {{ $n($shipment->weight_kg) }} kg</td></tr>
    <tr><td>Départ</td><td>{{ $shipment->origin_address }}@if($shipment->departed_at) · {{ $shipment->departed_at->format('d/m/Y H:i') }}@endif</td></tr>
    <tr><td>Destination</td><td>
      @if($shipment->pointOfSale)
        {{ $shipment->pointOfSale->name }}, {{ $shipment->pointOfSale->address }}, {{ $shipment->pointOfSale->city }}
      @else
        —
      @endif
      @if($shipment->delivered_at)
        · livrée le {{ $shipment->delivered_at->format('d/m/Y H:i') }}
      @endif
    </td></tr>
  </table>

  <h2>Lot de production</h2>
  @if($lot = $shipment->productionLot)
    <table class="info">
      <tr><td>Code du lot</td><td>{{ $lot->qr_code_data ?? 'Lot #'.$lot->id }}</td></tr>
      <tr><td>Produit fini</td><td>{{ $lot->getProductName() }} · {{ $lot->getQuantityDisplay() }}</td></tr>
      <tr><td>Transformateur</td><td>{{ $lot->processor->company_name ?: $lot->processor->name }}</td></tr>
      <tr><td>Produit le</td><td>{{ $lot->production_date->format('d/m/Y') }} · à consommer avant le {{ $lot->expiry_date->format('d/m/Y') }}</td></tr>
      @if($lot->recipe?->items->isNotEmpty())
        <tr><td>Ingrédients</td><td>{{ $lot->recipe->items->map(fn ($item) => $item->raw_material_name.' ('.$item->getQuantityDisplay().')')->implode(', ') }}</td></tr>
      @endif
    </table>

    <h2>Traçabilité jusqu'à la récolte</h2>
    @if($originHarvests->isNotEmpty())
      <table>
        <thead><tr><th>Lot récolte</th><th>Produit</th><th>Récolté le</th><th>Ferme</th></tr></thead>
        <tbody>
          @foreach($originHarvests as $harvest)
            <tr>
              <td>{{ $harvest->batch_code }}</td>
              <td>
                {{ $harvest->product_name }}
                @if($harvest->variety) ({{ $harvest->variety }}) @endif
                @if($harvest->category) · {{ $harvest->category->name }} @endif
              </td>
              <td>{{ $harvest->harvest_date->format('d/m/Y') }} · qualité {{ $harvest->quality_grade }}</td>
              <td>{{ $harvest->farm->name }}, {{ $harvest->farm->city }} ({{ $harvest->farm->governorate }})</td>
            </tr>
          @endforeach
        </tbody>
      </table>
      <p class="muted">Origine déduite des réceptions de matières premières du transformateur correspondant aux ingrédients de la recette.</p>
    @else
      <p class="muted">Récolte d'origine non retrouvée pour ce lot.</p>
    @endif
  @else
    <p class="muted">Aucun lot de production relié à cette expédition.</p>
  @endif

  <h2>Empreinte carbone</h2>
  <table class="totals">
    <tr>
      <td><strong>{{ $n($shipment->total_co2_kg) }}</strong>kg CO2e au total</td>
      <td><strong>{{ $n($shipment->total_distance_km, 1) }}</strong>km parcourus</td>
      <td><strong>{{ $shipment->weight_kg > 0 ? $n($shipment->total_co2_kg / ($shipment->weight_kg / 1000)) : '—' }}</strong>kg CO2e par tonne</td>
    </tr>
  </table>

  <h2>Étapes de transport</h2>
  @if($shipment->legs->isNotEmpty())
    <table>
      <thead>
        <tr>
          <th>#</th><th>Mode</th><th>Trajet</th>
          <th class="num">Distance (km)</th><th class="num">Facteur (kg/t.km)</th><th class="num">CO2 (kg)</th>
        </tr>
      </thead>
      <tbody>
        @foreach($shipment->legs as $leg)
          <tr>
            <td>{{ $leg->position }}</td>
            <td>{{ $leg->transportMode->name }}@if($leg->from_recommendation)<br><span class="muted">recommandé par l'IA</span>@endif</td>
            <td>{{ $leg->from_location }} → {{ $leg->to_location }}</td>
            <td class="num">{{ $n($leg->distance_km, 1) }}</td>
            <td class="num">{{ $n($leg->emission_factor, 4) }}</td>
            <td class="num">{{ $n($leg->co2_kg) }}</td>
          </tr>
        @endforeach
        <tr class="total-row">
          <td colspan="3">Total</td>
          <td class="num">{{ $n($shipment->total_distance_km, 1) }}</td>
          <td></td>
          <td class="num">{{ $n($shipment->total_co2_kg) }}</td>
        </tr>
      </tbody>
    </table>
    <p class="muted">CO2 (kg) = distance (km) × poids (t) × facteur d'émission du mode (Base Carbone ADEME, indicatif).</p>
  @else
    <p class="muted">Aucune étape de transport enregistrée.</p>
  @endif

  <h2>Journal de traçabilité</h2>
  @if($shipment->events->isNotEmpty())
    <table>
      <thead><tr><th>Date</th><th>Événement</th><th>Lieu</th><th>Note</th></tr></thead>
      <tbody>
        @foreach($shipment->events->sortBy('occurred_at') as $event)
          <tr>
            <td>{{ $event->occurred_at->format('d/m/Y H:i') }}</td>
            <td>{{ $event->getTypeLabel() }}</td>
            <td>{{ $event->location ?: '—' }}</td>
            <td>{{ $event->note }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @else
    <p class="muted">Aucun événement enregistré.</p>
  @endif

  <div class="footer">Document généré automatiquement par Farm to Fork (module Distributeur). Facteurs d'émission indicatifs, inspirés de la Base Carbone ADEME.</div>
</body>
</html>
