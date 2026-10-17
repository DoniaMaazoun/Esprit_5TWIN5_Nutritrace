@extends('frontend.layouts.app')

@section('title', 'Expédition ' . $shipment->reference . ' | Farm to Fork')

@section('extra-css')
  @include('pro.distributor.partials.styles')
@endsection

@section('content')
<div class="distributor-page">
  @include('pro.distributor.partials.layout-start')

  <div class="card distributor-card">
    <div class="card-header py-3"><i class="fa-solid fa-clipboard-list"></i> Modifier l'événement · {{ $shipment->reference }}</div>
    <div class="card-body">
      <form method="POST" action="{{ route('pro.distributor.shipments.events.update', [$shipment, $event]) }}">
        @csrf
        @method('PUT')
        @include('pro.distributor.events._form')
        <div class="mt-4 d-flex gap-2">
          <button type="submit" class="btn btn-success"><i class="fa-solid fa-check"></i> Enregistrer</button>
          <a href="{{ route('pro.distributor.shipments.show', $shipment) }}" class="btn btn-outline-secondary">Annuler</a>
        </div>
      </form>
    </div>
  </div>
</div>
</div>
@endsection
