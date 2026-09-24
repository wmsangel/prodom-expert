<?php
/**
 * includes/footer.php
 * Подвал сайта — подключается на каждой странице
 */
$currentYear = date('Y');
?>
  <footer class="site-footer" role="contentinfo">
    <div class="container">
      <div class="footer-grid">

        <!-- Бренд -->
        <div class="footer-brand">
          <a href="/" class="site-logo">
            <span class="logo-icon">🏠</span>Дом<span class="logo-accent">Эксперт</span>
          </a>
          <p class="footer-desc">
            Полезные статьи и практические советы по ремонту, строительству и обустройству дома. Помогаем создать уют своими руками.
          </p>
        </div>

        <!-- Категории -->
        <div>
          <h3 class="footer-heading">Категории</h3>
          <ul class="footer-links">
            <li><a href="/category/remont/">Ремонт</a></li>
            <li><a href="/category/okna/">Окна и двери</a></li>
            <li><a href="/category/santehnika/">Сантехника</a></li>
            <li><a href="/category/elektrika/">Электрика</a></li>
            <li><a href="/category/interer/">Интерьер</a></li>
            <li><a href="/category/sovety/">Советы</a></li>
          </ul>
        </div>

        <!-- Информация -->
        <div>
          <h3 class="footer-heading">Информация</h3>
          <ul class="footer-links">
            <li><a href="/about.php">О сайте</a></li>
            <li><a href="/editorial.php">Редакционная политика</a></li>
            <li><a href="/contacts.php">Контакты</a></li>
            <li><a href="/podderzhat.php">Поддержать проект</a></li>
            <li><a href="https://t.me/prodom_expert" target="_blank" rel="noopener">Telegram-канал</a></li>
            <li><a href="/privacy.php">Политика конфиденциальности</a></li>
            <li><a href="/usloviya.php">Условия использования</a></li>
            <li><a href="/articles.php">Все статьи — архив</a></li>
            <li><a href="/search.php">Поиск по сайту</a></li>
            <li><a href="/rss.php">RSS-лента</a></li>
            <li><a href="/sitemap.xml">Карта сайта</a></li>
            <li><a href="/llms.txt">llms.txt</a></li>
          </ul>
        </div>

      </div><!-- /.footer-grid -->

      <!-- Блок доверия: только проверяемые факты о том, как делается контент.
           Из соцсетей — Telegram-канал (в списке «Информация»). -->
      <div class="footer-trust">
        <h3 class="footer-trust-heading">Как мы работаем</h3>
        <ul class="footer-trust-list">
          <li>
            <strong>Материалы обновляются.</strong> У каждой статьи указана дата — вы всегда видите,
            насколько свежие цифры перед вами.
          </li>
          <li>
            <strong>Цены — ориентиры, а не прайс.</strong> Мы показываем структуру и пропорции затрат
            и честно предупреждаем о разбросе по регионам. Как считаем — в
            <a href="/editorial.php">редакционной политике</a>.
          </li>
          <li>
            <strong>Без платных обзоров.</strong> Мы не продаём места в статьях и не рекомендуем
            подрядчиков за вознаграждение. Рекламные блоки помечены отдельно.
          </li>
          <li>
            <strong>Границы рекомендаций обозначены.</strong> Там, где нужен проект, допуск или
            согласование, мы говорим об этом прямо, а не подменяем инструкцией из интернета.
          </li>
          <li>
            <strong>Нашли ошибку — исправим.</strong> Пишите на
            <a href="mailto:info@prodom-expert.ru">info@prodom-expert.ru</a>, порядок правок описан
            в <a href="/editorial.php">редполитике</a>.
          </li>
        </ul>
      </div>

      <div class="footer-bottom">
        <span>© <?= $currentYear ?> ДомЭксперт. Все права защищены.</span>
        <div class="footer-bottom-links">
          <a href="/about.php">О сайте</a>
          <a href="/contacts.php">Контакты</a>
          <a href="/privacy.php">Конфиденциальность</a>
          <a href="#" onclick="showCookieSettings();return false;">Настройки cookie</a>
        </div>
      </div>

    </div>
  </footer>

  <!-- Плавающий виджет: отзыв/ошибка + поддержать. Стили в style.css, логика в main.js. -->
  <div class="fab" id="fab">
    <div class="fab-actions" id="fabActions">
      <button type="button" class="fab-action" id="fabFeedback"><span class="ic" aria-hidden="true">💬</span> Отзыв / ошибка</button>
      <a class="fab-action" href="/podderzhat.php"><span class="ic" aria-hidden="true">☕</span> Поддержать</a>
    </div>
    <button type="button" class="fab-toggle" id="fabToggle"
            aria-expanded="false" aria-controls="fabActions" aria-label="Обратная связь и поддержка">+</button>
  </div>

  <!-- Модалка обратной связи -->
  <div class="fb-modal" id="fbModal" role="dialog" aria-modal="true" aria-labelledby="fbTitle" hidden>
    <div class="fb-dialog">
      <button type="button" class="fb-close" id="fbClose" aria-label="Закрыть">&times;</button>
      <h2 id="fbTitle">Отзыв или сообщение об ошибке</h2>
      <p class="fb-sub">Нашли неточность в цифрах или опечатку? Есть идея? Напишите — мы читаем и правим.</p>
      <form id="fbForm">
        <div class="fb-field">
          <label for="fbMessage">Сообщение</label>
          <textarea id="fbMessage" name="message" required maxlength="5000"
                    placeholder="Что не так или что предложить…"></textarea>
        </div>
        <div class="fb-field">
          <label for="fbEmail">Email для ответа <span style="font-weight:400;color:var(--text-muted)">(необязательно)</span></label>
          <input type="email" id="fbEmail" name="email" autocomplete="email" placeholder="you@example.com">
        </div>
        <div class="fb-hp" aria-hidden="true">
          <label>Не заполняйте это поле<input type="text" name="company" tabindex="-1" autocomplete="off"></label>
        </div>
        <input type="hidden" name="page" id="fbPage" value="">
        <button type="submit" class="fb-submit" id="fbSubmit">Отправить</button>
        <div class="fb-status" id="fbStatus" role="status" aria-live="polite"></div>
        <p class="fb-hint">Письмо уходит редакции на info@prodom-expert.ru. Email нужен только если хотите ответ.</p>
      </form>
    </div>
  </div>

  <script src="<?= htmlspecialchars(du_asset('/assets/js/main.js'), ENT_QUOTES, 'UTF-8') ?>" defer></script>

  <!-- Google tag (gtag.js) — в конце body, не блокирует FCP -->
  <script async src="https://www.googletagmanager.com/gtag/js?id=G-35WXXKG90T"></script>
  <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', 'G-35WXXKG90T');
  </script>

  <!-- Yandex.Metrika — один счётчик, после отрисовки страницы.
       Не запускается, если пользователь выбрал «Только необходимые». -->
  <script>
    (function(){
      var __noAnalytics = false;
      try { __noAnalytics = localStorage.getItem('cookie_consent') === 'necessary'; } catch (e) {}
      if (__noAnalytics) { return; }
      (function(m,e,t,r,i,k,a){
        m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
        m[i].l=1*new Date();
        for (var j = 0; j < document.scripts.length; j++) { if (document.scripts[j].src === r) { return; } }
        k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)
      })(window, document, 'script', 'https://mc.yandex.ru/metrika/tag.js?id=108673434', 'ym');
      ym(108673434, 'init', {ssr:true, webvisor:true, clickmap:true, ecommerce:'dataLayer', referrer: document.referrer, url: location.href, accurateTrackBounce:true, trackLinks:true});
    })();
  </script>
  <noscript><div><img src="https://mc.yandex.ru/watch/108673434" style="position:absolute; left:-9999px;" alt="" /></div></noscript>

  <?php
    // Свой баннер согласия — только пока не подключён CMP Ezoic (см. DOMEXPERT_CMP
    // в load-seo.php). Два CMP одновременно конфликтуют, поэтому при 'ezoic' прячем свой.
    if (!defined('DOMEXPERT_CMP') || DOMEXPERT_CMP === 'own') {
      include __DIR__ . '/cookie-consent.php';
    }
  ?>
</body>
</html>
