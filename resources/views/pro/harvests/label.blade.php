<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Étiquette {{ $harvest->batch_code }} | Farm to Fork</title>
  <link href="https://fonts.googleapis.com/css2?family=Lexend+Deca:wght@500;700&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    :root { --green: #1B4332; --mid: #2D6A4F; --light: #D8F3DC; }
    * { box-sizing: border-box; }
    body { margin: 0; font-family: 'Nunito', sans-serif; background: #eef3ef; color: #1A2E1F; }
    .toolbar { display: flex; justify-content: center; gap: 10px; padding: 18px; }
    .toolbar button, .toolbar label { font: 600 .9rem 'Lexend Deca', sans-serif; border: 0; border-radius: 10px; padding: 10px 16px; cursor: pointer; display: inline-flex; gap: 8px; align-items: center; }
    .toolbar .print { background: var(--mid); color: #fff; }
    .toolbar label { background: #fff; }
    .sheet { display: flex; flex-wrap: wrap; gap: 6mm; justify-content: center; padding: 0 0 30px; }
    .label { width: 105mm; height: 74mm; background: #fff; border: 1px dashed #b7c9bd; border-radius: 4mm; padding: 4.5mm; display: grid; grid-template-columns: 1fr 29mm; gap: 3mm; overflow: hidden; }
    .label__brand { display: flex; align-items: center; gap: 2mm; font: 700 3.2mm 'Lexend Deca', sans-serif; color: var(--mid); text-transform: uppercase; letter-spacing: .05em; }
    .label__brand i { background: var(--mid); color: #fff; width: 5.5mm; height: 5.5mm; border-radius: 1.5mm; display: grid; place-items: center; font-size: 3mm; }
    .label h1 { font: 700 5.6mm/1.1 'Lexend Deca', sans-serif; margin: 2mm 0 .8mm; color: var(--green); }
    .label .variety { font-size: 3.2mm; color: #5b6b5e; margin-bottom: 2mm; }
    .label dl { margin: 0; display: grid; grid-template-columns: 19mm 1fr; gap: .5mm 2mm; font-size: 2.6mm; line-height: 1.25; }
    .label dt { color: #6B7B6E; font-weight: 600; }
    .label dd { margin: 0; font-weight: 700; }
    .label__right { display: flex; flex-direction: column; align-items: center; justify-content: space-between; }
    .label__grade { font: 700 3mm 'Lexend Deca', sans-serif; background: var(--light); color: var(--green); padding: 1mm 2.5mm; border-radius: 99px; }
    .label__qr img, .label__qr canvas { width: 27mm !important; height: 27mm !important; }
    .label__code { font: 700 2.5mm monospace; color: var(--green); text-align: center; }
    .label__scan { font-size: 2.3mm; color: #6B7B6E; text-align: center; }
    @media print {
      body { background: #fff; }
      .toolbar { display: none; }
      .sheet { padding: 0; gap: 4mm; justify-content: flex-start; }
      .label { border-color: #ccc; break-inside: avoid; }
      @page { margin: 8mm; }
    }
  </style>
</head>
<body>
  <div class="toolbar">
    <label>Nombre d'étiquettes <input type="number" id="copies" value="1" min="1" max="24" style="width:60px"></label>
    <button class="print" onclick="window.print()"><i class="fa-solid fa-print"></i> Imprimer</button>
  </div>

  <div class="sheet" id="sheet">
    <article class="label" id="label-template">
      <div>
        <div class="label__brand"><i class="fa-solid fa-leaf"></i> Farm to Fork · Origine tracée</div>
        <h1>{{ $harvest->product_name }}</h1>
        <div class="variety">{{ $harvest->variety ?: $harvest->categoryLabel() }}</div>
        <dl>
          <dt>Producteur</dt><dd>{{ $harvest->farm->user->company_name }}</dd>
          <dt>Exploitation</dt><dd>{{ $harvest->farm->name }}</dd>
          <dt>Origine</dt><dd>{{ $harvest->farm->city }}, {{ $harvest->farm->governorate }} (Tunisie)</dd>
          <dt>Récolte</dt><dd>{{ $harvest->harvest_date->format('d/m/Y') }}</dd>
          <dt>À consommer</dt><dd>de préférence avant le {{ $fresh['best_before']->format('d/m/Y') }}</dd>
          <dt>Agriculture</dt><dd>{{ $harvest->farm->farmingTypeLabel() }}{{ $harvest->farm->certification ? ' · ' . $harvest->farm->certification : '' }}</dd>
        </dl>
      </div>
      <div class="label__right">
        <span class="label__grade">Qualité {{ $harvest->quality_grade }}</span>
        <div class="label__qr"></div>
        <div>
          <div class="label__code">{{ $harvest->batch_code }}</div>
          <div class="label__scan">Scannez pour l'origine</div>
        </div>
      </div>
    </article>
  </div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
  <script>
    (function () {
      const url = @json($harvest->traceabilityUrl());
      const sheet = document.getElementById('sheet');
      const template = document.getElementById('label-template');
      const render = () => {
        const n = Math.max(1, Math.min(24, parseInt(document.getElementById('copies').value, 10) || 1));
        sheet.innerHTML = '';
        for (let i = 0; i < n; i++) {
          const el = template.cloneNode(true);
          el.removeAttribute('id');
          const qr = el.querySelector('.label__qr');
          qr.innerHTML = '';
          sheet.appendChild(el);
          new QRCode(qr, { text: url, width: 200, height: 200, colorDark: '#1B4332', colorLight: '#ffffff', correctLevel: QRCode.CorrectLevel.M });
        }
      };
      document.getElementById('copies').addEventListener('input', render);
      render();
    })();
  </script>
</body>
</html>
