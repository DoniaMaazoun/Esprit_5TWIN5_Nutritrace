{{-- Champ photo du produit : glisser-déposer, aperçu, retrait. Variables : $current (URL ou null) --}}
@once
<style>
  .img-drop { position: relative; border: 2px dashed #b7d3c0; border-radius: 12px; background: #f6fbf7; min-height: 190px; display: flex; align-items: center; justify-content: center; overflow: hidden; transition: border-color .2s, background .2s; cursor: pointer; }
  .img-drop:hover, .img-drop.is-dragover { border-color: #2D6A4F; background: #edf7f0; }
  .img-drop.is-invalid { border-color: #dc3545; background: #fff5f5; }
  .img-drop__input { position: absolute; inset: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; z-index: 2; }
  .img-drop__preview { width: 100%; height: 190px; object-fit: cover; display: block; }
  .img-drop__placeholder { text-align: center; color: #6B7B6E; padding: 20px; font-size: .88rem; }
  .img-drop__placeholder i { font-size: 2.2rem; color: #40916C; display: block; margin-bottom: 8px; }
  .img-drop__placeholder strong { color: #1B4332; display: block; }
  .img-drop__change { position: absolute; bottom: 10px; right: 10px; z-index: 1; background: rgba(27,67,50,.85); color: #fff; font-size: .75rem; padding: 4px 10px; border-radius: 999px; }
  .img-drop__remove { display: flex; align-items: center; gap: 6px; margin-top: 8px; font-size: .85rem; color: #991b1b; cursor: pointer; }
</style>
@endonce

<div data-image-upload>
  <label for="image" class="form-label">Photo du produit</label>
  <div class="img-drop{{ $errors->has('image') ? ' is-invalid' : '' }}">
    <img class="img-drop__preview" src="{{ $current ?? '' }}" alt="Aperçu de la photo" @if(!$current) hidden @endif>
    <div class="img-drop__placeholder" @if($current) hidden @endif>
      <i class="fa-solid fa-camera mdi mdi-camera"></i>
      <strong>Glissez une photo ici ou cliquez pour choisir</strong>
      JPG, PNG ou WebP · 2 Mo maximum · 200 × 200 px minimum
    </div>
    <span class="img-drop__change" @if(!$current) hidden @endif><i class="fa-solid fa-arrows-rotate mdi mdi-refresh"></i> Changer</span>
    <input type="file" id="image" name="image" class="img-drop__input" accept="image/jpeg,image/png,image/webp" data-max-size="2048" data-min-dim="200">
  </div>
  @error('image')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
  @if($current)
    <label class="img-drop__remove"><input type="checkbox" name="remove_image" value="1"> Retirer la photo actuelle</label>
  @endif
</div>
