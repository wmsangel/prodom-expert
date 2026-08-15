<?php
declare(strict_types=1);

/**
 * Реестр калькуляторов (раздел /calculators/, страницы /calc/<slug>/).
 *
 * При добавлении калькулятора правится только этот файл плюс кладётся разметка
 * в calculators/<slug>.html — листинг, роутер, sitemap и перелинковка со
 * статьями строятся из реестра автоматически.
 *
 * Поле related — слаги статей, к которым калькулятор относится. Оно работает
 * в обе стороны: на странице калькулятора это блок «Разобраться подробнее»,
 * а на странице статьи — блок «Посчитать» (см. includes/article-calculators.php).
 */

/** Группы раздела в порядке вывода. */
function domexpert_calculator_groups(): array
{
  return [
    'smeta'      => ['label' => 'Смета и деньги',    'icon' => '💰',
                     'desc'  => 'Сколько стоит ремонт целиком, за квадратный метр и по этапам работ.'],
    'materialy'  => ['label' => 'Материалы',          'icon' => '📦',
                     'desc'  => 'Сколько купить обоев, краски, плитки и сухих смесей с запасом на подрезку.'],
    'elektrika'  => ['label' => 'Электрика',          'icon' => '💡',
                     'desc'  => 'Сечение кабеля, номиналы автоматов, мощность квартиры и тёплый пол.'],
    'santehnika' => ['label' => 'Сантехника и климат', 'icon' => '🚿',
                     'desc'  => 'Радиаторы, водонагреватель и воздухообмен по площади помещения.'],
  ];
}

/** Все калькуляторы: slug => мета. */
function domexpert_all_calculators_meta(): array
{
  return [
    'planirovshchik-remonta' => [
      'group'    => 'smeta',
      'title'    => 'Планировщик ремонта: план квартиры, смета и материалы',
      'h1'       => 'Планировщик ремонта',
      'desc'     => 'Нарисуйте план квартиры на холсте — планировщик посчитает площади пола, стен и потолков, подберёт материалы с запасом и соберёт смету по этапам со сроками.',
      'lead'     => 'Рисуете комнаты, двери и окна — получаете объёмы, материалы и смету. Считает по геометрии плана, а не по общей квадратуре.',
      'level'    => 'сложный',
      // Своя разметка и своё состояние: общие style.css и calc.js этим тянуть
      // не стоит. Подключаются в calc.php с меткой версии.
      'assetsCss' => ['/assets/css/planner.css'],
      'assetsJs'  => ['/assets/js/planner.js'],
      'related'  => ['obmer-kvartiry-svoimi-rukami', 'plan-kvartiry-gde-vzyat',
                     'vysota-potolka-chistovaya-otmetka', 'skolko-rozetok-nuzhno-v-kvartire',
                     'chto-vhodit-v-remont-pod-klyuch', 'otkuda-berutsya-ceny-na-remont',
                     'smeta-remonta-kvartiry', 'byudzhet-kapitalnogo-remonta-raschet',
                     'raschet-materialov-dlya-remonta', 'posledovatelnost-remonta-chek-list',
                     'chernovaya-otdelka-kvartiry-etapy', 'kapitalnyy-remont-kvartiry-polnyy-gid',
                     'sroki-remonta-kvartiry-po-etapam', 'kontrol-smety-remonta-po-etapam'],
    ],
    'smeta-remonta' => [
      'group'    => 'smeta',
      'title'    => 'Смета на ремонт квартиры — калькулятор по комнатам и этапам',
      'h1'       => 'Смета на ремонт квартиры',
      'desc'     => 'Расчёт сметы по комнатам и этапам работ: демонтаж, черновая отделка, инженерия, чистовая. Отдельно работы и материалы, три уровня отделки, цены 2026 года.',
      'lead'     => 'Считает работы и материалы по этапам для каждой комнаты. Уровень отделки задаёт цену за м², набор этапов — их состав.',
      'level'    => 'сложный',
      'related'  => ['smeta-remonta-kvartiry', 'byudzhet-kapitalnogo-remonta-raschet',
                     'kontrol-smety-remonta-po-etapam', 'raschet-materialov-dlya-remonta',
                     'chernovaya-otdelka-kvartiry-etapy',
                     'sroki-remonta-kvartiry-po-etapam',
                     'remont-pod-sdachu-v-arendu'],
    ],
    'stoimost-remonta-za-m2' => [
      'group'    => 'smeta',
      'title'    => 'Стоимость ремонта за м² — быстрая оценка бюджета',
      'h1'       => 'Быстрая оценка стоимости ремонта',
      'desc'     => 'Прикидка бюджета по площади и типу ремонта: косметический, капитальный, с перепланировкой. Отдельно работы, материалы и запас на непредвиденное.',
      'lead'     => 'Оценка за минуту: площадь, тип жилья и уровень отделки. Для детального расчёта — калькулятор сметы по этапам.',
      'level'    => 'простой',
      'related'  => ['smeta-remonta-kvartiry', 'kak-sekonomit-na-remonte',
                     'remont-v-novostroyke-s-chego-nachat', 'remont-vtorichki-chto-menyat',
                     'remont-pod-sdachu-v-arendu',
                     'sroki-remonta-kvartiry-po-etapam'],
    ],
    'byudzhet-po-etapam' => [
      'group'    => 'smeta',
      'title'    => 'Распределение бюджета ремонта по этапам — калькулятор',
      'h1'       => 'Бюджет ремонта по этапам',
      'desc'     => 'Как разложить общий бюджет на демонтаж, инженерию, черновую и чистовую отделку, двери, мебель и технику. Доли этапов и остаток на непредвиденное.',
      'lead'     => 'Показывает, сколько денег должно приходиться на каждый этап и какая часть бюджета остаётся на мебель и технику.',
      'level'    => 'средний',
      'related'  => ['byudzhet-kapitalnogo-remonta-raschet', 'posledovatelnost-remonta-chek-list',
                     'kak-profinansirovat-remont-kredit-rassrochka', 'kontrol-smety-remonta-po-etapam',
                     'sroki-remonta-kvartiry-po-etapam',
                     'dveri-nevidimki-skrytogo-montazha'],
    ],

    'oboi' => [
      'group'    => 'materialy',
      'title'    => 'Калькулятор обоев: сколько рулонов нужно на комнату',
      'h1'       => 'Расчёт обоев',
      'desc'     => 'Количество рулонов по периметру, высоте потолка и раппорту рисунка. Учитывает окна, двери и подгонку узора.',
      'lead'     => 'Считает по полосам, а не по площади: именно так обои и режут. Раппорт рисунка учитывается отдельно.',
      'level'    => 'простой',
      'related'  => ['poklejka-oboev-tehnologiya', 'kak-vibrat-oboi',
                     'raschet-materialov-dlya-remonta', 'podgotovka-sten-pod-pokrasku'],
    ],
    'kraska' => [
      'group'    => 'materialy',
      'title'    => 'Калькулятор краски: сколько литров нужно на стены и потолок',
      'h1'       => 'Расчёт краски',
      'desc'     => 'Литры краски по площади, числу слоёв и укрывистости. Отдельно грунт, стены и потолок, с поправкой на впитывающее основание.',
      'lead'     => 'Расход зависит не от площади, а от числа слоёв и основания. Калькулятор учитывает и то, и другое.',
      'level'    => 'простой',
      'related'  => ['pokraska-sten-kvartiry-sovety', 'podgotovka-sten-pod-pokrasku',
                     'potolok-pod-pokrasku-vyravnivanie', 'gruntovka-sten-osnovy',
                     'ciklevka-parketa-svoimi-rukami'],
    ],
    'plitka' => [
      'group'    => 'materialy',
      'title'    => 'Калькулятор плитки: количество, подрезка, клей и затирка',
      'h1'       => 'Расчёт плитки и керамогранита',
      'desc'     => 'Сколько плитки купить с запасом на подрезку, сколько уйдёт клея по размеру гребёнки и сколько затирки по ширине шва.',
      'lead'     => 'Запас зависит от раскладки: прямая, со смещением или по диагонали. Клей и затирка считаются отдельно.',
      'level'    => 'средний',
      'related'  => ['ukladka-plitki-v-vannoy', 'keramogranit-na-pol-ukladka',
                     'gidroizolyatsiya-vannoy', 'remont-vannoy-komnaty-s-nulya-gid',
                     'ustanovka-rakoviny-i-sifona'],
    ],
    'styazhka' => [
      'group'    => 'materialy',
      'title'    => 'Калькулятор стяжки и штукатурки: объём смеси и мешки',
      'h1'       => 'Расчёт стяжки и штукатурки',
      'desc'     => 'Объём раствора и количество мешков по площади и толщине слоя. Отдельно цементная стяжка, наливной пол и гипсовая штукатурка.',
      'lead'     => 'Считает по расходу сухой смеси на м² при слое 1 мм — так, как расход указан на мешке.',
      'level'    => 'средний',
      'related'  => ['styazhka-pola-suhaya-ili-mokraya', 'samovyiravnivayushayasya-styazhka-i-mayaki',
                     'shtukaturka-sten', 'shpaklevka-sten-start-finish-sravnenie',
                     'poly-po-lagam-v-kvartire'],
    ],

    'sechenie-kabelya' => [
      'group'    => 'elektrika',
      'title'    => 'Калькулятор сечения кабеля и номинала автомата',
      'h1'       => 'Сечение кабеля и автомат',
      'desc'     => 'Подбор сечения по мощности и току с учётом способа прокладки, длины линии и падения напряжения. Сразу подбирает номинал автомата.',
      'lead'     => 'Сечение выбирается по току нагрузки, а проверяется по потере напряжения на длине линии — калькулятор делает оба шага.',
      'level'    => 'сложный',
      'related'  => ['raschet-secheniya-kabelya-tablicy', 'zamena-provodki-v-kvartire',
                     'avtomaticheskie-vyklyuchateli-kak-vybrat', 'proekt-elektriki-kvartiry-polnyy',
                     'zamena-provodki-v-chastnom-dome',
                     'ibp-i-generator-dlya-doma'],
    ],
    'moshchnost-i-gruppy' => [
      'group'    => 'elektrika',
      'title'    => 'Калькулятор мощности квартиры и числа групп в щите',
      'h1'       => 'Мощность квартиры и группы',
      'desc'     => 'Суммарная мощность техники с коэффициентом одновременности, ток ввода и рекомендуемое число групп в квартирном щите.',
      'lead'     => 'Приборы не работают одновременно, поэтому ввод считают с коэффициентом спроса, а не по сумме мощностей.',
      'level'    => 'сложный',
      'related'  => ['proekt-elektriki-kvartiry-polnyy', 'kvartirnyy-schitok-sborka-i-markirovka',
                     'elektrika-na-kuhne-raschet-liniy', 'uzo-i-avr-v-schitke',
                     'zamena-provodki-v-chastnom-dome',
                     'ibp-i-generator-dlya-doma'],
    ],
    'elektricheskiy-teplyy-pol' => [
      'group'    => 'elektrika',
      'title'    => 'Калькулятор электрического тёплого пола: мощность и площадь',
      'h1'       => 'Электрический тёплый пол',
      'desc'     => 'Полезная площадь обогрева, удельная мощность под покрытие и режим работы, суммарная нагрузка и расход электроэнергии за месяц.',
      'lead'     => 'Считается по свободной площади без мебели, а мощность на м² зависит от того, основной это обогрев или комфортный.',
      'level'    => 'средний',
      'related'  => ['teplyy-pol', 'keramogranit-na-pol-ukladka',
                     'kvarcvinil-spc-lvt-ukladka', 'raschet-secheniya-kabelya-tablicy',
                     'poly-po-lagam-v-kvartire'],
    ],

    'sekcii-radiatorov' => [
      'group'    => 'santehnika',
      'title'    => 'Калькулятор радиаторов: сколько секций нужно на комнату',
      'h1'       => 'Секции радиатора',
      'desc'     => 'Тепловая мощность по площади, высоте потолка, числу наружных стен и окон. Перевод мощности в секции по паспортной теплоотдаче.',
      'lead'     => 'Формула «100 Вт на м²» работает только для типовой комнаты. Калькулятор учитывает угловое расположение, окна и остекление.',
      'level'    => 'средний',
      'related'  => ['zamena-radiatorov-otopleniya', 'uteplenie-sten-kvartiry-iznutri',
                     'energoeffektivnye-okna-teplo', 'uteplenie-lodzhii',
                     'poly-po-lagam-v-kvartire'],
    ],
    'obem-boylera' => [
      'group'    => 'santehnika',
      'title'    => 'Калькулятор объёма водонагревателя по числу жильцов',
      'h1'       => 'Объём водонагревателя',
      'desc'     => 'Нужный литраж накопительного бойлера по составу семьи и точкам разбора, время нагрева и запас горячей воды на душ и ванну.',
      'lead'     => 'Объём определяет не число людей, а сценарий разбора: ванна на 160 литров и душ на 40 — разные задачи.',
      'level'    => 'простой',
      'related'  => ['vodonagrevatel-boyler-vybor-montazh', 'bojler-nakopitelnyj-ili-protochnyj',
                     'razvodka-santehniki-kvartira-gid', 'vybor-i-ustanovka-vanny',
                     'povysitelnyy-nasos-v-kvartire',
                     'ustanovka-rakoviny-i-sifona'],
    ],
    'vozduhoobmen' => [
      'group'    => 'santehnika',
      'title'    => 'Калькулятор воздухообмена и мощности вытяжки',
      'h1'       => 'Воздухообмен и вытяжка',
      'desc'     => 'Норма притока по площади и числу жильцов, кратность воздухообмена для кухни и санузла, подбор производительности кухонной вытяжки.',
      'lead'     => 'Приток считают по людям и площади, вытяжку — по кратности обмена. Для кухни добавляется запас на длину воздуховода.',
      'level'    => 'средний',
      'related'  => ['rekuperator-pritochnaya-ventilyaciya-kvartiry', 'pritochnye-klapany-na-okna',
                     'plesen-v-kvartire-prichiny-i-borba', 'remont-kuhni-poryadok-rabot'],
    ],

    'laminat' => [
      'group'    => 'materialy',
      'title'    => 'Калькулятор ламината: упаковки, подложка и плинтус',
      'h1'       => 'Расчёт ламината и подложки',
      'desc'     => 'Сколько упаковок ламината или кварцвинила купить с запасом на раскладку, сколько нужно подложки, плёнки, плинтуса и порожков.',
      'lead'     => 'Запас на подрезку задаёт схема укладки: у прямой это 5%, у ёлочки — 18%. Считает заодно подложку, плинтус и порожки.',
      'level'    => 'простой',
      'related'  => ['ukladka-laminata', 'kvarcvinil-spc-lvt-ukladka',
                     'napolnye-pokrytiya-sravnenie-2026', 'parketnaya-doska-montazh-i-uhod',
                     'styazhka-pola-suhaya-ili-mokraya', 'zvukoizolyaciya-pola-plovuchiy-pol',
                     'poly-po-lagam-v-kvartire'],
    ],
    'gipsokarton' => [
      'group'    => 'materialy',
      'title'    => 'Калькулятор гипсокартона: листы, профиль и крепёж',
      'h1'       => 'Расчёт гипсокартона',
      'desc'     => 'Сколько листов ГКЛ, профиля, подвесов, саморезов и шпаклёвки нужно на перегородку, облицовку стены или подвесной потолок.',
      'lead'     => 'Листы дают около трети сметы: остальное — профиль, крепёж и шпаклёвка. Калькулятор считает всё сразу, по типу конструкции.',
      'level'    => 'средний',
      'related'  => ['gipsokarton-peregorodki-i-potolki', 'suhaya-otdelka-sten-paneli-reyki',
                     'shpaklevka-sten-pod-oboi-i-pokrasku', 'akusticheskaya-izolyaciya-kvartiry',
                     'natyazhnye-potolki', 'otkosy-okon-shtukaturka-gkl-sendvich'],
    ],
    'osveshchenie' => [
      'group'    => 'elektrika',
      'title'    => 'Калькулятор освещения: сколько люмен нужно комнате',
      'h1'       => 'Расчёт освещения',
      'desc'     => 'Нужный световой поток по площади и назначению помещения, поправки на высоту потолка и тёмную отделку, число светильников и разложение по трём слоям света.',
      'lead'     => 'Считать надо в люменах, а не в ваттах: у светодиодов отдача различается в полтора раза. Нормы освещённости — по своду правил.',
      'level'    => 'простой',
      'related'  => ['osveshcheniye-v-kvartire', 'svetovye-stsenarii-v-kvartire',
                     'led-podsvetka-nishi-i-karnizov', 'dimmery-sveta-led-sovmestimost',
                     'proekt-elektriki-kvartiry-polnyy', 'dizayn-detskoy-komnaty'],
    ],
    'moshchnost-kondicionera' => [
      'group'    => 'santehnika',
      'title'    => 'Калькулятор мощности кондиционера для комнаты',
      'h1'       => 'Мощность кондиционера',
      'desc'     => 'Тепловая нагрузка по площади, остеклению, числу людей и технике. Подбор типоразмера сплит-системы от 07 до 24 с запасом на пиковую жару.',
      'lead'     => 'Правило «киловатт на десять квадратов» перестаёт работать на последнем этаже, на кухне и при окнах на юг. Здесь считается приток тепла.',
      'level'    => 'средний',
      'related'  => ['montazh-kondicionera-elektrika-trassa', 'energoeffektivnye-okna-teplo',
                     'panoramnye-okna-v-pol', 'uteplenie-lodzhii',
                     'rekuperator-pritochnaya-ventilyaciya-kvartiry',
                     'elektrika-na-kuhne-raschet-liniy'],
    ],
    'vodyanoy-teplyy-pol' => [
      'group'    => 'santehnika',
      'title'    => 'Калькулятор водяного тёплого пола: длина трубы и контуры',
      'h1'       => 'Водяной тёплый пол',
      'desc'     => 'Длина трубы по шагу укладки, число контуров с учётом предела петли, выходы коллектора, теплоотдача под плитку, ламинат и паркет.',
      'lead'     => 'Длина петли ограничена: 90 м для трубы 16 мм и 120 м для 20 мм. Площадь сверх предела делится на контуры примерно равной длины.',
      'level'    => 'сложный',
      'related'  => ['vodyanoy-teplyy-pol-v-kvartire', 'teplyy-pol',
                     'styazhka-pola-suhaya-ili-mokraya', 'keramogranit-na-pol-ukladka',
                     'kollektornaya-razvodka-vody', 'samovyiravnivayushayasya-styazhka-i-mayaki'],
    ],
    'uteplitel-tolshchina' => [
      'group'    => 'materialy',
      'title'    => 'Калькулятор толщины утеплителя по теплосопротивлению',
      'h1'       => 'Толщина утеплителя',
      'desc'     => 'Расчёт слоя ЭППС, ПИР, минваты и пенопласта по нормируемому сопротивлению теплопередаче с учётом существующего основания, климата и типа конструкции.',
      'lead'     => 'Считает не «на глаз в сантиметрах», а по R: из нормы вычитается вклад существующей стены, остаток закрывает утеплитель.',
      'level'    => 'средний',
      'related'  => ['uteplenie-lodzhii', 'uteplenie-sten-kvartiry-iznutri',
                     'energoeffektivnye-okna-teplo', 'plesen-v-kvartire-prichiny-i-borba',
                     'zamena-balkonnogo-bloka-uteplenie', 'holodnoe-i-teploe-osteklenie-balkona'],
    ],
    'tochka-rosy' => [
      'group'    => 'santehnika',
      'title'    => 'Калькулятор точки росы и риска конденсата на стене',
      'h1'       => 'Точка росы и риск конденсата',
      'desc'     => 'Точка росы по температуре и влажности, температура внутренней поверхности стены, угла, откоса или стеклопакета и вердикт: сухо, риск плесени или конденсат.',
      'lead'     => 'Сравнивает два числа: точку росы воздуха в комнате и температуру самой поверхности. Отдельно считает угол и откос — там холоднее.',
      'level'    => 'средний',
      'related'  => ['plesen-v-kvartire-prichiny-i-borba', 'uteplenie-sten-kvartiry-iznutri',
                     'podokonnnik-montazh-i-kondensat', 'ventilyaciya-sanuzla-tyaga-i-ventilyator',
                     'energoeffektivnye-okna-teplo', 'pritochnye-klapany-na-okna',
                     'uteplenie-lodzhii'],
    ],
  ];
}

/** Калькуляторы одной группы. */
function domexpert_calculators_by_group(string $group): array
{
  return array_filter(
    domexpert_all_calculators_meta(),
    static fn(array $c): bool => $c['group'] === $group
  );
}

/**
 * Обратный индекс: какие калькуляторы показать на странице статьи.
 * Строится из поля related, поэтому связь всегда двусторонняя и не расходится.
 */
function domexpert_calculators_for_article(string $articleSlug): array
{
  $found = [];
  foreach (domexpert_all_calculators_meta() as $slug => $calc) {
    if (in_array($articleSlug, $calc['related'], true)) {
      $found[$slug] = $calc;
    }
  }
  return $found;
}

/**
 * Файл разметки калькулятора.
 *
 * Каталог называется calc-forms, а не calculators или calc: последние два заняты
 * под ЧПУ (/calculators/ и /calc/<slug>/), и физический каталог с таким именем
 * конфликтовал бы с правилами mod_rewrite.
 */
function domexpert_calculator_html_path(string $slug): string
{
  return dirname(__DIR__) . '/calc-forms/' . $slug . '.html';
}

/** Канонический адрес калькулятора. */
function du_calculator_url(string $slug): string
{
  return SITE_CANONICAL . '/calc/' . $slug . '/';
}
