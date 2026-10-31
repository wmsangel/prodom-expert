<?php
/**
 * includes/analytics.php — веб-аналитика (GA4 + Яндекс.Метрика) с тремя защитами.
 *
 * 1) ПРОВЕРКА ХОСТА. Аналитика стартует ТОЛЬКО на боевых доменах
 *    (prodom-expert.com / .ru и их www). На зеркалах (*.pages.dev), localhost и
 *    превью-деплоях не грузится ничего — чтобы они не засоряли статистику и не
 *    ставили cookies. (Само зеркало *.pages.dev вдобавок закрыто 301-редиректом
 *    в functions/_middleware.js.)
 *
 * 2) GOOGLE ANALYTICS 4 (G-35WXXKG90T). Грузится на боевом хосте везде. В
 *    ЕС/Британии/Швейцарии работает в cookieless-режиме через Google Consent
 *    Mode v2 (analytics_storage=denied до согласия — задаётся в header.php),
 *    поэтому cookies `_ga`/`_ga_*` там не ставятся до согласия. Отдельный баннер
 *    для GA4 по правилам Google не требуется.
 *
 * 3) ЯНДЕКС.МЕТРИКА (счётчик 108673434, с Вебвизором). Consent Mode НЕ
 *    поддерживает, поэтому в ЕС/Британии/Швейцарии грузим её ТОЛЬКО после явного
 *    согласия «Принять всё» в баннере (cookie_consent === 'all'). В остальном
 *    мире — грузим, если пользователь не выбрал «Только необходимые». Регион
 *    определяем по Cloudflare `/cdn-cgi/trace` (loc=XX); если регион неизвестен
 *    (trace недоступен) — трактуем консервативно, как «нужно согласие».
 */
?>
<!-- Веб-аналитика: GA4 + Яндекс.Метрика. Старт только на боевом хосте; Метрика с региональным opt-in. -->
<script>
(function(){
  var PROD_HOSTS = ['prodom-expert.com','www.prodom-expert.com','prodom-expert.ru','www.prodom-expert.ru'];
  if (PROD_HOSTS.indexOf(location.hostname) === -1) { return; } // зеркало/localhost/превью — без аналитики

  // Регионы, где аналитические cookie нельзя ставить без явного согласия (EEA + UK + CH).
  var CONSENT_REGION = {AT:1,BE:1,BG:1,HR:1,CY:1,CZ:1,DK:1,EE:1,FI:1,FR:1,DE:1,GR:1,HU:1,IE:1,IT:1,LV:1,LT:1,LU:1,MT:1,NL:1,PL:1,PT:1,RO:1,SK:1,SI:1,ES:1,SE:1,IS:1,LI:1,NO:1,GB:1,CH:1};
  function consent(){ try { return localStorage.getItem('cookie_consent'); } catch(e){ return null; } }

  // ── GA4. gtag/dataLayer уже заданы в header.php (Consent Mode). ──
  var ga = document.createElement('script'); ga.async = true;
  ga.src = 'https://www.googletagmanager.com/gtag/js?id=G-35WXXKG90T';
  document.head.appendChild(ga);
  window.dataLayer = window.dataLayer || [];
  if (typeof gtag !== 'function') { window.gtag = function(){ dataLayer.push(arguments); }; }
  gtag('js', new Date());
  gtag('config', 'G-35WXXKG90T');

  // ── Яндекс.Метрика (+ Вебвизор). Загрузчик идемпотентен (защита от двойной вставки). ──
  function loadMetrika(){
    (function(m,e,t,r,i,k,a){
      m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
      m[i].l=1*new Date();
      for (var j=0;j<document.scripts.length;j++){ if(document.scripts[j].src===r){ return; } }
      k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)
    })(window, document, 'script', 'https://mc.yandex.ru/metrika/tag.js?id=108673434', 'ym');
    ym(108673434, 'init', {ssr:true, webvisor:true, clickmap:true, ecommerce:'dataLayer', referrer: document.referrer, url: location.href, accurateTrackBounce:true, trackLinks:true});
  }
  // Доступно баннеру: нажали «Принять всё» → грузим Метрику сразу (идемпотентно).
  window.duLoadMetrika = loadMetrika;

  function decideMetrika(region){
    var c = consent();
    // Регион требует согласия, если это EEA/UK/CH ИЛИ регион неизвестен (trace сбой) — консервативно.
    var needConsent = !region || !!CONSENT_REGION[region];
    if (needConsent) {
      if (c === 'all') { loadMetrika(); }   // только после явного «Принять всё»
      return;
    }
    // Регион без обязательного opt-in: грузим, если не выбрано «Только необходимые».
    if (c !== 'necessary') { loadMetrika(); }
  }

  // Регион по Cloudflare /cdn-cgi/trace (loc=XX). Сбой → region=null → трактуем как «нужно согласие».
  fetch('/cdn-cgi/trace', {cache:'no-store'})
    .then(function(r){ return r.text(); })
    .then(function(t){ var m = /(?:^|\n)loc=([A-Z]{2})/.exec(t); decideMetrika(m ? m[1] : null); })
    .catch(function(){ decideMetrika(null); });
})();
</script>
<noscript><div><img src="https://mc.yandex.ru/watch/108673434" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
