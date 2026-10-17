{{-- AgriBot : assistant IA du producteur --}}
<button type="button" id="chatbot-toggle" class="chatbot-toggle" aria-controls="chatbot" aria-expanded="false" title="Assistant AgriBot">
  <i class="fa-solid fa-robot"></i>
</button>

<section id="chatbot" class="chatbot" aria-label="Assistant AgriBot">
  <header class="chatbot__head">
    <i class="fa-solid fa-robot fa-lg"></i>
    <div>
      <strong>AgriBot</strong>
      <small>{{ app(\App\Services\AiAssistant::class)->isEnabled() ? 'Assistant IA connecté' : 'Assistant (mode hors-ligne)' }}</small>
    </div>
    <button type="button" class="chatbot__close" aria-label="Fermer"><i class="fa-solid fa-xmark"></i></button>
  </header>

  <div class="chatbot__body" aria-live="polite">
    <div class="chat-msg chat-msg--bot">Bonjour {{ auth()->user()->name }} 👋
Je suis AgriBot. Je connais vos exploitations et vos récoltes : posez-moi une question !</div>
  </div>

  <div class="chatbot__suggestions">
    <button type="button">Résume mes exploitations</button>
    <button type="button">Quel est mon stock ?</button>
    <button type="button">Que planter ce mois-ci ?</button>
    <button type="button">Quel est mon meilleur rendement ?</button>
  </div>

  <form class="chatbot__form" autocomplete="off">
    <input type="text" name="message" maxlength="500" placeholder="Écrivez votre question…" aria-label="Votre question">
    <button type="submit" class="btn btn-success" aria-label="Envoyer"><i class="fa-solid fa-paper-plane"></i></button>
  </form>
</section>
