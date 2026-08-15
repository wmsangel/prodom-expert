#!/usr/bin/env python3
"""Обложки для статей, у которых их не было.

Из 192 статей реестра обложка была только у 93: в карточках листингов у
остальных стояла эмодзи-заглушка, а в соцсети и мессенджеры при репосте уходила
общая картинка сайта вместо превью материала.

Рисуем тем же генератором, что и остальные обложки (make_cover из скрипта
публикации), поэтому стиль совпадает. Категория берётся из реестра статей,
короткая подпись — из таблицы ниже: она печатается крупно, отсюда 1–3 слова.

Скрипт идемпотентен: статьи, у которых обложка уже есть, пропускаются —
существующие файлы не перезаписываются.

Запуск:  .venv-img/bin/python scripts/make_missing_covers.py
"""
from __future__ import annotations

import importlib.util
import re
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
COVERS = ROOT / "assets" / "img" / "articles"

# Слаг → подпись на обложке.
TITLES = {
    # Ремонт
    "zvukoizolyaciya-pola-plovuchiy-pol":          "Звукоизоляция пола",
    "suhaya-otdelka-sten-paneli-reyki":            "Сухая отделка стен",
    "gipsokarton-peregorodki-i-potolki":           "Гипсокартон",
    "pokraska-sten-kvartiry-sovety":               "Покраска стен",
    "gruntovka-sten-osnovy":                       "Грунтовка стен",
    "ukladka-laminata":                            "Укладка ламината",
    "shtukaturka-sten":                            "Штукатурка стен",
    "natyazhnye-potolki":                          "Натяжные потолки",
    "demontazh-sten-i-peregorodok":                "Демонтаж стен",
    "shpaklevka-sten-pod-oboi-i-pokrasku":         "Шпаклёвка стен",
    "kapitalnyy-remont-kvartiry-polnyy-gid":       "Капитальный ремонт",
    "chernovaya-otdelka-kvartiry-etapy":           "Черновая отделка",
    "styazhka-pola-suhaya-ili-mokraya":            "Стяжка пола",
    "samovyiravnivayushayasya-styazhka-i-mayaki":  "Наливной пол",
    "rekuperator-pritochnaya-ventilyaciya-kvartiry": "Приточная вентиляция",
    "akusticheskaya-izolyaciya-kvartiry":          "Акустическая изоляция",
    "podgotovka-sten-pod-pokrasku":                "Подготовка под покраску",

    # Окна и двери
    "podokonnnik-montazh-i-kondensat":             "Подоконник",
    "antivandalnaya-plenka-na-steklo":             "Плёнка на стекло",
    "steklopaket-tripleks-shumozashchita":         "Триплекс и шум",
    "energoeffektivnye-okna-teplo":                "Энергоэффективные окна",
    "ukhod-za-furniturou-okon":                    "Уход за фурнитурой",
    "plisse-i-roletnye-shtory":                    "Плиссе и рулонные шторы",
    "uteplenie-lodzhii":                           "Утепление лоджии",
    "kak-vybrat-plastikovye-okna":                 "Пластиковые окна",
    "vybor-vkhodnoy-dveri":                        "Входная дверь",
    "mezhkomnatnye-dveri":                         "Межкомнатные двери",
    "panoramnye-okna-v-pol":                       "Панорамные окна",
    "zamena-steklopaketa-bez-zameny-okna":         "Замена стеклопакета",
    "zamena-okon-polnoe-rukovodstvo":              "Замена окон",
    "zvukozashchita-okon-v-kvartire":              "Звукоизоляция окон",
    "zamery-okon-dlya-zakaza":                     "Замер окон",
    "otlivy-i-kapelnaya-liniya-okna":              "Отливы окна",

    # Сантехника
    "bojler-nakopitelnyj-ili-protochnyj":          "Водонагреватель",
    "termostat-dlya-dusha-i-vanny":                "Термостат для душа",
    "vodyanoy-teplyy-pol-v-kvartire":              "Водяной тёплый пол",
    "kollektornaya-razvodka-vody":                 "Коллекторная разводка",
    "umnye-smesiteli-ekonomiya-vody":              "Умные смесители",
    "zamen-schetchika-vody":                       "Замена счётчика воды",
    "filtr-pod-moyku-vybor":                       "Фильтр под мойку",
    "installyatsiya-unitaza":                      "Инсталляция унитаза",
    "gidroizolyatsiya-vannoy":                     "Гидроизоляция ванной",
    "kak-vybrat-smesitel":                         "Выбор смесителя",
    "ukladka-plitki-v-vannoy":                     "Укладка плитки",
    "zamena-stoyakov-vodosnabzheniya":             "Замена стояков",
    "podklyuchenie-stiralnoy-mashiny":             "Стиральная машина",
    "razvodka-santehniki-kvartira-gid":            "Разводка сантехники",
    "remont-vannoy-komnaty-s-nulya-gid":           "Ремонт ванной",
    "reduktor-davleniya-vody-v-kvartire":          "Редуктор давления",
    "vynosnaya-kolonna-smestitelya-vanna":         "Колонна смесителя",

    # Электрика
    "kvartirnyy-schitok-sborka-i-markirovka":      "Квартирный щиток",
    "kabel-kanaly-i-koroby":                       "Кабель-каналы",
    "zamena-elektroschetchika-kvartira":           "Замена электросчётчика",
    "prokladka-interneta-vitaya-para-remont":      "Интернет при ремонте",
    "zaryadka-elektromobilya-kvartira":            "Зарядка электромобиля",
    "stabilizator-napryazheniya-kvartira":         "Стабилизатор напряжения",
    "umnyy-dom-osnovy":                            "Умный дом",
    "zamena-provodki-v-kvartire":                  "Замена проводки",
    "rozetki-i-vyklyuchateli":                     "Розетки и выключатели",
    "osveshcheniye-v-kvartire":                    "Освещение в квартире",
    "teplyy-pol":                                  "Тёплый пол",
    "montazh-elektroshchita-svoimi-rukami":        "Монтаж электрощита",
    "shtroblenie-sten-pod-provodku":               "Штробление стен",
    "proekt-elektriki-kvartiry-polnyy":            "Проект электрики",
    "bezopasnaya-elektrika-v-kvartire-pravila":    "Безопасная электрика",
    "elektrika-na-kuhne-raschet-liniy":            "Электрика на кухне",
    "ulichnaya-rozetka-ip-zima-balkon":            "Уличная розетка",

    # Интерьер
    "zonirovanie-studii-odnokomnatnoy":            "Зонирование студии",
    "fitodizayn-rasteniya-v-interere":             "Растения в интерьере",
    "garderobnaya-planirovanie-i-svet":            "Гардеробная",
    "tepliy-minimalizm-interer":                   "Тёплый минимализм",
    "modulnaya-sistema-hranenia":                  "Системы хранения",
    "domashniy-ofis-v-kvartire":                   "Домашний офис",
    "malenkaya-spalnya-dizayn":                    "Маленькая спальня",
    "dizayn-gostinoy":                             "Дизайн гостиной",
    "interer-kuhni":                               "Дизайн кухни",
    "dizayn-vannoy":                               "Дизайн ванной",
    "dizayn-detskoy-komnaty":                      "Детская комната",
    "skandinavskiy-stil-v-interere":               "Скандинавский стиль",
    "dizayn-kvartiry-studii-mega-gid":             "Квартира-студия",
    "remont-interera-bez-dizaynera-gid":           "Интерьер без дизайнера",
    "svetovye-stsenarii-v-kvartire":               "Световые сценарии",
    "led-podsvetka-nishi-i-karnizov":              "LED-подсветка ниш",

    # Советы
    "vybor-remontnoj-brigady":                     "Выбор бригады",
    "raschet-materialov-dlya-remonta":             "Расчёт материалов",
    "remont-s-detmi-bezopasnost":                  "Ремонт с детьми",
    "stroitelnye-othody-vyvoz":                    "Строительные отходы",
    "smeta-remonta-kvartiry":                      "Смета на ремонт",
    "dogovor-podryada-remont":                     "Договор подряда",
    "kak-vibrat-oboi":                             "Как выбрать обои",
    "instrumenty-dlya-remonta":                    "Инструменты для ремонта",
    "kak-sekonomit-na-remonte":                    "Экономия на ремонте",
    "kak-vybrat-brigadu-dlya-remonta":             "Бригада для ремонта",
    "remont-v-novostroyke-s-chego-nachat":         "Ремонт в новостройке",
    "byudzhet-kapitalnogo-remonta-raschet":        "Бюджет ремонта",
    "top-oshibok-remonta-kvartiry":                "Ошибки ремонта",
    "kak-prinyat-remont-cheklist":                 "Приёмка ремонта",
    "remont-pri-deficit-materialov-grafik":        "Дефицит материалов",
    "posledovatelnost-remonta-chek-list":          "Этапы ремонта",
    # Партия от 3 августа 2026
    "reechnye-potolki-montazh":                    "Реечные потолки",
    "rolstavni-i-stavni-na-okna":                  "Рольставни на окна",
    "santehnicheskiy-lyuk-pod-plitku":             "Сантехнический люк",
    "provodka-v-gipsokartone-normy":               "Проводка в ГКЛ",
    "interer-semnoy-kvartiry":                     "Съёмная квартира",
    "brigada-brosila-obekt-chto-delat":            "Бригада ушла",
    # Партия от 6 августа 2026
    "smeta-remonta-chastnogo-doma":                "Смета на дом",
    "uzm-i-uzo-v-chem-raznica":                    "УЗМ и УЗО",
    # Партия от 10 августа 2026 — по одной статье в категорию
    "datchiki-dvizheniya-i-prisutstviya":          "Датчики движения",
    "ergonomika-kvartiry-razmery":                 "Эргономика квартиры",
    "dver-v-vannuyu-i-sanuzel":                    "Дверь в санузел",
    "styk-napolnyh-pokrytiy-porogi":               "Стык покрытий",
    "filtry-dlya-vody-v-kvartiru":                 "Фильтры для воды",
    "garantiya-na-remont-i-defekty":               "Гарантия на ремонт",
    # Партия от 11 августа 2026 — по одной статье в категорию
    "vybivaet-avtomat-poisk-prichiny":             "Выбивает автомат",
    "mudbord-i-podbor-materialov":                 "Мудборд",
    "dvernaya-furnitura-ruchki-zamki-petli":       "Дверная фурнитура",
    "krepezh-v-stenu-dyubeli-i-ankery":            "Крепёж в стену",
    "ventilyaciya-sanuzla-tyaga-i-ventilyator":    "Вентиляция санузла",
    "skrytye-raboty-akty-i-fotofiksaciya":         "Скрытые работы",
    # Кластер вокруг планировщика ремонта
    "obmer-kvartiry-svoimi-rukami":                "Обмер квартиры",
    "plan-kvartiry-gde-vzyat":                     "План квартиры",
    "skolko-rozetok-nuzhno-v-kvartire":            "Сколько розеток",
    "vysota-potolka-chistovaya-otmetka":           "Высота потолка",
    "chto-vhodit-v-remont-pod-klyuch":             "Ремонт под ключ",
    "otkuda-berutsya-ceny-na-remont":              "Цены на ремонт",
}

COVER_EXTS = (".jpg", ".jpeg", ".webp", ".png")   # порядок как в includes/article-cover.php


def load_generator():
    """Берём make_cover из скрипта публикации, чтобы обложки совпадали по стилю."""
    src = ROOT / "scripts" / "publish_july31_articles.py"
    spec = importlib.util.spec_from_file_location("publisher", src)
    mod = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(mod)
    return mod


def article_categories() -> dict[str, str]:
    """Слаг → категория из includes/all-articles-meta.php."""
    meta = (ROOT / "includes" / "all-articles-meta.php").read_text(encoding="utf-8")
    return dict(re.findall(r"^  '([a-z0-9-]+)' => \['cat' => '([a-z]+)'", meta, re.M))


def has_cover(slug: str) -> bool:
    return any((COVERS / f"{slug}{ext}").is_file() for ext in COVER_EXTS)


def main() -> int:
    cats = article_categories()
    pub = load_generator()
    COVERS.mkdir(parents=True, exist_ok=True)

    done, already, problems = 0, 0, []

    for slug, short in TITLES.items():
        cat = cats.get(slug)
        if cat is None:
            problems.append(f"{slug}: нет в реестре статей")
            continue
        if has_cover(slug):
            already += 1
            continue

        pub.make_cover(slug, cat, short)
        if not (COVERS / f"{slug}.png").is_file():
            problems.append(f"{slug}: обложка не создалась")
            continue

        done += 1
        print(f"  ▸ {slug:45} [{cat:10}] «{short}»")

    # Статьи реестра, для которых подписи так и нет
    missing = [s for s in cats if not has_cover(s) and s not in TITLES]
    if missing:
        problems.append("нет подписи в TITLES: " + ", ".join(missing))

    print(f"\nСоздано обложек: {done}; уже были: {already}")
    if problems:
        print("Требует внимания:")
        for p in problems:
            print("  !", p)
        return 1
    return 0


if __name__ == "__main__":
    sys.exit(main())
