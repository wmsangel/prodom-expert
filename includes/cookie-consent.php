<?php
/**
 * includes/cookie-consent.php
 * Баннер согласия на cookie. Управляет рекламными и аналитическими cookie через
 * Google Consent Mode v2 — значения по умолчанию заданы в header.php ещё до
 * загрузки рекламы. Выбор сохраняется в localStorage; изменить его можно по
 * ссылке «Настройки cookie» в подвале (window.showCookieSettings).
 */
?>
<div id="cookie-consent" class="cookie-consent" role="dialog" aria-label="Согласие на использование cookie" aria-live="polite" hidden>
  <div class="cookie-consent__inner">
    <p class="cookie-consent__text">
      Мы используем файлы cookie для аналитики и показа рекламы. Нажимая «Принять всё», вы
      соглашаетесь на это. «Только необходимые» оставит работать сайт без рекламных и
      аналитических cookie. Подробнее — в <a href="/privacy.php">политике конфиденциальности</a>.
    </p>
    <div class="cookie-consent__actions">
      <button type="button" class="cookie-consent__btn cookie-consent__btn--ghost" data-consent="necessary">Только необходимые</button>
      <button type="button" class="cookie-consent__btn cookie-consent__btn--primary" data-consent="all">Принять всё</button>
    </div>
  </div>
</div>

<style>
  .cookie-consent{position:fixed;left:0;right:0;bottom:0;z-index:9999;background:var(--white);border-top:1px solid var(--border);box-shadow:0 -4px 22px var(--shadow);padding:16px 20px}
  .cookie-consent[hidden]{display:none}
  .cookie-consent__inner{max-width:var(--max-width,1200px);margin:0 auto;display:flex;align-items:center;gap:20px;flex-wrap:wrap}
  .cookie-consent__text{margin:0;flex:1 1 340px;font-size:.9rem;line-height:1.55;color:var(--text)}
  .cookie-consent__text a{color:var(--brick);text-decoration:underline}
  .cookie-consent__actions{display:flex;gap:10px;flex-wrap:wrap}
  .cookie-consent__btn{cursor:pointer;font-family:inherit;font-size:.9rem;font-weight:600;padding:10px 18px;border-radius:var(--radius,6px);border:1px solid var(--border);transition:filter .2s,border-color .2s,color .2s}
  .cookie-consent__btn--ghost{background:transparent;color:var(--heading)}
  .cookie-consent__btn--ghost:hover{border-color:var(--brick);color:var(--brick)}
  .cookie-consent__btn--primary{background:var(--brick);color:var(--on-dark);border-color:var(--brick)}
  .cookie-consent__btn--primary:hover{filter:brightness(1.07)}
  .cookie-consent__btn:focus-visible{outline:2px solid var(--brick);outline-offset:2px}
  @media (max-width:600px){.cookie-consent__inner{gap:12px}.cookie-consent__actions{width:100%}.cookie-consent__btn{flex:1 1 auto}}
</style>

<script>
(function () {
  var KEY = 'cookie_consent';
  var box = document.getElementById('cookie-consent');
  function stored(){ try { return localStorage.getItem(KEY); } catch (e) { return null; } }
  function apply(choice){
    if (typeof gtag === 'function') {
      if (choice === 'all') {
        gtag('consent','update',{ad_storage:'granted',ad_user_data:'granted',ad_personalization:'granted',analytics_storage:'granted'});
      } else {
        gtag('consent','update',{ad_storage:'denied',ad_user_data:'denied',ad_personalization:'denied',analytics_storage:'denied'});
      }
    }
  }
  function choose(choice){
    try { localStorage.setItem(KEY, choice); } catch (e) {}
    apply(choice);
    if (box) { box.hidden = true; }
  }
  if (box && !stored()) { box.hidden = false; }
  if (box) {
    box.querySelectorAll('[data-consent]').forEach(function (b) {
      b.addEventListener('click', function () { choose(b.getAttribute('data-consent')); });
    });
  }
  // Повторный вызов из подвала: «Настройки cookie»
  window.showCookieSettings = function () {
    try { localStorage.removeItem(KEY); } catch (e) {}
    if (box) { box.hidden = false; }
  };
})();
</script>
