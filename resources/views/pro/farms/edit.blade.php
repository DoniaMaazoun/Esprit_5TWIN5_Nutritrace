@extends('pro.layouts.producer')

@section('title', 'Modifier ' . $farm->name . ' | Farm to Fork')

@section('producer-content')
<div class="page-head">
  <div>
    <a href="{{ route('pro.farms.show', $farm) }}" class="small"><i class="fa-solid fa-arrow-left"></i> {{ $farm->name }}</a>
    <h1>Modifier l'exploitation</h1>
    <p>Les champs marqués <span class="text-danger">*</span> sont obligatoires.</p>
  </div>
</div>

@include('pro.farms._form', ['action' => route('pro.farms.update', $farm)])
@endsection
