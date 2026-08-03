#!/usr/bin/env python3
"""Публикация партии статей от 30 июля 2026 (по 2 больших материала в каждую категорию).

Тексты статей лежат в articles/<cat>/<slug>.html.
Скрипт рисует обложку, схему-инфографику и таблицу-картинку для каждой статьи,
затем регистрирует статьи во всех реестрах сайта (all-articles-meta.php, article.php,
rss.php, search.php, sitemap.xml). Идемпотентен: уже зарегистрированные slug'и пропускаются.
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

DATE = "30 июля 2026"
ISO = "2026-07-30"
RSS_DAY = "Thu, 30 Jul 2026"

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
        slug="potolok-pod-pokrasku-vyravnivanie", cat="remont",
        title="Потолок под покраску: выравнивание, шпаклёвка и покраска без полос",
        desc="Когда потолок пригоден под покраску, армирование рустов, три слоя шпаклёвки, контроль боковым светом и покраска «по мокрому».",
        readTime="20 мин", short="Потолок под покраску",
        scheme=dict(kind="layers", title="Пирог потолка под покраску",
                    items=[("Краска: 2–3 слоя глубокоматовой", "110–140 мл/м² на слой, «по мокрому»"),
                           ("Финишная шпаклёвка, 2–3 слоя", "По 0,5–1,5 мм, шлифовка P180–P240"),
                           ("Армирование рустов и стыков", "Расшивка 5–8 мм, серпянка или лента"),
                           ("Штукатурка при перепаде свыше 5 мм", "По маякам, слой до 15–20 мм за проход"),
                           ("Грунт: бетонконтакт или глубокий", "100–200 г/м², обязательный слой"),
                           ("Плита перекрытия без побелки", "Побелку размывают в два подхода")],
                    note="Контроль боковым светом с четырёх сторон — до покраски, а не после"),
        table=dict(headers=["Этап", "Материал или инструмент", "Расход и режим"],
                   rows=[["Грунт по бетону", "Глубокопроникающий, бетонконтакт", "100–200 г/м²"],
                         ["Заделка рустов", "Ремонтная смесь + серпянка", "Лента 45–50 мм"],
                         ["Стартовая шпаклёвка", "Гипсовая, слой до 5 мм", "1,0–1,2 кг/м² на 1 мм"],
                         ["Финишная, 2–3 слоя", "Полимерная или готовая паста", "0,5–1,0 кг/м² на слой"],
                         ["Шлифовка", "P150 → P220, лампа сбоку", "2 прохода с проверкой"],
                         ["Краска", "Глубокоматовая для потолков", "110–140 мл/м² на слой"]],
                   caption="Работы «под ключ» по бетону — 1800–3500 ₽/м² с материалами, 2026 год"),
    ),
    dict(
        slug="proem-v-nesushchey-stene-usilenie", cat="remont",
        title="Проём в несущей стене: усиление рамой, согласование и порядок работ",
        desc="Как определить несущую стену, что считает конструктор, из чего состоит стальная рама, сколько стоят все этапы и чем грозит незаконный проём.",
        readTime="21 мин", short="Проём в несущей стене",
        scheme=dict(kind="steps", title="Порядок устройства проёма",
                    items=["Техпаспорт и поэтажный план БТИ",
                           "Обследование и техническое заключение",
                           "Проект с расчётом усиления",
                           "Согласование в жилищной инспекции",
                           "Подготовка и временные подпорки",
                           "Алмазная резка и монтаж рамы",
                           "Акты скрытых работ и авторский надзор",
                           "Приёмка и новый техплан в ЕГРН"],
                    note="Резать начинают на шаге 6 — после разрешения, а не до"),
        table=dict(headers=["Этап", "Что входит", "Стоимость, 2026"],
                   rows=[["Техническое заключение", "Обследование конструкций", "25 000–60 000 ₽"],
                         ["Проект усиления", "Расчёт рамы и узлов", "30 000–80 000 ₽"],
                         ["Согласование", "Сам или через посредника", "0–70 000 ₽"],
                         ["Алмазная резка", "Проём 900×2100 мм", "25 000–50 000 ₽"],
                         ["Стальная рама", "Металл и монтаж", "45 000–110 000 ₽"],
                         ["Мусор и отделка откосов", "Вывоз, штукатурка", "18 000–50 000 ₽"]],
                   caption="Итого по проёму в кирпичной стене 380 мм — 150 000–400 000 ₽"),
    ),
    dict(
        slug="montazh-mezhkomnatnoy-dveri", cat="okna",
        title="Монтаж межкомнатной двери: сборка коробки, петли, доборы и наличники",
        desc="Замер по чистовому полу, сборка коробки с диагоналями до 2 мм, петлевая стойка по вертикали, распорки под пену и врезка фурнитуры.",
        readTime="19 мин", short="Монтаж межкомнатной двери",
        scheme=dict(kind="steps", title="Монтаж двери по шагам",
                    items=["Замер проёма по чистовому полу",
                           "Сборка коробки на полу, диагонали до 2 мм",
                           "Петлевая стойка по вертикали на клиньях",
                           "Вторая стойка по полотну, зазор 3 мм",
                           "Распорки и пена с низким расширением",
                           "Доборы, наличники, регулировка петель"],
                    note="Полотно в полуоткрытом положении не двигается само — только тогда пенят"),
        table=dict(headers=["Дефект", "Причина", "Что делать"],
                   rows=[["Дверь открывается сама", "Коробка завалена от проёма", "Переставить до застывания пены"],
                         ["Зазор клином по высоте", "Стойка выставлена не по полотну", "Сдвинуть, перезапенить участок"],
                         ["Полотно цепляет по верху", "Провисание на двух петлях", "Отрегулировать или добавить третью"],
                         ["Стойки выгнуты внутрь", "Монтаж без распорок", "Вырезать пену, выставить заново"],
                         ["Язычок не попадает в планку", "Планка врезана по разметке", "Переврезать по факту"],
                         ["Щели в углах наличников", "Рез не под 45° или кривая стена", "Подрезать, стену подшпаклевать"]],
                   caption="Зазоры: 3 мм по полотну, 10–15 мм до чистого пола, 10–15 мм под пену"),
    ),
    dict(
        slug="bezopasnost-okon-dlya-detey", cat="okna",
        title="Безопасность окон для детей: ограничители, замки и сетки-антикошка",
        desc="Почему режим проветривания и москитная сетка не защищают, какие устройства работают по возрасту, монтаж замка в армирующий профиль и чек-лист по квартире.",
        readTime="17 мин", short="Безопасность окон",
        scheme=dict(kind="columns", title="Что реально защищает ребёнка",
                    items=[("Работает надёжно", "#3B7A5A",
                            ["Ручка с ключом", "Замок-блокиратор с ключом",
                             "Ограничитель в фурнитуре", "Съёмная ручка",
                             "Решётка с распашной секцией", "Мебель отодвинута от окна"]),
                           ("Только до 3–4 лет", "#C9A227",
                            ["Гребёнка-ограничитель", "Накладка на ручку",
                             "Сетка «антикошка»", "Договорённость с ребёнком"]),
                           ("Не защищает вовсе", "#B04A3A",
                            ["Москитная сетка", "Режим проветривания",
                             "Высокий подоконник", "Закрытая штора",
                             "«Он у нас спокойный»"])],
                    note="Всё, что открывается без ключа, ребёнок освоит за одно лето"),
        table=dict(headers=["Устройство", "Как работает", "Цена с монтажом"],
                   rows=[["Ручка с ключом", "Запирается в закрытом положении", "900–2 500 ₽"],
                         ["Замок-блокиратор", "Ригель или тросик на раме", "1 200–3 500 ₽"],
                         ["Гребёнка", "Фиксирует угол открывания", "400–1 200 ₽"],
                         ["Ограничитель в фурнитуре", "Не даёт открыться шире 10 см", "1 500–4 000 ₽"],
                         ["Сетка «антикошка»", "Металлическая рама на саморезах", "2 500–6 000 ₽"],
                         ["Решётка, шаг прутка ≤100 мм", "Барьер с распашной секцией", "от 5 000 ₽"]],
                   caption="Полная защита квартиры с четырьмя окнами — 5 000–12 000 ₽"),
    ),
    dict(
        slug="shumoizolyaciya-kanalizacionnogo-stoyaka", cat="santehnika",
        title="Шумоизоляция канализационного стояка: почему слышно соседей и как заглушить",
        desc="Воздушный и структурный шум, упругие хомуты, обмотка мембраной, короб с минватой и пять ошибок, из-за которых шум остаётся.",
        readTime="19 мин", short="Шумоизоляция стояка",
        scheme=dict(kind="layers", title="Пирог шумоизоляции стояка",
                    items=[("Отделка: плитка по ГВЛ", "Ревизионный люк обязателен"),
                           ("Два слоя ГКЛВ или ГВЛ вразбежку", "Периметр — шов 3–5 мм на герметике"),
                           ("Минеральная вата 40–60 кг/м³", "Без пустот, без утрамбовки"),
                           ("Каркас на виброподвесах", "Не касается трубы нигде"),
                           ("Тяжёлая обмотка 3–8 кг/м²", "Все швы проклеены, раструбы тоже"),
                           ("Труба на хомутах с EPDM", "Развязка от стены и перекрытия")],
                    note="Сначала развязка крепежа, потом масса. Пена вокруг трубы делает хуже"),
        table=dict(headers=["Решение", "Толщина пирога", "Эффект и цена"],
                   rows=[["Упругие хомуты", "0 мм", "Убирает стуки, 1 500–4 000 ₽"],
                         ["Обмотка мембраной", "8–15 мм", "Гасит журчание, 3 000–7 500 ₽"],
                         ["Обмотка + короб с минватой", "80–120 мм", "Смыв не слышен, от 15 000 ₽"],
                         ["Малошумная труба + короб", "80–120 мм", "Тишина, от 35 000 ₽"],
                         ["Зашивка ГКЛ без изоляции", "60–80 мм", "Почти без эффекта, иногда резонанс"],
                         ["Пена вокруг трубы", "—", "Ухудшение: жёсткая связь с коробом"]],
                   caption="Цены 2026 года на санузел с одним стояком высотой 2,7 м"),
    ),
    dict(
        slug="vybor-i-ustanovka-vanny", cat="santehnika",
        title="Какую ванну выбрать: акрил, сталь, чугун, кварил — и как её установить",
        desc="Толщина акрила и стали, вес чугуна, дополнительные опоры под дно, проверка наливом и эластичное примыкание, сделанное по воде.",
        readTime="20 мин", short="Выбор и установка ванны",
        scheme=dict(kind="steps", title="Установка ванны по шагам",
                    items=["Гидроизоляция и плитка на стенах готовы",
                           "Сборка каркаса или ножек",
                           "Горизонт по уровню, борт 55–60 см",
                           "Слив-перелив и уклон 2–3 см на метр",
                           "Проверка наливом до перелива, 20–30 минут",
                           "Опоры под дно, шумоизоляция для стали",
                           "Примыкание по воде, экран с люком"],
                    note="Шов у стены делают при наполненной водой ванне — иначе он лопнет"),
        table=dict(headers=["Проблема", "Причина", "Решение"],
                   rows=[["Дно прогибается", "Тонкий акрил, нет опор", "Опоры на растворе или пена по воде"],
                         ["Шов у стены чернеет", "Не санитарный силикон, шов насухо", "Вырезать и переделать по воде"],
                         ["Гул при наборе воды", "Сталь без шумоизоляции", "Вибромембрана или пена снаружи"],
                         ["Вода уходит медленно", "Нет уклона, длинная гофра", "Переложить 2–3 см на метр"],
                         ["Запах из слива", "Сорванный гидрозатвор", "Проверить сифон и вентиляцию"],
                         ["Нет доступа к сифону", "Экран заложен наглухо", "Вырезать и поставить люк"]],
                   caption="Акрил от 5 мм, сталь от 2,5 мм, чугун 90–140 кг — три числа при выборе"),
    ),
    dict(
        slug="zashchita-ot-protechek-datchiki-krany", cat="elektrika",
        title="Защита от протечек: датчики, краны с приводом и схема монтажа",
        desc="Куда ставить датчики, какие краны врезать после счётчиков, отдельная линия 6–10 А с аккумулятором и чего система не умеет.",
        readTime="19 мин", short="Защита от протечек",
        scheme=dict(kind="steps", title="Как работает система",
                    items=["Датчик на полу замыкается водой",
                           "Сигнал уходит на контроллер",
                           "Контроллер закрывает оба крана на вводе",
                           "Сирена и уведомление в телефон",
                           "Профилактическая прокрутка кранов раз в месяц"],
                    note="Краны сохраняют положение без питания, поэтому нужен аккумулятор"),
        table=dict(headers=["Место установки датчика", "Что защищает", "Приоритет"],
                   rows=[["Под стиральной машиной", "Шланг, помпа, манжета люка", "Обязательно"],
                         ["Под мойкой на кухне", "Сифон, подводки, фильтр", "Обязательно"],
                         ["У унитаза и инсталляции", "Подводка, наливной клапан", "Обязательно"],
                         ["Под ванной или в душевой", "Сифон, трап, примыкание", "Обязательно"],
                         ["У коллектора и счётчиков", "Резьбы, фитинги, редуктор", "Желательно"],
                         ["У бойлера и радиаторов", "Обвязка (краны не перекроют)", "По ситуации"]],
                   caption="Комплект из двух кранов, контроллера и 4–5 датчиков — 15 000–35 000 ₽"),
    ),
    dict(
        slug="poisk-skrytoy-provodki-v-stene", cat="elektrika",
        title="Как найти провод в стене: детекторы, методы и сверление без аварии",
        desc="Нормируемые зоны прокладки, работа детектором при включённых автоматах, что мешает приборам и порядок из шести шагов перед сверлением.",
        readTime="19 мин", short="Поиск скрытой проводки",
        scheme=dict(kind="steps", title="Порядок перед сверлением",
                    items=["Отойти на 20 см от розеток и выключателей",
                           "Детектор при включённых автоматах, два направления",
                           "Перепроверить металлодетектором",
                           "Обесточить линию в щите",
                           "Ограничить глубину: первые 15–20 мм",
                           "Первые миллиметры — сверлением, без удара"],
                    note="Ёмкостной детектор видит только кабель под напряжением — автоматы включают"),
        table=dict(headers=["Прибор", "Что находит", "Глубина и цена"],
                   rows=[["Индикаторная отвёртка", "Провод под напряжением", "20–30 мм, 300–900 ₽"],
                         ["Детектор скрытой проводки", "Кабель под напряжением, металл", "50–80 мм, 1 500–8 000 ₽"],
                         ["Мультидетектор", "Кабель, арматура, трубы, пустоты", "100–150 мм, от 12 000 ₽"],
                         ["Металлодетектор", "Любой металл, в том числе без тока", "60–100 мм, от 2 000 ₽"],
                         ["Тепловизор", "Нагруженный кабель по нагреву", "20–30 мм, от 25 000 ₽"],
                         ["Трассоискатель", "Обесточенный кабель по сигналу", "до 200 мм, от 20 000 ₽"]],
                   caption="Ремонт перебитой линии — 3 000–20 000 ₽ плюс отделка участка"),
    ),
    dict(
        slug="kak-vybrat-divan", cat="interer",
        title="Как выбрать диван: механизм, каркас, наполнение и обивка",
        desc="Механизмы под ежедневный сон, плотность ППУ от 30 кг/м³, каркас из массива, стойкость обивки в циклах и замеры под раскладку.",
        readTime="19 мин", short="Как выбрать диван",
        scheme=dict(kind="bars", title="Четыре числа при выборе дивана",
                    items=[("Плотность ППУ в сиденье, кг/м³", 32, "от 30"),
                           ("Глубина посадки, см", 58, "55–60"),
                           ("Высота сиденья, см", 43, "40–45"),
                           ("Проход перед диваном, см", 90, "70–90"),
                           ("Стойкость обивки, тыс. циклов", 35, "20–50")],
                    note="Плотность ППУ ниже 30 кг/м³ — просадка по форме тела за 2–3 года"),
        table=dict(headers=["Бюджет, 2026", "Что за него дают", "Срок службы"],
                   rows=[["до 40 000 ₽", "ЛДСП в каркасе, ППУ 25 кг/м³", "2–4 года"],
                         ["40 000–90 000 ₽", "Массив, ППУ 30–35 кг/м³", "6–10 лет"],
                         ["90 000–200 000 ₽", "HR-пена или независимый блок", "10–15 лет"],
                         ["свыше 200 000 ₽", "Свои размеры, кожа, модульность", "15+ лет"],
                         ["Перетяжка старого", "Новый ППУ и обивка, каркас цел", "+7–10 лет"]],
                   caption="Диван за 80 тысяч в пересчёте на год службы дешевле, чем за 40"),
    ),
    dict(
        slug="kovry-v-interere-razmer-material", cat="interer",
        title="Ковёр в интерьере: размер под зону, ворс, материал и уход",
        desc="Правило мебельной группы, размеры для гостиной, столовой и спальни, материалы по трафику, высота ворса и ковёр как акустика.",
        readTime="18 мин", short="Ковёр в интерьере",
        scheme=dict(kind="bars", title="Размеры ковра по зонам, см",
                    items=[("Гостиная: диван + кресла", 300, "200×300–240×340"),
                           ("Столовая: отступ от стола", 75, "60–75 вокруг"),
                           ("Спальня: выступ у кровати", 70, "50–70 с трёх сторон"),
                           ("Дорожки у кровати", 200, "80×200 ×2"),
                           ("Прихожая: зона разувания", 150, "80×150–100×200")],
                    note="Ковёр держит мебельную группу: минимум передние ножки стоят на нём"),
        table=dict(headers=["Материал", "Плюсы", "Минусы и цена за м²"],
                   rows=[["Шерсть", "Упругая, тёплая, 15–20 лет", "Линяет, боится влаги; 4 000–15 000 ₽"],
                         ["Полипропилен", "Дёшево, моется, не выгорает", "Приминается за 2–3 года; от 800 ₽"],
                         ["Полиэстер", "Мягкий, гипоаллергенный", "Электризуется, лоснится; от 1 200 ₽"],
                         ["Вискоза", "Блеск как у шёлка", "Пятна от воды навсегда; от 3 000 ₽"],
                         ["Джут и сизаль", "Фактура, стойкость к трафику", "Жёсткий, боится воды; 1 500–6 000 ₽"],
                         ["Хлопок, плоское плетение", "Стирается в машине", "Быстро изнашивается; 1 000–3 500 ₽"]],
                   caption="Столовая и прихожая — без вискозы: там будут пятна и влага"),
    ),
    dict(
        slug="dizayn-proekt-kvartiry-sostav-cena", cat="sovety",
        title="Дизайн-проект квартиры: что входит, сколько стоит и когда он экономит",
        desc="Разница между концепцией и рабочей документацией, 14 разделов комплекта, цены за м², сроки на 1,5–3 месяца и проверка дизайнера.",
        readTime="19 мин", short="Дизайн-проект квартиры",
        scheme=dict(kind="columns", title="Из чего состоит комплект",
                    items=[("Концепция", "#7C8FA6",
                            ["Обмерный план", "Планировочные решения",
                             "Визуализации или коллажи", "Ведомость отделки"]),
                           ("Рабочие чертежи", "#4F6B85",
                            ["План демонтажа и монтажа", "План электрики с размерами",
                             "План освещения и групп", "План сантехники",
                             "Планы полов и потолков", "Развёртки стен",
                             "Узлы и детали"]),
                           ("Сопровождение", "#8C6A4A",
                            ["Спецификации с артикулами", "Комплектация и закупка",
                             "Авторский надзор", "Согласование изменений"])],
                    note="Нет развёрток стен и планов электрики с размерами — это не проект, а концепция"),
        table=dict(headers=["Вариант", "Что входит", "Цена за м², 2026"],
                   rows=[["Только планировка", "Обмеры + 2–3 варианта", "300–900 ₽"],
                         ["Концепция без чертежей", "Планировка, визуализации", "1 000–2 500 ₽"],
                         ["Полный проект", "Концепция + все чертежи", "2 000–5 000 ₽"],
                         ["Проект + комплектация", "Плюс подбор и закупка", "3 000–7 000 ₽"],
                         ["Онлайн-проект", "Без выезда, по вашим обмерам", "800–2 000 ₽"],
                         ["Авторский надзор", "2–4 выезда в месяц", "15 000–60 000 ₽ / мес"]],
                   caption="Заказывать за 2–3 месяца до выхода бригады, иначе проект догоняет стройку"),
    ),
    dict(
        slug="tehnadzor-v-remonte", cat="sovety",
        title="Технадзор в ремонте: что проверяет, когда приглашать и сколько стоит",
        desc="Пять точек контроля скрытых работ, что найдёт технадзор в среднем ремонте, цены 3–7% от сметы и как не поссорить его с бригадой.",
        readTime="19 мин", short="Технадзор в ремонте",
        scheme=dict(kind="steps", title="Точки контроля по этапам",
                    items=["Демонтаж: объёмы и сохранность конструкций",
                           "Электрика до штукатурки: сечения и точки",
                           "Вода до стяжки: опрессовка и уклоны",
                           "Гидроизоляция до плитки: площадь и углы",
                           "Стяжка и штукатурка: ровность и влажность",
                           "Финальная приёмка по чек-листу"],
                    note="Каждый выезд привязан к моменту, после которого работа станет скрытой"),
        table=dict(headers=["Формат", "Что входит", "Цена, 2026"],
                   rows=[["Разовый выезд", "Проверка этапа, отчёт с фото", "6 000–15 000 ₽"],
                         ["Приёмка ремонта", "Чек-лист, дефектная ведомость", "10 000–25 000 ₽"],
                         ["Ежемесячный надзор", "2–4 выезда, отчёты", "20 000–60 000 ₽ / мес"],
                         ["Полный цикл", "Все этапы, приёмка, акты", "3–7% от сметы работ"],
                         ["Проверка смет и КС", "Сверка объёмов и расценок", "5 000–20 000 ₽"]],
                   caption="Окупается одной предотвращённой переделкой: гидроизоляция — от 150 000 ₽"),
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
