/* ==========================================================
   Module 1 (Producteur) — validation, IA, chatbot, confirmations
   ========================================================== */
(function () {
  'use strict';

  const cfg = window.AGRI || {};

  const escapeHtml = (s) => String(s)
    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;').replace(/'/g, '&#39;');

  // Texte IA : on échappe tout, puis on autorise uniquement le **gras**
  const renderRich = (s) => escapeHtml(s).replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');

  async function postJson(url, payload, method = 'POST') {
    const res = await fetch(url, {
      method,
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': cfg.csrf },
      body: method === 'GET' ? undefined : JSON.stringify(payload),
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
      const firstError = data.errors ? Object.values(data.errors)[0][0] : null;
      throw new Error(firstError || data.message || 'Erreur ' + res.status);
    }
    return data;
  }
  window.AGRI.postJson = postJson;
  window.AGRI.renderRich = renderRich;

  // ---------------------------------------------------------
  // 1. Contrôle de saisie côté client (doublé côté serveur)
  // ---------------------------------------------------------
  const today = () => new Date().toISOString().slice(0, 10);

  function fieldLabel(field) {
    const label = field.form.querySelector(`label[for="${field.id}"]`);
    return label ? label.textContent.replace('*', '').trim() : 'Ce champ';
  }

  function validateFile(field) {
    const file = field.files[0];
    if (!file) return '';
    const accepted = field.accept.split(',').map((s) => s.trim());
    if (!accepted.includes(file.type)) return 'Formats acceptés : JPG, PNG ou WebP.';
    if (file.size > +field.dataset.maxSize * 1024) return `L'image ne doit pas dépasser ${field.dataset.maxSize / 1024} Mo.`;
    if (field.dataset.dimError) return `L'image doit mesurer au moins ${field.dataset.minDim} × ${field.dataset.minDim} pixels.`;
    return '';
  }

  function validateField(field) {
    if (field.type === 'file') {
      const fileMsg = validateFile(field);
      setError(field, fileMsg);
      return !fileMsg;
    }
    const v = field.value.trim();
    const label = fieldLabel(field);
    const form = field.form;
    let msg = '';

    if (field.required && !v) {
      msg = `Le champ « ${label} » est obligatoire.`;
    } else if (v) {
      const min = field.getAttribute('minlength');
      const max = field.getAttribute('maxlength');
      if (min && v.length < +min) msg = `« ${label} » doit contenir au moins ${min} caractères.`;
      else if (max && v.length > +max) msg = `« ${label} » ne doit pas dépasser ${max} caractères.`;
      else if (field.type === 'number') {
        const n = Number(v);
        if (Number.isNaN(n)) msg = `« ${label} » doit être un nombre.`;
        else if (field.min !== '' && n < +field.min) msg = field.dataset.minMsg || `« ${label} » doit être au moins ${field.min}.`;
        else if (field.max !== '' && n > +field.max) msg = field.dataset.maxMsg || `« ${label} » ne doit pas dépasser ${field.max}.`;
        else if (field.dataset.gt !== undefined && n <= +field.dataset.gt) msg = `« ${label} » doit être strictement supérieur à ${field.dataset.gt}.`;
      } else if (field.dataset.pattern && !new RegExp(`^(?:${field.dataset.pattern})$`, 'u').test(v)) {
        msg = field.dataset.patternMsg || `Le format de « ${label} » est invalide.`;
      }

      // Règles personnalisées : data-rules="not-future|before:harvest_date|lte-farm-area"
      (field.dataset.rules || '').split('|').filter(Boolean).forEach((rule) => {
        if (msg) return;
        const [name, arg] = rule.split(':');
        if (name === 'not-future' && v > today()) {
          msg = `« ${label} » ne peut pas être dans le futur.`;
        }
        if (name === 'before') {
          const other = form.elements[arg];
          if (other && other.value && v >= other.value) msg = `« ${label} » doit précéder « ${fieldLabel(other)} ».`;
        }
        if (name === 'lte-farm-area') {
          const opt = form.elements[arg]?.selectedOptions[0];
          const area = opt ? Number(opt.dataset.area) : NaN;
          if (!Number.isNaN(area) && Number(v) > area) msg = `La parcelle ne peut pas dépasser la surface de l'exploitation (${area} ha).`;
        }
        if (name === 'with') {
          const other = form.elements[arg];
          if (other && !other.value.trim()) msg = 'Indiquez la latitude et la longitude ensemble.';
        }
      });
    }

    // Règle conditionnelle : data-required-if="farming_type=organic"
    if (!msg && !v && field.dataset.requiredIf) {
      const [other, expected] = field.dataset.requiredIf.split('=');
      if (form.elements[other]?.value === expected) {
        msg = field.dataset.requiredIfMsg || `Le champ « ${label} » est obligatoire.`;
      }
    }

    setError(field, msg);
    return !msg;
  }

  function setError(field, msg) {
    const anchor = field.closest('.img-drop') || field.closest('.input-group') || field;
    if (anchor !== field) anchor.classList.toggle('is-invalid', !!msg);
    // Retire l'erreur serveur (affichée juste après le champ) dès que le champ est revalidé
    const serverError = anchor.nextElementSibling;
    if (serverError && serverError.matches('.invalid-feedback:not(.js-feedback)')) serverError.remove();

    let fb = document.getElementById(`${field.id}-feedback`);
    if (!fb) {
      fb = document.createElement('div');
      fb.id = `${field.id}-feedback`;
      fb.className = 'invalid-feedback js-feedback';
      anchor.insertAdjacentElement('afterend', fb);
    }
    fb.textContent = msg;
    fb.style.display = msg ? 'block' : '';
    field.classList.toggle('is-invalid', !!msg);
    field.classList.toggle('is-valid', !msg && field.value.trim() !== '');
  }

  document.querySelectorAll('form[data-validate]').forEach((form) => {
    form.setAttribute('novalidate', 'novalidate');
    const fields = () => Array.from(form.querySelectorAll('input:not([type=hidden]), select, textarea'));

    fields().forEach((f) => {
      f.addEventListener('blur', () => validateField(f));
      f.addEventListener(f.tagName === 'SELECT' ? 'change' : 'input', () => {
        if (f.classList.contains('is-invalid') || f.classList.contains('is-valid')) validateField(f);
      });
    });

    form.addEventListener('submit', (e) => {
      const invalid = fields().filter((f) => !validateField(f));
      if (invalid.length) {
        e.preventDefault();
        invalid[0].focus();
        invalid[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
        const alert = form.querySelector('.js-form-alert');
        if (alert) {
          alert.textContent = `${invalid.length} champ(s) à corriger avant d'enregistrer.`;
          alert.classList.remove('d-none');
        }
      } else {
        const btn = form.querySelector('[type=submit]');
        if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Enregistrement…'; }
      }
    });
  });

  // Photo du produit : aperçu, glisser-déposer, contrôle des dimensions
  document.querySelectorAll('[data-image-upload]').forEach((box) => {
    const input = box.querySelector('input[type=file]');
    const drop = box.querySelector('.img-drop');
    const preview = box.querySelector('.img-drop__preview');
    const placeholder = box.querySelector('.img-drop__placeholder');
    const change = box.querySelector('.img-drop__change');
    const remove = box.querySelector('input[name=remove_image]');
    const original = preview.getAttribute('src');

    const show = (src) => {
      preview.src = src || '';
      preview.hidden = !src;
      placeholder.hidden = !!src;
      change.hidden = !src;
    };

    ['dragenter', 'dragover'].forEach((ev) => drop.addEventListener(ev, () => drop.classList.add('is-dragover')));
    ['dragleave', 'drop'].forEach((ev) => drop.addEventListener(ev, () => drop.classList.remove('is-dragover')));

    input.addEventListener('change', () => {
      const file = input.files[0];
      delete input.dataset.dimError;
      if (!file) { show(remove?.checked ? '' : original); validateField(input); return; }
      if (remove) remove.checked = false;
      if (validateFile(input)) { show(original); validateField(input); return; }

      const url = URL.createObjectURL(file);
      const img = new Image();
      img.onload = () => {
        const min = +input.dataset.minDim;
        if (img.naturalWidth < min || img.naturalHeight < min) input.dataset.dimError = '1';
        if (validateField(input)) show(url); else show(original);
      };
      img.src = url;
    });

    remove?.addEventListener('change', () => {
      if (remove.checked) { input.value = ''; delete input.dataset.dimError; validateField(input); show(''); }
      else show(original);
    });
  });

  // Compteur de caractères
  document.querySelectorAll('[data-count]').forEach((el) => {
    const counter = document.getElementById(el.dataset.count);
    const update = () => { counter.textContent = `${el.value.length} / ${el.maxLength}`; };
    el.addEventListener('input', update);
    update();
  });

  // ---------------------------------------------------------
  // 2. Description générée par IA
  // ---------------------------------------------------------
  document.querySelectorAll('[data-ai-description]').forEach((btn) => {
    btn.addEventListener('click', async () => {
      const form = btn.closest('form');
      const target = document.getElementById(btn.dataset.target);
      const status = document.getElementById(btn.dataset.status);
      const fields = ['name', 'governorate', 'city', 'total_area', 'farming_type', 'irrigation_type', 'soil_type', 'main_crops', 'certification', 'established_year'];
      const payload = {};
      fields.forEach((f) => { if (form.elements[f]?.value) payload[f] = form.elements[f].value; });

      const original = btn.innerHTML;
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Rédaction…';
      status.textContent = '';
      try {
        const data = await postJson(cfg.descriptionUrl, payload);
        target.value = data.text.slice(0, target.maxLength || 2000);
        target.dispatchEvent(new Event('input'));
        status.innerHTML = data.source === 'ai'
          ? '<i class="fa-solid fa-wand-magic-sparkles"></i> Rédigé par l\'IA — relisez et ajustez si besoin.'
          : '<i class="fa-solid fa-microchip"></i> Généré par le moteur local (aucune clé IA configurée).';
      } catch (err) {
        status.innerHTML = `<span class="text-danger">${escapeHtml(err.message)}</span>`;
      } finally {
        btn.disabled = false;
        btn.innerHTML = original;
      }
    });
  });

  // Fiche consommateur d'un lot générée par IA
  document.querySelectorAll('[data-ai-harvest]').forEach((btn) => {
    btn.addEventListener('click', async () => {
      const form = btn.closest('form');
      const target = document.getElementById(btn.dataset.target);
      const status = document.getElementById(btn.dataset.status);
      const fields = ['product_name', 'variety', 'category_id', 'farm_id', 'harvest_date', 'quality_grade', 'storage_conditions', 'treatments'];
      const payload = {};
      fields.forEach((f) => { if (form.elements[f]?.value) payload[f] = form.elements[f].value; });

      const original = btn.innerHTML;
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Rédaction…';
      status.textContent = '';
      try {
        const data = await postJson(cfg.harvestDescriptionUrl, payload);
        target.value = data.text.slice(0, target.maxLength || 1500);
        target.dispatchEvent(new Event('input'));
        status.innerHTML = data.source === 'ai'
          ? '<i class="fa-solid fa-wand-magic-sparkles"></i> Rédigé par l\'IA — relisez avant d\'enregistrer.'
          : '<i class="fa-solid fa-microchip"></i> Généré par le moteur local (aucune clé IA configurée).';
      } catch (err) {
        status.innerHTML = `<span class="text-danger">${escapeHtml(err.message)}</span>`;
      } finally {
        btn.disabled = false;
        btn.innerHTML = original;
      }
    });
  });

  // ---------------------------------------------------------
  // 3. Confirmation de suppression (modal Bootstrap)
  // ---------------------------------------------------------
  const modalEl = document.getElementById('confirmModal');
  if (modalEl && window.bootstrap) {
    const modal = new bootstrap.Modal(modalEl);
    let pendingForm = null;
    document.addEventListener('submit', (e) => {
      const form = e.target;
      if (!form.matches('form[data-confirm]') || form.dataset.confirmed) return;
      e.preventDefault();
      pendingForm = form;
      modalEl.querySelector('.js-confirm-text').textContent = form.dataset.confirm;
      modal.show();
    });
    modalEl.querySelector('.js-confirm-ok').addEventListener('click', () => {
      if (!pendingForm) return;
      pendingForm.dataset.confirmed = '1';
      pendingForm.submit();
    });
  }

  // ---------------------------------------------------------
  // 4. Chatbot AgriBot
  // ---------------------------------------------------------
  const bot = document.getElementById('chatbot');
  const toggle = document.getElementById('chatbot-toggle');
  if (bot && toggle) {
    const body = bot.querySelector('.chatbot__body');
    const form = bot.querySelector('.chatbot__form');
    const input = form.querySelector('input');
    const history = [];

    const add = (role, text, extra = '') => {
      const el = document.createElement('div');
      el.className = `chat-msg chat-msg--${role} ${extra}`;
      el.innerHTML = renderRich(text);
      body.appendChild(el);
      body.scrollTop = body.scrollHeight;
      return el;
    };

    const open = () => { bot.classList.add('open'); toggle.setAttribute('aria-expanded', 'true'); input.focus(); };
    const close = () => { bot.classList.remove('open'); toggle.setAttribute('aria-expanded', 'false'); };
    toggle.addEventListener('click', () => (bot.classList.contains('open') ? close() : open()));
    bot.querySelector('.chatbot__close').addEventListener('click', close);

    async function send(text) {
      text = text.trim();
      if (!text) return;
      if (text.length > 500) { add('bot', 'Votre message est trop long (500 caractères maximum).'); return; }
      add('user', text);
      input.value = '';
      input.disabled = true;
      const typing = add('bot', 'AgriBot réfléchit…', 'chat-msg--typing');
      try {
        const data = await postJson(cfg.chatUrl, { message: text, history: history.slice(-8) });
        typing.remove();
        add('bot', data.reply);
        history.push({ role: 'user', content: text }, { role: 'assistant', content: data.reply });
      } catch (err) {
        typing.remove();
        add('bot', 'Désolé, une erreur est survenue : ' + err.message);
      } finally {
        input.disabled = false;
        input.focus();
      }
    }

    form.addEventListener('submit', (e) => { e.preventDefault(); send(input.value); });
    bot.querySelectorAll('.chatbot__suggestions button').forEach((b) => b.addEventListener('click', () => send(b.textContent)));
  }
})();
