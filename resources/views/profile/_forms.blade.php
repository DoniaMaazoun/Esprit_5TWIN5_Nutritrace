{{--
  Formulaires « Mon profil », communs au front-office (Bootstrap 5) et au back-office Metis (Bootstrap 4).
  Les icônes portent les deux classes (Font Awesome côté front, MDI côté admin), comme shared/image-upload.
  Variable : $user
--}}
@php
  $role = match (true) {
      $user->isAdmin() => 'Administrateur',
      $user->isPro() => $user->getProTypeLabel(),
      default => 'Consommateur',
  };
  $proStatus = $user->isPro() ? match ($user->status) {
      'approved' => ['Compte validé', 'ok'],
      'rejected' => ['Compte refusé', 'ko'],
      default => ['Validation en attente', 'wait'],
  } : null;
  $err = fn (string $bag, string $field) => $errors->getBag($bag)->first($field);
@endphp

<style>
  .pf { --pf-green: #2D6A4F; --pf-dark: #1B4332; --pf-border: #e3ece5; --pf-muted: #6B7B6E; color: #243028; }
  .pf-grid { display: grid; grid-template-columns: 320px 1fr; gap: 22px; align-items: start; }
  @media (max-width: 1199px) { .pf-grid { grid-template-columns: 1fr; } }
  .pf-card { background: #fff; border: 1px solid var(--pf-border); border-radius: 14px; padding: 22px; box-shadow: 0 2px 8px rgba(27,67,50,.05); margin-bottom: 22px; }
  .pf-card h2 { font-size: 1.08rem; font-weight: 700; color: var(--pf-dark); margin: 0 0 4px; display: flex; align-items: center; gap: 8px; }
  .pf-card > p.pf-sub { color: var(--pf-muted); font-size: .86rem; margin: 0 0 18px; }
  .pf-id { text-align: center; }
  .pf-avatar { width: 112px; height: 112px; border-radius: 50%; margin: 0 auto 14px; background: linear-gradient(135deg, #2D6A4F, #40916C); color: #fff; font-size: 2.2rem; font-weight: 800; display: flex; align-items: center; justify-content: center; box-shadow: 0 6px 18px rgba(27,67,50,.18); }
  .pf-id strong { display: block; font-size: 1.1rem; color: var(--pf-dark); }
  .pf-id small { color: var(--pf-muted); word-break: break-all; }
  .pf-badges { display: flex; flex-wrap: wrap; gap: 6px; justify-content: center; margin: 12px 0 4px; }
  .pf-badge { font-size: .74rem; font-weight: 700; padding: 3px 10px; border-radius: 999px; background: #e8f5ec; color: var(--pf-dark); }
  .pf-badge--wait { background: #fff4e0; color: #8a5a00; } .pf-badge--ko { background: #fde8e8; color: #9b1c1c; }
  .pf-meta { list-style: none; padding: 0; margin: 16px 0 0; font-size: .84rem; text-align: left; border-top: 1px solid var(--pf-border); }
  .pf-meta li { display: flex; justify-content: space-between; gap: 10px; padding: 8px 0; border-bottom: 1px solid var(--pf-border); }
  .pf-meta span { color: var(--pf-muted); }
  .pf-photo { margin-top: 18px; text-align: left; }
  .pf-photo input[type=file] { font-size: .84rem; width: 100%; }
  .pf-hint { font-size: .78rem; color: var(--pf-muted); margin-top: 4px; }
  .pf-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
  @media (max-width: 575px) { .pf-row { grid-template-columns: 1fr; } }
  .pf-field { margin-bottom: 16px; }
  .pf-field label { display: block; font-weight: 600; font-size: .86rem; margin-bottom: 5px; color: #2f3b33; }
  .pf-field .form-control { border-radius: 9px; }
  .pf-field .form-control[readonly] { background: #f5f8f6; }
  .pf .invalid-feedback { display: block; }
  .pf-actions { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
  .pf-btn { display: inline-flex; align-items: center; justify-content: center; gap: 7px; padding: 9px 18px; border-radius: 9px; border: 1px solid transparent; font-weight: 700; font-size: .88rem; cursor: pointer; transition: background .15s; line-height: 1.2; }
  .pf-btn--primary { background: var(--pf-green); color: #fff; } .pf-btn--primary:hover { background: var(--pf-dark); color: #fff; }
  .pf-btn--ghost { background: #fff; border-color: #cfd8d2; color: #374038; } .pf-btn--ghost:hover { background: #f3f6f4; }
  .pf-btn--danger { background: #c53030; color: #fff; } .pf-btn--danger:hover { background: #9b2c2c; color: #fff; }
  .pf-btn--sm { padding: 6px 12px; font-size: .8rem; }
  .pf-btn[disabled] { opacity: .55; cursor: not-allowed; }
  .pf-note { font-size: .82rem; background: #f6fbf7; border: 1px dashed #b7d3c0; border-radius: 9px; padding: 10px 12px; color: #31533f; margin-bottom: 16px; }
  .pf-danger { border-color: #f3c4c4; }
  .pf-danger h2 { color: #9b2c2c; }
  .pf-flash { border-radius: 10px; padding: 12px 16px; margin-bottom: 18px; font-weight: 600; display: flex; gap: 8px; align-items: center; }
  .pf-flash--ok { background: #e8f5ec; color: #1B4332; border: 1px solid #b7d3c0; }
  .pf-flash--ko { background: #fde8e8; color: #9b1c1c; border: 1px solid #f3c4c4; }
</style>

<div class="pf">
  @if(session('success'))
    <div class="pf-flash pf-flash--ok" role="status"><i class="fa-solid fa-circle-check mdi mdi-check-circle"></i> {{ session('success') }}</div>
  @endif

  <div class="pf-grid">
    {{-- Identité + photo --}}
    <aside class="pf-card pf-id">
      <div class="pf-avatar">@include('profile._avatar', ['user' => $user])</div>
      <strong>{{ $user->name }}</strong>
      <small>{{ $user->email }}</small>
      <div class="pf-badges">
        <span class="pf-badge">{{ $role }}</span>
        @if($proStatus)<span class="pf-badge pf-badge--{{ $proStatus[1] }}">{{ $proStatus[0] }}</span>@endif
      </div>
      <ul class="pf-meta">
        @if($user->isPro())
          <li><span>Établissement</span><strong>{{ $user->company_name ?: '—' }}</strong></li>
          <li><span>Matricule fiscal</span><strong>{{ $user->tax_id ?: '—' }}</strong></li>
        @endif
        <li><span>Membre depuis</span><strong>{{ $user->created_at?->format('d/m/Y') }}</strong></li>
      </ul>

      <form method="POST" action="{{ route('profile.avatar') }}" enctype="multipart/form-data" class="pf-photo" data-pf-photo novalidate>
        @csrf
        <div class="pf-field">
          <label for="avatar">Photo de profil</label>
          <input type="file" id="avatar" name="avatar" accept="image/jpeg,image/png,image/webp" class="{{ $err('avatar', 'avatar') ? 'is-invalid' : '' }}">
          <div class="pf-hint">JPG, PNG ou WebP · 2 Mo maximum</div>
          @if($m = $err('avatar', 'avatar'))<div class="invalid-feedback">{{ $m }}</div>@endif
          <div class="invalid-feedback" data-pf-photo-error hidden></div>
        </div>
        <div class="pf-actions">
          <button type="submit" class="pf-btn pf-btn--primary pf-btn--sm" data-pf-photo-submit disabled><i class="fa-solid fa-upload mdi mdi-upload"></i> Enregistrer la photo</button>
        </div>
      </form>
      @if($user->avatar_path)
        <form method="POST" action="{{ route('profile.avatar.destroy') }}" class="mt-2" style="text-align:left" onsubmit="return confirm('Supprimer votre photo de profil ?')">
          @csrf
          @method('DELETE')
          <button type="submit" class="pf-btn pf-btn--ghost pf-btn--sm"><i class="fa-solid fa-trash mdi mdi-delete"></i> Supprimer la photo</button>
        </form>
      @endif
    </aside>

    <div>
      {{-- Informations --}}
      <section class="pf-card">
        <h2><i class="fa-solid fa-id-card mdi mdi-account"></i> Informations personnelles</h2>
        <p class="pf-sub">Ces informations apparaissent dans votre menu et sur vos échanges avec la plateforme.</p>
        <form method="POST" action="{{ route('profile.update') }}" novalidate>
          @csrf
          @method('PATCH')
          <div class="pf-row">
            <div class="pf-field">
              <label for="name">Nom complet</label>
              <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" maxlength="255" required autocomplete="name"
                     class="form-control {{ $err('profile', 'name') ? 'is-invalid' : '' }}">
              @if($m = $err('profile', 'name'))<div class="invalid-feedback">{{ $m }}</div>@endif
            </div>
            <div class="pf-field">
              <label for="email">Adresse e-mail</label>
              <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" maxlength="255" required autocomplete="email"
                     class="form-control {{ $err('profile', 'email') ? 'is-invalid' : '' }}">
              @if($m = $err('profile', 'email'))<div class="invalid-feedback">{{ $m }}</div>@endif
            </div>
          </div>
          @if($user->isPro())
            <div class="pf-row">
              <div class="pf-field">
                <label for="company_name">Nom de l'établissement</label>
                <input type="text" id="company_name" name="company_name" value="{{ old('company_name', $user->company_name) }}" maxlength="255" required
                       class="form-control {{ $err('profile', 'company_name') ? 'is-invalid' : '' }}">
                @if($m = $err('profile', 'company_name'))<div class="invalid-feedback">{{ $m }}</div>@endif
              </div>
              <div class="pf-field">
                <label for="pro_type_ro">Type d'activité</label>
                <input type="text" id="pro_type_ro" value="{{ $user->getProTypeLabel() }}" class="form-control" readonly>
              </div>
            </div>
            <div class="pf-note"><i class="fa-solid fa-circle-info mdi mdi-information"></i> Le type d'activité et le matricule fiscal ont été vérifiés par l'administrateur : contactez-le pour les modifier.</div>
          @endif
          <div class="pf-actions">
            <button type="submit" class="pf-btn pf-btn--primary"><i class="fa-solid fa-floppy-disk mdi mdi-content-save"></i> Enregistrer</button>
          </div>
        </form>
      </section>

      {{-- Mot de passe --}}
      <section class="pf-card">
        <h2><i class="fa-solid fa-key mdi mdi-key"></i> Mot de passe</h2>
        <p class="pf-sub">Utilisez au moins 8 caractères. Votre mot de passe actuel est demandé par sécurité.</p>
        <form method="POST" action="{{ route('profile.password') }}" novalidate>
          @csrf
          @method('PUT')
          <div class="pf-field">
            <label for="current_password">Mot de passe actuel</label>
            <input type="password" id="current_password" name="current_password" required autocomplete="current-password"
                   class="form-control {{ $err('password', 'current_password') ? 'is-invalid' : '' }}">
            @if($m = $err('password', 'current_password'))<div class="invalid-feedback">{{ $m }}</div>@endif
          </div>
          <div class="pf-row">
            <div class="pf-field">
              <label for="password">Nouveau mot de passe</label>
              <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password"
                     class="form-control {{ $err('password', 'password') ? 'is-invalid' : '' }}">
              @if($m = $err('password', 'password'))<div class="invalid-feedback">{{ $m }}</div>@endif
            </div>
            <div class="pf-field">
              <label for="password_confirmation">Confirmation</label>
              <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password" class="form-control">
            </div>
          </div>
          <div class="pf-actions">
            <button type="submit" class="pf-btn pf-btn--primary"><i class="fa-solid fa-lock mdi mdi-lock-reset"></i> Changer le mot de passe</button>
          </div>
        </form>
      </section>

      {{-- Suppression du compte --}}
      <section class="pf-card pf-danger" id="suppression">
        <h2><i class="fa-solid fa-triangle-exclamation mdi mdi-alert"></i> Supprimer mon compte</h2>
        <p class="pf-sub">La suppression est définitive : votre compte et les données qui lui sont liées (exploitations, recettes, expéditions…) seront effacés.</p>
        @if($m = $err('deletion', 'account'))
          <div class="pf-flash pf-flash--ko" role="alert"><i class="fa-solid fa-ban mdi mdi-cancel"></i> {{ $m }}</div>
        @endif
        <form method="POST" action="{{ route('profile.destroy') }}" novalidate onsubmit="return confirm('Supprimer définitivement votre compte ? Cette action est irréversible.')">
          @csrf
          @method('DELETE')
          <div class="pf-field" style="max-width: 360px">
            <label for="delete_password">Confirmez avec votre mot de passe</label>
            <input type="password" id="delete_password" name="password" required autocomplete="current-password"
                   class="form-control {{ $err('deletion', 'password') ? 'is-invalid' : '' }}">
            @if($m = $err('deletion', 'password'))<div class="invalid-feedback">{{ $m }}</div>@endif
          </div>
          <button type="submit" class="pf-btn pf-btn--danger"><i class="fa-solid fa-user-xmark mdi mdi-account-remove"></i> Supprimer définitivement</button>
        </form>
      </section>
    </div>
  </div>
</div>

<script>
  // Photo : aperçu immédiat et contrôle format / taille (doublé côté serveur)
  (function () {
    const form = document.querySelector('[data-pf-photo]');
    if (!form) return;
    const input = form.querySelector('input[type=file]');
    const submit = form.querySelector('[data-pf-photo-submit]');
    const error = form.querySelector('[data-pf-photo-error]');
    const avatar = document.querySelector('.pf-avatar');
    input.addEventListener('change', () => {
      const file = input.files[0];
      const msg = !file ? '' : (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) ? 'Formats acceptés : JPG, PNG ou WebP.'
        : (file.size > 2 * 1024 * 1024 ? 'La photo ne doit pas dépasser 2 Mo.' : ''));
      error.textContent = msg;
      error.hidden = !msg;
      input.classList.toggle('is-invalid', !!msg);
      submit.disabled = !file || !!msg;
      if (file && !msg) {
        const img = document.createElement('img');
        img.src = URL.createObjectURL(file);
        img.alt = 'Aperçu';
        img.style.cssText = 'width:100%;height:100%;object-fit:cover;border-radius:inherit;display:block';
        avatar.replaceChildren(img);
      }
    });
  })();
</script>
