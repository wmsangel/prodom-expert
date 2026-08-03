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
            <li><a href="/privacy.php">Политика конфиденциальности</a></li>
            <li><a href="/articles.php">Все статьи — архив</a></li>
            <li><a href="/search.php">Поиск по сайту</a></li>
            <li><a href="/rss.php">RSS-лента</a></li>
            <li><a href="/sitemap.xml">Карта сайта</a></li>
            <li><a href="/llms.txt">llms.txt</a></li>
          </ul>
        </div>

      </div><!-- /.footer-grid -->

      <div class="footer-bottom">
        <span>© <?= $currentYear ?> ДомЭксперт. Все права защищены.</span>
        <div class="footer-bottom-links">
          <a href="/about.php">О сайте</a>
          <a href="/contacts.php">Контакты</a>
          <a href="/privacy.php">Конфиденциальность</a>
        </div>
      </div>

    </div>
  </footer>

  <script src="/assets/js/main.js" defer></script>

  <!-- Google tag (gtag.js) — в конце body, не блокирует FCP -->
  <script async src="https://www.googletagmanager.com/gtag/js?id=G-35WXXKG90T"></script>
  <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', 'G-35WXXKG90T');
  </script>

  <!-- Yandex.Metrika — один счётчик, после отрисовки страницы -->
  <script>
    (function(m,e,t,r,i,k,a){
      m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
      m[i].l=1*new Date();
      for (var j = 0; j < document.scripts.length; j++) { if (document.scripts[j].src === r) { return; } }
      k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)
    })(window, document, 'script', 'https://mc.yandex.ru/metrika/tag.js?id=108673434', 'ym');
    ym(108673434, 'init', {ssr:true, webvisor:true, clickmap:true, ecommerce:'dataLayer', referrer: document.referrer, url: location.href, accurateTrackBounce:true, trackLinks:true});
  </script>
  <noscript><div><img src="https://mc.yandex.ru/watch/108673434" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
</body>
</html>
