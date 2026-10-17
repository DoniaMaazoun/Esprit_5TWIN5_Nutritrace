@extends('pro.layouts.producer')

@section('title', 'Modifier le lot ' . $harvest->batch_code . ' | Farm to Fork')

@section('producer-content')
<div class="page-head">
  <div>
    <a href="{{ route('pro.harvests.show', $harvest) }}" class="small"><i class="fa-solid fa-arrow-left"></i> Lot {{ $harvest->batch_code }}</a>
    <h1>Modifier la récolte</h1>
    <p>Le code de lot <code>{{ $harvest->batch_code }}</code> reste inchangé : les QR codes déjà imprimés restent valides.</p>
  </div>
</div>

@include('pro.harvests._form', ['action' => route('pro.harvests.update', $harvest)])
@endsection
