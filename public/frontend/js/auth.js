/* ==========================================================
   Connexion / inscription : contrôle de saisie, mot de passe, type de compte
   ========================================================== */
(function () {
  'use strict';

  const emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

  function showError(input, msg) {
    const box = document.querySelector(`[data-error-for="${input.name}"]`);
    input.classList.toggle('is-invalid', !!msg);
    input.classList.toggle('is-valid', !msg && input.value.trim() !== '' && input.type !== 'checkbox');
    if (box) { box.hidden = !msg; box.querySelector('span').textContent = msg || ''; }
    return !msg;
  }

  // Afficher / masquer le mot de passe
  document.querySelectorAll('[data-toggle-password]').forEach((btn) => btn.addEventListener('click', () => {
    const input = document.getElementById(btn.dataset.togglePassword);
    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    btn.innerHTML = show ? '<i class="fa-regular fa-eye-slash"></i>' : '<i class="fa-regular fa-eye"></i>';
    btn.setAttribute('aria-label', show ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
  }));

  // ---------- Connexion ----------
  const login = document.getElementById('loginForm');
  if (login) {
    const rules = {
      email: (v) => !v ? 'L\'adresse e-mail est obligatoire.' : (!emailRe.test(v) ? 'L\'adresse e-mail n\'est pas valide.' : ''),
      password: (v) => !v ? 'Le mot de passe est obligatoire.' : '',
    };
    const check = (input) => showError(input, rules[input.name](input.value.trim()));
    Object.keys(rules).forEach((n) => login.elements[n].addEventListener('blur', () => check(login.elements[n])));
    login.addEventListener('submit', (e) => {
      const ok = Object.keys(rules).map((n) => check(login.elements[n])).every(Boolean);
      if (!ok) { e.preventDefault(); return; }
      const btn = login.querySelector('[type=submit]');
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Connexion…';
    });
  }

  // ---------- Inscription ----------
  const reg = document.getElementById('registerForm');
  if (!reg) return;

  const proFields = document.getElementById('proFields');
  const isPro = () => reg.querySelector('input[name=account_type]:checked')?.value === 'pro';
  const toggle = () => proFields.classList.toggle('show', isPro());
  reg.querySelectorAll('input[name=account_type]').forEach((r) => r.addEventListener('change', toggle));
  toggle();

  // Jauge de robustesse du mot de passe
  const pwd = reg.elements.password;
  const meter = document.getElementById('strength');
  const meterLabel = document.getElementById('strength-label');
  const labels = ['Trop court', 'Faible', 'Moyen', 'Bon', 'Excellent'];
  const score = (v) => {
    if (v.length < 8) return v.length ? 1 : 0;
    return 1 + [/[A-Z]/.test(v) && /[a-z]/.test(v), /\d/.test(v), /[^A-Za-z0-9]/.test(v) || v.length >= 12].filter(Boolean).length;
  };
  pwd.addEventListener('input', () => {
    const s = score(pwd.value);
    const tooShort = pwd.value.length > 0 && pwd.value.length < 8;
    meter.className = 'strength' + (s ? ' s' + s : '');
    meterLabel.textContent = !pwd.value ? 'Au moins 8 caractères (lettres, chiffres, symboles conseillés).'
      : 'Robustesse : ' + (tooShort ? labels[0] : labels[s]);
  });

  const rules = {
    name: (v) => !v ? 'Le nom est obligatoire.' : (v.length > 255 ? 'Le nom ne doit pas dépasser 255 caractères.' : ''),
    email: (v) => !v ? 'L\'adresse e-mail est obligatoire.' : (!emailRe.test(v) ? 'L\'adresse e-mail n\'est pas valide.' : ''),
    password: (v) => !v ? 'Le mot de passe est obligatoire.' : (v.length < 8 ? 'Le mot de passe doit contenir au moins 8 caractères.' : ''),
    password_confirmation: (v) => !v ? 'Confirmez le mot de passe.' : (v !== pwd.value ? 'Les mots de passe ne correspondent pas.' : ''),
    company_name: (v) => isPro() && !v ? 'Le nom de l\'établissement est obligatoire.' : '',
    tax_id: (v) => isPro() && !v ? 'Le matricule fiscal est obligatoire.' : (isPro() && !/^[A-Za-z0-9\/\-]{7,20}$/.test(v) ? 'Matricule fiscal invalide (7 à 20 caractères : lettres, chiffres, / ou -).' : ''),
  };
  const check = (name) => showError(reg.elements[name], rules[name](reg.elements[name].value.trim()));
  Object.keys(rules).forEach((n) => reg.elements[n].addEventListener('blur', () => check(n)));

  const checkProType = () => {
    const box = document.querySelector('[data-error-for="pro_type"]');
    const ok = !isPro() || !!reg.querySelector('input[name=pro_type]:checked');
    box.hidden = ok;
    box.querySelector('span').textContent = ok ? '' : 'Choisissez votre type d\'activité.';
    return ok;
  };
  const checkTerms = () => {
    const box = document.querySelector('[data-error-for="terms"]');
    const ok = reg.elements.terms.checked;
    box.hidden = ok;
    box.querySelector('span').textContent = ok ? '' : 'Vous devez accepter les conditions d\'utilisation.';
    return ok;
  };

  reg.addEventListener('submit', (e) => {
    const results = Object.keys(rules).map(check).concat([checkProType(), checkTerms()]);
    if (results.includes(false)) {
      e.preventDefault();
      reg.querySelector('.is-invalid, [data-error-for]:not([hidden])')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
      return;
    }
    const btn = reg.querySelector('[type=submit]');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Création du compte…';
  });
})();
