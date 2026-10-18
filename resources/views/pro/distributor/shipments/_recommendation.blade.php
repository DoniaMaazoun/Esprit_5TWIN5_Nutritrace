{{-- Recommandation IA du mode de transport, utilise les champs distance / poids du formulaire d'étape --}}
<div class="border rounded-3 p-3 mb-3" id="ai-recommendation" style="background: #f6fbf9;">
  <div class="d-flex flex-wrap align-items-end gap-3">
    <div>
      <label for="ai_max_hours" class="form-label mb-1">Délai souhaité (heures)</label>
      <input type="number" step="1" min="1" id="ai_max_hours" class="form-control form-control-sm" placeholder="Optionnel"
             onkeydown="if (event.key === 'Enter') { event.preventDefault(); }" style="max-width: 160px;">
    </div>
    <button type="button" class="btn btn-outline-success btn-sm" id="ai-recommend-btn">
      <i class="fa-solid fa-wand-magic-sparkles"></i> Recommandation IA
    </button>
    <span class="form-text m-0">Renseignez d'abord la distance et le poids de l'étape ci-dessous.</span>
  </div>

  <div id="ai-recommend-error" class="alert alert-warning mt-3 mb-0 d-none"></div>

  <div id="ai-recommend-result" class="mt-3 d-none">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
      <strong><i class="fa-solid fa-leaf text-success"></i> <span data-field="name"></span></strong>
      <span class="badge bg-secondary" data-field="source"></span>
    </div>
    <p class="mb-2 mt-2" data-field="explanation"></p>
    <div class="row text-center g-2 mb-2">
      <div class="col-sm-4"><div class="fw-bold" data-field="co2"></div><div class="small text-muted">CO2 estimé</div></div>
      <div class="col-sm-4"><div class="fw-bold" data-field="hours"></div><div class="small text-muted">Durée estimée</div></div>
      <div class="col-sm-4"><div class="fw-bold text-success" data-field="saving"></div><div class="small text-muted" data-field="saving-label">Gain vs camion</div></div>
    </div>
    <button type="button" class="btn btn-success btn-sm" id="ai-recommend-apply">
      <i class="fa-solid fa-check"></i> Utiliser ce mode pour l'étape
    </button>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    const box = document.getElementById('ai-recommendation');
    const button = document.getElementById('ai-recommend-btn');
    const errorBox = document.getElementById('ai-recommend-error');
    const result = document.getElementById('ai-recommend-result');
    const field = (name) => result.querySelector(`[data-field="${name}"]`);
    const kg = (value) => Number(value).toLocaleString('fr-FR', { maximumFractionDigits: 2 }) + ' kg CO2e';
    let recommendedId = null;

    button.addEventListener('click', async () => {
      const form = box.closest('form') ?? box.parentElement;
      const distance = form.querySelector('[name="distance_km"]')?.value;
      const weight = form.querySelector('[name="weight_kg"]')?.value;

      errorBox.classList.add('d-none');
      if (!distance) {
        errorBox.textContent = 'Indiquez la distance de l\'étape pour obtenir une recommandation.';
        errorBox.classList.remove('d-none');
        return;
      }

      button.disabled = true;
      button.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Analyse en cours…';

      try {
        const response = await fetch(@json(route('pro.distributor.shipments.transport-recommendation', $shipment)), {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': @json(csrf_token()) },
          body: JSON.stringify({ distance_km: distance, weight_kg: weight || null, max_hours: document.getElementById('ai_max_hours').value || null }),
        });
        const data = await response.json();
        if (!response.ok) {
          throw new Error(data.message || 'Recommandation indisponible.');
        }

        recommendedId = data.recommended.id;
        field('name').textContent = data.recommended.name;
        field('source').textContent = data.source === 'ai' ? 'IA' : 'Calcul ADEME (hors-ligne)';
        field('explanation').textContent = data.explanation;
        field('co2').textContent = kg(data.recommended.co2_kg);
        field('hours').textContent = '≈ ' + data.recommended.hours + ' h';
        if (data.reference) {
          field('saving').textContent = kg(data.saving_kg) + (data.saving_percent !== null ? ` (${data.saving_percent} %)` : '');
          field('saving-label').textContent = 'Gain vs ' + data.reference.name;
        } else {
          field('saving').textContent = '—';
        }
        result.classList.remove('d-none');
      } catch (error) {
        errorBox.textContent = error.message;
        errorBox.classList.remove('d-none');
      } finally {
        button.disabled = false;
        button.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles"></i> Recommandation IA';
      }
    });

    document.getElementById('ai-recommend-apply').addEventListener('click', () => {
      const select = (box.closest('form') ?? box.parentElement).querySelector('[name="transport_mode_id"]');
      if (select && recommendedId) {
        select.value = recommendedId;
        select.dispatchEvent(new Event('change'));
      }
    });
  });
</script>
