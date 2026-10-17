# Аудит фронтенда prodom-expert.ru

Снимок репозитория на 14 октября 2026. Только чтение: шаблоны и статьи не менялись.

Область: тела `articles/**/*.html`, шаблоны `*.php` в корне и `includes/*.php`, статика `assets/img`.

Как считалось:

- `alt` пустой, если атрибута нет или строка пустая (после обрезки пробелов).
- Числовые `width` и `height` — целые пиксели. `height="auto"` записан отдельно: атрибут есть, числа нет, резерв места в вёрстке он не даёт.
- `loading="lazy"` не требуется у первой картинки файла и у обложки внутри тела, если `src` указывает на `assets/img/articles/<slug>.jpg|jpeg|png|webp`.
- Обложки, которые собирает `du_cover_picture()` в шаблоне статьи (`fetchpriority="high"`), тоже не требуют lazy.
- Крупное изображение — файл в `assets/img` больше 300 КиБ (307 200 байт).
- Mixed content — реальная загрузка по `http://` в `src`, `href`, `srcset` или `url()`. Пространства имён XML и `xmlns` внутри data-URI к mixed content не относятся.

## Сводка

- Картинок `<img>` в статьях: **143** в 72 файлах
- Без alt или с пустым alt (статьи): **0**
- Без атрибута width или height (статьи): **0**
- Нечисловые width/height (статьи): **143**
- Без loading="lazy", кроме первой и обложки (статьи): **0**
- Шаблоны без width/height: **7** (6 превью карточек и фон героя на главной; у героя ещё и пустой alt)
- Пиксель Метрики с пустым alt: **1** (для невидимого счётчика это норма)
- h1 внутри тел: **0**
- Пропуски уровней: **0**
- Пустые заголовки: **0**
- Первый заголовок не h2: **0**
- Статей без заголовков: **0**
- Изображения больше 300 КиБ: **35**
- Реальные загрузки по http://: **0**
- Внешние URL проверены: **73**, из них HTTP 404/410: **0**
- Локальные ресурсы, которых нет на диске: **0**

## 1. Картинки

### 1.1. Статьи: нет alt или пустой alt (0)

В телах статей таких `<img>` нет: у каждого тега alt непустой.

### 1.2. Статьи: нет атрибута width или height (0)

Таких тегов нет: width и height указаны (значение разобрано в следующем пункте).

### 1.3. Статьи: width или height не число (143)

Одинаковый фрагмент у большинства иллюстраций: `width="960" height="auto"`. Атрибуты на месте, числовой высоты нет.

- нечисловые размеры: height="auto": **143**

Пример:

```
<img src="/assets/img/inline/avtomaticheskie-vyklyuchateli-kak-vybrat-table.png" alt="Типовые связки кабель — автомат" width="960" height="auto" loading="lazy" decoding="async" style="width:100%;height:auto;border-rad...
```

Все вхождения:

- `articles/elektrika/avtomaticheskie-vyklyuchateli-kak-vybrat.html`:3
- `articles/elektrika/avtomaticheskie-vyklyuchateli-kak-vybrat.html`:9
- `articles/elektrika/elektrika-v-vannoy-zony-ip.html`:18
- `articles/elektrika/elektrika-v-vannoy-zony-ip.html`:77
- `articles/elektrika/ibp-i-generator-dlya-doma.html`:25
- `articles/elektrika/ibp-i-generator-dlya-doma.html`:105
- `articles/elektrika/led-lenta-montazh-pitanie-profil.html`:3
- `articles/elektrika/led-lenta-montazh-pitanie-profil.html`:8
- `articles/elektrika/montazh-kondicionera-elektrika-trassa.html`:15
- `articles/elektrika/montazh-kondicionera-elektrika-trassa.html`:71
- `articles/elektrika/podklyuchenie-varochnoy-paneli-i-duhovki.html`:16
- `articles/elektrika/podklyuchenie-varochnoy-paneli-i-duhovki.html`:132
- `articles/elektrika/poisk-skrytoy-provodki-v-stene.html`:22
- `articles/elektrika/poisk-skrytoy-provodki-v-stene.html`:114
- `articles/elektrika/prohodnye-vyklyuchateli-shemy.html`:15
- `articles/elektrika/prohodnye-vyklyuchateli-shemy.html`:123
- `articles/elektrika/raschet-secheniya-kabelya-tablicy.html`:18
- `articles/elektrika/raschet-secheniya-kabelya-tablicy.html`:59
- `articles/elektrika/zamena-provodki-v-chastnom-dome.html`:22
- `articles/elektrika/zamena-provodki-v-chastnom-dome.html`:118
- `articles/elektrika/zashchita-ot-protechek-datchiki-krany.html`:15
- `articles/elektrika/zashchita-ot-protechek-datchiki-krany.html`:104
- `articles/elektrika/zazemlenie-v-kvartire-tn-c-s.html`:3
- `articles/elektrika/zazemlenie-v-kvartire-tn-c-s.html`:19
- `articles/interer/dekor-sten-razveska-kartin.html`:16
- `articles/interer/dekor-sten-razveska-kartin.html`:112
- `articles/interer/dizayn-balkona-kak-komnaty.html`:16
- `articles/interer/dizayn-balkona-kak-komnaty.html`:63
- `articles/interer/dizayn-prihozhey-planirovka-hranenie.html`:11
- `articles/interer/dizayn-prihozhey-planirovka-hranenie.html`:66
- `articles/interer/kak-vybrat-krovat-i-matras.html`:18
- `articles/interer/kak-vybrat-krovat-i-matras.html`:93
- `articles/interer/kovry-v-interere-razmer-material.html`:9
- `articles/interer/kovry-v-interere-razmer-material.html`:102
- `articles/interer/kuhnya-gostinaya-planirovka-zonirovanie.html`:15
- `articles/interer/kuhnya-gostinaya-planirovka-zonirovanie.html`:118
- `articles/interer/loft-stil-v-kvartire-gid.html`:3
- `articles/interer/loft-stil-v-kvartire-gid.html`:8
- `articles/interer/mebel-na-zakaz-ili-gotovaya-sravnenie.html`:3
- `articles/interer/mebel-na-zakaz-ili-gotovaya-sravnenie.html`:8
- `articles/interer/neoklassika-v-interere-kvartiry.html`:11
- `articles/interer/neoklassika-v-interere-kvartiry.html`:88
- `articles/interer/obedennaya-zona-stol-i-stulya.html`:26
- `articles/interer/obedennaya-zona-stol-i-stulya.html`:95
- `articles/interer/palitra-dlya-malenkoy-kvartiry.html`:3
- `articles/interer/palitra-dlya-malenkoy-kvartiry.html`:12
- `articles/mebel/kak-vybrat-divan.html`:17
- `articles/mebel/kak-vybrat-divan.html`:113
- `articles/okna/bezopasnost-okon-dlya-detey.html`:17
- `articles/okna/bezopasnost-okon-dlya-detey.html`:114
- `articles/okna/dveri-nevidimki-skrytogo-montazha.html`:25
- `articles/okna/dveri-nevidimki-skrytogo-montazha.html`:98
- `articles/okna/klass-vzlomostoykosti-vkhodnoy-dveri.html`:3
- `articles/okna/klass-vzlomostoykosti-vkhodnoy-dveri.html`:8
- `articles/okna/montazh-mezhkomnatnoy-dveri.html`:38
- `articles/okna/montazh-mezhkomnatnoy-dveri.html`:127
- `articles/okna/montazh-vkhodnoy-dveri-svoimi-rukami.html`:16
- `articles/okna/montazh-vkhodnoy-dveri-svoimi-rukami.html`:133
- `articles/okna/otkosy-okon-shtukaturka-gkl-sendvich.html`:9
- `articles/okna/otkosy-okon-shtukaturka-gkl-sendvich.html`:64
- `articles/okna/pritochnye-klapany-na-okna.html`:17
- `articles/okna/pritochnye-klapany-na-okna.html`:128
- `articles/okna/pvh-ili-alyuminievye-okna-sravnenie.html`:3
- `articles/okna/pvh-ili-alyuminievye-okna-sravnenie.html`:8
- `articles/okna/razdvizhnye-dveri-kupe-penal.html`:21
- `articles/okna/razdvizhnye-dveri-kupe-penal.html`:78
- `articles/okna/regulirovka-plastikovykh-okon.html`:9
- `articles/okna/regulirovka-plastikovykh-okon.html`:56
- `articles/okna/zamena-balkonnogo-bloka-uteplenie.html`:3
- `articles/okna/zamena-balkonnogo-bloka-uteplenie.html`:13
- `articles/okna/zamena-uplotnitelya-okon.html`:21
- `articles/okna/zamena-uplotnitelya-okon.html`:96
- `articles/remont/ciklevka-parketa-svoimi-rukami.html`:24
- `articles/remont/ciklevka-parketa-svoimi-rukami.html`:95
- `articles/remont/keramogranit-na-pol-ukladka.html`:24
- `articles/remont/keramogranit-na-pol-ukladka.html`:100
- `articles/remont/kvarcvinil-spc-lvt-ukladka.html`:16
- `articles/remont/kvarcvinil-spc-lvt-ukladka.html`:114
- `articles/remont/napolnye-pokrytiya-sravnenie-2026.html`:3
- `articles/remont/napolnye-pokrytiya-sravnenie-2026.html`:8
- `articles/remont/plesen-v-kvartire-prichiny-i-borba.html`:14
- `articles/remont/plesen-v-kvartire-prichiny-i-borba.html`:78
- `articles/remont/poklejka-oboev-tehnologiya.html`:19
- `articles/remont/poklejka-oboev-tehnologiya.html`:92
- `articles/remont/poly-po-lagam-v-kvartire.html`:19
- `articles/remont/poly-po-lagam-v-kvartire.html`:111
- `articles/remont/potolok-pod-pokrasku-vyravnivanie.html`:20
- `articles/remont/potolok-pod-pokrasku-vyravnivanie.html`:129
- `articles/remont/proem-v-nesushchey-stene-usilenie.html`:32
- `articles/remont/proem-v-nesushchey-stene-usilenie.html`:126
- `articles/remont/remont-kuhni-poryadok-rabot.html`:30
- `articles/remont/remont-kuhni-poryadok-rabot.html`:137
- `articles/remont/shpaklevka-sten-start-finish-sravnenie.html`:4
- `articles/remont/shpaklevka-sten-start-finish-sravnenie.html`:16
- `articles/remont/uteplenie-sten-kvartiry-iznutri.html`:5
- `articles/remont/uteplenie-sten-kvartiry-iznutri.html`:111
- `articles/santehnika/dush-ili-vanna-dlya-malogo-santeuzla.html`:3
- `articles/santehnika/dush-ili-vanna-dlya-malogo-santeuzla.html`:8
- `articles/santehnika/dushevoy-trap-i-uklon-pola.html`:17
- `articles/santehnika/dushevoy-trap-i-uklon-pola.html`:119
- `articles/santehnika/podklyuchenie-posudomoechnoy-mashiny.html`:17
- `articles/santehnika/podklyuchenie-posudomoechnoy-mashiny.html`:121
- `articles/santehnika/polotencesushitel-vodyanoy-ili-elektricheskiy.html`:3
- `articles/santehnika/polotencesushitel-vodyanoy-ili-elektricheskiy.html`:8
- `articles/santehnika/povysitelnyy-nasos-v-kvartire.html`:26
- `articles/santehnika/povysitelnyy-nasos-v-kvartire.html`:103
- `articles/santehnika/shumoizolyaciya-kanalizacionnogo-stoyaka.html`:22
- `articles/santehnika/shumoizolyaciya-kanalizacionnogo-stoyaka.html`:117
- `articles/santehnika/ustanovka-rakoviny-i-sifona.html`:27
- `articles/santehnika/ustanovka-rakoviny-i-sifona.html`:91
- `articles/santehnika/vodonagrevatel-boyler-vybor-montazh.html`:13
- `articles/santehnika/vodonagrevatel-boyler-vybor-montazh.html`:43
- `articles/santehnika/vybor-i-ustanovka-vanny.html`:48
- `articles/santehnika/vybor-i-ustanovka-vanny.html`:121
- `articles/santehnika/zaliv-sosedey-chto-delat-akt.html`:17
- `articles/santehnika/zaliv-sosedey-chto-delat-akt.html`:67
- `articles/santehnika/zamena-radiatorov-otopleniya.html`:26
- `articles/santehnika/zamena-radiatorov-otopleniya.html`:82
- `articles/santehnika/zasor-kanalizacii-prichiny-profilaktika.html`:3
- `articles/santehnika/zasor-kanalizacii-prichiny-profilaktika.html`:8
- `articles/sovety/chek-list-zakupok-do-nachala-remonta.html`:3
- `articles/sovety/chek-list-zakupok-do-nachala-remonta.html`:8
- `articles/sovety/chto-sdelat-samomu-a-chto-otdat-masteram.html`:13
- `articles/sovety/chto-sdelat-samomu-a-chto-otdat-masteram.html`:104
- `articles/sovety/dizayn-proekt-kvartiry-sostav-cena.html`:9
- `articles/sovety/dizayn-proekt-kvartiry-sostav-cena.html`:116
- `articles/sovety/kak-profinansirovat-remont-kredit-rassrochka.html`:7
- `articles/sovety/kak-profinansirovat-remont-kredit-rassrochka.html`:45
- `articles/sovety/kontrol-smety-remonta-po-etapam.html`:5
- `articles/sovety/kontrol-smety-remonta-po-etapam.html`:13
- `articles/sovety/remont-pod-sdachu-v-arendu.html`:23
- `articles/sovety/remont-pod-sdachu-v-arendu.html`:101
- `articles/sovety/remont-vtorichki-chto-menyat.html`:13
- `articles/sovety/remont-vtorichki-chto-menyat.html`:47
- `articles/sovety/sroki-remonta-kvartiry-po-etapam.html`:13
- `articles/sovety/sroki-remonta-kvartiry-po-etapam.html`:82
- `articles/sovety/strahovanie-kvartiry-na-vremya-remonta.html`:5
- `articles/sovety/tehnadzor-v-remonte.html`:22
- `articles/sovety/tehnadzor-v-remonte.html`:123
- `articles/sovety/zakon-o-tishine-remontnye-raboty.html`:17
- `articles/sovety/zakon-o-tishine-remontnye-raboty.html`:73
- `articles/sovety/zhit-vo-vremya-remonta-ili-syehat.html`:13
- `articles/sovety/zhit-vo-vremya-remonta-ili-syehat.html`:115

### 1.4. Статьи: нет loading="lazy" у картинок после первой и не у обложки (0)

Таких тегов нет. Первая картинка файла и обложка из `assets/img/articles/<slug>.*` из требования исключены.

### 1.5. Шаблоны

Учтены литеральные `<img>` и вызовы `du_cover_picture()`, которые собирают `<img>` на странице. Упоминания `<img>` в комментарии `includes/article-cover.php` и сама функция-генератор в список не входят: размеры и alt туда приходят аргументами.

Превью карточек без `width` и `height` (alt и `loading="lazy"` на месте):

- `search.php`:78 и `search.php`:120 — список результатов поиска.
  - `<img src="<?= htmlspecialchars($searchCover, ENT_QUOTES, 'UTF-8') ?>" alt="…" loading="lazy" decoding="async">`
- `articles.php`:151 — список архива статей. Тот же фрагмент, переменная `$listCover`.
- `category.php`:168 — `du_cover_picture($listCover, $article['title'] . ' — превью статьи', ['loading' => 'lazy', 'decoding' => 'async'])`
- `index.php`:301 — карточки на главной. `du_cover_picture($featCover, $article['title'], ['loading' => 'lazy', 'decoding' => 'async'])`
- `article.php`:465 — блок похожих статей. `du_cover_picture($relCover, $rel['title'] . ' — превью статьи', ['loading' => 'lazy', 'decoding' => 'async'])`

Пустой alt и нет размеров:

- `index.php`:118 — фон слайда героя: `du_cover_picture($sCover, '', …)`. У первого слайда стоит `fetchpriority="high"` и нет lazy, у остальных lazy передаётся. Заголовок слайда есть в `<h2>` и в `aria-label` ссылки, поэтому пустой alt не дублирует его для скринридера. Числовых width/height у фона нет.

Обложка самой статьи в порядке: `article.php`:340 передаёт alt из заголовка, width, height и `fetchpriority="high"`. Lazy там не нужен.

Служебный пиксель:

- `includes/footer.php`:165 — `<img src="https://mc.yandex.ru/watch/108673434" style="position:absolute; left:-9999px;" alt="" />`. Пустой alt у невидимого счётчика в `<noscript>` уместен. Размеров нет.

`includes/article-photos.php`, `includes/affiliate.php` и `podderzhat.php` отдают `<img>` с alt, числовыми width/height и `loading="lazy"`.

## 2. Заголовки в телах статей

H1 страницы выводит шаблон. В теле ожидается иерархия с первого заголовка уровня h2. Лид-абзац или схема перед первым h2 сами по себе в этот список не входят: отмечаются h1 внутри тела, пустые заголовки, скачок уровня и случай, когда первый заголовок не h2.

Чем открывается файл (для ориентира, не список дефектов):

- `<p>` — 253

### 2.1. Первый заголовок не h2 (0)

У всех статей, где заголовки есть, первый заголовок — h2.

### 2.2. h1, пустые заголовки, пропуск уровня (0)

Таких заголовков нет.

### 2.3. В теле нет заголовков (0)

Таких файлов нет.

## 3. Крупные изображения в assets/img

Файлов больше 300 КиБ: **35**.

Суммарно 14119,2 КиБ (14 458 107 байт).

- `assets/img/photos/podgotovka-sten-pod-pokrasku/02.webp` — 704,5 КиБ (721 388 байт)
- `assets/img/photos/podgotovka-sten-pod-pokrasku/02.jpg` — 695,1 КиБ (711 818 байт)
- `assets/img/photos/led-prozhektory-podsvetka-bezopasnost/02.jpg` — 582,1 КиБ (596 049 байт)
- `assets/img/photos/rozetki-i-vyklyuchateli/02.jpg` — 541,4 КиБ (554 411 байт)
- `assets/img/photos/rozetki-i-vyklyuchateli/02.webp` — 501,3 КиБ (513 346 байт)
- `assets/img/photos/led-prozhektory-podsvetka-bezopasnost/02.webp` — 499,1 КиБ (511 108 байт)
- `assets/img/photos/shtukaturka-sten/02.jpg` — 492,2 КиБ (504 062 байт)
- `assets/img/photos/videodomofon-provodka-i-pitanie/01.jpg` — 472,0 КиБ (483 283 байт)
- `assets/img/photos/neoklassika-v-interere-kvartiry/01.jpg` — 450,9 КиБ (461 678 байт)
- `assets/img/photos/kvartirnyy-schitok-sborka-i-markirovka/02.jpg` — 439,4 КиБ (449 953 байт)
- `assets/img/photos/dizayn-balkona-kak-komnaty/01.jpg` — 433,4 КиБ (443 840 байт)
- `assets/img/photos/vodonagrevatel-boyler-vybor-montazh/01.jpg` — 412,3 КиБ (422 220 байт)
- `assets/img/photos/shtukaturka-sten/02.webp` — 405,1 КиБ (414 778 байт)
- `assets/img/photos/reechnye-potolki-montazh/01.jpg` — 401,6 КиБ (411 285 байт)
- `assets/img/photos/moskitnye-setki-na-okna/01.jpg` — 392,9 КиБ (402 337 байт)
- `assets/img/photos/umnyy-dom-osnovy/01.jpg` — 385,0 КиБ (394 223 байт)
- `assets/img/photos/teplyy-pol/01.jpg` — 375,9 КиБ (384 962 байт)
- `assets/img/photos/videodomofon-provodka-i-pitanie/01.webp` — 375,2 КиБ (384 212 байт)
- `assets/img/photos/neoklassika-v-interere-kvartiry/01.webp` — 372,8 КиБ (381 734 байт)
- `assets/img/photos/videodomofon-provodka-i-pitanie/02.jpg` — 348,6 КиБ (357 016 байт)
- `assets/img/photos/styazhka-pola-suhaya-ili-mokraya/01.jpg` — 345,3 КиБ (353 617 байт)
- `assets/img/photos/kvartirnyy-schitok-sborka-i-markirovka/02.webp` — 340,8 КиБ (349 008 байт)
- `assets/img/photos/steklopaket-tripleks-shumozashchita/01.jpg` — 336,9 КиБ (345 016 байт)
- `assets/img/photos/mebel-na-zakaz-ili-gotovaya-sravnenie/02.jpg` — 334,4 КиБ (342 470 байт)
- `assets/img/photos/videodomofon-provodka-i-pitanie/02.webp` — 333,8 КиБ (341 760 байт)
- `assets/img/photos/vodonagrevatel-boyler-vybor-montazh/01.webp` — 331,7 КиБ (339 694 байт)
- `assets/img/photos/razvodka-santehniki-kvartira-gid/01.jpg` — 330,3 КиБ (338 272 байт)
- `assets/img/photos/rolstavni-i-stavni-na-okna/01.jpg` — 327,2 КиБ (335 020 байт)
- `assets/img/photos/moskitnye-setki-na-okna/01.webp` — 317,5 КиБ (325 144 байт)
- `assets/img/photos/umnye-smesiteli-ekonomiya-vody/02.jpg` — 317,2 КиБ (324 765 байт)
- `assets/img/photos/dizayn-balkona-kak-komnaty/01.webp` — 312,8 КиБ (320 334 байт)
- `assets/img/photos/reechnye-potolki-montazh/01.webp` — 308,3 КиБ (315 686 байт)
- `assets/img/photos/zakupki-stroymaterialov-onlayn/01.jpg` — 301,4 КиБ (308 662 байт)
- `assets/img/photos/keramogranit-na-pol-ukladka/01.jpg` — 300,5 КиБ (307 754 байт)
- `assets/img/photos/samovyiravnivayushayasya-styazhka-i-mayaki/01.jpg` — 300,0 КиБ (307 202 байт)

## 4. Mixed content и битые ссылки

### 4.1. Загрузки по http:// (0)

Реальных загрузок по `http://` в статьях, шаблонах, CSS и JS нет.

Упоминания `http://`, которые браузер не запрашивает как страницу (xmlns, schema, data-URI): **17**. Это `sitemap.xml`, `sitemap_index.xml`, `sitemap-feeds.xml`, пространство имён RSS и SVG-xmlns внутри data-URI в `assets/css/style.css` и `assets/img/favicon.svg`.

### 4.2. Локальные файлы, на которые ссылаются статьи и шаблоны, но которых нет (0)

Битых локальных `src`/`href` на статику с расширением не найдено.

### 4.3. Внешние URL (73 проверено, служебные хосты пропущены)

Пропущены как инфраструктура: schema.org (5), mc.yandex.ru (3), fonts.googleapis.com (2), pagead2.googlesyndication.com (2), www.w3.org (1), purl.org (1), fonts.gstatic.com (1), ogp.me (1), www.googletagmanager.com (1).

Ответ меньше 400: **67**. HTTP 404 или 410: **0**.

Заглушки, не являющиеся адресами страниц: пример `https://ad.admitad.com/g/ВАША_ССЫЛКА/` в комментарии `includes/affiliate.php`; закомментированные `https://vk.com/…` и `https://dzen.ru/…` в `includes/seo-config.php`.

Ответы, которые не равны 404 (проверка с обычным User-Agent, партнёрские редиректы часто закрываются от бота):

- HTTP 403 — `includes/affiliate.php`: `https://bednari.com/g/xcawb6ikii5593ebc048161cbbe6df/?erid=5jtCeReNwxHpfQTEQWKMenp`, `https://ru.gettracker.net/6ac72cd364948?erid=2SDnjdK8ZkK`, `https://ru.i08o.xyz/6ac72d3482fa4?erid=2SDnjdZynVV`, `https://ru.trackerpro.org/6ac72c96d7811?erid=2SDnjcJ8CBq`
- HTTP 503 — `includes/affiliate.php`: `https://dkfrh.com/g/4f5bbwmspn5593ebc0485e55659f09/?erid=2bL9aMPo2e49hMef4rqytKpfaC`
- HTTP 429 — `privacy.php`: `https://optout.aboutads.info/`

## 5. Повторяющиеся inline-стили в статьях

Ниже стили, которые повторяются и просятся в класс. В файлы ничего не переносилось.

### Блок иллюстрации figure + img + figcaption

- `<figure style="margin:1.5rem 0">` — **143** раз в 72 файлах
- `<img style="width:100%;height:auto;border-radius:8px;display:block">` — **143** раз в 72 файлах
- `<figcaption style="margin-top:0.5rem;color:#666;font-size:0.9rem">` — **143** раз в 72 файлах

Связка «figure с этим стилем и img с этим стилем в одном файле» встречается в **72** статьях (пар figure+img с этими точными строками: 143). Это один и тот же каркас картинки с подписью: ему достаточно трёх классов вместо трёх одинаковых `style`.

### Остальные частые style

- `<td>` **5943** раз в 200 файлах: `padding:10px;border:1px solid#ddd`
- `<th>` **1139** раз в 200 файлах: `padding:10px;border:1px solid#ddd;text-align:left;background:#f5f5f5`
- `<div>` **384** раз в 200 файлах: `overflow-x:auto;margin:1.25rem 0`
- `<table>` **165** раз в 92 файлах: `width:100%;border-collapse:collapse;min-width:560px`
- `<td>` **95** раз в 4 файлах: `padding:10px;border:1px solid#ddd;text-align:right`
- `<table>` **65** раз в 21 файлах: `width:100%;border-collapse:collapse;min-width:760px`
- `<td>` **60** раз в 1 файле: `padding:9px;border:1px solid#ddd`
- `<table>` **48** раз в 39 файлах: `width:100%;border-collapse:collapse;min-width:640px`
- `<table>` **43** раз в 40 файлах: `width:100%;border-collapse:collapse;min-width:600px`
- `<td>` **27** раз в 2 файлах: `padding:8px;border:1px solid#ddd`
- `<th>` **22** раз в 7 файлах: `padding:10px;border:1px solid#ddd;background:#f5f5f5`
- `<table>` **19** раз в 19 файлах: `width:100%;border-collapse:collapse;min-width:700px`
- `<table>` **17** раз в 16 файлах: `width:100%;border-collapse:collapse;min-width:660px`
- `<td>` **16** раз в 3 файлах: `padding:10px;border:1px solid#ddd;background:#fafafa`
- `<td>` **15** раз в 1 файле: `padding:9px;border:1px solid#ddd;text-align:center`
- `<th>` **14** раз в 4 файлах: `padding:10px;border:1px solid#ddd;text-align:right;background:#f5f5f5`
- `<table>` **11** раз в 11 файлах: `width:100%;border-collapse:collapse;min-width:620px`
- `<td>` **10** раз в 1 файле: `padding:9px;border:1px solid#ddd;background:#fafafa`
- `<td>` **7** раз в 3 файлах: `padding:10px;border:1px solid#ddd;text-align:right;background:#fafafa`
- `<td>` **7** раз в 1 файле: `padding:9px;border:1px solid#ddd;background:#f5f5f5`
- `<table>` **6** раз в 5 файлах: `width:100%;border-collapse:collapse;min-width:720px`
- `<th>` **6** раз в 2 файлах: `padding:8px;border:1px solid#ddd;text-align:left`
- `<table>` **5** раз в 5 файлах: `width:100%;border-collapse:collapse;min-width:520px`
- `<table>` **4** раз в 4 файлах: `width:100%;border-collapse:collapse;min-width:680px`
- `<th>` **3** раз в 1 файле: `padding:9px;border:1px solid#ddd;text-align:right;background:#f5f5f5`
