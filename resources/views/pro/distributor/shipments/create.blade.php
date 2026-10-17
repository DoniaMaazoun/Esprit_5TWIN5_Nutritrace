@extends('frontend.layouts.app')

@section('title', "Nouvelle expédition | Farm to Fork")

@section('extra-css')
  @include('pro.distributor.partials.styles')
@endsection

@section('content')
<div class="distributor-page">
  @include('pro.distributor.partials.layout-start')

  <div class="card distributor-card">
    <div class="card-header py-3"><i class="fa-solid fa-route"></i> Nouvelle expédition{{ $shipment->reference ? ' ' . $shipment->reference : '' }}</div>
    <div class="card-body">
      <form method="POST" action="{{ route('pro.distributor.shipments.store') }}">
        @csrf
        @include('pro.distributor.shipments._form')
        <div class="mt-4 d-flex gap-2">
          <button type="submit" class="btn btn-success"><i class="fa-solid fa-check"></i> Créer</button>
          <a href="{{ route('pro.distributor.shipments.index') }}" class="btn btn-outline-secondary">Annuler</a>
        </div>
      </form>
    </div>
  </div>
</div>
</div>
@endsection
