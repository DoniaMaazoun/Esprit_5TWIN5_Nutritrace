@extends('backend.layouts.app')

@section('title', 'Modifier ' . $category->name . ' - Farm to Fork Admin')

@section('extra-css')
  @include('backend.pages.categories.styles')
@endsection

@section('content')
<div class="m1-head">
  <div>
    <div class="m1-crumb"><a href="{{ route('backend.dashboard') }}">Back-office</a> / <a href="{{ route('backend.categories.index') }}">Catégories</a></div>
    <h3><i class="mdi mdi-pencil"></i> Modifier « {{ $category->name }} »</h3>
    <p>{{ $category->harvests_count }} produit(s) classé(s) dans cette catégorie.</p>
  </div>
</div>

<div class="cat-side" style="max-width: 520px; position: static">
  @include('backend.pages.categories._form', ['action' => route('backend.categories.update', $category)])
</div>
@endsection

@section('extra-js')
  <script>window.AGRI = { csrf: @json(csrf_token()) };</script>
  <script src="{{ asset('frontend/js/producer.js') }}"></script>
  @stack('category-scripts')
@endsection
