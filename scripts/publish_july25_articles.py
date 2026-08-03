#!/usr/bin/env python3
"""Публикация партии статей от 25 июля 2026 (по 2 больших материала в каждую категорию).

Тексты статей лежат в articles/<cat>/<slug>.html.
Скрипт рисует обложку, схему-инфографику и таблицу-картинку для каждой статьи,
затем регистрирует статьи во всех реестрах сайта (all-articles-meta.php, article.php,
rss.php, search.php, sitemap.xml). Идемпотентен: уже зарегистрированные slug'и пропускаются.
"""
from __future__ import annotations

import os
import sys
import textwrap
from pathlib import Path

from PIL import Image, ImageDraw, ImageFont

ROOT = Path(__file__).resolve().parents[1]
ARTICLES = ROOT / "articles"
COVERS = ROOT / "assets" / "img" / "articles"
INLINE = ROOT / "assets" / "img" / "inline"

DATE = "25 июля 2026"
ISO = "2026-07-25"
RSS_DAY = "Sat, 25 Jul 2026"

CAT_COLORS = {
    "remont": ("#2C2C2C", "#C45C4A", "Ремонт"),
    "okna": ("#1F2A37", "#3B82A0", "Окна и двери"),
    "santehnika": ("#1E2F2C", "#2F8F7B", "Сантехника"),
    "elektrika": ("#2A2418", "#C9A227", "Электрика"),
    "interer": ("#2B2420", "#A67C52", "Интерьер"),
    "sovety": ("#222831", "#5B7C99", "Советы"),
}

AUTHORS = {
    "remont": "Редакция ДомЭксперт",
    "okna": "Редакция ДомЭксперт",
    "santehnika": "Иван Петров",
    "elektrika": "Алексей Смирнов",
    "interer": "Мария Соколова",
    "sovety": "Редакция ДомЭксперт",
}

# slug, cat, title, desc, readTime, short (для обложки), scheme (инфографика), table (таблица-картинка)
ARTS = [
    dict(
        slug="remont-kuhni-poryadok-rabot", cat="remont",
        title="Ремонт кухни: последовательность работ, фартук, вытяжка и розетки под гарнитур",
        desc="Почему чертёж гарнитура идёт раньше штроб, 14 шагов по порядку, высоты розеток, выбор фартука и расчёт вытяжки.",
        readTime="22 мин", short="Ремонт кухни",
        scheme=dict(kind="steps", title="Порядок работ на кухне",
                    items=["Проект гарнитура и техники", "Демонтаж и проверка вентканала",
                           "Вода и канализация", "Электрика: линии и розетки",
                           "Штукатурка, стяжка, гидроизоляция", "Пол, фартук, потолок",
                           "Замер и сборка гарнитура"],
                    note="Между шагами 1 и 4 менять планировку кухни ещё дёшево, после — уже нет"),
        table=dict(headers=["Линия", "Кабель / автомат", "Высота розетки"],
                   rows=[["Варочная панель", "6 мм², 32 А", "15–20 см, колодка"],
                         ["Духовой шкаф", "2,5 мм², 16 А", "15–20 см"],
                         ["Посудомойка", "2,5 мм², 16 А + УЗО", "15–20 см"],
                         ["Холодильник", "2,5 мм², 16 А", "15–20 см"],
                         ["Фартук, 2 группы", "2,5 мм², 16 А", "110–115 см"],
                         ["Вытяжка", "1,5 мм², 10–16 А", "200–220 см"]],
                   caption="Розетки за корпусами техники не размещают — только в соседнем модуле"),
    ),
    dict(
        slug="kvarcvinil-spc-lvt-ukladka", cat="remont",
        title="Кварцвинил SPC и LVT: чем отличается от ламината, укладка и тёплый пол",
        desc="Защитный слой 0,3 или 0,55 мм, требования к основанию, тонкая подложка, зазоры и ограничение нагрева +27 °C.",
        readTime="20 мин", short="Кварцвинил SPC и LVT",
        scheme=dict(kind="layers", title="Пирог пола с замковым SPC",
                    items=[("Защитный слой 0,3–0,55 мм", "Держит песок, каблуки, колёса"),
                           ("Декор и плита SPC, 3,5–5 мм", "Жёсткая основа, замок click"),
                           ("Подложка IXPE 1–1,5 мм", "Только тонкая и жёсткая"),
                           ("Стяжка: 2 мм на 2 м, влажность ≤2%", "Ровность и сухость — обязательны")],
                    note="Толстая подложка от ламината ломает замки SPC за сезон"),
        table=dict(headers=["Критерий", "Кварцвинил SPC", "Ламинат 33"],
                   rows=[["Вода", "Не боится", "Разбухает по стыкам"],
                         ["Шум шагов", "Тихий", "Гулкий"],
                         ["Основание", "2 мм на 2 м", "2–3 мм на 2 м"],
                         ["Материал за м²", "1500–4500 ₽", "900–2500 ₽"],
                         ["Работа за м²", "450–800 ₽", "400–700 ₽"],
                         ["Тёплый пол", "До +27 °C", "До +27 °C"]],
                   caption="Цены 2026 года для крупных городов"),
    ),
    dict(
        slug="montazh-vkhodnoy-dveri-svoimi-rukami", cat="okna",
        title="Монтаж входной двери: замер проёма, анкеровка, монтажный шов и откосы",
        desc="Зазоры 15–25 мм, вертикаль петлевой стойки, 8 точек анкеровки, трёхслойный шов и регулировка ригелей.",
        readTime="19 мин", short="Монтаж входной двери",
        scheme=dict(kind="steps", title="Монтаж входной двери по шагам",
                    items=["Замер проёма в трёх точках", "Демонтаж и ремонт проёма",
                           "Коробка по уровню на клиньях", "Анкеровка 3+3+2 без перетяжки",
                           "Трёхслойный шов, распорки на сутки", "Доборы, наличники, регулировка"],
                    note="После застывания пены геометрию уже не исправить"),
        table=dict(headers=["Симптом", "Причина", "Что делать"],
                   rows=[["Дверь сама открывается", "Завал коробки", "Переставить до застывания пены"],
                         ["Ригель заходит туго", "Перетянут анкер", "Ослабить, добавить прокладку"],
                         ["Дует по периметру", "Пустоты в пене", "Пропенить, настроить прижим"],
                         ["Конденсат зимой", "Мостик холода", "Терморазрыв, утепление откоса"],
                         ["Скрип петель", "Сухие подшипники", "Смазка, проверка провисания"]],
                   caption="Типовые дефекты монтажа и их устранение"),
    ),
    dict(
        slug="pritochnye-klapany-na-okna", cat="okna",
        title="Приточные клапаны на окна: почему душно после замены стеклопакетов",
        desc="Диагностика тяги за вечер, типы клапанов и их расход, монтаж оконного и стенового, обмерзание и шум.",
        readTime="19 мин", short="Приточные клапаны",
        scheme=dict(kind="steps", title="Как воздух идёт по квартире",
                    items=["Клапан на окне: приток 20–40 м³/ч", "Жилая комната",
                           "Переток: зазор 15–20 мм под дверью", "Вытяжной канал кухни и санузла",
                           "Проверка листом бумаги у решётки"],
                    note="Нет вытяжной тяги или перетока — приточный клапан не работает"),
        table=dict(headers=["Тип клапана", "Приток, м³/ч", "Цена с монтажом"],
                   rows=[["Фальцевый", "3–7", "1 000–3 000 ₽"],
                         ["Щелевой накладной", "5–15", "3 000–6 000 ₽"],
                         ["Оконный с фрезеровкой", "20–40", "5 000–10 000 ₽"],
                         ["Стеновой КИВ", "30–60", "10 000–20 000 ₽"],
                         ["Бризер с фильтрами", "30–160", "30 000–80 000 ₽"]],
                   caption="Норма притока — 30 м³/ч на человека"),
    ),
    dict(
        slug="dushevoy-trap-i-uklon-pola", cat="santehnika",
        title="Душ без поддона: трап, уклон пола и гидроизоляция без протечек",
        desc="Высота пирога 110–170 мм, точечный или линейный трап, уклон 1,5% в стяжке и проверка наливом воды.",
        readTime="21 мин", short="Душевой трап и уклон",
        scheme=dict(kind="layers", title="Пирог пола душевой без поддона",
                    items=[("Плитка R10–R11 + эпоксидная затирка", "Швы 2–3 мм, углы на силикон"),
                           ("Клей C2 TE S1, двойное нанесение", "Без пустот под плиткой"),
                           ("Гидроизоляция 2 слоя + манжета трапа", "Заводится под фланец"),
                           ("Стяжка с уклоном 1,5%", "Уклон формируют здесь, не клеем"),
                           ("Плита перекрытия, трап 65–150 мм", "Штробить плиту нельзя")],
                    note="Проверка наливом воды на сутки — до укладки плитки"),
        table=dict(headers=["Параметр", "Точечный трап", "Линейный лоток"],
                   rows=[["Уклон", "В четырёх плоскостях", "В одной"],
                         ["Формат плитки", "Мозаика, мелкий", "Любой, до крупного"],
                         ["Пропускная способность", "0,4–0,8 л/с", "0,6–1,2 л/с"],
                         ["Цена", "3 000–12 000 ₽", "9 000–35 000 ₽"],
                         ["Высота узла", "от 90 мм", "от 70 мм"]],
                   caption="Крупноформатный керамогранит требует линейного лотка"),
    ),
    dict(
        slug="podklyuchenie-posudomoechnoy-mashiny", cat="santehnika",
        title="Подключение посудомоечной машины: вода, слив с петлёй и розетка",
        desc="Кран с аквастопом, антисифонная петля 40–60 см, врезка выше гидрозатвора и линия 16 А с УЗО.",
        readTime="19 мин", short="Подключение посудомойки",
        scheme=dict(kind="steps", title="Три узла подключения",
                    items=["Кран 1/2\" на холодной воде", "Шланг с аквастопом, без удлинения",
                           "Слив: петля 40–60 см от пола", "Врезка выше гидрозатвора сифона",
                           "Розетка 16 А с УЗО 30 мА вне корпуса"],
                    note="Слив без петли — запах, грязная вода в баке и «не отмывается посуда»"),
        table=dict(headers=["Симптом", "Причина", "Решение"],
                   rows=[["Запах из машины", "Нет петли слива", "Поднять шланг на 50 см"],
                         ["Вода в баке после цикла", "Засор фильтра", "Промыть фильтр и шланг"],
                         ["Белый налёт на посуде", "Нет соли, жёсткая вода", "Соль и настройка жёсткости"],
                         ["Не набирает воду", "Сработал аквастоп", "Проверить кран и сеточку"],
                         ["Лужа под машиной", "Негерметичный штуцер", "Перебрать соединение"]],
                   caption="Диагностика по первым признакам"),
    ),
    dict(
        slug="prohodnye-vyklyuchateli-shemy", cat="elektrika",
        title="Проходные и перекрёстные выключатели: схемы на 2, 3 и более точек",
        desc="Где рвать фазу, две «бегающие» жилы, перекрёстный механизм, импульсное реле и мигание LED-ламп.",
        readTime="19 мин", short="Проходные выключатели",
        scheme=dict(kind="steps", title="Схема управления светом из двух мест",
                    items=["Щит: автомат 10 А на группу света", "Распределительная коробка: фаза",
                           "Проходной №1: общий контакт", "Две жилы между выключателями",
                           "Проходной №2 → светильник", "Ноль идёт на светильник напрямую"],
                    note="3 точки = 2 проходных + 1 перекрёстный; от 4 точек — импульсное реле"),
        table=dict(headers=["Критерий", "Проходные", "Импульсное реле"],
                   rows=[["Число точек", "Оптимум 2–3", "Любое"],
                         ["Кабель", "3×1,5 между точками", "Шлейф кнопок"],
                         ["Место в щите", "Не нужно", "1–2 модуля"],
                         ["Работа без электроники", "Да", "Нет"],
                         ["Цена на группу", "1 500–4 000 ₽", "3 000–7 000 ₽"]],
                   caption="Выбор схемы по числу точек управления"),
    ),
    dict(
        slug="podklyuchenie-varochnoy-paneli-i-duhovki", cat="elektrika",
        title="Подключение варочной панели и духового шкафа: кабель, автомат, клеммник",
        desc="Кабель 3×6 мм² и автомат 32 А, клеммная колодка вместо розетки, перемычки L1-L2-L3 и линия 16 А на духовку.",
        readTime="20 мин", short="Панель и духовка",
        scheme=dict(kind="steps", title="Силовая линия варочной панели",
                    items=["Щит: автомат 32 А, кривая C", "УЗО или дифавтомат 30 мА, тип A",
                           "Кабель ВВГнг-LS 3×6 мм² без соединений", "Клеммная колодка с доступом, 15–25 см",
                           "Перемычки L1-L2-L3 и N1-N2 на панели"],
                    note="Духовка — отдельная линия 2,5 мм² и розетка 16 А вне корпуса"),
        table=dict(headers=["Мощность", "Ток при 230 В", "Кабель", "Автомат"],
                   rows=[["до 3,5 кВт", "до 16 А", "3×2,5 мм²", "16 А"],
                         ["до 5,0 кВт", "до 22 А", "3×4 мм²", "25 А"],
                         ["до 7,4 кВт", "до 32 А", "3×6 мм²", "32 А"],
                         ["до 9,0 кВт", "до 40 А", "3×10 мм²", "40 А"]],
                   caption="Автомат защищает кабель — номинал подбирают под сечение"),
    ),
    dict(
        slug="kuhnya-gostinaya-planirovka-zonirovanie", cat="interer",
        title="Кухня-гостиная: планировка, остров, вытяжка и приёмы зонирования",
        desc="Проходы 100–120 см, четыре схемы под площадь, борьба с запахами и шумом, обеденная зона и свет по сценариям.",
        readTime="20 мин", short="Кухня-гостиная",
        scheme=dict(kind="bars", title="Опорные размеры кухни-гостиной, см",
                    items=[("Проход у острова", 120, "100–120"),
                           ("Основной проход", 90, "80–90"),
                           ("Фронт мойка — плита", 110, "90–120"),
                           ("Проход за стулом", 85, "75–90"),
                           ("Место за столом на человека", 65, "60–70")],
                    note="Не сходятся размеры — планировку меняют, а не стиль"),
        table=dict(headers=["Схема", "Площадь от", "Главный минус"],
                   rows=[["Линейная вдоль стены", "18 м²", "Мало столешницы"],
                         ["Г-образная", "20 м²", "Угол требует карусели"],
                         ["С полуостровом", "22 м²", "Один проход перекрыт"],
                         ["С островом", "28–30 м²", "Дорогие коммуникации"]],
                   caption="Остров требует ширины комнаты от 3,6 м"),
    ),
    dict(
        slug="dekor-sten-razveska-kartin", cat="interer",
        title="Декор стен: развеска картин, галерейная стена, высота и свет",
        desc="Правило 145–150 см, пропорция к мебели, пять схем развески, крепёж под основание и подсветка под углом 30°.",
        readTime="18 мин", short="Развеска картин",
        scheme=dict(kind="bars", title="Четыре числа развески",
                    items=[("Центр композиции от пола, см", 150, "145–150"),
                           ("Над спинкой мебели, см", 25, "15–25"),
                           ("Ширина группы от мебели, %", 75, "60–75"),
                           ("Шаг между рамами, см", 10, "5–10"),
                           ("Угол подсветки, °", 30, "30")],
                    note="Сначала раскладка на полу и бумажные шаблоны, потом перфоратор"),
        table=dict(headers=["Основание", "Крепёж", "Нагрузка"],
                   rows=[["Бетон, кирпич", "Дюбель 6×40", "до 25–30 кг"],
                         ["Гипсокартон", "Дюбель-бабочка, Molly", "5–15 кг"],
                         ["ГКЛ по стойке", "Саморез в профиль", "до 20 кг"],
                         ["Лёгкая рама", "Клеевые полоски", "до 2–3 кг"],
                         ["Плитка", "Сверло в шов + дюбель", "до 15 кг"]],
                   caption="Перед сверлением — детектор проводки"),
    ),
    dict(
        slug="zhit-vo-vremya-remonta-ili-syehat", cat="sovety",
        title="Жить во время ремонта или съехать: расчёт по деньгам, срокам и здоровью",
        desc="Полная стоимость обоих сценариев, жёсткий этап 4–8 недель, гибридные варианты и защита от пыли.",
        readTime="19 мин", short="Жить в ремонте или съехать",
        scheme=dict(kind="bars", title="Двушка 55 м²: во что обходится решение, тыс. ₽",
                    items=[("Съехать: аренда 4 месяца", 220, "220 тыс. ₽"),
                           ("Съехать: переезд и хранение", 40, "40 тыс. ₽"),
                           ("Остаться: удорожание работ", 135, "+15% к смете"),
                           ("Остаться: пыль, уборки, быт", 40, "40 тыс. ₽")],
                    note="Остаться дешевле на ~125 тыс. ₽, но ремонт идёт на 3 месяца дольше"),
        table=dict(headers=["Задача", "Решение", "Стоимость"],
                   rows=[["Отсечь пыль", "Штора с молнией в проём", "800–2 500 ₽"],
                         ["Убрать взвесь", "Очиститель с HEPA", "от 12 000 ₽"],
                         ["Пыль в источнике", "Пылесос М-класса", "700–1 500 ₽/сут"],
                         ["Временная кухня", "Плитка и микроволновка", "6 000–15 000 ₽"],
                         ["Хранение вещей", "Склад 3–5 м²", "3 000–9 000 ₽/мес"]],
                   caption="Минимальный набор, если решили остаться"),
    ),
    dict(
        slug="chto-sdelat-samomu-a-chto-otdat-masteram", cat="sovety",
        title="Что делать самому, а что отдать мастерам: разбор по видам работ",
        desc="Три вопроса перед решением, зелёная, жёлтая и красная зоны работ, реальная экономия и аренда инструмента.",
        readTime="19 мин", short="Самому или мастерам",
        scheme=dict(kind="columns", title="Матрица работ: где экономия реальна",
                    items=[("Делайте сами", "#4B8B5A",
                            ["Демонтаж и вывоз мусора", "Грунтование", "Покраска по готовому",
                             "Обои на флизелине", "Ламинат и кварцвинил", "Сборка мебели",
                             "Замена смесителя", "Финальная уборка"]),
                           ("Можно с оговорками", "#C9A227",
                            ["Шпаклёвка стен", "Перегородка из ГКЛ", "Плитка на малой площади",
                             "Замена межкомнатной двери", "Кабель в готовой штробе",
                             "Установка стиралки"]),
                           ("Только мастера", "#B04A3A",
                            ["Стояки воды и канализации", "Газовое оборудование", "Сборка щита",
                             "Перепланировка и проёмы", "Гидроизоляция мокрых зон",
                             "Стяжка и штукатурка", "Натяжные потолки", "Монтаж окон"])],
                    note="Правило: сами — где ошибка стоит материала; мастерам — всё скрытое и общедомовое"),
        table=dict(headers=["Инструмент", "Покупка", "Аренда в сутки"],
                   rows=[["Шуруповёрт", "4 000–15 000 ₽", "400 ₽"],
                         ["Перфоратор", "7 000–25 000 ₽", "700–1 200 ₽"],
                         ["Лазерный уровень", "5 000–30 000 ₽", "500–900 ₽"],
                         ["Плиткорез электрический", "15 000–60 000 ₽", "1 000–2 000 ₽"],
                         ["Строительный пылесос", "12 000–50 000 ₽", "700–1 500 ₽"],
                         ["Шлифмашина для стен", "15 000–45 000 ₽", "900–1 800 ₽"]],
                   caption="Что дешевле арендовать, чем покупать ради одного ремонта"),
    ),
]


# ─── шрифты и цвета ──────────────────────────────────────────────────

SFNS = "/System/Library/Fonts/SFNS.ttf"  # единственный системный шрифт с глифом ₽ и кириллицей


def font(size: int, bold: bool = False):
    """SF Pro с нужным начертанием; Arial как запасной вариант.

    В macOS-версии Arial нет символа ₽ (U+20BD) — в ценах вместо него рисуется квадрат,
    поэтому основной шрифт для картинок — вариативный SFNS.
    """
    if os.path.exists(SFNS):
        try:
            f = ImageFont.truetype(SFNS, size)
            f.set_variation_by_name("Bold" if bold else "Regular")
            return f
        except Exception:
            pass
    candidates = [
        "/System/Library/Fonts/Supplemental/Arial Bold.ttf" if bold
        else "/System/Library/Fonts/Supplemental/Arial.ttf",
        "/System/Library/Fonts/Helvetica.ttc",
        "/Library/Fonts/Arial.ttf",
    ]
    for path in candidates:
        if os.path.exists(path):
            try:
                return ImageFont.truetype(path, size)
            except Exception:
                continue
    return ImageFont.load_default()


def _shade(hex_color: str, delta: int) -> str:
    h = hex_color.lstrip("#")
    rgb = [max(0, min(255, int(h[i:i + 2], 16) + delta)) for i in (0, 2, 4)]
    return "#{:02x}{:02x}{:02x}".format(*rgb)


def _fit(draw, text: str, f, max_w: int) -> str:
    """Обрезает строку по ширине с многоточием."""
    if draw.textlength(text, font=f) <= max_w:
        return text
    while text and draw.textlength(text + "…", font=f) > max_w:
        text = text[:-1]
    return text + "…"


def _wrap(draw, text: str, f, max_w: int, max_lines: int = 3) -> list[str]:
    words, lines, cur = text.split(), [], ""
    for w in words:
        probe = f"{cur} {w}".strip()
        if draw.textlength(probe, font=f) <= max_w:
            cur = probe
        else:
            if cur:
                lines.append(cur)
            cur = w
            if len(lines) == max_lines:
                break
    if cur and len(lines) < max_lines:
        lines.append(cur)
    if len(lines) == max_lines:
        lines[-1] = _fit(draw, lines[-1], f, max_w)
    return lines


# ─── обложка ─────────────────────────────────────────────────────────

def make_cover(slug: str, cat: str, title: str) -> None:
    left, accent, cat_label = CAT_COLORS[cat]
    img = Image.new("RGB", (1200, 630), left)
    d = ImageDraw.Draw(img)
    d.polygon([(680, 0), (1200, 0), (1200, 630), (520, 630)], fill=accent)
    d.polygon([(780, 0), (1200, 0), (1200, 630), (900, 630)], fill=_shade(accent, -18))
    d.rounded_rectangle([48, 48, 84, 84], radius=6, fill=accent)
    d.text((100, 52), "ДомЭксперт", font=font(28, True), fill="#FFFFFF")
    pill_w = 28 + len(cat_label) * 14
    d.rounded_rectangle([48, 110, 48 + pill_w, 146], radius=14, fill=accent)
    d.text((64, 116), cat_label, font=font(18, True), fill="#FFFFFF")
    y = 220
    for line in textwrap.wrap(title, width=22)[:4]:
        d.text((48, y), line, font=font(48, True), fill="#FFFFFF")
        y += 58
    d.text((48, 560), "prodom-expert.ru", font=font(20), fill="#DDDDDD")
    img.save(COVERS / f"{slug}.png", "PNG", optimize=True)


# ─── схемы-инфографика ───────────────────────────────────────────────

BG = "#FAFAF8"
INK = "#232323"
MUTED = "#6E6A63"


def _scheme_canvas(title: str, accent: str, height: int):
    img = Image.new("RGB", (1200, height), BG)
    d = ImageDraw.Draw(img)
    d.rectangle([0, 0, 1200, 8], fill=accent)
    d.text((48, 40), title, font=font(34, True), fill=INK)
    return img, d


def _scheme_note(d: ImageDraw.ImageDraw, note: str, y: int, accent: str) -> None:
    if not note:
        return
    d.rounded_rectangle([48, y, 1152, y + 60], radius=10, fill="#F0EEE8")
    d.rectangle([48, y, 54, y + 60], fill=accent)
    d.text((72, y + 20), _fit(d, note, font(20), 1050), font=font(20), fill=MUTED)


def draw_steps(slug: str, accent: str, spec: dict) -> None:
    items = spec["items"]
    rows = (len(items) + 1) // 2
    h = 150 + rows * 108 + 90
    img, d = _scheme_canvas(spec["title"], accent, h)
    box_w, box_h, gap = 528, 88, 20
    for i, text in enumerate(items):
        col, row = i % 2, i // 2
        x = 48 + col * (box_w + gap)
        y = 130 + row * (box_h + gap)
        d.rounded_rectangle([x, y, x + box_w, y + box_h], radius=12, fill="#FFFFFF",
                            outline="#E3DFD6", width=2)
        d.ellipse([x + 18, y + 24, x + 58, y + 64], fill=accent)
        num = str(i + 1)
        d.text((x + 38 - d.textlength(num, font=font(22, True)) / 2, y + 33),
               num, font=font(22, True), fill="#FFFFFF")
        lines = _wrap(d, text, font(21), box_w - 100, 2)
        ty = y + (box_h - len(lines) * 27) // 2
        for line in lines:
            d.text((x + 74, ty), line, font=font(21), fill=INK)
            ty += 27
        if i < len(items) - 1 and col == 0:
            d.polygon([(x + box_w + 4, y + box_h // 2 - 7), (x + box_w + 16, y + box_h // 2),
                       (x + box_w + 4, y + box_h // 2 + 7)], fill=accent)
    _scheme_note(d, spec.get("note", ""), 130 + rows * (box_h + gap) + 6, accent)
    img.save(INLINE / f"{slug}-scheme.png", "PNG", optimize=True)


def draw_layers(slug: str, accent: str, spec: dict) -> None:
    items = spec["items"]
    band_h = 92
    h = 140 + len(items) * (band_h + 12) + 90
    img, d = _scheme_canvas(spec["title"], accent, h)
    for i, (name, hint) in enumerate(items):
        y = 130 + i * (band_h + 12)
        tone = _shade(accent, 46 - i * 16)
        d.rounded_rectangle([48, y, 1152, y + band_h], radius=10, fill="#FFFFFF",
                            outline="#E3DFD6", width=2)
        d.rounded_rectangle([48, y, 320, y + band_h], radius=10, fill=tone)
        d.rectangle([300, y, 320, y + band_h], fill=tone)
        d.text((70, y + 34), f"Слой {i + 1}", font=font(22, True), fill="#FFFFFF")
        d.text((348, y + 20), _fit(d, name, font(23, True), 780), font=font(23, True), fill=INK)
        d.text((348, y + 52), _fit(d, hint, font(19), 780), font=font(19), fill=MUTED)
    _scheme_note(d, spec.get("note", ""), 130 + len(items) * (band_h + 12) + 6, accent)
    img.save(INLINE / f"{slug}-scheme.png", "PNG", optimize=True)


def draw_bars(slug: str, accent: str, spec: dict) -> None:
    items = spec["items"]
    row_h = 76
    h = 140 + len(items) * row_h + 90
    img, d = _scheme_canvas(spec["title"], accent, h)
    peak = max(v for _, v, _ in items) or 1
    label_w, bar_x, bar_max = 420, 500, 520
    for i, (name, value, caption) in enumerate(items):
        y = 130 + i * row_h
        d.text((48, y + 16), _fit(d, name, font(22), label_w), font=font(22), fill=INK)
        width = max(24, int(bar_max * value / peak))
        d.rounded_rectangle([bar_x, y + 12, bar_x + bar_max, y + 48], radius=8, fill="#EFECE4")
        d.rounded_rectangle([bar_x, y + 12, bar_x + width, y + 48], radius=8,
                            fill=_shade(accent, -6 * (i % 3)))
        d.text((bar_x + width + 16, y + 18), caption, font=font(21, True), fill=MUTED)
    _scheme_note(d, spec.get("note", ""), 130 + len(items) * row_h + 6, accent)
    img.save(INLINE / f"{slug}-scheme.png", "PNG", optimize=True)


def draw_columns(slug: str, accent: str, spec: dict) -> None:
    cols = spec["items"]
    max_items = max(len(c[2]) for c in cols)
    h = 150 + 70 + max_items * 40 + 100
    img, d = _scheme_canvas(spec["title"], accent, h)
    col_w, gap = 352, 24
    for i, (head, color, entries) in enumerate(cols):
        x = 48 + i * (col_w + gap)
        y = 130
        body_h = 70 + max_items * 40 + 20
        d.rounded_rectangle([x, y, x + col_w, y + body_h], radius=12, fill="#FFFFFF",
                            outline="#E3DFD6", width=2)
        d.rounded_rectangle([x, y, x + col_w, y + 56], radius=12, fill=color)
        d.rectangle([x, y + 40, x + col_w, y + 56], fill=color)
        d.text((x + 20, y + 15), head, font=font(24, True), fill="#FFFFFF")
        ty = y + 78
        for entry in entries:
            d.ellipse([x + 22, ty + 9, x + 32, ty + 19], fill=color)
            d.text((x + 44, ty), _fit(d, entry, font(20), col_w - 70), font=font(20), fill=INK)
            ty += 40
    _scheme_note(d, spec.get("note", ""), 130 + 70 + max_items * 40 + 34, accent)
    img.save(INLINE / f"{slug}-scheme.png", "PNG", optimize=True)


SCHEMES = {"steps": draw_steps, "layers": draw_layers, "bars": draw_bars, "columns": draw_columns}


# ─── таблица-картинка ────────────────────────────────────────────────

def make_table_image(slug: str, cat: str, spec: dict) -> None:
    _, accent, _ = CAT_COLORS[cat]
    headers, rows = spec["headers"], spec["rows"]
    pad, row_h = 28, 52
    col_w = (1200 - pad * 2) // len(headers)
    h = pad * 2 + row_h * (1 + len(rows)) + 40
    img = Image.new("RGB", (1200, h), BG)
    d = ImageDraw.Draw(img)
    d.rectangle([0, 0, 1200, pad + row_h], fill=accent)
    for i, head in enumerate(headers):
        d.text((pad + i * col_w + 14, pad + 15),
               _fit(d, head, font(21, True), col_w - 28), font=font(21, True), fill="#FFFFFF")
    for r, row in enumerate(rows):
        y = pad + row_h * (r + 1)
        if r % 2 == 0:
            d.rectangle([0, y, 1200, y + row_h], fill="#F1EFE9")
        for c, cell in enumerate(row):
            d.text((pad + c * col_w + 14, y + 15),
                   _fit(d, str(cell), font(20), col_w - 28), font=font(20), fill=INK)
    caption = spec.get("caption", "")
    if caption:
        d.text((pad, h - 34), _fit(d, caption, font(18), 1140), font=font(18), fill=MUTED)
    img.save(INLINE / f"{slug}-table.png", "PNG", optimize=True)


# ─── регистрация в реестрах ──────────────────────────────────────────

def php_q(s: str) -> str:
    return s.replace("\\", "\\\\").replace("'", "\\'")


def insert_after(path: Path, anchor: str, block: str) -> bool:
    text = path.read_text(encoding="utf-8")
    idx = text.find(anchor)
    if idx == -1:
        return False
    cut = text.index("\n", idx) + 1
    path.write_text(text[:cut] + block + text[cut:], encoding="utf-8")
    return True


def registered(path: Path, slug: str) -> bool:
    return f"'{slug}'" in path.read_text(encoding="utf-8")


def main() -> int:
    missing = [a for a in ARTS if not (ARTICLES / a["cat"] / f"{a['slug']}.html").is_file()]
    if missing:
        print("НЕТ ФАЙЛОВ СТАТЕЙ:", ", ".join(a["slug"] for a in missing))
        return 1

    COVERS.mkdir(parents=True, exist_ok=True)
    INLINE.mkdir(parents=True, exist_ok=True)

    meta_php = ROOT / "includes" / "all-articles-meta.php"
    article_php = ROOT / "article.php"
    rss_php = ROOT / "rss.php"
    search_php = ROOT / "search.php"
    sitemap = ROOT / "sitemap.xml"

    meta_lines, data_lines, rss_lines, search_lines, sitemap_lines = [], [], [], [], []

    for i, a in enumerate(ARTS):
        slug, cat = a["slug"], a["cat"]
        accent = CAT_COLORS[cat][1]
        cat_label = CAT_COLORS[cat][2]

        make_cover(slug, cat, a["short"])
        SCHEMES[a["scheme"]["kind"]](slug, accent, a["scheme"])
        make_table_image(slug, cat, a["table"])
        print(f"  ▸ {slug}: обложка + схема ({a['scheme']['kind']}) + таблица")

        t, dsc = php_q(a["title"]), php_q(a["desc"])

        if not registered(meta_php, slug):
            meta_lines.append(
                f"  '{slug}' => ['cat' => '{cat}', 'title' => '{t}', 'desc' => '{dsc}', "
                f"'date' => '{DATE}', 'readTime' => '{a['readTime']}'],\n"
            )
        if not registered(article_php, slug):
            data_lines.append(
                f"  '{slug}' => [\n"
                f"    'title'    => '{t}',\n"
                f"    'catSlug'  => '{cat}',\n"
                f"    'catLabel' => '{cat_label}',\n"
                f"    'date'     => '{DATE}',\n"
                f"    'author'   => '{AUTHORS[cat]}',\n"
                f"    'readTime' => '{a['readTime']}',\n"
                f"    'desc'     => '{dsc}',\n"
                f"  ],\n"
            )
        if not registered(rss_php, slug):
            hh, mm = 9 + (i * 20) // 60, (i * 20) % 60
            rss_lines.append(
                f"  ['slug' => '{slug}', 'title' => '{t}', 'desc' => '{php_q(a['desc'][:88])}', "
                f"'date' => '{RSS_DAY} {hh:02d}:{mm:02d}:00 +0300', 'cat' => '{cat_label}'],\n"
            )
        if not registered(search_php, slug):
            search_lines.append(
                f"  ['slug' => '{slug}', 'cat' => '{cat}', 'catLabel' => '{cat_label}', "
                f"'title' => '{t}', 'desc' => '{dsc}'],\n"
            )
        if f"/article/{slug}/" not in sitemap.read_text(encoding="utf-8"):
            sitemap_lines.append(
                f"  <url><loc>https://prodom-expert.ru/article/{slug}/</loc>"
                f"<changefreq>monthly</changefreq><priority>0.8</priority>"
                f"<lastmod>{ISO}</lastmod></url>\n"
            )

    targets = [
        (meta_php, "  return [", meta_lines, "all-articles-meta.php"),
        (article_php, "$articlesData = [", data_lines, "article.php"),
        (rss_php, "$articles = [", rss_lines, "rss.php"),
        (search_php, "$allArticles = [", search_lines, "search.php"),
    ]
    for path, anchor, lines, label in targets:
        if not lines:
            print(f"  = {label}: всё уже зарегистрировано")
            continue
        if not insert_after(path, anchor, "".join(lines)):
            print(f"  ! ЯКОРЬ НЕ НАЙДЕН в {label}: {anchor!r}")
            return 1
        print(f"  + {label}: {len(lines)} записей")

    if sitemap_lines:
        xml = sitemap.read_text(encoding="utf-8")
        sitemap.write_text(
            xml.replace("</urlset>", "".join(sitemap_lines) + "</urlset>"), encoding="utf-8"
        )
        print(f"  + sitemap.xml: {len(sitemap_lines)} записей")
    else:
        print("  = sitemap.xml: всё уже зарегистрировано")

    print(f"\nГотово: {len(ARTS)} статей, дата {DATE}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
