#!/usr/bin/env python3
"""Публикация партии статей от 31 июля 2026.

Тексты статей лежат в articles/<cat>/<slug>.html.
Скрипт рисует обложку, схему-инфографику и таблицу-картинку для каждой статьи,
регистрирует статьи во всех реестрах сайта и освежает lastmod листингов в картах.
Идемпотентен: уже зарегистрированные slug'и пропускаются.
"""
from __future__ import annotations

import os
import re
import sys
import textwrap
from pathlib import Path

from PIL import Image, ImageDraw, ImageFont

ROOT = Path(__file__).resolve().parents[1]
ARTICLES = ROOT / "articles"
COVERS = ROOT / "assets" / "img" / "articles"
INLINE = ROOT / "assets" / "img" / "inline"

DATE = "31 июля 2026"
ISO = "2026-07-31"
RSS_DAY = "Fri, 31 Jul 2026"

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
        slug="poly-po-lagam-v-kvartire", cat="remont",
        title="Полы по лагам в квартире: пирог, шаг лаг и защита от скрипа",
        desc="Когда лаги выгоднее стяжки, состав пирога, шаг под толщину настила, виброразвязка и пять причин скрипа.",
        readTime="18 мин", short="Полы по лагам",
        scheme=dict(kind="layers", title="Пирог пола по лагам",
                    items=[("Покрытие: ламинат, кварцвинил, паркет", "Укладывается по готовому настилу"),
                           ("Фанера 12–18 мм в два слоя вразбежку", "Стыки со смещением от 200 мм"),
                           ("Минеральная вата 35–50 кг/м³", "Гасит звук в полости, не утепляет"),
                           ("Лаги: брус влажностью 12–14%", "Шаг под толщину настила"),
                           ("Виброопоры под каждой точкой", "Резина или пробка, 8–15 мм"),
                           ("Перекрытие с гидроизоляцией", "Плёнка 200 мкм с заходом на стены")],
                    note="Лаги не крепят к перекрытию насквозь — иначе каждый шаг слышат соседи"),
        table=dict(headers=["Настил", "Шаг лаг", "Где применяют"],
                   rows=[["Фанера 12 мм в два слоя", "400 мм", "Жилые комнаты, тонкий пирог"],
                         ["Фанера 15 мм в два слоя", "500 мм", "Универсальный вариант"],
                         ["Фанера 18 мм в два слоя", "600 мм", "Под тяжёлую мебель"],
                         ["Шпунтованная доска 36 мм", "600 мм", "Дощатый пол под лак"],
                         ["ОСП-3 18 мм в два слоя", "500 мм", "Бюджетный вариант"]],
                   caption="Пирог по лагам — 2600–5000 ₽/м² против 1800–3200 ₽ у мокрой стяжки"),
    ),
    dict(
        slug="ciklevka-parketa-svoimi-rukami", cat="remont",
        title="Циклёвка паркета: когда возможна, порядок работ и чем покрывать",
        desc="Толщина слоя износа у разных покрытий, шаги зернистости, шпатлевание своей пылью, выбор между лаком и маслом.",
        readTime="19 мин", short="Циклёвка паркета",
        scheme=dict(kind="steps", title="Циклёвка по шагам",
                    items=["Демонтаж плинтусов, добой гвоздей",
                           "Замена битых плашек",
                           "Грубая шлифовка P40–P60 под углом 45°",
                           "Шпатлевание пылью со связующим",
                           "Тонкая шлифовка P80 → P120",
                           "Края и углы «сапожком»",
                           "Обеспыливание и грунт",
                           "Лак в 3 слоя с межслойной шлифовкой"],
                    note="Пропуск шага зернистости не догоняется: риски проявятся под лаком"),
        table=dict(headers=["Покрытие", "Слой износа", "Сколько циклёвок"],
                   rows=[["Штучный паркет 15–22 мм", "6–8 мм", "5–8 раз"],
                         ["Массивная доска", "6–10 мм", "5–8 раз"],
                         ["Паркетная доска, шпон 4 мм", "2,5–3 мм", "2–3 раза"],
                         ["Паркетная доска, шпон 2,5 мм", "1–1,5 мм", "1 раз, аккуратно"],
                         ["Паркетная доска, шпон 0,6 мм", "нет", "Нельзя"],
                         ["Ламинат", "нет", "Нельзя, это не дерево"]],
                   caption="Своими силами 1250–2050 ₽/м², бригада 1200–2500 ₽/м² — разница почти нулевая"),
    ),
    dict(
        slug="zamena-uplotnitelya-okon", cat="okna",
        title="Замена уплотнителя пластиковых окон: диагностика и монтаж за час",
        desc="Как отличить износ резинки от разрегулированной фурнитуры, чем EPDM лучше TPE, подбор по образцу и укладка без натяжения.",
        readTime="17 мин", short="Замена уплотнителя окон",
        scheme=dict(kind="steps", title="Замена уплотнителя по шагам",
                    items=["Тест бумагой по всему периметру",
                           "Замер периметра створки и рамы",
                           "Извлечение старого уплотнителя",
                           "Очистка и обезжиривание паза",
                           "Укладка от середины верха, без натяжения",
                           "Стык на клей, проверка прижима"],
                    note="Натянутая при укладке резинка через месяц стянется и откроет углы"),
        table=dict(headers=["Симптом", "Причина", "Что делать"],
                   rows=[["Дует в одной точке", "Разрегулирован прижим", "Подкрутить эксцентрики"],
                         ["Дует по всему периметру", "Резинка потеряла упругость", "Замена контура"],
                         ["Резинка жёсткая, в трещинах", "Возрастной износ", "Замена"],
                         ["Резинка вылезает из паза", "Растянулась при укладке", "Замена, не подрезка"],
                         ["Конденсат по периметру", "Продувание и слабый приток", "Замена + вентиляция"],
                         ["Створка цепляет раму", "Провисание, не уплотнитель", "Регулировка петель"]],
                   caption="Материал на окно 800–2000 ₽, работа мастера 1500–3500 ₽"),
    ),
    dict(
        slug="dveri-nevidimki-skrytogo-montazha", cat="okna",
        title="Двери-невидимки: скрытая коробка, монтаж и реальная цена",
        desc="Как устроена скрытая система, типы открывания, что заложить до штукатурки, армирование по полке и когда приём не работает.",
        readTime="17 мин", short="Двери-невидимки",
        scheme=dict(kind="steps", title="Монтаж скрытой двери",
                    items=["Проём в чистовых размерах, жёсткое обрамление",
                           "Коробка по уровню, диагонали до 2 мм",
                           "Анкеровка через прокладки, распорки",
                           "Пена с низким расширением",
                           "Штукатурка на полку с армированием сеткой",
                           "Шпаклёвка в плоскость стены",
                           "Навеска полотна, регулировка зазора 3 мм"],
                    note="Коробка ставится до штукатурки — переиграть решение после отделки нельзя"),
        table=dict(headers=["Тип открывания", "Как выглядит", "Особенности"],
                   rows=[["От себя (наружное)", "Заподлицо со стороны комнаты", "С обратной видна коробка"],
                         ["На себя (внутреннее)", "Заподлицо со стороны коридора", "Чаще для санузлов"],
                         ["Двусторонняя", "Заподлицо с обеих сторон", "Дороже на 30–60%"],
                         ["Комплект под покраску", "Красится вместе со стеной", "25 000–60 000 ₽"],
                         ["Комплект в эмали", "Заводская окраска", "45 000–120 000 ₽"]],
                   caption="Одна дверь-невидимка с монтажом — 40 000–150 000 ₽ против 12 000–35 000 ₽ у обычной"),
    ),
    dict(
        slug="povysitelnyy-nasos-v-kvartire", cat="santehnika",
        title="Повысительный насос в квартире: когда нужен и как врезать",
        desc="Замер давления манометром, пять причин слабого напора дешевле насоса, типы насосов, байпас и защита от сухого хода.",
        readTime="17 мин", short="Повысительный насос",
        scheme=dict(kind="steps", title="Порядок действий при слабом напоре",
                    items=["Замер манометром ночью и в час пик",
                           "Проверка косого фильтра на вводе",
                           "Проверка аэраторов и вводного крана",
                           "Проверка настройки редуктора давления",
                           "Подбор насоса по недостающему давлению",
                           "Врезка после счётчика с байпасом"],
                    note="Врезать насос в общий стояк нельзя: это ухудшает напор у соседей"),
        table=dict(headers=["Прибор", "Минимум", "Комфортно"],
                   rows=[["Смеситель раковины", "0,5 бар", "1,5–2 бар"],
                         ["Душ, тропический душ", "1,5 бар", "2,5–3 бар"],
                         ["Стиральная машина", "1 бар", "2 бар"],
                         ["Посудомоечная машина", "1 бар", "2 бар"],
                         ["Газовая колонка", "0,3–0,5 бар", "1,5 бар"],
                         ["Гидромассажная ванна", "2 бар", "3–4 бар"]],
                   caption="Норма на вводе в квартиру — 3–6 бар; ниже 1,5 бар постоянно — повод для насоса"),
    ),
    dict(
        slug="ustanovka-rakoviny-i-sifona", cat="santehnika",
        title="Установка раковины и сборка сифона своими руками",
        desc="Высота 80–85 см, крепёж под конкретную стену, сборка сифона на сухие прокладки и проверка наливом до отделки.",
        readTime="17 мин", short="Установка раковины",
        scheme=dict(kind="steps", title="Установка раковины по шагам",
                    items=["Разметка высоты и точек крепления",
                           "Сверление плитки без удара",
                           "Крепёж: анкеры или закладная в ГКЛ",
                           "Смеситель ставится до навески",
                           "Навеска чаши, затяжка от руки",
                           "Сборка сифона на конусные прокладки",
                           "Проверка наливом с бумагой под стыками"],
                    note="Перетянутая гайка — причина трещины по фаянсу и течи сифона"),
        table=dict(headers=["Тип раковины", "Крепление", "Особенности"],
                   rows=[["Подвесная на кронштейнах", "Анкеры или шпильки", "Нужна прочная стена"],
                         ["С пьедесталом", "Стена + пьедестал", "Прячет сифон"],
                         ["На тумбе", "На корпус тумбы", "Хранение, боится влаги"],
                         ["Накладная на столешницу", "На герметик", "Высокая посадка"],
                         ["Врезная в столешницу", "Вырез по шаблону", "Нужна влагостойкая основа"],
                         ["Над стиральной машиной", "Кронштейны, плоский сифон", "Экономит метр площади"]],
                   caption="Работа мастера 3500–7000 ₽, самостоятельно — 1,5–3 часа"),
    ),
    dict(
        slug="zamena-provodki-v-chastnom-dome", cat="elektrika",
        title="Замена проводки в частном доме: щиты, заземление и линии на улицу",
        desc="Чем электрика дома отличается от квартирной: три щита, собственный контур заземления, разделение PEN и прокладка по горючим конструкциям.",
        readTime="21 мин", short="Проводка в частном доме",
        scheme=dict(kind="steps", title="Порядок замены проводки в доме",
                    items=["Проект и расчёт мощности",
                           "Согласование ввода с сетевой организацией",
                           "Контур заземления до отделки",
                           "Вводной щит с учётом на границе участка",
                           "Главный щит: разделение PEN, реле, ОПН",
                           "Черновая разводка по этажам",
                           "Сборка этажных щитов",
                           "Замеры изоляции и контура, проверка УЗО"],
                    note="Разделение PEN на PE и N выполняется ровно в одной точке — на вводной шине ГРЩ"),
        table=dict(headers=["Линия", "Кабель", "Автомат"],
                   rows=[["Ввод 15 кВт однофазный", "10–16 мм²", "63 А"],
                         ["Питание этажного щита", "6–10 мм²", "32–40 А"],
                         ["Розеточные группы", "2,5 мм²", "16 А + УЗО 30 мА"],
                         ["Освещение", "1,5 мм²", "10 А"],
                         ["Электрокотёл 6–9 кВт", "6 мм²", "32–40 А"],
                         ["Гараж или баня по улице", "4–10 мм² бронированный", "по нагрузке + УЗО"]],
                   caption="Дом 150 м² под ключ — 550 000–1 100 000 ₽ вместе с контуром и щитами"),
    ),
    dict(
        slug="ibp-i-generator-dlya-doma", cat="elektrika",
        title="ИБП и генератор для дома: что резервировать и какой мощности",
        desc="Три уровня резерва, пусковые токи, чистая синусоида для котла, подключение через рубильник или АВР и связка ИБП с генератором.",
        readTime="19 мин", short="ИБП и генератор",
        scheme=dict(kind="columns", title="Три уровня резервирования",
                    items=[("Минимальный, 0,3–0,8 кВт", "#3B7A5A",
                            ["Газовый котёл", "Циркуляционные насосы",
                             "Роутер и связь", "Аварийный свет", "Закрывает 90% случаев"]),
                           ("Комфортный, 2,5–4 кВт", "#C9A227",
                            ["Плюс холодильник", "Скважинный насос",
                             "Розетки кухни", "Свет по всему дому"]),
                           ("Полный, 6–10 кВт", "#B04A3A",
                            ["Плюс стиральная машина", "Бытовая техника",
                             "Часть мощных приборов", "Дорого и редко нужно"])],
                    note="Источник подбирают по пиковой мощности с пусковыми токами, а не по сумме номиналов"),
        table=dict(headers=["Параметр генератора", "Что выбирать", "Почему"],
                   rows=[["Тип двигателя", "Бензин до 5 кВт, дизель выше", "Ресурс дизеля втрое выше"],
                         ["Форма напряжения", "Инверторный или с AVR", "Без стабилизации котёл сгорает"],
                         ["Запуск", "Электростартер, лучше автозапуск", "Ручной старт ночью в мороз"],
                         ["Длительная нагрузка", "Не более 70–80% номинала", "На пределе ресурс падает"],
                         ["Подключение", "Рубильник или АВР", "«Розетка в розетку» опасна"]],
                   caption="ИБП с чистым синусом на котельную — от 60 000 ₽, дизель с автозапуском — от 150 000 ₽"),
    ),
    dict(
        slug="kak-vybrat-krovat-i-matras", cat="interer",
        title="Как выбрать кровать и матрас: размеры, основание и жёсткость",
        desc="Размер под комнату и рост, реечное основание против щита, жёсткость по весу спящего и проверка матраса ладонью под поясницей.",
        readTime="18 мин", short="Кровать и матрас",
        scheme=dict(kind="bars", title="Опорные размеры спальной зоны, см",
                    items=[("Длина матраса сверх роста", 15, "рост + 15"),
                           ("Проход вдоль кровати", 70, "60–75"),
                           ("Высота посадки от пола", 55, "50–60"),
                           ("Ширина места на человека", 80, "80–90"),
                           ("Проход в изножье", 70, "от 70")],
                    note="Плотность пены в матрасе — от 30–35 кг/м³, иначе просадка за пару лет"),
        table=dict(headers=["Вес спящего", "Жёсткость", "Что подойдёт"],
                   rows=[["до 55 кг", "Мягкий", "Пена с memory, латекс"],
                         ["55–90 кг", "Средний", "Независимые пружины + латекс"],
                         ["90–110 кг", "Средне-жёсткий", "Усиленный блок, тонкий кокос"],
                         ["свыше 110 кг", "Жёсткий усиленный", "Блок 550+ пружин на м²"],
                         ["Пара с разницей 30+ кг", "Беспружинный", "Латекс или пена высокой плотности"]],
                   caption="Матрас меняют раз в 8–10 лет независимо от вида наполнителя"),
    ),
    dict(
        slug="obedennaya-zona-stol-i-stulya", cat="interer",
        title="Обеденная зона: размер стола, проходы, стулья и свет над столом",
        desc="Пять чисел геометрии обеденной группы, размер стола под число мест, высоты стойки и стула, подвес светильника 70–80 см.",
        readTime="18 мин", short="Обеденная зона",
        scheme=dict(kind="bars", title="Пять чисел обеденной зоны, см",
                    items=[("Место на человека по периметру", 65, "60–70"),
                           ("Глубина места на прибор", 40, "40"),
                           ("Проход за отодвинутым стулом", 85, "75–90"),
                           ("Просвет сиденье — столешница", 30, "28–32"),
                           ("Подвес светильника над столом", 75, "70–80")],
                    note="Стол «на шестерых» из каталога честно вмещает четверых — считайте по периметру"),
        table=dict(headers=["Мест за столом", "Прямоугольный", "Круглый"],
                   rows=[["2–3", "80 × 80 см", "⌀ 80–90 см"],
                         ["4", "120 × 80 см", "⌀ 100–110 см"],
                         ["6", "160 × 90 см", "⌀ 130–140 см"],
                         ["8", "200 × 100 см", "⌀ 150–160 см"],
                         ["10–12", "260 × 100 см", "лучше овал"]],
                   caption="Под стол 160×90 нужно пятно примерно 310×240 см вместе с проходами"),
    ),
    dict(
        slug="remont-pod-sdachu-v-arendu", cat="sovety",
        title="Ремонт под сдачу в аренду: где вкладываться, а где экономить",
        desc="Три категории вложений, износостойкая отделка, расчёт окупаемости по ставке аренды и решения, экономящие при смене жильцов.",
        readTime="19 мин", short="Ремонт под сдачу",
        scheme=dict(kind="columns", title="Куда вкладываться в арендной квартире",
                    items=[("Экономить нельзя", "#B04A3A",
                            ["Проводка и щит с УЗО", "Трубы без стыков в стенах",
                             "Гидроизоляция санузла", "Защита от протечек", "Окна"]),
                           ("Средний сегмент", "#C9A227",
                            ["Ламинат 32–33 класса", "Керамогранит на кухне",
                             "Двери с фурнитурой", "Модульная кухня",
                             "Техника с сервисом"]),
                           ("Эконом оправдан", "#4F6B85",
                            ["Моющаяся краска", "Виниловые обои",
                             "Светильники", "Мебель", "Текстиль и декор"])],
                    note="Качество элемента обратно пропорционально лёгкости его замены"),
        table=dict(headers=["Уровень ремонта", "Стоимость, 38 м²", "Что даёт"],
                   rows=[["Косметика без инженерии", "250 000–450 000 ₽", "Ставка +5–10%"],
                         ["Капитальный под аренду", "700 000–1 200 000 ₽", "Ставка +20–30%"],
                         ["Ремонт «как для себя»", "от 1 500 000 ₽", "Ставка +30–35%, хуже окупаемость"],
                         ["Мебель и техника", "250 000–500 000 ₽", "Обязательный минимум"]],
                   caption="Разумный предел вложений — 20–25 месячных ставок аренды"),
    ),
    dict(
        slug="sroki-remonta-kvartiry-po-etapam", cat="sovety",
        title="Сроки ремонта квартиры по этапам: реальный календарь работ",
        desc="Сколько занимает каждый этап и сколько — обязательное ожидание, что можно вести параллельно и где график ломается на практике.",
        readTime="19 мин", short="Сроки ремонта",
        scheme=dict(kind="bars", title="Обязательное ожидание, дней",
                    items=[("Стяжка до укладки покрытия", 25, "21–28"),
                           ("Изготовление кухни и дверей", 42, "21–56"),
                           ("Просушка штукатурки", 10, "7–14"),
                           ("Гидроизоляция между слоями", 2, "1–2"),
                           ("Акклиматизация покрытия пола", 2, "2")],
                    note="Ожидание не ускоряется деньгами и числом рабочих — только совмещением работ"),
        table=dict(headers=["Тип ремонта", "40–60 м²", "80–120 м²"],
                   rows=[["Косметический", "3–5 недель", "5–8 недель"],
                         ["Капитальный в новостройке", "3–4 месяца", "4–6 месяцев"],
                         ["Капитальный во вторичке", "4–5 месяцев", "5–8 месяцев"],
                         ["С перепланировкой", "6–9 месяцев", "8–12 месяцев"],
                         ["Только санузел", "3–5 недель", "3–5 недель"],
                         ["Только кухня", "4–7 недель", "5–8 недель"]],
                   caption="Кухню и двери заказывают в первый месяц: их срок изготовления и есть критический путь"),
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


# ─── актуализация lastmod в картах сайта ─────────────────────────────

def refresh_lastmod(cats: set[str]) -> None:
    """Проставляет дату партии там, где содержимое реально изменилось.

    Записи статей скрипт добавляет сам, а листинги остаются со старой датой:
    главная, архив и страницы категорий показывают новые материалы, но в карте
    у них висит lastmod прошлых месяцев. Обходчик перестаёт им доверять и
    заходит реже. Даты статических страниц (about, contacts, privacy) не
    трогаем: их содержимое не менялось, и врать в lastmod вредно.

    Индекс карт получает максимальную дату из своей дочерней карты.
    """
    # 1) главная, архив и затронутые категории в sitemap.xml
    listings = ["", "articles.php"] + [f"category/{c}/" for c in sorted(cats)]
    sitemap = ROOT / "sitemap.xml"
    xml = sitemap.read_text(encoding="utf-8")
    touched = 0
    for path in listings:
        pattern = re.compile(
            rf"(<loc>https://prodom-expert\.ru/{re.escape(path)}</loc>.*?<lastmod>)\d{{4}}-\d\d-\d\d(</lastmod>)"
        )
        xml, n = pattern.subn(rf"\g<1>{ISO}\g<2>", xml)
        touched += n
    sitemap.write_text(xml, encoding="utf-8")

    # 2) фиды: главная и RSS меняются с каждой партией
    feeds = ROOT / "sitemap-feeds.xml"
    if feeds.is_file():
        feeds.write_text(
            re.sub(r"<lastmod>\d{4}-\d\d-\d\d</lastmod>", f"<lastmod>{ISO}</lastmod>",
                   feeds.read_text(encoding="utf-8")),
            encoding="utf-8",
        )

    # 3) индекс карт — по самой свежей дате внутри каждой дочерней карты
    index = ROOT / "sitemap_index.xml"
    if index.is_file():
        idx = index.read_text(encoding="utf-8")
        for child in ("sitemap.xml", "sitemap-feeds.xml"):
            child_path = ROOT / child
            if not child_path.is_file():
                continue
            dates = re.findall(r"<lastmod>(\d{4}-\d\d-\d\d)</lastmod>",
                               child_path.read_text(encoding="utf-8"))
            if not dates:
                continue
            idx = re.sub(
                rf"(<loc>https://prodom-expert\.ru/{re.escape(child)}</loc>\s*<lastmod>)\d{{4}}-\d\d-\d\d",
                rf"\g<1>{max(dates)}", idx,
            )
        index.write_text(idx, encoding="utf-8")

    print(f"  + lastmod {ISO}: {touched} листингов (главная, архив, категории: "
          f"{', '.join(sorted(cats))}) + фиды и индекс карт")


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

    refresh_lastmod({a["cat"] for a in ARTS})

    print(f"\nГотово: {len(ARTS)} статей, дата {DATE}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
