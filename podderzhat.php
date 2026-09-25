<?php
/**
 * podderzhat.php — Поддержать проект (крипто-чаевые + бесплатные способы помочь).
 * Реквизиты проверены программно перед публикацией (TRON base58check/0x41,
 * Solana 32 байта, ETH формат). QR-коды сгенерированы и хостятся у нас
 * (assets/img/donate/*.svg), не из чужого image-API.
 */
require_once __DIR__ . '/includes/load-seo.php';

$pageTitle = 'Поддержать проект | ДомЭксперт';
$pageDesc  = 'Поддержите ДомЭксперт крипто-чаевыми (USDT, SOL, ETH/USDC) или бесплатно — поделитесь сайтом. Добровольно, ни к чему не обязывает и ничего не разблокирует.';
$pageUrl   = SITE_CANONICAL . '/podderzhat.php';
$ogTitle   = $pageTitle;
$ogDesc    = $pageDesc;
$extraCss  = ['/assets/css/donate.css'];

$shareUrl  = SITE_CANONICAL . '/';
$shareText = 'ДомЭксперт — ремонт в цифрах: нормы, расчёты и калькуляторы для ремонта квартиры.';
$githubUrl = 'https://github.com/wmsangel/prodom-expert';
$tgUrl     = 'https://t.me/prodom_expert';

$articleJsonLd = json_encode([
  '@context'    => 'https://schema.org',
  '@type'       => 'WebPage',
  'name'        => 'Поддержать проект ДомЭксперт',
  'description' => $pageDesc,
  'url'         => $pageUrl,
  'inLanguage'  => 'ru-RU',
  'isPartOf'    => ['@id' => domexpert_website_id()],
  'publisher'   => ['@id' => domexpert_org_id()],
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

$breadcrumbJsonLd = json_encode([
  '@context'        => 'https://schema.org',
  '@type'           => 'BreadcrumbList',
  'itemListElement' => [
    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Главная',          'item' => SITE_CANONICAL . '/'],
    ['@type' => 'ListItem', 'position' => 2, 'name' => 'Поддержать проект', 'item' => $pageUrl],
  ],
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

// Крипто-реквизиты (единые на всех проектах). Проверены перед публикацией.
$wallets = [
  [
    'net'   => 'TRON · TRC-20',
    'asset' => 'USDT',
    'sub'   => 'Tether (USDT) по сети TRON',
    'addr'  => 'TTYkkhf3Pbc3Vw8h8wt2Y1uEGfxmT1TcL6',
    'qr'    => '/assets/img/donate/tron-usdt.svg',
    'warn'  => 'Отправляйте только <b>USDT по сети TRON (TRC-20)</b>. Перевод по другой сети (ERC-20, BEP-20 и т.д.) на этот адрес — безвозвратная потеря средств.',
  ],
  [
    'net'   => 'Solana · SPL',
    'asset' => 'SOL / USDT',
    'sub'   => 'SOL или USDT по сети Solana',
    'addr'  => 'He8CCQNSxyeGTiBG1EwxjbfNnQJndYB58jY15fBezyLX',
    'qr'    => '/assets/img/donate/solana.svg',
    'warn'  => 'Отправляйте только <b>SOL или USDT по сети Solana (SPL)</b>. Актив в другой сети на этот адрес не дойдёт.',
  ],
  [
    'net'   => 'Ethereum · ERC-20',
    'asset' => 'ETH / USDT / USDC',
    'sub'   => 'ETH, USDT или USDC по сети Ethereum',
    'addr'  => '0x80cda3f917b5cb07217bacc5d81605d406cbcfb8',
    'qr'    => '/assets/img/donate/eth.svg',
    'warn'  => 'Отправляйте только <b>ETH, USDT или USDC по сети Ethereum (ERC-20)</b>. Не путайте с BEP-20 или TRON.',
  ],
];

include __DIR__ . '/includes/header.php';
?>

<!-- BREADCRUMBS -->
<nav class="breadcrumbs" aria-label="Навигационная цепочка">
  <div class="container">
    <ol>
      <li><a href="/">Главная</a></li>
      <li>Поддержать проект</li>
    </ol>
  </div>
</nav>

<div class="category-header">
  <div class="container">
    <h1>☕ Поддержать проект</h1>
    <p>ДомЭксперт держится на энтузиазме. Если материалы вам помогли — можно сказать спасибо.</p>
  </div>
</div>

<div class="page-wrapper">
  <div class="container">
    <div class="main-layout main-layout--solo">
      <main id="main-content" role="main">
        <div class="article-body" style="margin-top:0;">

          <p class="donate-intro">
            Весь контент сайта бесплатный и таким останется. Донат — исключительно добровольная
            благодарность: он ничего не разблокирует и ни к чему вас не обязывает. Принимаем
            крипто-чаевые — это удобно без карт и границ.
          </p>

          <h2>Криптовалютой</h2>
          <div class="donate-grid">
            <?php foreach ($wallets as $w): ?>
            <div class="donate-card">
              <span class="donate-net"><?= esc($w['net']) ?></span>
              <p class="donate-assets"><?= esc($w['asset']) ?><small><?= esc($w['sub']) ?></small></p>
              <div class="donate-qr">
                <img src="<?= esc($w['qr']) ?>" width="168" height="168" loading="lazy"
                     alt="QR-код кошелька <?= esc($w['net']) ?>">
              </div>
              <div class="donate-addr-row">
                <code class="donate-addr" id="addr-<?= esc(md5($w['addr'])) ?>"><?= esc($w['addr']) ?></code>
                <button type="button" class="donate-copy" data-copy="<?= esc($w['addr']) ?>" data-net="<?= esc($w['net']) ?>">Копировать</button>
              </div>
              <p class="donate-warn">⚠️ <?= $w['warn'] ?></p>
            </div>
            <?php endforeach; ?>
          </div>

          <p class="donate-note">
            🔒 <b>Проверьте сеть и адрес перед отправкой.</b> Криптопереводы <b>необратимы</b>:
            ошибка в сети или адресе означает потерю средств без возможности вернуть. Сначала
            пришлите небольшую тестовую сумму, убедитесь, что она пришла, и только потом основную.
          </p>

          <h2>Куда идут деньги</h2>
          <p>
            На то, что держит сайт живым: хостинг и домен, инструменты и подписки для работы,
            и — главное — на время, которое уходит на новые статьи, калькуляторы и обновление
            цифр в уже опубликованных материалах. Никакой обязательной подписки и платного
            доступа: поддержка помогает делать больше бесплатного, а не открывает «закрытое».
          </p>

          <h2>Бесплатные способы помочь</h2>
          <p>Не обязательно донатить — репост и ссылка помогают проекту не меньше денег:</p>
          <div class="help-actions">
            <a class="help-btn" target="_blank" rel="noopener" data-track="share_click" data-net="x"
               href="https://twitter.com/intent/tweet?text=<?= rawurlencode($shareText) ?>&url=<?= rawurlencode($shareUrl) ?>">𝕏 Поделиться в X</a>
            <a class="help-btn" target="_blank" rel="noopener" data-track="share_click" data-net="reddit"
               href="https://www.reddit.com/submit?url=<?= rawurlencode($shareUrl) ?>&title=<?= rawurlencode($shareText) ?>">Reddit</a>
            <a class="help-btn tg" target="_blank" rel="noopener" data-track="share_click" data-net="telegram"
               href="https://t.me/share/url?url=<?= rawurlencode($shareUrl) ?>&text=<?= rawurlencode($shareText) ?>">✈️ Поделиться в Telegram</a>
            <button type="button" class="help-btn" data-copy="<?= esc($shareUrl) ?>" data-track="share_click" data-net="copy_link">🔗 Скопировать ссылку</button>
          </div>
          <div class="help-actions">
            <a class="help-btn tg" target="_blank" rel="noopener" data-track="tg_subscribe" href="<?= esc($tgUrl) ?>">✈️ Подписаться на Telegram-канал</a>
            <a class="help-btn gh" target="_blank" rel="noopener" data-track="github_star" href="<?= esc($githubUrl) ?>">⭐ Star на GitHub</a>
          </div>
          <p style="color:var(--text-light); font-size:.92rem; margin-top:14px;">
            А ещё можно просто порекомендовать ДомЭксперт знакомым, которые затеяли ремонт, или
            сослаться на наши статьи и калькуляторы на форуме — для нас это ценнее всего.
          </p>

          <h2>Важно знать</h2>
          <ul>
            <li>Донат <b>добровольный</b> и ничего не разблокирует — весь контент и так бесплатный.</li>
            <li>Криптопереводы <b>необратимы</b>; всегда проверяйте актив, сеть и адрес.</li>
            <li>Отправляйте <b>сначала маленькую тестовую сумму</b>, затем основную.</li>
            <li>В ряде стран дарение или получение криптовалюты — <b>налоговое событие</b>; вы отвечаете за это сами.</li>
          </ul>

          <p style="color:var(--text-muted); font-size:.9rem; margin-top:18px;">
            Спасибо, что читаете и поддерживаете. Вопросы — на
            <a href="mailto:info@prodom-expert.ru">info@prodom-expert.ru</a>.
          </p>

        </div>
      </main>
    </div>
  </div>
</div>

<script>
(function () {
  function flash(btn, ok) {
    var old = btn.textContent;
    btn.textContent = ok ? 'Скопировано ✓' : 'Не удалось';
    setTimeout(function () { btn.textContent = old; }, 1600);
  }
  function track(name, params) { if (window.duTrack) window.duTrack(name, params || {}); }
  document.querySelectorAll('[data-copy]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var text = btn.getAttribute('data-copy');
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(function () { flash(btn, true); },
                                                  function () { flash(btn, false); });
      } else {
        try {
          var ta = document.createElement('textarea');
          ta.value = text; document.body.appendChild(ta); ta.select();
          document.execCommand('copy'); document.body.removeChild(ta); flash(btn, true);
        } catch (e) { flash(btn, false); }
      }
      // Аналитика: копирование адреса кошелька (data-net) или ссылки (data-track)
      if (btn.hasAttribute('data-net') && !btn.hasAttribute('data-track')) {
        track('copy_address', { net: btn.getAttribute('data-net') });
      } else if (btn.getAttribute('data-track') === 'share_click') {
        track('share_click', { net: btn.getAttribute('data-net') || 'copy_link' });
      }
    });
  });
  // Аналитика: клики по ссылкам-помощи (share/tg/github)
  document.querySelectorAll('a[data-track]').forEach(function (a) {
    a.addEventListener('click', function () {
      track(a.getAttribute('data-track'), { net: a.getAttribute('data-net') || '' });
    });
  });
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
