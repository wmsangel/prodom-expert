<?php
/**
 * includes/affiliate.php
 * Партнёрские блоки (Admitad и др.) — «Где купить» на страницах с коммерческим
 * интентом: калькуляторы и статьи про материалы.
 *
 * Пока реестр пуст — на сайте НИЧЕГО не показывается (никаких заглушек).
 * Как появятся офферы: добавь строку в domexpert_affiliate_offers() с deeplink-
 * ссылкой из Admitad — блок появится сам на страницах с подходящим тегом.
 *
 * Ссылки идут с rel="sponsored nofollow noopener" и target="_blank" — это
 * правильная разметка партнёрских ссылок для поисковиков.
 *
 * Использование:
 *   require_once __DIR__ . '/affiliate.php';
 *   echo domexpert_affiliate_block(['materialy']);      // блок под тегом
 *   echo domexpert_affiliate_block(['plitka','materialy'], 2);
 */

if (!function_exists('domexpert_affiliate_offers')) {
  /**
   * Реестр партнёрских офферов. Каждый оффер:
   *   'id'    — короткий идентификатор
   *   'title' — название магазина/оффера
   *   'desc'  — короткое пояснение (1 строка)
   *   'cta'   — текст кнопки
   *   'url'   — ПАРТНЁРСКАЯ (deeplink) ссылка из Admitad
   *   'icon'  — эмодзи
   *   'tags'  — на каких страницах показывать: 'all' — везде, либо тематические
   *             теги ('materialy','plitka','kraska','oboi','laminat','kabel',
   *             'instrument','mebel','santehnika','okna','finansy','strahovanie')
   *
   * ПРИМЕР (раскомментируй и подставь свою deeplink-ссылку):
   *
   *   ['id' => 'ozon-materialy',
   *    'title' => 'Ozon',
   *    'desc'  => 'Стройматериалы, инструмент и техника с доставкой.',
   *    'cta'   => 'Смотреть на Ozon',
   *    'url'   => 'https://ad.admitad.com/g/ВАША_ССЫЛКА/',
   *    'icon'  => '🛒',
   *    'tags'  => ['all']],
   */
  function domexpert_affiliate_offers(): array {
    return [
      // Samura — premium-ножи для кухни (CPA /g/, 5.90% с заказа, средний чек 5000₽+,
      // EPC оффера 7647₽). Узкая ниша (кухонная утварь), поэтому теги — только статьи с
      // buyer-intent по кухне: профильная «как выбрать нож» + интерьер кухни + обеденная
      // зона. Стоит ПЕРВЫМ в реестре, чтобы на этих страницах занять слот раньше широких
      // interer-офферов (Евродом/Максидом), которые иначе заполнили бы лимит 2.
      [
        'id'    => 'samura',
        'title' => 'Samura',
        'desc'  => 'Профессиональные кухонные ножи из стали VG-10 и AUS-8.',
        'cta'   => 'Смотреть ножи Samura',
        'url'   => 'https://xmknb.com/g/4jfy3qmkf25593ebc048c0c6052a6b/?erid=2bL9aMPo2e49hMef4rqU6oVXgA',
        'icon'  => '🔪',
        'tags'  => ['kak-vybrat-kuhonnyy-nozh', 'interer-kuhni', 'obedennaya-zona-stol-i-stulya'],
      ],
      // Lu.ru — интернет-гипермаркет света (дом/улица), нац.доставка, средний чек 20000₽+,
      // EPC 466₽ → CPA (/g/, 6.71%). Специализированный магазин света: на статьях про
      // освещение он релевантнее AliExpress, поэтому стоит раньше в реестре.
      [
        'id'    => 'luru',
        'title' => 'Lu.ru',
        'desc'  => 'Гипермаркет света: люстры, светильники, LED и электроустановка.',
        'cta'   => 'Смотреть свет на Lu.ru',
        'url'   => 'https://thevospad.com/g/muxydgfucg5593ebc048ca00fd3984/?erid=5jtCeReNwxHpfQTDurBMqTr',
        'icon'  => '💡',
        'tags'  => ['osveshcheniye-v-kvartire', 'svetovye-stsenarii-v-kvartire',
                    'led-podsvetka-nishi-i-karnizov', 'dimmery-sveta-led-sovmestimost',
                    'garderobnaya-planirovanie-i-svet', 'zerkalo-v-prihozhey-razmer-i-svet'],
      ],
      // Аскона — фабрика №1 по матрасам/товарам для сна (кровати, матрасы, подушки),
      // нац.сеть 700+ магазинов + доставка по РФ, EPC 271₽ → CPA (/g/, 4.45%). Профильный
      // магазин сна: на статьях про кровать/матрас, детскую и спальню релевантнее диванных
      // брендов, поэтому стоит РАНЬШЕ Цвет Диванов/Divan BOSS. Ссылка идёт через трекер
      // SberMarketing (JS-редирект), erid сохранён — проверено.
      [
        'id'    => 'askona',
        'title' => 'Аскона',
        'desc'  => 'Матрасы, кровати и товары для сна фабрики №1. Гарантия до 25 лет, доставка по РФ.',
        'cta'   => 'Смотреть матрасы Аскона',
        'url'   => 'https://ytebb.com/g/av30r8vbjv5593ebc048a9fe403c97/?erid=25H8d7vbP8SRTvG4ygwa7P',
        'icon'  => '🛏',
        'tags'  => ['kak-vybrat-krovat-i-matras', 'mebel-dlya-detskoy-komnaty', 'malenkaya-spalnya-dizayn'],
      ],
      // Postel Deluxe — маркетплейс домашнего текстиля (постельное бельё, покрывала,
      // шторы), нац.доставка, CPA (/g/, 8.25–12.08%). Отдельной текстильной ниши у нас
      // не было — вешаем на текстиль/ковры/спальню/съёмную квартиру. Комплементарен
      // Асконе (матрас + бельё). Стоит рано, чтобы вести на текстильных страницах.
      [
        'id'    => 'postel',
        'title' => 'Postel Deluxe',
        'desc'  => 'Домашний текстиль: постельное бельё, покрывала, пледы и шторы. 150 брендов, доставка по РФ.',
        'cta'   => 'Смотреть текстиль Postel Deluxe',
        'url'   => 'https://dkfrh.com/g/4f5bbwmspn5593ebc0485e55659f09/?erid=2bL9aMPo2e49hMef4rqytKpfaC',
        'icon'  => '🛏',
        'tags'  => ['tekstil-shtory-v-gostinoy', 'kovry-v-interere-razmer-material',
                    'malenkaya-spalnya-dizayn', 'interer-semnoy-kvartiry'],
      ],
      // Мебель (мягкая): Цвет Диванов (zvet.ru) и divanboss — обе нац.сети, CPA (/g/),
      // высокий чек (divanboss 35000₽+). Стоят раньше широких interer-офферов (Евродом/
      // Максидом), чтобы на мебельных статьях занять слот. Дают обе — «в ротацию»: на
      // общих мебельных страницах блок покажет пару для сравнения (как Петрович+МЕГАСТРОЙ).
      [
        'id'    => 'zvet',
        'title' => 'Цвет Диванов',
        'desc'  => 'Диваны, кровати и мягкая мебель федеральной сети. Рассрочка, доставка по РФ.',
        'cta'   => 'Смотреть в Цвет Диванов',
        'url'   => 'https://thevospad.com/g/67570bafb65593ebc04866146bcf1d/?erid=5jtCeReLm1S3Xx3Lfj3wyRM',
        'icon'  => '🛋',
        'tags'  => ['kak-vybrat-kreslo', 'kak-vybrat-divan', 'dizayn-gostinoy', 'kuhnya-gostinaya-planirovka-zonirovanie',
                    'zonirovanie-studii-odnokomnatnoy', 'interer-semnoy-kvartiry',
                    'kak-vybrat-krovat-i-matras'],
      ],
      [
        'id'    => 'divanboss',
        'title' => 'Divan BOSS',
        'desc'  => 'Мягкая и корпусная мебель от производителя: диваны, кресла, шкафы.',
        'cta'   => 'Смотреть в Divan BOSS',
        'url'   => 'https://zallj.com/g/b5fs3128w25593ebc048fb3b97602e/?erid=2bL9aMPo2e49hMef4peV7UGo33',
        'icon'  => '🛋',
        'tags'  => ['kak-vybrat-kompyuternoe-kreslo', 'komod-i-sistemy-hraneniya', 'mebel-dlya-detskoy-komnaty', 'kak-vybrat-divan', 'shkaf-kupe-planirovka-napolnenie', 'dizayn-gostinoy', 'kuhnya-gostinaya-planirovka-zonirovanie',
                    'zonirovanie-studii-odnokomnatnoy', 'malenkaya-spalnya-dizayn'],
      ],
      // Сантехника Тут — профильный интернет-гипермаркет сантехники, доставка по РФ,
      // средний чек 30000₽ → CPA (/g/, 3.36%). Стоит раньше Петровича: на статьях
      // про сантехнику (унитаз, раковина, смеситель, бойлер, душ) профильный магазин
      // релевантнее общего строймага. На сантех-страницах блок покажет обоих.
      [
        'id'    => 'santehnikatut',
        'title' => 'Сантехника Тут',
        'desc'  => 'Профильный гипермаркет сантехники: смесители, унитазы, ванны, душ. Доставка по РФ.',
        'cta'   => 'Смотреть в Сантехника Тут',
        'url'   => 'https://dorinebeaumont.com/g/nxgdto54265593ebc048eb6db1b093/?erid=25H8d7vbP8SRTvGZQfQ5kD',
        'icon'  => '🚿',
        'tags'  => ['santehnika'],
      ],
      // Мегамаркет (Сбер) — крупный маркетплейс, CPA (/g/): 224.79₽ за заказ нового
      // клиента, 46.19₽ за повторный (фикс, не %). Взят УЗКО под пробел «бытовая
      // техника / умный дом», где у нас нет спец-магазина: варочные/духовки, посудомойки,
      // бойлеры, кондиционеры, вытяжки, умный дом. На общие материалы/мебель НЕ вешаем —
      // там хватает Петровича/AliExpress. Стоит перед AliExpress, чтобы на «технических»
      // страницах вести магазин техники, а не Али.
      [
        'id'    => 'megamarket',
        'title' => 'Мегамаркет',
        'desc'  => 'Маркетплейс Сбера: бытовая техника, умный дом, приборы. Кэшбэк, доставка по РФ.',
        'cta'   => 'Смотреть на Мегамаркете',
        'url'   => 'https://yyczo.com/g/apbut4et2w5593ebc0489371df1336/?erid=2bL9aMPo2e49hMef4rqxsS1r4P',
        'icon'  => '🛒',
        'tags'  => ['vytyazhka-dlya-kuhni-vybor', 'podklyuchenie-varochnoy-paneli-i-duhovki', 'podklyuchenie-posudomoechnoy-mashiny',
                    'bojler-nakopitelnyj-ili-protochnyj', 'vodonagrevatel-boyler-vybor-montazh',
                    'montazh-kondicionera-elektrika-trassa', 'umnyy-dom-osnovy',
                    'ventilyaciya-sanuzla-tyaga-i-ventilyator'],
      ],
      [
        'id'    => 'aliexpress',
        'title' => 'AliExpress',
        'desc'  => 'Инструмент, LED-подсветка, умный дом и фурнитура с доставкой.',
        'cta'   => 'Смотреть на AliExpress',
        'url'   => 'https://rzekl.com/g/1e8d1144945593ebc04816525dc3e8/',
        'icon'  => '🛒',
        'tags'  => ['elektrika', 'led-podsvetka-nishi-i-karnizov', 'svetovye-stsenarii-v-kvartire',
                    'zamery-okon-dlya-zakaza', 'instrumenty-dlya-remonta',
                    'dvernaya-furnitura-ruchki-zamki-petli', 'ukhod-za-furniturou-okon'],
      ],
      [
        'id'    => 'malare',
        'title' => 'Malare',
        'desc'  => 'Краски для дома от завода-производителя: интерьерные, фасадные, декоративные.',
        'cta'   => 'Выбрать краску',
        'url'   => 'https://hvjjg.com/g/km68poc2w35593ebc048e1ad5bc072/?erid=2bL9aMPo2e49hMef4rqUn6AAV2',
        'icon'  => '🎨',
        'tags'  => ['kraska', 'pokraska-sten-kvartiry-sovety', 'podgotovka-sten-pod-pokrasku',
                    'potolok-pod-pokrasku-vyravnivanie', 'dekorativnaya-shtukaturka-faktury',
                    'gruntovka-sten-osnovy'],
      ],
      [
        'id'    => 'eurodom',
        'title' => 'Евродом',
        'desc'  => 'Премиум-товары для дома: посуда, декор, текстиль. Бренды Zwilling, WMF, Peugeot.',
        'cta'   => 'Смотреть Евродом',
        'url'   => 'https://yynbx.com/g/wxsxy74ehc5593ebc0482a9bafdd81/?erid=MvGzQC98w3Z1gMq1mSxY8C15',
        'icon'  => '🏡',
        'tags'  => ['interer'],
      ],
      [
        'id'    => 'petrovich',
        'title' => 'Петрович',
        'desc'  => 'Стройматериалы, сухие смеси, сантехника и инструмент. Доставка за 2–4 часа.',
        'cta'   => 'Смотреть в Петровиче',
        'url'   => 'https://dhwnh.com/g/fjtv2ijs435593ebc0483d96fce434/?erid=25H8d7vbP8SRTvG4XfhAnc',
        'icon'  => '🧱',
        'tags'  => ['remont', 'santehnika', 'smeta', 'materialy',
                    'smeta-remonta-kvartiry', 'smeta-remonta-chastnogo-doma',
                    'byudzhet-kapitalnogo-remonta-raschet', 'raschet-materialov-dlya-remonta',
                    'chek-list-zakupok-do-nachala-remonta', 'instrumenty-dlya-remonta',
                    'zakupki-stroymaterialov-onlayn'],
      ],
      // Максидом — DIY-гипермаркет (мебель, товары для дома, инструмент). Ссылка
      // тарифа CPC (/c/, ₽8/клик по РФ), а НЕ CPA: магазин гео-ограничен (СПб+Москва),
      // покупок с всероссийского трафика мало, поэтому платим за клик, а не за заказ.
      // Ниша разведена с Петровичем: Максидом на интерьер/мебель/инструмент, Петрович
      // на ремонт/сантехнику/смету. CPA-запас (/g/) и коды баннеров — внизу файла.
      [
        'id'    => 'maxidom',
        'title' => 'Максидом',
        'desc'  => 'Гипермаркет для дома: мебель, инструмент, декор и товары для дачи.',
        'cta'   => 'Смотреть в Максидоме',
        'url'   => 'https://uuwgc.com/c/vw6dqabpgk5593ebc048b6a2cdd7f0/?erid=25H8d7vbP8SRTvH4HtSZJ1',
        'icon'  => '🔨',
        'tags'  => ['mebel', 'interer', 'instrumenty-dlya-remonta', 'obmer-kvartiry-svoimi-rukami',
                    'krepezh-v-stenu-dyubeli-i-ankery'],
      ],
      // Профи.ру — сервис поиска мастеров/специалистов. Ссылка CPA (/g/, Per Sale):
      // подтверждённая заявка 41–300 ₽, EPC оффера 358 ₽, работает по РФ+Казахстану,
      // интент на статьях «нужен мастер» высокий — тут CPA кратно выгоднее CPC ₽8.
      // Уникальная категория (услуги), с остальными офферами не пересекается.
      [
        'id'    => 'profi',
        'title' => 'Профи.ру',
        'desc'  => 'Проверенные мастера и бригады для ремонта под конкретную задачу.',
        'cta'   => 'Найти мастера на Профи.ру',
        'url'   => 'https://dkfrh.com/g/zqyi8ot6o25593ebc0487e4bf1243c/?erid=MvGzQC98w3Z1gMq1kwWR4fyN',
        'icon'  => '🛠',
        'tags'  => ['vybor-remontnoj-brigady', 'chto-vhodit-v-remont-pod-klyuch',
                    'brigada-brosila-obekt-chto-delat', 'sroki-remonta-kvartiry-po-etapam',
                    'otkuda-berutsya-ceny-na-remont'],
      ],
      // МЕГАСТРОЙ — федеральная DIY-сеть (стройка/ремонт/декор/сад), доставка по всей РФ
      // (в отличие от гео-ограниченного Максидома) → CPA (/g/, Per Sale, 1.55–3.61%),
      // EPC оффера 501 ₽. Ставим В РОТАЦИЮ к Петровичу на строй-материальных страницах:
      // на пересекающихся тегах блок покажет обоих (Петрович + МЕГАСТРОЙ, лимит 2) —
      // читатель сравнивает две сети. Имя МЕГАСТРОЙ узнаваемо в Поволжье/Татарстане.
      [
        'id'    => 'megastroy',
        'title' => 'МЕГАСТРОЙ',
        'desc'  => 'Строительный DIY-гипермаркет: материалы, инструмент, декор и сад.',
        'cta'   => 'Смотреть в МЕГАСТРОЙ',
        'url'   => 'https://bednari.com/g/xcawb6ikii5593ebc048161cbbe6df/?erid=5jtCeReNwxHpfQTEQWKMenp',
        'icon'  => '🏬',
        'tags'  => ['mebel', 'remont', 'materialy', 'raschet-materialov-dlya-remonta',
                    'zakupki-stroymaterialov-onlayn', 'chek-list-zakupok-do-nachala-remonta'],
      ],
    ];
  }
}

if (!function_exists('domexpert_affiliate_pick')) {
  /**
   * Выбор офферов под теги страницы. Оффер подходит, если у него есть тег 'all'
   * или пересечение с $tags. Порядок реестра сохраняется.
   */
  function domexpert_affiliate_pick(array $tags = [], int $limit = 2): array {
    $picked = [];
    foreach (domexpert_affiliate_offers() as $o) {
      if (empty($o['url'])) { continue; }
      $ot = $o['tags'] ?? ['all'];
      if (in_array('all', $ot, true) || array_intersect($ot, $tags)) {
        $picked[] = $o;
        if (count($picked) >= $limit) { break; }
      }
    }
    return $picked;
  }
}

if (!function_exists('domexpert_affiliate_block')) {
  /**
   * Рендерит блок «Где купить». Возвращает '' если подходящих офферов нет —
   * поэтому вызов безопасен на любой странице.
   */
  function domexpert_affiliate_block(array $tags = [], int $limit = 2): string {
    $offers = domexpert_affiliate_pick($tags, $limit);
    if (!$offers) { return ''; }

    $rel = 'sponsored nofollow noopener';
    $items = '';
    foreach ($offers as $o) {
      $url   = htmlspecialchars($o['url'],   ENT_QUOTES, 'UTF-8');
      $title = htmlspecialchars($o['title'], ENT_QUOTES, 'UTF-8');
      $desc  = htmlspecialchars($o['desc'] ?? '', ENT_QUOTES, 'UTF-8');
      $cta   = htmlspecialchars($o['cta'] ?? 'Перейти', ENT_QUOTES, 'UTF-8');
      $icon  = htmlspecialchars($o['icon'] ?? '🛒', ENT_QUOTES, 'UTF-8');
      $items .= <<<HTML
    <a class="aff-item" href="{$url}" target="_blank" rel="{$rel}">
      <span class="aff-icon" aria-hidden="true">{$icon}</span>
      <span class="aff-text"><strong class="aff-title">{$title}</strong><span class="aff-desc">{$desc}</span></span>
      <span class="aff-cta">{$cta} →</span>
    </a>

HTML;
    }

    return <<<HTML
<aside class="aff-block" aria-label="Партнёрские магазины">
  <span class="aff-label">Реклама · где купить</span>
{$items}</aside>
HTML;
  }
}

if (!function_exists('domexpert_sidebar_banner')) {
  /**
   * Контекстный баннер 300×250 в сайдбаре — монетизирует ранее пустой слот.
   * На мебельных страницах показывает Divan BOSS (в тему), на остальных —
   * Профи.ру (релевантен любому ремонту, «нужен мастер»). $tags — теги страницы
   * ([slug, catSlug]); если среди подходящих офферов есть мебельный — баннер мебельный.
   * Картинка адаптивная, erid в ссылке, метка «Реклама» — маркировка соблюдена.
   */
  function domexpert_sidebar_banner(array $tags = []): string {
    // Мебельный баннер — на всей рубрике «Мебель» (catSlug) и на интерьерных
    // страницах, где подходит мягкая мебель (совпал оффер divanboss/zvet).
    $isFurniture = in_array('mebel', $tags, true);
    if (!$isFurniture) {
      foreach (domexpert_affiliate_pick($tags, 5) as $o) {
        if (in_array($o['id'], ['divanboss', 'zvet'], true)) { $isFurniture = true; break; }
      }
    }
    if ($isFurniture) {
      $href = 'https://zallj.com/g/g3odrskrah5593ebc048fb3b97602e/?i=4&erid=2bL9aMPo2e49hMef4rqUKy1pYL';
      $img  = 'https://aflink.ru/b/g3odrskrah5593ebc048fb3b97602e/';
      $alt  = 'Divan BOSS — мягкая и корпусная мебель от производителя';
    } else {
      $href = 'https://dkfrh.com/g/9oieanlurm5593ebc0487e4bf1243c/?i=4&erid=2bL9aMPo2e49hMef4rqyCujmBL';
      $img  = 'https://aflink.ru/b/9oieanlurm5593ebc0487e4bf1243c/';
      $alt  = 'Профи.ру — проверенные мастера для ремонта';
    }
    $h = htmlspecialchars($href, ENT_QUOTES, 'UTF-8');
    $i = htmlspecialchars($img,  ENT_QUOTES, 'UTF-8');
    $a = htmlspecialchars($alt,  ENT_QUOTES, 'UTF-8');
    return <<<HTML
<div class="sidebar-widget aff-banner-widget">
  <span class="aff-banner-label">Реклама</span>
  <a class="aff-banner" href="{$h}" target="_blank" rel="sponsored nofollow noopener">
    <img src="{$i}" width="300" height="250" alt="{$a}" loading="lazy" decoding="async">
  </a>
</div>
HTML;
  }
}

/* ── Полки рекомендованных товаров (deeplink на конкретные товары) ────────── */

if (!function_exists('domexpert_product_shelves')) {
  /**
   * Полки товаров под тему статьи. Каждая: title (заголовок), tags (на каких
   * страницах), items (товары: icon, name, url — deeplink Admitad на товар).
   * Пустой url у товара — пропускается.
   */
  function domexpert_product_shelves(): array {
    return [
      [
        'id'    => 'instrument-zamer',
        'title' => 'Инструмент для замеров',
        'tags'  => ['zamery-okon-dlya-zakaza', 'instrumenty-dlya-remonta', 'obmer-kvartiry-svoimi-rukami'],
        'items' => [
          ['icon' => '🔦', 'name' => 'Лазерный уровень',   'url' => 'https://rzekl.com/g/1e8d1144945593ebc04816525dc3e8/?ulp=https%3A%2F%2Faliexpress.ru%2Fwholesale%3FSearchText%3D%D0%BB%D0%B0%D0%B7%D0%B5%D1%80%D0%BD%D1%8B%D0%B9%2B%D1%83%D1%80%D0%BE%D0%B2%D0%B5%D0%BD%D1%8C'],
          ['icon' => '📏', 'name' => 'Рулетка 5 м',        'url' => 'https://rzekl.com/g/1e8d1144945593ebc04816525dc3e8/?ulp=https%3A%2F%2Faliexpress.ru%2Fwholesale%3FSearchText%3D%D1%80%D1%83%D0%BB%D0%B5%D1%82%D0%BA%D0%B0%2B%D0%B8%D0%B7%D0%BC%D0%B5%D1%80%D0%B8%D1%82%D0%B5%D0%BB%D1%8C%D0%BD%D0%B0%D1%8F%2B5%D0%BC'],
          ['icon' => '🎯', 'name' => 'Лазерный дальномер', 'url' => 'https://rzekl.com/g/1e8d1144945593ebc04816525dc3e8/?ulp=https%3A%2F%2Faliexpress.ru%2Fwholesale%3FSearchText%3D%D0%BB%D0%B0%D0%B7%D0%B5%D1%80%D0%BD%D1%8B%D0%B9%2B%D0%B4%D0%B0%D0%BB%D1%8C%D0%BD%D0%BE%D0%BC%D0%B5%D1%80'],
        ],
      ],
      [
        'id'    => 'led',
        'title' => 'Всё для LED-подсветки',
        'tags'  => ['led-podsvetka-nishi-i-karnizov', 'dimmery-sveta-led-sovmestimost',
                    'svetovye-stsenarii-v-kvartire', 'led-lenta-montazh-pitanie-profil'],
        'items' => [
          ['icon' => '💡', 'name' => 'Светодиодная лента 12 В', 'url' => 'https://rzekl.com/g/1e8d1144945593ebc04816525dc3e8/?ulp=https%3A%2F%2Faliexpress.ru%2Fwholesale%3FSearchText%3D%D1%81%D0%B2%D0%B5%D1%82%D0%BE%D0%B4%D0%B8%D0%BE%D0%B4%D0%BD%D0%B0%D1%8F%2B%D0%BB%D0%B5%D0%BD%D1%82%D0%B0%2B12%D0%B2'],
          ['icon' => '📐', 'name' => 'Профиль для ленты',       'url' => 'https://rzekl.com/g/1e8d1144945593ebc04816525dc3e8/?ulp=https%3A%2F%2Faliexpress.ru%2Fwholesale%3FSearchText%3D%D0%BF%D1%80%D0%BE%D1%84%D0%B8%D0%BB%D1%8C%2B%D0%B4%D0%BB%D1%8F%2B%D1%81%D0%B2%D0%B5%D1%82%D0%BE%D0%B4%D0%B8%D0%BE%D0%B4%D0%BD%D0%BE%D0%B9%2B%D0%BB%D0%B5%D0%BD%D1%82%D1%8B'],
          ['icon' => '🔌', 'name' => 'Блок питания 12 В',        'url' => 'https://rzekl.com/g/1e8d1144945593ebc04816525dc3e8/?ulp=https%3A%2F%2Faliexpress.ru%2Fwholesale%3FSearchText%3D%D0%B1%D0%BB%D0%BE%D0%BA%2B%D0%BF%D0%B8%D1%82%D0%B0%D0%BD%D0%B8%D1%8F%2B12%D0%B2'],
        ],
      ],
    ];
  }
}

if (!function_exists('domexpert_product_shelf_block')) {
  /** Рендерит первую подходящую полку под теги страницы, иначе ''. */
  function domexpert_product_shelf_block(array $tags = []): string {
    // ⚠️ ВРЕМЕННО ОТКЛЮЧЕНО. Deeplink текущей ссылки AliExpress (программа WW) не
    // пробрасывают целевую страницу: параметр ulp игнорируется, все ведут на
    // главную (общий редирект 8ej4e1d). Убрать этот return, когда появятся рабочие
    // deeplink — из программы RU&CIS (заточена под aliexpress.ru) или на конкретный
    // товар (product.html), а не на страницу поиска.
    return '';

    $shelf = null;
    foreach (domexpert_product_shelves() as $s) {
      if (array_intersect($s['tags'], $tags)) { $shelf = $s; break; }
    }
    if (!$shelf || empty($shelf['items'])) { return ''; }
    $rel = 'sponsored nofollow noopener';
    $items = '';
    foreach ($shelf['items'] as $it) {
      if (empty($it['url'])) { continue; }
      $url  = htmlspecialchars($it['url'],  ENT_QUOTES, 'UTF-8');
      $name = htmlspecialchars($it['name'], ENT_QUOTES, 'UTF-8');
      $icon = htmlspecialchars($it['icon'] ?? '🛒', ENT_QUOTES, 'UTF-8');
      $items .= "<a class=\"shelf-item\" href=\"{$url}\" target=\"_blank\" rel=\"{$rel}\">"
              . "<span class=\"shelf-ic\" aria-hidden=\"true\">{$icon}</span>"
              . "<span class=\"shelf-name\">{$name}</span>"
              . "<span class=\"shelf-go\" aria-hidden=\"true\">→</span></a>";
    }
    if ($items === '') { return ''; }
    $title = htmlspecialchars($shelf['title'], ENT_QUOTES, 'UTF-8');
    return "<aside class=\"shelf\" aria-label=\"Товары по теме\">"
         . "<span class=\"shelf-label\">Реклама · рекомендуем купить</span>"
         . "<p class=\"shelf-title\">{$title}</p>"
         . "<div class=\"shelf-grid\">{$items}</div></aside>";
  }
}

/* ── Запас: Максидом ─────────────────────────────────────────────────────────
 * В проде используется CPC-ссылка (/c/, см. оффер 'maxidom' выше). Здесь лежат
 * запасные материалы на случай отдельного display-блока (они идут по CPA /g/):
 *
 * CPA (Per Sale): https://uuwgc.com/g/vw6dqabpgk5593ebc048b6a2cdd7f0/?erid=25H8d7vbP8SRTvH4HtSZJ1
 * CPC (Per Click): https://uuwgc.com/c/vw6dqabpgk5593ebc048b6a2cdd7f0/?erid=25H8d7vbP8SRTvH4HtSZJ1  ← в проде
 *
 * Баннеры (Admitad, тариф CPA — на нашем гео-ограниченном трафике почти пустые):
 *   300×250: https://uuwgc.com/g/kboop4ys9y5593ebc048b6a2cdd7f0/?i=4&erid=2bL9aMPo2e49hMef4piUd2z2s9  img: https://aflink.ru/b/kboop4ys9y5593ebc048b6a2cdd7f0/
 *   728×90:  https://uuwgc.com/g/59tnhpjact5593ebc048b6a2cdd7f0/?i=4&erid=2bL9aMPo2e49hMef4piUd2z2s3  img: https://aflink.ru/b/59tnhpjact5593ebc048b6a2cdd7f0/
 *   160×600: https://uuwgc.com/g/g55ahs9es95593ebc048b6a2cdd7f0/?i=4&erid=2bL9aMPo2e49hMef4piUd2z2s8  img: https://aflink.ru/b/g55ahs9es95593ebc048b6a2cdd7f0/
 * ─────────────────────────────────────────────────────────────────────────── */
