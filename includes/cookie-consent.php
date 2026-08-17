<?php
/**
 * includes/cookie-consent.php
 * Информационный баннер о cookie (152-ФЗ): уведомляет и фиксирует согласие,
 * скрипты не блокирует — иначе просела бы аналитика по всей аудитории.
 *
 * GDPR-согласие для трафика из ЕС включается отдельно во встроенных сообщениях
 * AdSense (панель → «Конфиденциальность и обмен сообщениями»), с гео-таргетингом
 * только на ЕС — на рунет-посетителей оно не влияет.
 *
 * Выбор хранится в localStorage, поэтому баннер показывается один раз.
 */
?>
<div class="cookie-banner" id="cookieBanner" role="dialog" aria-live="polite"
     aria-label="Уведомление об использовании cookie" hidden>
  <p class="cookie-banner-text">
    🍪 Мы используем файлы cookie для аналитики и корректной работы сайта. Продолжая
    пользоваться сайтом, вы соглашаетесь с этим — подробнее в
    <a href="/privacy.php">политике конфиденциальности</a>.
  </p>
  <div class="cookie-banner-actions">
    <button type="button" class="cookie-banner-btn" id="cookieAccept">Принять</button>
  </div>
</div>
<script>
(function () {
  var KEY = 'domexpert-cookie-consent';
  var banner = document.getElementById('cookieBanner');
  if (!banner) { return; }
  var saved = null;
  try { saved = localStorage.getItem(KEY); } catch (e) {}
  if (!saved) { banner.hidden = false; }
  var btn = document.getElementById('cookieAccept');
  if (btn) {
    btn.addEventListener('click', function () {
      try { localStorage.setItem(KEY, 'accepted'); } catch (e) {}
      banner.hidden = true;
    });
  }
})();
</script>
