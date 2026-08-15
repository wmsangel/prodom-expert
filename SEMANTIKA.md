# ДомЭксперт — группы запросов и посадочные страницы

Состояние на 11 августа 2026. Документ закрывает три пункта чеклиста Яндекса: «собраны целевые запросы», «запросы объединены в группы», «под запросы найдены посадочные страницы».

Правило, на котором всё построено: **одна группа запросов — одна посадочная страница**. Остальные материалы группы работают спутниками и ссылаются на посадочную, а не конкурируют с ней.

---

## 0. Что здесь измерено, а что нет

Разделение принципиальное, потому что от него зависит, чему верить.

| Раздел | Источник | Статус |
|---|---|---|
| 1. Кластеры с подтверждённым спросом | Search Console и Яндекс.Вебмастер | Реальные показы, зафиксированы по ходу работы |
| 2. Структурная карта | Реестр статей `includes/all-articles-meta.php` | Выведена из содержания сайта, спроса под ней не замерялось |
| 3. Калькуляторы | Реестр `includes/all-calculators-meta.php` | То же |
| 4. Пересечения | Анализ заголовков и внутренних ссылок | Числа реальные, посчитаны по файлам |

Разделы 2 и 3 — это карта того, что есть, а не отчёт о том, что ищут. Она нужна, чтобы новые материалы вставали в структуру, а не рядом с ней. Как только появятся выгрузки за полный период, разделы сводятся: к каждому структурному кластеру подставляются его реальные запросы и показы.

---

## 1. Кластеры с подтверждённым спросом

Данные накоплены по ходу работы над сайтом. Показы — за период наблюдения, порядок величины, а не точная статистика.

| Кластер | Запросов | Показов | Посадочная | Статус |
|---|---|---|---|---|
| Смета на ремонт | 13 | ~650 | `smeta-remonta-kvartiry` | Закрыт: страница была заглушкой 3,6 КБ, переписана в полный гид |
| Замена проводки | 5 | ~171 | `zamena-provodki-v-kvartire` (квартира), `zamena-provodki-v-chastnom-dome` (дом) | Закрыт, разделение по типу жилья осмысленное |
| Разводка и коллектор | 5 | ~132 | `razvodka-santehniki-kvartira-gid` (общая), `kollektornaya-razvodka-vody` (схема) | Закрыт |
| Замеры окон | 2 | 117 | `zamery-okon-dlya-zakaza` | Закрыт |
| Утепление изнутри | 4 | ~97 | `uteplenie-sten-kvartiry-iznutri` | Закрыт, поддержан калькулятором `uteplitel-tolshchina` |
| Черновая отделка | 3 | ~82 | `chernovaya-otdelka-kvartiry-etapy` | Закрыт |
| Гигиенический душ | 2 | 50 | `gigienicheskiy-dush-podklyucheniye` | Закрыт |
| УЗМ и УЗО | 4 | нет данных | `uzm-i-uzo-v-chem-raznica` | Закрыт, статья написана под этот кластер |

Общий вывод по разделу: **самый крупный из измеренных кластеров — сметный**, и он же был хуже всего закрыт до августа. Это и есть основной практический смысл документа — расхождение между тем, что ищут, и тем, что на сайте написано, видно только в такой таблице.

---

## 2. Структурная карта: тема → посадочная

Для каждой категории перечислены её кластеры. Первая страница в строке — посадочная, остальные спутники.

### Электрика (37 статей)

| Кластер | Посадочная | Спутники |
|---|---|---|
| Замена и прокладка проводки | `zamena-provodki-v-kvartire` | `zamena-provodki-v-chastnom-dome`, `shtroblenie-sten-pod-provodku`, `raschet-secheniya-kabelya-tablicy`, `provodka-v-gipsokartone-normy`, `poisk-skrytoy-provodki-v-stene`, `kabel-kanaly-i-koroby` |
| Щиток и защита | `kvartirnyy-schitok-sborka-i-markirovka` | `montazh-elektroshchita-svoimi-rukami`, `uzo-i-avr-v-schitke`, `uzm-i-uzo-v-chem-raznica`, `avtomaticheskie-vyklyuchateli-kak-vybrat`, `stabilizator-napryazheniya-kvartira`, `ibp-i-generator-dlya-doma` |
| Диагностика неисправностей | `vybivaet-avtomat-poisk-prichiny` | `uzo-i-avr-v-schitke`, `zamena-provodki-v-kvartire` |
| Освещение и управление им | `osveshcheniye-v-kvartire` | `dimmery-sveta-led-sovmestimost`, `prohodnye-vyklyuchateli-shemy`, `led-lenta-montazh-pitanie-profil`, `led-prozhektory-podsvetka-bezopasnost`, `datchiki-dvizheniya-i-prisutstviya` |
| Розетки и линии под технику | `rozetki-i-vyklyuchateli` | `skolko-rozetok-nuzhno-v-kvartire`, `elektrika-na-kuhne-raschet-liniy`, `podklyuchenie-varochnoy-paneli-i-duhovki`, `montazh-kondicionera-elektrika-trassa`, `zaryadka-elektromobilya-kvartira` |
| Влажные и наружные зоны | `elektrika-v-vannoy-zony-ip` | `ulichnaya-rozetka-ip-zima-balkon`, `zashchita-ot-protechek-datchiki-krany` |
| Нормы и безопасность | `bezopasnaya-elektrika-v-kvartire-pravila` | `proekt-elektriki-kvartiry-polnyy`, `zazemlenie-v-kvartire-tn-c-s`, `zamena-elektroschetchika-kvartira` |
| Слаботочка и автоматизация | `umnyy-dom-osnovy` | `prokladka-interneta-vitaya-para-remont`, `videodomofon-provodka-i-pitanie` |
| Электрический тёплый пол | `teplyy-pol` | `vodyanoy-teplyy-pol-v-kvartire` (сантехника) |

### Сантехника (35 статей)

| Кластер | Посадочная | Спутники |
|---|---|---|
| Разводка воды | `razvodka-santehniki-kvartira-gid` | `kollektornaya-razvodka-vody`, `zamena-stoyakov-vodosnabzheniya`, `reduktor-davleniya-vody-v-kvartire`, `povysitelnyy-nasos-v-kvartire` |
| Ремонт санузла целиком | `remont-vannoy-komnaty-s-nulya-gid` | `gidroizolyatsiya-vannoy`, `ukladka-plitki-v-vannoy`, `dushevoy-trap-i-uklon-pola`, `santehnicheskiy-lyuk-pod-plitku`, `dush-ili-vanna-dlya-malogo-santeuzla` |
| Смесители и душ | `kak-vybrat-smesitel` | `termostat-dlya-dusha-i-vanny`, `dushevaya-lejka-shlang-vybor`, `gigienicheskiy-dush-podklyucheniye`, `umnye-smesiteli-ekonomiya-vody`, `vynosnaya-kolonna-smestitelya-vanna` |
| Установка приборов | `installyatsiya-unitaza` | `vybor-i-ustanovka-vanny`, `ustanovka-rakoviny-i-sifona`, `podklyuchenie-stiralnoy-mashiny`, `podklyuchenie-posudomoechnoy-mashiny` |
| Горячая вода и отопление | `vodonagrevatel-boyler-vybor-montazh` | `zamena-radiatorov-otopleniya`, `polotencesushitel-vodyanoy-ili-elektricheskiy`, `vodyanoy-teplyy-pol-v-kvartire` |
| Канализация и запахи | `zasor-kanalizacii-prichiny-profilaktika` | `zapah-kanalizacii-sifon-trap`, `shumoizolyaciya-kanalizacionnogo-stoyaka` |
| Качество воды | `filtry-dlya-vody-v-kvartiru` | `filtr-pod-moyku-vybor` |
| Вентиляция санузла | `ventilyaciya-sanuzla-tyaga-i-ventilyator` | `rekuperator-pritochnaya-ventilyaciya-kvartiry` (ремонт) |
| Аварии и учёт | `zaliv-sosedey-chto-delat-akt` | `zamen-schetchika-vody` |

### Окна и двери (34 статьи)

| Кластер | Посадочная | Спутники |
|---|---|---|
| Выбор и замена окон | `zamena-okon-polnoe-rukovodstvo` | `kak-vybrat-plastikovye-okna`, `zamery-okon-dlya-zakaza`, `pvh-ili-alyuminievye-okna-sravnenie`, `energoeffektivnye-okna-teplo`, `panoramnye-okna-v-pol` |
| Монтаж и узлы окна | `germetik-montazhnyy-shov-okon` | `otkosy-okon-shtukaturka-gkl-sendvich`, `otlivy-i-kapelnaya-liniya-okna`, `podokonnnik-montazh-i-kondensat` |
| Обслуживание и ремонт | `regulirovka-plastikovykh-okon` | `ukhod-za-furniturou-okon`, `zamena-uplotnitelya-okon`, `zamena-steklopaketa-bez-zameny-okna` |
| Стекло, шум, защита | `steklopaket-tripleks-shumozashchita` | `zvukozashchita-okon-v-kvartire`, `antivandalnaya-plenka-na-steklo`, `bezopasnost-okon-dlya-detey`, `rolstavni-i-stavni-na-okna`, `moskitnye-setki-na-okna` |
| Балкон и лоджия | `holodnoe-i-teploe-osteklenie-balkona` | `uteplenie-lodzhii`, `zamena-balkonnogo-bloka-uteplenie` |
| Входная дверь | `vybor-vkhodnoy-dveri` | `klass-vzlomostoykosti-vkhodnoy-dveri`, `montazh-vkhodnoy-dveri-svoimi-rukami` |
| Межкомнатные двери | `mezhkomnatnye-dveri` | `montazh-mezhkomnatnoy-dveri`, `dvernaya-furnitura-ruchki-zamki-petli`, `razdvizhnye-dveri-kupe-penal`, `dveri-nevidimki-skrytogo-montazha`, `dver-v-vannuyu-i-sanuzel` |
| Приток и затенение | `pritochnye-klapany-na-okna` | `plisse-i-roletnye-shtory` |

### Ремонт (36 статей)

| Кластер | Посадочная | Спутники |
|---|---|---|
| Полный цикл работ | `kapitalnyy-remont-kvartiry-polnyy-gid` | `posledovatelnost-remonta-chek-list` (советы), `chernovaya-otdelka-kvartiry-etapy` |
| Стены: подготовка и отделка | `shtukaturka-sten` | `podgotovka-sten-pod-pokrasku`, `shpaklevka-sten-pod-oboi-i-pokrasku`, `gruntovka-sten-osnovy`, `pokraska-sten-kvartiry-sovety`, `poklejka-oboev-tehnologiya`, `dekorativnaya-shtukaturka-faktury`, `mikrocement-pol-i-steny` |
| Полы: выбор покрытия | `napolnye-pokrytiya-sravnenie-2026` | `ukladka-laminata`, `kvarcvinil-spc-lvt-ukladka`, `keramogranit-na-pol-ukladka`, `parketnaya-doska-montazh-i-uhod`, `ciklevka-parketa-svoimi-rukami`, `styk-napolnyh-pokrytiy-porogi` |
| Полы: основание | `styazhka-pola-suhaya-ili-mokraya` | `samovyiravnivayushayasya-styazhka-i-mayaki`, `poly-po-lagam-v-kvartire` |
| Потолки | `natyazhnye-potolki` | `potolok-pod-pokrasku-vyravnivanie`, `reechnye-potolki-montazh`, `moldingi-i-plintusy-stykovka` |
| Перегородки и конструктив | `gipsokarton-peregorodki-i-potolki` | `demontazh-sten-i-peregorodok`, `proem-v-nesushchey-stene-usilenie`, `suhaya-otdelka-sten-paneli-reyki`, `krepezh-v-stenu-dyubeli-i-ankery` |
| Тепло, звук, влага | `akusticheskaya-izolyaciya-kvartiry` | `zvukoizolyaciya-pola-plovuchiy-pol`, `uteplenie-sten-kvartiry-iznutri`, `plesen-v-kvartire-prichiny-i-borba`, `rekuperator-pritochnaya-ventilyaciya-kvartiry` |
| Ремонт кухни | `remont-kuhni-poryadok-rabot` | `interer-kuhni` (интерьер), `elektrika-na-kuhne-raschet-liniy` (электрика) |

### Интерьер (34 статьи)

| Кластер | Посадочная | Спутники |
|---|---|---|
| Планировка и эргономика | `ergonomika-kvartiry-razmery` | `zonirovanie-studii-odnokomnatnoy`, `dizayn-kvartiry-studii-mega-gid`, `kuhnya-gostinaya-planirovka-zonirovanie` |
| Цвет и подбор материалов | `palitra-dlya-malenkoy-kvartiry` | `cvetovye-akcenty-v-interere`, `mudbord-i-podbor-materialov` |
| Стили | `skandinavskiy-stil-v-interere` | `loft-stil-v-kvartire-gid`, `neoklassika-v-interere-kvartiry`, `tepliy-minimalizm-interer` |
| Комнаты | `dizayn-gostinoy` | `malenkaya-spalnya-dizayn`, `dizayn-detskoy-komnaty`, `interer-kuhni`, `dizayn-vannoy`, `dizayn-prihozhey-planirovka-hranenie`, `domashniy-ofis-v-kvartire`, `dizayn-balkona-kak-komnaty` |
| Мебель | `mebel-na-zakaz-ili-gotovaya-sravnenie` | `kak-vybrat-divan`, `kak-vybrat-krovat-i-matras`, `obedennaya-zona-stol-i-stulya`, `modulnaya-sistema-hranenia`, `garderobnaya-planirovanie-i-svet` |
| Свет в интерьере | `svetovye-stsenarii-v-kvartire` | `led-podsvetka-nishi-i-karnizov`, `osveshcheniye-v-kvartire` (электрика) |
| Текстиль и декор | `tekstil-shtory-v-gostinoy` | `kovry-v-interere-razmer-material`, `dekor-sten-razveska-kartin`, `zerkalo-v-prihozhey-razmer-i-svet`, `fitodizayn-rasteniya-v-interere` |
| Без ремонта | `interer-semnoy-kvartiry` | `remont-interera-bez-dizaynera-gid` |

### Советы (36 статей)

| Кластер | Посадочная | Спутники |
|---|---|---|
| Деньги и смета | `smeta-remonta-kvartiry` | `byudzhet-kapitalnogo-remonta-raschet`, `kontrol-smety-remonta-po-etapam`, `kak-sekonomit-na-remonte`, `kak-profinansirovat-remont-kredit-rassrochka`, `smeta-remonta-chastnogo-doma`, `otkuda-berutsya-ceny-na-remont`, `chto-vhodit-v-remont-pod-klyuch` |
| Замеры и план квартиры | `obmer-kvartiry-svoimi-rukami` | `plan-kvartiry-gde-vzyat`, `vysota-potolka-chistovaya-otmetka` (ремонт) |
| Подрядчики и документы | `kak-vybrat-brigadu-dlya-remonta` | `dogovor-podryada-remont`, `tehnadzor-v-remonte`, `garantiya-na-remont-i-defekty`, `brigada-brosila-obekt-chto-delat`, `skrytye-raboty-akty-i-fotofiksaciya`, `kak-prinyat-remont-cheklist` |
| Планирование и сроки | `posledovatelnost-remonta-chek-list` | `sroki-remonta-kvartiry-po-etapam`, `chek-list-zakupok-do-nachala-remonta`, `remont-pri-deficit-materialov-grafik` |
| Старт: новостройка и вторичка | `remont-v-novostroyke-s-chego-nachat` | `remont-vtorichki-chto-menyat`, `priemka-kvartiry-ot-zastroishchika` |
| Правовое | `soglasovanie-pereplanirovki-kvartiry` | `zakon-o-tishine-remontnye-raboty`, `strahovanie-kvartiry-na-vremya-remonta` |
| Быт во время ремонта | `zhit-vo-vremya-remonta-ili-syehat` | `remont-s-detmi-bezopasnost`, `stroitelnye-othody-vyvoz`, `remont-zimoy-klimat-materialy` |
| Закупки и материалы | `raschet-materialov-dlya-remonta` | `zakupki-stroymaterialov-onlayn`, `instrumenty-dlya-remonta`, `kak-vibrat-oboi` |
| Ошибки и сценарии | `top-oshibok-remonta-kvartiry` | `chto-sdelat-samomu-a-chto-otdat-masteram`, `remont-pod-sdachu-v-arendu`, `dizayn-proekt-kvartiry-sostav-cena` |

---

## 3. Калькуляторы как посадочные

Калькуляторы забирают на себя расчётные формулировки запроса — те, где пользователю нужна цифра, а не текст. Связь со статьями двусторонняя и строится из поля `related` в реестре, поэтому расходиться не может.

| Группа | Калькуляторы | Тип запроса |
|---|---|---|
| Смета и деньги | `planirovshchik-remonta` (флагман), `smeta-remonta`, `stoimost-remonta-za-m2`, `byudzhet-po-etapam` | «сколько стоит», «расчёт стоимости», «план квартиры онлайн» |
| Материалы | `oboi`, `kraska`, `plitka`, `styazhka`, `laminat`, `gipsokarton`, `uteplitel-tolshchina` | «сколько нужно», «расход на м²» |
| Электрика | `sechenie-kabelya`, `moshchnost-i-gruppy`, `osveshchenie`, `elektricheskiy-teplyy-pol` | «какое сечение», «сколько люмен» |
| Сантехника и климат | `sekcii-radiatorov`, `obem-boylera`, `vozduhoobmen`, `moshchnost-kondicionera`, `vodyanoy-teplyy-pol`, `tochka-rosy` | «сколько секций», «какой объём», «будет ли конденсат» |

Практический вывод: расчётные запросы должны вести на калькулятор, а не на статью. Если статья по теме калькулятора начинает собирать расчётные формулировки, в неё добавляется блок «Посчитать» — он строится автоматически из `related`.

---

## 4. Пересечения: где две страницы конкурируют за один запрос

Посчитано по заголовкам и внутренним ссылкам. Числа реальные.

### Требуют решения

| Пара | Ссылок / размер | Проблема | Предлагаемое решение |
|---|---|---|---|
| `kak-vybrat-brigadu-dlya-remonta`<br>`vybor-remontnoj-brigady` | 13 / 25 КБ<br>4 / 12 КБ | Заголовки практически совпадают, тема одна | Оставить первую как посадочную, вторую переписать под другой угол или склеить с 301 |
| `vodonagrevatel-boyler-vybor-montazh`<br>`bojler-nakopitelnyj-ili-protochnyj` | 4 / 25 КБ<br>0 / 11 КБ | Один и тот же вопрос «накопительный или проточный» | Вторая — кандидат на склейку, у неё нет ни одной входящей ссылки |
| `kvartirnyy-schitok-sborka-i-markirovka`<br>`montazh-elektroshchita-svoimi-rukami` | 16 / 12 КБ<br>5 / 29 КБ | Обе про сборку щитка, номиналы и маркировку | Расхождение: ссылки ведут на короткую, содержание глубже у длинной. Либо развести по углу (выбор состава против монтажа), либо перенести ссылочную массу |
| `shpaklevka-sten-pod-oboi-i-pokrasku`<br>`shpaklevka-sten-start-finish-sravnenie` | 6 / 27 КБ<br>4 / 7 КБ | Обе про стартовую и финишную шпаклёвку | Первая — посадочная, вторую сузить до сравнения составов и расхода |

### Разведены корректно, вмешательства не требуют

| Пара | Чем разведены |
|---|---|
| `teplyy-pol` / `vodyanoy-teplyy-pol-v-kvartire` | Электрический против водяного — разные запросы и разные ограничения |
| `zamena-provodki-v-kvartire` / `zamena-provodki-v-chastnom-dome` | Тип жилья, подтверждено данными кластера |
| `smeta-remonta-kvartiry` / `byudzhet-kapitalnogo-remonta-raschet` | Пример с ценами против метода расчёта и структуры долей |
| `gidroizolyatsiya-vannoy` / `remont-vannoy-komnaty-s-nulya-gid` | Узел против полного цикла |
| `filtr-pod-moyku-vybor` / `filtry-dlya-vody-v-kvartiru` | Питьевая линия против водоподготовки всей квартиры |
| `kak-vybrat-plastikovye-okna` / `zamena-okon-polnoe-rukovodstvo` | Выбор изделия против процесса замены |
| `dizayn-kvartiry-studii-mega-gid` / `zonirovanie-studii-odnokomnatnoy` | Формально разведены, но гид всего 5 КБ — он слабее своего спутника и является кандидатом на доработку |

### Отдельно: слаги с опечатками

Семь адресов содержат опечатки. Все они уже проиндексированы, поэтому переименование требует 301 и даёт лишний хоп — решение за владельцем.

| Слаг | Должно быть |
|---|---|
| `zamen-schetchika-vody` | `zamena-schetchika-vody` |
| `podokonnnik-montazh-i-kondensat` | `podokonnik-montazh-i-kondensat` |
| `vynosnaya-kolonna-smestitelya-vanna` | `vynosnaya-kolonna-smesitelya-vanna` |
| `zvukoizolyaciya-pola-plovuchiy-pol` | `zvukoizolyaciya-pola-plavayushchiy-pol` |
| `samovyiravnivayushayasya-styazhka-i-mayaki` | `samovyravnivayushchayasya-styazhka-i-mayaki` |
| `kak-vibrat-oboi` | `kak-vybrat-oboi` |
| `dush-ili-vanna-dlya-malogo-santeuzla` | `dush-ili-vanna-dlya-malogo-sanuzla` |

Рекомендация: не трогать. Опечатка в слаге не влияет на ранжирование, а редирект — влияет. Исправлять имеет смысл только при полной переработке конкретной статьи, когда 301 всё равно будет ставиться.

---

## 5. Правило при добавлении новой статьи

1. Определить, в какой кластер раздела 2 она попадает.
2. Если кластер есть — статья становится спутником: даёт ссылку на посадочную и получает ссылку от неё.
3. Если кластера нет — это новый кластер, и в документ добавляется строка с посадочной.
4. Если тема совпадает с существующей посадочной — статья не пишется. Вместо неё дорабатывается существующая.

Пункт 4 — единственная защита от роста таблицы пересечений. Все четыре пары из раздела 4 появились именно потому, что этой проверки не было.

---

## 6. Что нужно от владельца, чтобы свести разделы 1 и 2

- **Выгрузка Search Console** за 12 месяцев: запросы, показы, клики, средняя позиция, страница.
- **Выгрузка Яндекс.Вебмастера**: «Запросы» → «Все запросы», за тот же период.
- Обе выгрузки в CSV, без фильтров.

Что будет сделано с ними: запросы разложатся по кластерам раздела 2, каждый кластер получит реальные показы, и станет видно две вещи — какие кластеры собирают спрос без нормальной посадочной и какие страницы получают показы по запросам чужого кластера. Это тот же анализ, который на сметном кластере уже дал результат.
