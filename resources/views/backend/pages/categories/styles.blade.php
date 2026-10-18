<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
@include('backend.pages.partials.module-styles')
<style>
  .cat-layout { display: grid; grid-template-columns: minmax(0, 1fr) 340px; gap: 18px; align-items: start; }
  @media (max-width: 1500px) { .cat-layout { grid-template-columns: 1fr; } .cat-side { position: static; } }
  .cat-side { background: #fff; border: 1px solid var(--m1-border); border-radius: 12px; padding: 18px; position: sticky; top: 80px; }
  .cat-side h5 { font-weight: 700; color: var(--m1-dark); margin-bottom: 14px; }
  .cat-badge { width: 42px; height: 42px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; color: #fff; font-size: 1.1rem; flex-shrink: 0; }
  .cat-name { display: flex; align-items: center; gap: 12px; }
  .cat-name strong { color: var(--m1-dark); }
  .cat-bar { height: 8px; background: #eef2f0; border-radius: 999px; overflow: hidden; min-width: 90px; }
  .cat-bar span { display: block; height: 100%; border-radius: 999px; }
  .cat-off td { opacity: .55; }
  .cat-preview { display: flex; align-items: center; gap: 12px; background: #f8faf9; border: 1px dashed #cfe0d5; border-radius: 10px; padding: 10px 12px; }
  .cat-preview__icon { width: 46px; height: 46px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 1.2rem; }
  .icon-picker { display: grid; grid-template-columns: repeat(6, 1fr); gap: 6px; }
  .icon-picker__item { margin: 0; cursor: pointer; }
  .icon-picker__item input { position: absolute; opacity: 0; pointer-events: none; }
  .icon-picker__item span { display: flex; align-items: center; justify-content: center; height: 38px; border: 1px solid var(--m1-border); border-radius: 8px; color: #4a5a50; transition: all .15s; }
  .icon-picker__item:hover span { border-color: var(--m1-green); }
  .icon-picker__item input:checked + span { background: var(--m1-green); border-color: var(--m1-green); color: #fff; }
  .icon-picker__item input:focus-visible + span { outline: 2px solid #1d6fd1; }
  .color-swatch { width: 46px; height: 38px; padding: 2px; border: 1px solid #ced4da; border-radius: 6px; background: #fff; cursor: pointer; }
  .switch-line { display: flex; align-items: center; gap: 8px; height: 38px; margin: 0; font-weight: 600; cursor: pointer; }
</style>
