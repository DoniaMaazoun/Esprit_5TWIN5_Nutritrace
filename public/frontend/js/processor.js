/* ==========================================================
   Module Transformateur - JavaScript
   ========================================================== */

document.addEventListener('DOMContentLoaded', function() {
  // Confirmation de suppression
  document.querySelectorAll('form[data-confirm]').forEach(form => {
    form.addEventListener('submit', function(e) {
      const message = this.getAttribute('data-confirm');
      if (!confirm(message)) {
        e.preventDefault();
      }
    });
  });

  // Auto-submit des filtres
  const searchInput = document.getElementById('q');
  if (searchInput) {
    let timeout;
    searchInput.addEventListener('input', function() {
      clearTimeout(timeout);
      timeout = setTimeout(() => {
        this.form.submit();
      }, 500);
    });
  }
});
