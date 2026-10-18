@extends('frontend.layouts.app')

@section('title', "Modifier l'expédition | Farm to Fork")

@section('extra-css')
  @include('pro.distributor.partials.styles')
@endsection

@section('content')
<div class="distributor-page">
  @include('pro.distributor.partials.layout-start')

  <div class="card distributor-card">
    <div class="card-header py-3"><i class="fa-solid fa-route"></i> Modifier l'expédition{{ $shipment->reference ? ' ' . $shipment->reference : '' }}</div>
    <div class="card-body">
      <form method="POST" action="{{ route('pro.distributor.shipments.update', $shipment) }}">
        @csrf
        @method('PUT')
        @include('pro.distributor.shipments._form')
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
