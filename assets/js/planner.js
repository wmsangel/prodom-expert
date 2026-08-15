/**
 * Планировщик ремонта ДомЭксперт.
 *
 * План квартиры рисуется на canvas в метрах, из геометрии считаются площади
 * пола, стен и потолка, а из них — материалы с запасом, смета по этапам и сроки.
 *
 * Почему без библиотек: на сайте нет сборки, а тянуть внешний холст ради
 * прямоугольников и линий дороже, чем написать рендер руками. Заодно он читает
 * цвета из CSS-переменных и поэтому сам живёт в обеих темах.
 *
 * Всё считается в браузере, план хранится в localStorage — на сервер не уходит.
 */
(function (window, document) {
  'use strict';

  var root = document.getElementById('planner');
  if (!root) { return; }

  // ── Константы предметной области ──────────────────────────────────────────

  var GRID = 0.1;          // шаг привязки, м
  var WALL = 0.12;         // толщина стены для отрисовки, м
  var MIN_SIDE = 1.0;      // минимальная сторона комнаты, м
  var HANDLE = 9;          // радиус маркера ресайза, px

  /** Типы помещений: цвет на плане, отделка по умолчанию, точки электрики. */
  var ROOM_TYPES = {
    zhilaya:  { name: 'Жилая комната', hue: 210, wet: false, points: 8,
                def: { floor: 'laminat', walls: 'kraska', ceiling: 'natyazhnoy' } },
    kuhnya:   { name: 'Кухня',         hue: 35,  wet: true,  points: 14,
                def: { floor: 'plitka',  walls: 'kraska', ceiling: 'natyazhnoy' } },
    sanuzel:  { name: 'Санузел',       hue: 172, wet: true,  points: 5,
                def: { floor: 'plitka',  walls: 'plitka', ceiling: 'natyazhnoy' } },
    koridor:  { name: 'Коридор',       hue: 275, wet: false, points: 5,
                def: { floor: 'plitka',  walls: 'kraska', ceiling: 'natyazhnoy' } },
    balkon:   { name: 'Балкон',        hue: 140, wet: false, points: 3,
                def: { floor: 'plitka',  walls: 'shtukaturka', ceiling: 'pokraska' } }
  };

  /**
   * Отделка: цена материала и работы в ₽ за м² для уровня «стандарт», 2026 год,
   * крупные города. Уровень двигает их коэффициентами ниже.
   */
  var FINISH = {
    floor: {
      laminat:    { name: 'Ламинат',        mat: 1200, work: 550,  screed: true },
      kvarcvinil: { name: 'Кварцвинил SPC', mat: 1900, work: 650,  screed: true },
      plitka:     { name: 'Керамогранит',   mat: 1800, work: 1300, screed: true },
      parket:     { name: 'Паркетная доска',mat: 3800, work: 1100, screed: true },
      linoleum:   { name: 'Линолеум',       mat: 700,  work: 350,  screed: true }
    },
    walls: {
      kraska:      { name: 'Покраска',              mat: 350,  work: 900 },
      oboi:        { name: 'Обои',                  mat: 600,  work: 550 },
      plitka:      { name: 'Плитка',                mat: 1700, work: 1500 },
      dekor:       { name: 'Декоративная штукатурка', mat: 1200, work: 1400 },
      shtukaturka: { name: 'Штукатурка под покраску', mat: 220, work: 700 }
    },
    ceiling: {
      natyazhnoy: { name: 'Натяжной',    mat: 550, work: 350 },
      pokraska:   { name: 'Покраска',    mat: 300, work: 800 },
      gkl:        { name: 'Гипсокартон', mat: 700, work: 1200 }
    }
  };

  /** Уровень отделки: материалы разлетаются сильнее, чем работы. */
  var LEVELS = {
    ekonom:   { name: 'Эконом',   mat: 0.60, work: 0.75 },
    standart: { name: 'Стандарт', mat: 1.00, work: 1.00 },
    premium:  { name: 'Премиум',  mat: 2.20, work: 1.50 }
  };

  /** Сценарий задаёт, нужны ли демонтаж и черновая отделка. */
  var SCENARIOS = {
    novostroyka: { name: 'Новостройка без отделки', demo: false, rough: true },
    vtorichka:   { name: 'Вторичка с демонтажом',   demo: true,  rough: true },
    kosmetika:   { name: 'Косметический ремонт',    demo: false, rough: false }
  };

  /** Прочие расценки, ₽. Точечные — за штуку, площадные — за м². */
  var RATES = {
    demoWork:      900,    // демонтаж, за м² пола
    debrisPerM2:   0.25,   // кубометров мусора на м² пола
    debrisPerM3:   1800,   // вывоз, за м³
    plasterMat:    180,    // штукатурка стен, за м²
    plasterWork:   700,
    screedMat:     250,    // стяжка пола, за м²
    screedWork:    600,
    waterproofMat: 600,    // гидроизоляция мокрой зоны, за м² пола
    waterproofWork:700,
    elecPoint:     1900,   // электроточка под ключ
    elecPanel:     35000,  // щиток на квартиру
    plumbBath:     95000,  // разводка и приборы санузла
    plumbKitchen:  25000,  // подводка кухни
    doorUnit:      28000,  // межкомнатная дверь с монтажом
    windowPerM2:   25000,  // замена окна, за м² проёма
    warmFloorMat:  1400,   // тёплый пол, за м² покрытия
    warmFloorWork: 900,
    warmFloorFrac: 0.7,    // доля площади комнаты под тёплым полом
    plinthMat:     250,    // плинтус, за пог. м
    plinthWork:    200
  };

  /** Проёмы: габариты по умолчанию, м. */
  var OPENING = {
    door:   { name: 'Дверь', w: 0.9, h: 2.05 },
    window: { name: 'Окно',  w: 1.4, h: 1.5, sill: 0.85 }
  };

  /** Этапы сметы: порядок вывода, цвет полосы и норма выработки, дней на м². */
  var STAGES = [
    { key: 'demo',   name: 'Демонтаж и вывоз',  hue: 0,   perM2: 0.15 },
    { key: 'eng',    name: 'Инженерия',         hue: 40,  perM2: 0.35 },
    { key: 'rough',  name: 'Черновая отделка',  hue: 200, perM2: 0.50 },
    { key: 'finish', name: 'Чистовая отделка',  hue: 145, perM2: 0.60 },
    { key: 'units',  name: 'Двери и изделия',   hue: 280, perM2: 0.10 }
  ];

  // ── Готовые планировки ────────────────────────────────────────────────────

  var PRESETS = {
    studiya: {
      name: 'Студия 33 м²',
      rooms: [
        { n: 'Жилая-кухня', t: 'kuhnya',  x: 0,   y: 0,   w: 5.6, h: 4.2 },
        { n: 'Санузел',     t: 'sanuzel', x: 5.6, y: 0,   w: 2.2, h: 1.9 },
        { n: 'Прихожая',    t: 'koridor', x: 5.6, y: 1.9, w: 2.2, h: 2.3 }
      ],
      openings: [
        { r: 0, wall: 's', at: 2.0, type: 'window' },
        { r: 0, wall: 's', at: 4.4, type: 'window' },
        { r: 1, wall: 's', at: 1.1, type: 'door' },
        { r: 2, wall: 'w', at: 1.2, type: 'door' }
      ]
    },
    odnushka: {
      name: 'Однушка 43 м²',
      rooms: [
        { n: 'Гостиная', t: 'zhilaya', x: 0,   y: 0,   w: 4.4, h: 3.6 },
        { n: 'Кухня',    t: 'kuhnya',  x: 4.4, y: 0,   w: 3.2, h: 3.6 },
        { n: 'Коридор',  t: 'koridor', x: 0,   y: 3.6, w: 4.4, h: 2.0 },
        { n: 'Санузел',  t: 'sanuzel', x: 4.4, y: 3.6, w: 3.2, h: 2.0 }
      ],
      openings: [
        { r: 0, wall: 'n', at: 2.2, type: 'window' },
        { r: 1, wall: 'n', at: 1.6, type: 'window' },
        { r: 0, wall: 's', at: 3.4, type: 'door' },
        { r: 1, wall: 'w', at: 1.8, type: 'door' },
        { r: 3, wall: 'w', at: 1.0, type: 'door' }
      ]
    },
    dvushka: {
      name: 'Двушка 58 м²',
      rooms: [
        { n: 'Гостиная', t: 'zhilaya', x: 0,   y: 0,   w: 4.6, h: 3.8 },
        { n: 'Спальня',  t: 'zhilaya', x: 4.6, y: 0,   w: 4.4, h: 3.8 },
        { n: 'Кухня',    t: 'kuhnya',  x: 0,   y: 3.8, w: 3.4, h: 2.6 },
        { n: 'Коридор',  t: 'koridor', x: 3.4, y: 3.8, w: 2.6, h: 2.6 },
        { n: 'Санузел',  t: 'sanuzel', x: 6.0, y: 3.8, w: 3.0, h: 2.6 }
      ],
      openings: [
        { r: 0, wall: 'n', at: 2.3, type: 'window' },
        { r: 1, wall: 'n', at: 2.2, type: 'window' },
        { r: 2, wall: 's', at: 1.7, type: 'window' },
        { r: 0, wall: 'e', at: 3.0, type: 'door' },
        { r: 1, wall: 's', at: 0.8, type: 'door' },
        { r: 2, wall: 'e', at: 1.3, type: 'door' },
        { r: 4, wall: 'w', at: 1.3, type: 'door' }
      ]
    },
    treshka: {
      name: 'Трёшка 76 м²',
      rooms: [
        { n: 'Гостиная', t: 'zhilaya', x: 0,   y: 0,   w: 4.6, h: 4.0 },
        { n: 'Спальня',  t: 'zhilaya', x: 4.6, y: 0,   w: 3.0, h: 4.0 },
        { n: 'Детская',  t: 'zhilaya', x: 7.6, y: 0,   w: 2.4, h: 4.0 },
        { n: 'Кухня',    t: 'kuhnya',  x: 0,   y: 4.0, w: 3.4, h: 3.6 },
        { n: 'Коридор',  t: 'koridor', x: 3.4, y: 4.0, w: 3.2, h: 3.6 },
        { n: 'Санузел',  t: 'sanuzel', x: 6.6, y: 4.0, w: 1.8, h: 3.6 },
        { n: 'Ванная',   t: 'sanuzel', x: 8.4, y: 4.0, w: 1.6, h: 3.6 }
      ],
      openings: [
        { r: 0, wall: 'n', at: 2.3, type: 'window' },
        { r: 1, wall: 'n', at: 1.5, type: 'window' },
        { r: 2, wall: 'n', at: 1.2, type: 'window' },
        { r: 3, wall: 's', at: 1.7, type: 'window' },
        { r: 0, wall: 's', at: 3.8, type: 'door' },
        { r: 1, wall: 's', at: 1.5, type: 'door' },
        { r: 2, wall: 's', at: 1.2, type: 'door' },
        { r: 3, wall: 'e', at: 1.8, type: 'door' },
        { r: 5, wall: 'w', at: 1.8, type: 'door' },
        { r: 6, wall: 'w', at: 1.8, type: 'door' }
      ]
    }
  };

  // ── Состояние ─────────────────────────────────────────────────────────────

  var state = null;                        // { rooms, openings, level, scenario, seq }
  var view = { scale: 60, px: 0, py: 0 };  // масштаб px/м и сдвиг холста в px
  var tool = 'select';
  // Черновик формы «добавить комнату по размерам»: панель перерисовывается
  // на каждый пересчёт, и без этого введённые значения сбрасывались бы.
  var draft = { type: 'zhilaya', w: 3.5, h: 3, name: '' };
  var sel = null;                          // { kind: 'room'|'opening', id }
  var drag = null;
  var history = [], future = [];
  var STORE_KEY = 'domexpert.planner.v1';

  var canvas = document.getElementById('pl-canvas');
  var ctx = canvas.getContext('2d');
  var theme = {};

  // ── Утилиты ───────────────────────────────────────────────────────────────

  var NBSP = ' ';

  function num(v, d) {
    if (!isFinite(v)) { return '—'; }
    var s = Math.abs(v).toFixed(typeof d === 'number' ? d : 0).split('.');
    s[0] = s[0].replace(/\B(?=(\d{3})+(?!\d))/g, NBSP);
    return (v < 0 ? '−' : '') + s.join(',');
  }
  function money(v) { return num(Math.round(v), 0) + NBSP + '₽'; }
  /**
   * Привязка к сетке. Умножение на GRID даёт хвост вида 1.3000000000000003,
   * и он вылезает прямо в поле ввода — поэтому делим на целое, а не умножаем.
   */
  var GRID_INV = Math.round(1 / GRID);
  function snap(v) { return Math.round(v * GRID_INV) / GRID_INV; }
  function esc(s) {
    return String(s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }
  function el(id) { return document.getElementById(id); }

  /** Склонение: 1 день, 2 дня, 5 дней. */
  function plural(n, one, few, many) {
    var a = Math.abs(n) % 100, b = a % 10;
    if (a > 10 && a < 20) { return many; }
    if (b > 1 && b < 5) { return few; }
    if (b === 1) { return one; }
    return many;
  }

  // ── Модель ────────────────────────────────────────────────────────────────

  function blankState() {
    return { rooms: [], openings: [], level: 'standart', scenario: 'vtorichka',
             height: 2.7, replaceWindows: false, seq: 1 };
  }

  function makeRoom(type, x, y, w, h, name) {
    var t = ROOM_TYPES[type] || ROOM_TYPES.zhilaya;
    return {
      id: state.seq++, type: type, name: name || t.name,
      x: x, y: y, w: w, h: h,
      // Вырезы: угловые дают Г-образную комнату, пристеночные — короба и шахты.
      // Комната = прямоугольник минус эти прямоугольники.
      cuts: [],
      floor: t.def.floor, walls: t.def.walls, ceiling: t.def.ceiling,
      points: t.points, warmFloor: type === 'sanuzel' || type === 'kuhnya'
    };
  }

  var CORNER_LABEL = { nw: '↖ левый верхний', ne: '↗ правый верхний', se: '↘ правый нижний', sw: '↙ левый нижний' };
  var WALL_LABEL = { n: '↑ верхняя стена', e: '→ правая стена', s: '↓ нижняя стена', w: '← левая стена' };

  /** Прямоугольник выреза в мировых координатах. */
  function cutRect(room, c) {
    if (c.kind === 'corner') {
      return {
        x: (c.corner === 'nw' || c.corner === 'sw') ? room.x : room.x + room.w - c.w,
        y: (c.corner === 'nw' || c.corner === 'ne') ? room.y : room.y + room.h - c.d,
        w: c.w, h: c.d
      };
    }
    if (c.wall === 'n') { return { x: room.x + c.at - c.w / 2, y: room.y, w: c.w, h: c.d }; }
    if (c.wall === 's') { return { x: room.x + c.at - c.w / 2, y: room.y + room.h - c.d, w: c.w, h: c.d }; }
    if (c.wall === 'w') { return { x: room.x, y: room.y + c.at - c.w / 2, w: c.d, h: c.w }; }
    return { x: room.x + room.w - c.d, y: room.y + c.at - c.w / 2, w: c.d, h: c.w };
  }

  function cornerCut(room, name) {
    var cuts = room.cuts || [];
    for (var i = 0; i < cuts.length; i++) {
      if (cuts[i].kind === 'corner' && cuts[i].corner === name) { return cuts[i]; }
    }
    return null;
  }
  function wallCuts(room, wall) {
    return (room.cuts || []).filter(function (c) {
      return c.kind === 'wall' && c.wall === wall;
    }).sort(function (a, b) { return a.at - b.at; });
  }

  /**
   * Контур комнаты как многоугольник: обход по часовой стрелке от левого
   * верхнего угла. Площадь и периметр считаются потом из него, а не по
   * отдельной формуле под каждый вид выреза — иначе они разъедутся.
   */
  function roomPolygon(room) {
    var x1 = room.x, y1 = room.y, x2 = room.x + room.w, y2 = room.y + room.h;
    var p = [], c;

    c = cornerCut(room, 'nw');
    if (c) { p.push({ x: x1, y: y1 + c.d }, { x: x1 + c.w, y: y1 + c.d }, { x: x1 + c.w, y: y1 }); }
    else { p.push({ x: x1, y: y1 }); }

    wallCuts(room, 'n').forEach(function (cu) {           // слева направо
      var a = x1 + cu.at - cu.w / 2, b = x1 + cu.at + cu.w / 2;
      p.push({ x: a, y: y1 }, { x: a, y: y1 + cu.d }, { x: b, y: y1 + cu.d }, { x: b, y: y1 });
    });

    c = cornerCut(room, 'ne');
    if (c) { p.push({ x: x2 - c.w, y: y1 }, { x: x2 - c.w, y: y1 + c.d }, { x: x2, y: y1 + c.d }); }
    else { p.push({ x: x2, y: y1 }); }

    wallCuts(room, 'e').forEach(function (cu) {           // сверху вниз
      var a = y1 + cu.at - cu.w / 2, b = y1 + cu.at + cu.w / 2;
      p.push({ x: x2, y: a }, { x: x2 - cu.d, y: a }, { x: x2 - cu.d, y: b }, { x: x2, y: b });
    });

    c = cornerCut(room, 'se');
    if (c) { p.push({ x: x2, y: y2 - c.d }, { x: x2 - c.w, y: y2 - c.d }, { x: x2 - c.w, y: y2 }); }
    else { p.push({ x: x2, y: y2 }); }

    wallCuts(room, 's').slice().reverse().forEach(function (cu) {   // справа налево
      var a = x1 + cu.at + cu.w / 2, b = x1 + cu.at - cu.w / 2;
      p.push({ x: a, y: y2 }, { x: a, y: y2 - cu.d }, { x: b, y: y2 - cu.d }, { x: b, y: y2 });
    });

    c = cornerCut(room, 'sw');
    if (c) { p.push({ x: x1 + c.w, y: y2 }, { x: x1 + c.w, y: y2 - c.d }, { x: x1, y: y2 - c.d }); }
    else { p.push({ x: x1, y: y2 }); }

    wallCuts(room, 'w').slice().reverse().forEach(function (cu) {   // снизу вверх
      var a = y1 + cu.at + cu.w / 2, b = y1 + cu.at - cu.w / 2;
      p.push({ x: x1, y: a }, { x: x1 + cu.d, y: a }, { x: x1 + cu.d, y: b }, { x: x1, y: b });
    });

    return p;
  }

  /** Площадь многоугольника по формуле шнурков. */
  function polyArea(p) {
    var s = 0;
    for (var i = 0, n = p.length; i < n; i++) {
      var q = p[(i + 1) % n];
      s += p[i].x * q.y - q.x * p[i].y;
    }
    return Math.abs(s) / 2;
  }

  /** Периметр замкнутого контура. */
  function polyPerimeter(p) {
    var s = 0;
    for (var i = 0, n = p.length; i < n; i++) {
      var q = p[(i + 1) % n];
      s += Math.hypot(q.x - p[i].x, q.y - p[i].y);
    }
    return s;
  }

  /** Точка внутри комнаты: в габарите и ни в одном из вырезов. */
  function pointInRoom(room, wx, wy) {
    if (wx < room.x || wx > room.x + room.w || wy < room.y || wy > room.y + room.h) { return false; }
    var cuts = room.cuts || [];
    for (var i = 0; i < cuts.length; i++) {
      var r = cutRect(room, cuts[i]);
      if (wx > r.x && wx < r.x + r.w && wy > r.y && wy < r.y + r.h) { return false; }
    }
    return true;
  }

  /** Вырезы не должны съедать комнату целиком или вылезать за габарит. */
  function clampCuts(room) {
    var cuts = room.cuts || [];
    if (!cuts.length) { return; }
    var maxW = room.w * 0.85, maxH = room.h * 0.85;

    cuts.forEach(function (c) {
      if (c.kind === 'corner') {
        c.w = snap(Math.max(0.2, Math.min(c.w, maxW)));
        c.d = snap(Math.max(0.2, Math.min(c.d, maxH)));
      } else {
        var len = (c.wall === 'n' || c.wall === 's') ? room.w : room.h;
        var opp = (c.wall === 'n' || c.wall === 's') ? room.h : room.w;
        c.w = snap(Math.max(0.2, Math.min(c.w, len * 0.8)));
        c.d = snap(Math.max(0.2, Math.min(c.d, opp * 0.8)));
        c.at = snap(Math.min(Math.max(c.at, c.w / 2), len - c.w / 2));
      }
    });

    // Два угловых выреза на одной стороне не должны перекрыться.
    [['nw', 'ne', 'w'], ['sw', 'se', 'w'], ['nw', 'sw', 'd'], ['ne', 'se', 'd']].forEach(function (pair) {
      var a = cornerCut(room, pair[0]), b = cornerCut(room, pair[1]);
      if (!a || !b) { return; }
      var key = pair[2] === 'w' ? 'w' : 'd';
      var limit = (key === 'w' ? room.w : room.h) * 0.9;
      if (a[key] + b[key] > limit) {
        var k = limit / (a[key] + b[key]);
        a[key] = snap(a[key] * k); b[key] = snap(b[key] * k);
      }
    });

    // Пристеночный короб не должен налезать на угловой вырез или на соседний
    // короб: наложение даёт самопересекающийся контур и неверный периметр.
    var drop = [];
    cuts.filter(function (c) { return c.kind === 'wall'; }).forEach(function (c) {
      var free = wallFreeIntervals(room, c.wall, c);
      var fits = free.filter(function (iv) { return iv[1] - iv[0] >= c.w; });
      if (fits.length) {
        var best = fits[0], bestD = Infinity;
        fits.forEach(function (iv) {
          var at = Math.min(Math.max(c.at, iv[0] + c.w / 2), iv[1] - c.w / 2);
          var d = Math.abs(at - c.at);
          if (d < bestD) { bestD = d; best = iv; }
        });
        c.at = snap(Math.min(Math.max(c.at, best[0] + c.w / 2), best[1] - c.w / 2));
        return;
      }
      // Не влезает целиком — ужимаем до самого широкого свободного участка.
      var big = free.slice().sort(function (p, q) { return (q[1] - q[0]) - (p[1] - p[0]); })[0];
      if (!big || big[1] - big[0] < 0.2) { drop.push(c); return; }
      c.w = snap(big[1] - big[0]);
      c.at = snap((big[0] + big[1]) / 2);
    });
    if (drop.length) {
      room.cuts = cuts.filter(function (c) { return drop.indexOf(c) === -1; });
    }
  }

  /**
   * Свободные участки стены, не занятые вырезами: [[от, до], …].
   * except — вырез, который сам себя занимать не должен (нужно при его правке).
   */
  function wallFreeIntervals(room, wall, except) {
    var len = wallLen(room, wall);
    var busy = [];
    // Какие угловые вырезы обрезают начало и конец этой стены.
    var ends = {
      n: ['nw', 'ne', 'w'], s: ['sw', 'se', 'w'],
      w: ['nw', 'sw', 'd'], e: ['ne', 'se', 'd']
    }[wall];
    var a = cornerCut(room, ends[0]), b = cornerCut(room, ends[1]);
    if (a) { busy.push([0, a[ends[2]]]); }
    if (b) { busy.push([len - b[ends[2]], len]); }
    wallCuts(room, wall).forEach(function (c) {
      if (c === except) { return; }
      busy.push([c.at - c.w / 2, c.at + c.w / 2]);
    });

    busy.sort(function (p, q) { return p[0] - q[0]; });
    var free = [], cur = 0;
    busy.forEach(function (b2) {
      if (b2[0] > cur) { free.push([cur, b2[0]]); }
      cur = Math.max(cur, b2[1]);
    });
    if (cur < len) { free.push([cur, len]); }
    return free;
  }

  function roomById(id) {
    for (var i = 0; i < state.rooms.length; i++) {
      if (state.rooms[i].id === id) { return state.rooms[i]; }
    }
    return null;
  }
  function openingById(id) {
    for (var i = 0; i < state.openings.length; i++) {
      if (state.openings[i].id === id) { return state.openings[i]; }
    }
    return null;
  }

  /** Длина стены комнаты, м. */
  function wallLen(room, wall) {
    return (wall === 'n' || wall === 's') ? room.w : room.h;
  }

  /** Две точки стены в мировых координатах. */
  function wallPoints(room, wall) {
    if (wall === 'n') { return [room.x, room.y, room.x + room.w, room.y]; }
    if (wall === 's') { return [room.x, room.y + room.h, room.x + room.w, room.y + room.h]; }
    if (wall === 'w') { return [room.x, room.y, room.x, room.y + room.h]; }
    return [room.x + room.w, room.y, room.x + room.w, room.y + room.h];
  }

  /** Центр проёма в мировых координатах. */
  function openingCenter(op) {
    var room = roomById(op.roomId);
    if (!room) { return null; }
    var p = wallPoints(room, op.wall);
    var len = wallLen(room, op.wall);
    var t = Math.min(Math.max(op.at, op.w / 2), Math.max(op.w / 2, len - op.w / 2));
    var k = len > 0 ? t / len : 0;
    return { x: p[0] + (p[2] - p[0]) * k, y: p[1] + (p[3] - p[1]) * k, room: room };
  }

  /**
   * Проёмы не должны торчать за укоротившуюся стену и попадать в вырезы:
   * загоняем каждый в ближайший свободный участок, который его вмещает.
   */
  function clampOpenings() {
    state.openings = state.openings.filter(function (op) {
      var room = roomById(op.roomId);
      if (!room) { return false; }
      var free = wallFreeIntervals(room, op.wall).filter(function (iv) {
        return iv[1] - iv[0] >= op.w + 0.1;
      });
      if (!free.length) { return false; }       // на стене больше нет места
      var best = free[0], bestD = Infinity;
      free.forEach(function (iv) {
        var c = Math.min(Math.max(op.at, iv[0] + op.w / 2), iv[1] - op.w / 2);
        var d = Math.abs(c - op.at);
        if (d < bestD) { bestD = d; best = iv; }
      });
      op.at = Math.min(Math.max(op.at, best[0] + op.w / 2), best[1] - op.w / 2);
      return true;
    });
  }

  // ── История ───────────────────────────────────────────────────────────────

  function snapshot() {
    history.push(JSON.stringify(state));
    if (history.length > 40) { history.shift(); }
    future.length = 0;
    syncHistoryButtons();
  }
  function undo() {
    if (!history.length) { return; }
    future.push(JSON.stringify(state));
    state = JSON.parse(history.pop());
    sel = null; syncHistoryButtons(); refresh();
  }
  function redo() {
    if (!future.length) { return; }
    history.push(JSON.stringify(state));
    state = JSON.parse(future.pop());
    sel = null; syncHistoryButtons(); refresh();
  }
  function syncHistoryButtons() {
    var u = el('pl-undo'), r = el('pl-redo');
    if (u) { u.disabled = !history.length; }
    if (r) { r.disabled = !future.length; }
  }

  // ── Хранилище ─────────────────────────────────────────────────────────────

  function save() {
    try { window.localStorage.setItem(STORE_KEY, JSON.stringify(state)); } catch (e) { /* приватный режим */ }
  }
  function load() {
    try {
      var raw = window.localStorage.getItem(STORE_KEY);
      if (!raw) { return null; }
      var s = JSON.parse(raw);
      return (s && Array.isArray(s.rooms) && s.rooms.length) ? s : null;
    } catch (e) { return null; }
  }

  function applyPreset(key) {
    var p = PRESETS[key];
    if (!p) { return; }
    snapshot();
    var keepLevel = state ? state.level : 'standart';
    var keepScenario = state ? state.scenario : 'vtorichka';
    var keepHeight = state ? state.height : 2.7;
    state = blankState();
    state.level = keepLevel; state.scenario = keepScenario; state.height = keepHeight;
    var made = p.rooms.map(function (r) {
      var room = makeRoom(r.t, r.x, r.y, r.w, r.h, r.n);
      state.rooms.push(room);
      return room;
    });
    p.openings.forEach(function (o) {
      var room = made[o.r];
      if (!room) { return; }
      var def = OPENING[o.type];
      state.openings.push({ id: state.seq++, roomId: room.id, wall: o.wall,
                            at: o.at, type: o.type, w: def.w, h: def.h });
    });
    clampOpenings();
    sel = null;
    fitToPlan();
    refresh();
  }

  // ── Координаты ────────────────────────────────────────────────────────────

  function toScreenX(wx) { return wx * view.scale + view.px; }
  function toScreenY(wy) { return wy * view.scale + view.py; }
  function toWorldX(sx) { return (sx - view.px) / view.scale; }
  function toWorldY(sy) { return (sy - view.py) / view.scale; }

  function canvasSize() {
    return { w: canvas.clientWidth || 800, h: canvas.clientHeight || 500 };
  }

  function planBounds() {
    if (!state.rooms.length) { return null; }
    var b = { x1: Infinity, y1: Infinity, x2: -Infinity, y2: -Infinity };
    state.rooms.forEach(function (r) {
      b.x1 = Math.min(b.x1, r.x); b.y1 = Math.min(b.y1, r.y);
      b.x2 = Math.max(b.x2, r.x + r.w); b.y2 = Math.max(b.y2, r.y + r.h);
    });
    return b;
  }

  function fitToPlan() {
    var b = planBounds(), size = canvasSize();
    if (!b) { view.scale = 60; view.px = size.w / 2 - 200; view.py = size.h / 2 - 120; return; }
    var pad = 48;
    var sx = (size.w - pad * 2) / Math.max(0.5, b.x2 - b.x1);
    var sy = (size.h - pad * 2) / Math.max(0.5, b.y2 - b.y1);
    var fit = Math.min(sx, sy);
    // Math.max(18, NaN) даёт NaN, поэтому NaN отсекаем отдельно.
    view.scale = isFinite(fit) ? Math.max(18, Math.min(160, fit)) : 60;
    view.px = size.w / 2 - (b.x1 + b.x2) / 2 * view.scale;
    view.py = size.h / 2 - (b.y1 + b.y2) / 2 * view.scale;
  }

  function zoomBy(k, cx, cy) {
    var size = canvasSize();
    cx = (typeof cx === 'number') ? cx : size.w / 2;
    cy = (typeof cy === 'number') ? cy : size.h / 2;
    var wx = toWorldX(cx), wy = toWorldY(cy);
    view.scale = Math.max(14, Math.min(220, view.scale * k));
    view.px = cx - wx * view.scale;
    view.py = cy - wy * view.scale;
    draw();
  }

  // ── Тема ──────────────────────────────────────────────────────────────────

  function readTheme() {
    var cs = getComputedStyle(document.documentElement);
    function v(name, fallback) {
      var x = cs.getPropertyValue(name);
      return (x && x.trim()) ? x.trim() : fallback;
    }
    var dark = document.documentElement.getAttribute('data-theme') === 'dark';
    theme = {
      dark: dark,
      bg:      v('--white', dark ? '#15171A' : '#FFFFFF'),
      grid:    dark ? 'rgba(255,255,255,0.07)' : 'rgba(0,0,0,0.06)',
      gridBig: dark ? 'rgba(255,255,255,0.14)' : 'rgba(0,0,0,0.12)',
      text:    v('--text', dark ? '#D7DBE0' : '#333333'),
      muted:   v('--text-muted', dark ? '#98A0AA' : '#70757D'),
      accent:  v('--brick', dark ? '#E2705C' : '#C0392B'),
      wall:    dark ? '#C7CBD2' : '#3A3A3A'
    };
  }

  function roomFill(room, selected) {
    var t = ROOM_TYPES[room.type] || ROOM_TYPES.zhilaya;
    var l = theme.dark ? 62 : 52;
    var a = selected ? (theme.dark ? 0.34 : 0.28) : (theme.dark ? 0.20 : 0.15);
    return 'hsla(' + t.hue + ', 55%, ' + l + '%, ' + a + ')';
  }
  function roomStroke(room) {
    var t = ROOM_TYPES[room.type] || ROOM_TYPES.zhilaya;
    return 'hsla(' + t.hue + ', 55%, ' + (theme.dark ? 68 : 42) + '%, 0.95)';
  }

  // ── Рендер ────────────────────────────────────────────────────────────────

  function resizeCanvas() {
    var dpr = window.devicePixelRatio || 1;
    var w = canvas.clientWidth || 800, h = canvas.clientHeight || 500;
    canvas.width = Math.round(w * dpr);
    canvas.height = Math.round(h * dpr);
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
  }

  function draw() {
    var size = canvasSize();
    ctx.clearRect(0, 0, size.w, size.h);
    ctx.fillStyle = theme.bg;
    ctx.fillRect(0, 0, size.w, size.h);

    drawGrid(size);
    state.rooms.forEach(function (r) { drawRoomFill(r); });
    state.rooms.forEach(function (r) { drawRoomWalls(r); });
    state.openings.forEach(function (op) { drawOpening(op); });
    state.rooms.forEach(function (r) { drawRoomLabel(r); });

    if (sel && sel.kind === 'room') {
      var r = roomById(sel.id);
      if (r) { drawHandles(r); drawDimensions(r); }
    }
    if (drag && drag.mode === 'create' && drag.rect) { drawGhost(drag.rect); }

    drawScaleBar(size);
    if (!state.rooms.length) { drawEmptyHint(size); }
  }

  function drawGrid(size) {
    // Шаг обязан быть положительным: при нуле или NaN циклы ниже зациклятся
    // и повесят вкладку насмерть. Дешевле проверить, чем ловить потом.
    if (!(view.scale > 1)) { return; }
    var step = view.scale * 0.5;                     // полметра
    if (step < 12) { step = view.scale; }            // на мелком масштабе — метр
    var x0 = view.px % step, y0 = view.py % step;
    ctx.lineWidth = 1;
    ctx.strokeStyle = theme.grid;
    ctx.beginPath();
    for (var x = x0; x < size.w; x += step) { ctx.moveTo(Math.round(x) + 0.5, 0); ctx.lineTo(Math.round(x) + 0.5, size.h); }
    for (var y = y0; y < size.h; y += step) { ctx.moveTo(0, Math.round(y) + 0.5); ctx.lineTo(size.w, Math.round(y) + 0.5); }
    ctx.stroke();

    // Метровая сетка поверх, чтобы читался масштаб.
    var big = view.scale;
    if (big >= 24) {
      ctx.strokeStyle = theme.gridBig;
      ctx.beginPath();
      for (var bx = view.px % big; bx < size.w; bx += big) { ctx.moveTo(Math.round(bx) + 0.5, 0); ctx.lineTo(Math.round(bx) + 0.5, size.h); }
      for (var by = view.py % big; by < size.h; by += big) { ctx.moveTo(0, Math.round(by) + 0.5); ctx.lineTo(size.w, Math.round(by) + 0.5); }
      ctx.stroke();
    }
  }

  /** Путь по контуру комнаты в экранных координатах. */
  function tracePolygon(room) {
    var p = roomPolygon(room);
    ctx.beginPath();
    ctx.moveTo(toScreenX(p[0].x), toScreenY(p[0].y));
    for (var i = 1; i < p.length; i++) { ctx.lineTo(toScreenX(p[i].x), toScreenY(p[i].y)); }
    ctx.closePath();
  }

  function drawRoomFill(room) {
    var selected = sel && sel.kind === 'room' && sel.id === room.id;
    ctx.fillStyle = roomFill(room, selected);
    tracePolygon(room);
    ctx.fill();
  }

  function drawRoomWalls(room) {
    ctx.lineJoin = 'miter';
    ctx.strokeStyle = theme.wall;
    ctx.lineWidth = Math.max(2, WALL * view.scale);
    tracePolygon(room);
    ctx.stroke();
    (room.cuts || []).forEach(function (c) { drawCutHatch(room, c); });
  }

  /**
   * Вырез штрихуется по диагонали: без этого небольшая шахта читается как
   * дырка в плане, а не как короб, который занимает место в комнате.
   */
  function drawCutHatch(room, c) {
    var r = cutRect(room, c);
    var sx = toScreenX(r.x), sy = toScreenY(r.y);
    var w = r.w * view.scale, h = r.h * view.scale;
    if (w < 8 || h < 8) { return; }
    ctx.save();
    ctx.beginPath();
    ctx.rect(sx, sy, w, h);
    ctx.clip();
    ctx.strokeStyle = theme.muted;
    ctx.globalAlpha = 0.45;
    ctx.lineWidth = 1;
    ctx.beginPath();
    for (var o = -h; o < w; o += 9) {
      ctx.moveTo(sx + o, sy + h);
      ctx.lineTo(sx + o + h, sy);
    }
    ctx.stroke();
    ctx.restore();
  }

  function drawOpening(op) {
    var c = openingCenter(op);
    if (!c) { return; }
    var horiz = (op.wall === 'n' || op.wall === 's');
    var half = op.w / 2 * view.scale;
    var thick = Math.max(2, WALL * view.scale) + 2;
    var sx = toScreenX(c.x), sy = toScreenY(c.y);
    var selected = sel && sel.kind === 'opening' && sel.id === op.id;

    // Вырезаем кусок стены цветом фона, поверх рисуем символ проёма.
    ctx.save();
    ctx.strokeStyle = theme.bg;
    ctx.lineWidth = thick;
    ctx.lineCap = 'butt';
    ctx.beginPath();
    if (horiz) { ctx.moveTo(sx - half, sy); ctx.lineTo(sx + half, sy); }
    else { ctx.moveTo(sx, sy - half); ctx.lineTo(sx, sy + half); }
    ctx.stroke();

    ctx.strokeStyle = selected ? theme.accent : (op.type === 'door' ? theme.wall : 'hsl(200,60%,50%)');
    ctx.lineWidth = selected ? 3 : 2;

    if (op.type === 'window') {
      // Окно — две тонкие линии по краям стены.
      var off = thick / 3;
      ctx.beginPath();
      if (horiz) {
        ctx.moveTo(sx - half, sy - off); ctx.lineTo(sx + half, sy - off);
        ctx.moveTo(sx - half, sy + off); ctx.lineTo(sx + half, sy + off);
      } else {
        ctx.moveTo(sx - off, sy - half); ctx.lineTo(sx - off, sy + half);
        ctx.moveTo(sx + off, sy - half); ctx.lineTo(sx + off, sy + half);
      }
      ctx.stroke();
    } else {
      // Дверь: полотно в открытом положении и дуга от закрытого к открытому.
      // Считаем векторами, а не углами — с углами легко получить дугу на 270°.
      var al = wallAlong(op.wall);            // вдоль стены
      var nr = wallNormal(op.wall);           // внутрь комнаты
      var r = op.w * view.scale;              // длина полотна равна ширине проёма
      var hx = sx - al.x * half, hy = sy - al.y * half;   // петля на краю проёма

      ctx.beginPath();
      ctx.moveTo(hx, hy);
      ctx.lineTo(hx + nr.x * r, hy + nr.y * r);
      ctx.stroke();

      // Короткая сторона дуги определяется знаком векторного произведения.
      var cross = al.x * nr.y - al.y * nr.x;
      ctx.globalAlpha = 0.45;
      ctx.beginPath();
      ctx.arc(hx, hy, r, Math.atan2(al.y, al.x), Math.atan2(nr.y, nr.x), cross < 0);
      ctx.stroke();
      ctx.globalAlpha = 1;
    }
    ctx.restore();
  }

  /** Единичный вектор вдоль стены — в ту же сторону, что и отсчёт позиции проёма. */
  function wallAlong(wall) {
    return (wall === 'n' || wall === 's') ? { x: 1, y: 0 } : { x: 0, y: 1 };
  }
  /** Единичная нормаль внутрь комнаты. */
  function wallNormal(wall) {
    if (wall === 'n') { return { x: 0, y: 1 }; }
    if (wall === 's') { return { x: 0, y: -1 }; }
    if (wall === 'w') { return { x: 1, y: 0 }; }
    return { x: -1, y: 0 };
  }

  /** Куда поставить подпись: центр габарита, вытолкнутый из вырезов. */
  function labelAnchor(room) {
    var cx = room.x + room.w / 2, cy = room.y + room.h / 2;
    (room.cuts || []).forEach(function (c) {
      var r = cutRect(room, c);
      if (cx <= r.x || cx >= r.x + r.w || cy <= r.y || cy >= r.y + r.h) { return; }
      // Выходим в ближайшую сторону выреза — так подпись остаётся в комнате.
      var out = [
        { d: cx - r.x, x: r.x - 0.15, y: cy },
        { d: r.x + r.w - cx, x: r.x + r.w + 0.15, y: cy },
        { d: cy - r.y, x: cx, y: r.y - 0.15 },
        { d: r.y + r.h - cy, x: cx, y: r.y + r.h + 0.15 }
      ].sort(function (a, b) { return a.d - b.d; })[0];
      cx = Math.min(Math.max(out.x, room.x + 0.2), room.x + room.w - 0.2);
      cy = Math.min(Math.max(out.y, room.y + 0.2), room.y + room.h - 0.2);
    });
    return { x: cx, y: cy };
  }

  function drawRoomLabel(room) {
    var w = room.w * view.scale, h = room.h * view.scale;
    if (w < 46 || h < 30) { return; }
    var a = labelAnchor(room);
    var cx = toScreenX(a.x), cy = toScreenY(a.y);
    var area = polyArea(roomPolygon(room));

    ctx.textAlign = 'center';
    ctx.fillStyle = theme.text;
    ctx.font = '600 ' + Math.max(11, Math.min(15, w / 9)) + 'px ' + bodyFont();
    ctx.fillText(room.name, cx, cy - (h > 62 ? 10 : 4));

    if (h > 52) {
      ctx.fillStyle = theme.muted;
      ctx.font = Math.max(10, Math.min(13, w / 11)) + 'px ' + bodyFont();
      ctx.fillText(num(room.w, 2) + ' × ' + num(room.h, 2) + ' м', cx, cy + 7);
    }
    if (h > 78) {
      ctx.fillStyle = theme.accent;
      ctx.font = '600 ' + Math.max(10, Math.min(13, w / 11)) + 'px ' + bodyFont();
      ctx.fillText(num(area, 2) + ' м²', cx, cy + 24);
    }
  }

  function bodyFont() {
    return "'Source Sans 3', 'Helvetica Neue', Arial, sans-serif";
  }

  function drawHandles(room) {
    var pts = handlePoints(room);
    ctx.fillStyle = theme.accent;
    ctx.strokeStyle = theme.bg;
    ctx.lineWidth = 2;
    pts.forEach(function (p) {
      ctx.beginPath();
      ctx.arc(p.sx, p.sy, HANDLE / 2 + 2, 0, Math.PI * 2);
      ctx.fill(); ctx.stroke();
    });
  }

  function handlePoints(room) {
    var x1 = toScreenX(room.x), y1 = toScreenY(room.y);
    var x2 = toScreenX(room.x + room.w), y2 = toScreenY(room.y + room.h);
    return [
      { k: 'nw', sx: x1, sy: y1 }, { k: 'ne', sx: x2, sy: y1 },
      { k: 'sw', sx: x1, sy: y2 }, { k: 'se', sx: x2, sy: y2 }
    ];
  }

  function drawDimensions(room) {
    var x1 = toScreenX(room.x), y1 = toScreenY(room.y);
    var x2 = toScreenX(room.x + room.w), y2 = toScreenY(room.y + room.h);
    var off = 18;
    ctx.strokeStyle = theme.accent;
    ctx.fillStyle = theme.accent;
    ctx.lineWidth = 1;
    ctx.font = '600 11px ' + bodyFont();
    ctx.textAlign = 'center';

    ctx.beginPath();
    ctx.moveTo(x1, y1 - off); ctx.lineTo(x2, y1 - off);
    ctx.moveTo(x1, y1 - off - 4); ctx.lineTo(x1, y1 - off + 4);
    ctx.moveTo(x2, y1 - off - 4); ctx.lineTo(x2, y1 - off + 4);
    ctx.stroke();
    ctx.fillText(num(room.w, 2) + ' м', (x1 + x2) / 2, y1 - off - 5);

    ctx.save();
    ctx.beginPath();
    ctx.moveTo(x1 - off, y1); ctx.lineTo(x1 - off, y2);
    ctx.moveTo(x1 - off - 4, y1); ctx.lineTo(x1 - off + 4, y1);
    ctx.moveTo(x1 - off - 4, y2); ctx.lineTo(x1 - off + 4, y2);
    ctx.stroke();
    ctx.translate(x1 - off - 6, (y1 + y2) / 2);
    ctx.rotate(-Math.PI / 2);
    ctx.fillText(num(room.h, 2) + ' м', 0, 0);
    ctx.restore();
  }

  function drawGhost(r) {
    ctx.save();
    ctx.setLineDash([6, 4]);
    ctx.strokeStyle = theme.accent;
    ctx.fillStyle = theme.dark ? 'rgba(226,112,92,0.14)' : 'rgba(192,57,43,0.10)';
    ctx.lineWidth = 2;
    var sx = toScreenX(r.x), sy = toScreenY(r.y);
    ctx.fillRect(sx, sy, r.w * view.scale, r.h * view.scale);
    ctx.strokeRect(sx, sy, r.w * view.scale, r.h * view.scale);
    ctx.restore();
    ctx.fillStyle = theme.accent;
    ctx.font = '600 12px ' + bodyFont();
    ctx.textAlign = 'left';
    ctx.fillText(num(r.w, 2) + ' × ' + num(r.h, 2) + ' м', sx + 6, sy + 18);
  }

  function drawScaleBar(size) {
    if (!(view.scale > 1)) { return; }
    var meters = 1;
    // Ограничение по числу шагов: без него отрицательный масштаб крутит цикл вечно.
    for (var i = 0; i < 12 && meters * view.scale < 60; i++) { meters *= 2; }
    var w = meters * view.scale;
    var x = 16, y = size.h - 18;
    ctx.strokeStyle = theme.muted;
    ctx.fillStyle = theme.muted;
    ctx.lineWidth = 1;
    ctx.beginPath();
    ctx.moveTo(x, y - 5); ctx.lineTo(x, y); ctx.lineTo(x + w, y); ctx.lineTo(x + w, y - 5);
    ctx.stroke();
    ctx.font = '11px ' + bodyFont();
    ctx.textAlign = 'left';
    ctx.fillText(meters + ' м', x + w + 8, y + 1);
  }

  function drawEmptyHint(size) {
    ctx.textAlign = 'center';
    ctx.fillStyle = theme.text;
    ctx.font = '600 16px ' + bodyFont();
    ctx.fillText('Рисуйте свою планировку', size.w / 2, size.h / 2 - 22);
    ctx.fillStyle = theme.muted;
    ctx.font = '13px ' + bodyFont();
    ctx.fillText('Инструмент «Комната» — растяните прямоугольник прямо на сетке,', size.w / 2, size.h / 2 + 2);
    ctx.fillText('либо задайте точные размеры в панели справа.', size.w / 2, size.h / 2 + 22);
    ctx.fillText('Готовые планировки сверху — это просто быстрый старт.', size.w / 2, size.h / 2 + 46);
  }

  // ── Попадания ─────────────────────────────────────────────────────────────

  function hitHandle(sx, sy) {
    if (!sel || sel.kind !== 'room') { return null; }
    var room = roomById(sel.id);
    if (!room) { return null; }
    var pts = handlePoints(room), best = null;
    pts.forEach(function (p) {
      var d = Math.hypot(p.sx - sx, p.sy - sy);
      if (d <= HANDLE + 4 && (!best || d < best.d)) { best = { k: p.k, d: d }; }
    });
    return best;
  }

  function hitOpening(sx, sy) {
    for (var i = state.openings.length - 1; i >= 0; i--) {
      var op = state.openings[i], c = openingCenter(op);
      if (!c) { continue; }
      if (Math.hypot(toScreenX(c.x) - sx, toScreenY(c.y) - sy) <= Math.max(10, op.w / 2 * view.scale)) {
        return op;
      }
    }
    return null;
  }

  function hitRoom(wx, wy) {
    for (var i = state.rooms.length - 1; i >= 0; i--) {
      if (pointInRoom(state.rooms[i], wx, wy)) { return state.rooms[i]; }
    }
    return null;
  }

  /** Ближайшая стена комнаты к точке — для установки проёма. */
  function nearestWall(room, wx, wy) {
    var d = {
      n: Math.abs(wy - room.y), s: Math.abs(wy - (room.y + room.h)),
      w: Math.abs(wx - room.x), e: Math.abs(wx - (room.x + room.w))
    };
    var best = 'n';
    Object.keys(d).forEach(function (k) { if (d[k] < d[best]) { best = k; } });
    var at = (best === 'n' || best === 's') ? wx - room.x : wy - room.y;
    return { wall: best, at: at, dist: d[best] };
  }

  // ── Взаимодействия ────────────────────────────────────────────────────────

  function localPoint(ev) {
    var r = canvas.getBoundingClientRect();
    return { sx: ev.clientX - r.left, sy: ev.clientY - r.top };
  }

  canvas.addEventListener('pointerdown', function (ev) {
    // Захват указателя — удобство, а не необходимость: если он не удался,
    // обработчик обязан доработать, иначе клик просто пропадёт.
    try { canvas.setPointerCapture(ev.pointerId); } catch (e) { /* указатель уже неактивен */ }
    var p = localPoint(ev);
    var wx = toWorldX(p.sx), wy = toWorldY(p.sy);

    if (tool === 'room') {
      snapshot();
      drag = { mode: 'create', x0: snap(wx), y0: snap(wy), rect: null };
      return;
    }

    if (tool === 'door' || tool === 'window') {
      var room = hitRoom(wx, wy);
      if (!room) { return; }
      var nw = nearestWall(room, wx, wy);
      var def = OPENING[tool];
      var len = wallLen(room, nw.wall);
      if (len < def.w + 0.2) { flash('Стена короче проёма — выберите стену подлиннее'); return; }
      snapshot();
      state.openings.push({
        id: state.seq++, roomId: room.id, wall: nw.wall,
        at: Math.min(Math.max(nw.at, def.w / 2), len - def.w / 2),
        type: tool, w: def.w, h: def.h
      });
      setTool('select');
      refresh();
      return;
    }

    if (tool === 'erase') {
      var op = hitOpening(p.sx, p.sy);
      if (op) { snapshot(); removeOpening(op.id); refresh(); return; }
      var rr = hitRoom(wx, wy);
      if (rr) { snapshot(); removeRoom(rr.id); refresh(); }
      return;
    }

    // Инструмент выбора: маркер → проём → комната → панорама.
    var h = hitHandle(p.sx, p.sy);
    if (h) {
      snapshot();
      var r0 = roomById(sel.id);
      drag = { mode: 'resize', k: h.k, id: r0.id,
               orig: { x: r0.x, y: r0.y, w: r0.w, h: r0.h } };
      return;
    }
    var opHit = hitOpening(p.sx, p.sy);
    if (opHit) {
      sel = { kind: 'opening', id: opHit.id };
      snapshot();
      drag = { mode: 'moveOpening', id: opHit.id };
      renderPanel(); draw();
      return;
    }
    var room2 = hitRoom(wx, wy);
    if (room2) {
      sel = { kind: 'room', id: room2.id };
      snapshot();
      drag = { mode: 'move', id: room2.id, dx: wx - room2.x, dy: wy - room2.y };
      renderPanel(); draw();
      return;
    }
    sel = null;
    drag = { mode: 'pan', sx: p.sx, sy: p.sy, px: view.px, py: view.py };
    renderPanel(); draw();
  });

  canvas.addEventListener('pointermove', function (ev) {
    var p = localPoint(ev);
    if (!drag) { updateCursor(p); return; }
    var wx = toWorldX(p.sx), wy = toWorldY(p.sy);

    if (drag.mode === 'pan') {
      view.px = drag.px + (p.sx - drag.sx);
      view.py = drag.py + (p.sy - drag.sy);
      draw(); return;
    }
    if (drag.mode === 'create') {
      var x1 = Math.min(drag.x0, snap(wx)), y1 = Math.min(drag.y0, snap(wy));
      var x2 = Math.max(drag.x0, snap(wx)), y2 = Math.max(drag.y0, snap(wy));
      drag.rect = { x: x1, y: y1, w: snap(x2 - x1), h: snap(y2 - y1) };
      draw(); return;
    }
    if (drag.mode === 'move') {
      var r = roomById(drag.id);
      if (!r) { return; }
      r.x = snap(wx - drag.dx); r.y = snap(wy - drag.dy);
      draw(); return;
    }
    if (drag.mode === 'resize') {
      resizeRoom(drag, wx, wy);
      draw(); return;
    }
    if (drag.mode === 'moveOpening') {
      var op = openingById(drag.id);
      if (!op) { return; }
      var rm = roomById(op.roomId);
      var nw = nearestWall(rm, wx, wy);
      var len;
      if (nw.dist < 0.6) {                       // перетащили на соседнюю стену
        len = wallLen(rm, nw.wall);
        if (len >= op.w + 0.2) { op.wall = nw.wall; op.at = nw.at; }
      } else {
        op.at = (op.wall === 'n' || op.wall === 's') ? wx - rm.x : wy - rm.y;
      }
      len = wallLen(rm, op.wall);
      op.at = snap(Math.min(Math.max(op.at, op.w / 2), len - op.w / 2));
      draw(); return;
    }
  });

  function resizeRoom(d, wx, wy) {
    var r = roomById(d.id);
    if (!r) { return; }
    var o = d.orig;
    var left = o.x, top = o.y, right = o.x + o.w, bottom = o.y + o.h;
    if (d.k === 'nw' || d.k === 'sw') { left = Math.min(snap(wx), right - MIN_SIDE); }
    if (d.k === 'ne' || d.k === 'se') { right = Math.max(snap(wx), left + MIN_SIDE); }
    if (d.k === 'nw' || d.k === 'ne') { top = Math.min(snap(wy), bottom - MIN_SIDE); }
    if (d.k === 'sw' || d.k === 'se') { bottom = Math.max(snap(wy), top + MIN_SIDE); }
    r.x = left; r.y = top; r.w = snap(right - left); r.h = snap(bottom - top);
  }

  function endDrag() {
    if (!drag) { return; }
    if (drag.mode === 'create') {
      var r = drag.rect;
      if (r && r.w >= MIN_SIDE && r.h >= MIN_SIDE) {
        var room = makeRoom(defaultTypeFor(), r.x, r.y, r.w, r.h);
        state.rooms.push(room);
        sel = { kind: 'room', id: room.id };
        setTool('select');
      } else {
        history.pop();                     // ничего не создали — снимок не нужен
        syncHistoryButtons();
      }
    }
    if (drag.mode === 'resize' || drag.mode === 'move') { clampOpenings(); }
    var was = drag.mode;
    drag = null;
    if (was === 'pan') { draw(); } else { refresh(); }
  }

  canvas.addEventListener('pointerup', endDrag);
  canvas.addEventListener('pointercancel', endDrag);
  canvas.addEventListener('pointerleave', function () { if (drag && drag.mode === 'pan') { endDrag(); } });

  canvas.addEventListener('wheel', function (ev) {
    ev.preventDefault();
    var p = localPoint(ev);
    zoomBy(ev.deltaY < 0 ? 1.12 : 1 / 1.12, p.sx, p.sy);
  }, { passive: false });

  function updateCursor(p) {
    var cur = 'default';
    if (tool === 'room') { cur = 'crosshair'; }
    else if (tool === 'door' || tool === 'window') { cur = 'copy'; }
    else if (tool === 'erase') { cur = 'not-allowed'; }
    else {
      var h = hitHandle(p.sx, p.sy);
      if (h) { cur = (h.k === 'nw' || h.k === 'se') ? 'nwse-resize' : 'nesw-resize'; }
      else if (hitOpening(p.sx, p.sy)) { cur = 'grab'; }
      else if (hitRoom(toWorldX(p.sx), toWorldY(p.sy))) { cur = 'move'; }
      else { cur = 'grab'; }
    }
    canvas.style.cursor = cur;
  }

  /** Новую комнату называем по тому, чего на плане ещё нет. */
  function defaultTypeFor() {
    var has = {};
    state.rooms.forEach(function (r) { has[r.type] = (has[r.type] || 0) + 1; });
    if (!has.zhilaya) { return 'zhilaya'; }
    if (!has.kuhnya) { return 'kuhnya'; }
    if (!has.sanuzel) { return 'sanuzel'; }
    if (!has.koridor) { return 'koridor'; }
    return 'zhilaya';
  }

  /**
   * Куда поставить комнату, добавленную по числам: вплотную справа к тому,
   * что уже нарисовано. Иначе она легла бы поверх существующих.
   */
  function nextRoomSpot() {
    var b = planBounds();
    return b ? { x: snap(b.x2), y: snap(b.y1) } : { x: 0, y: 0 };
  }

  /** Добавить комнату точными размерами, без рисования мышью. */
  function addRoomByNumbers(type, w, h, name) {
    w = snap(Math.max(MIN_SIDE, Math.min(30, w || 0)));
    h = snap(Math.max(MIN_SIDE, Math.min(30, h || 0)));
    snapshot();
    var spot = nextRoomSpot();
    var room = makeRoom(type, spot.x, spot.y, w, h, name || null);
    if (name) { room.nameEdited = true; }
    state.rooms.push(room);
    sel = { kind: 'room', id: room.id };
    fitToPlan();
    refresh();
    return room;
  }

  /** Чистый лист: убрать всё, но сохранить настройки сценария и уровня. */
  function startBlank() {
    snapshot();
    var lv = state.level, sc = state.scenario, hh = state.height, rw = state.replaceWindows;
    state = blankState();
    state.level = lv; state.scenario = sc; state.height = hh; state.replaceWindows = rw;
    sel = null;
    fitToPlan();
    setTool('room');
    refresh();
    flash('Чистый лист. Растяните комнату на сетке или задайте размеры справа');
  }

  function removeRoom(id) {
    state.rooms = state.rooms.filter(function (r) { return r.id !== id; });
    state.openings = state.openings.filter(function (o) { return o.roomId !== id; });
    if (sel && sel.kind === 'room' && sel.id === id) { sel = null; }
  }
  function removeOpening(id) {
    state.openings = state.openings.filter(function (o) { return o.id !== id; });
    if (sel && sel.kind === 'opening' && sel.id === id) { sel = null; }
  }

  document.addEventListener('keydown', function (ev) {
    if (/^(INPUT|SELECT|TEXTAREA)$/.test((ev.target.tagName || '').toUpperCase())) { return; }
    if (!root.contains(document.activeElement) && document.activeElement !== document.body) { return; }
    if (ev.key === 'Delete' || ev.key === 'Backspace') {
      if (sel) {
        ev.preventDefault(); snapshot();
        if (sel.kind === 'room') { removeRoom(sel.id); } else { removeOpening(sel.id); }
        refresh();
      }
    } else if ((ev.ctrlKey || ev.metaKey) && ev.key.toLowerCase() === 'z') {
      ev.preventDefault();
      if (ev.shiftKey) { redo(); } else { undo(); }
    } else if (ev.key === 'Escape') {
      setTool('select'); sel = null; renderPanel(); draw();
    }
  });

  // ── Расчёт ────────────────────────────────────────────────────────────────

  /** Геометрия одной комнаты: контур с вырезами минус проёмы в стенах. */
  function roomGeom(room) {
    var poly = roomPolygon(room);
    var floor = polyArea(poly);
    var perim = polyPerimeter(poly);
    var opArea = 0, doors = 0, windows = 0, winArea = 0;
    state.openings.forEach(function (op) {
      if (op.roomId !== room.id) { return; }
      opArea += op.w * op.h;
      if (op.type === 'door') { doors++; } else { windows++; winArea += op.w * op.h; }
    });
    var walls = Math.max(0, perim * state.height - opArea);
    // Дверные проёмы разрывают плинтус, оконные — нет.
    var plinth = Math.max(0, perim - doors * OPENING.door.w);
    return { floor: floor, perim: perim, walls: walls, ceiling: floor,
             doors: doors, windows: windows, winArea: winArea, plinth: plinth };
  }

  function calc() {
    var L = LEVELS[state.level] || LEVELS.standart;
    var SC = SCENARIOS[state.scenario] || SCENARIOS.vtorichka;

    var total = { floor: 0, walls: 0, ceiling: 0, perim: 0, doors: 0, windows: 0, winArea: 0, plinth: 0, wet: 0 };
    var stages = {}; STAGES.forEach(function (s) { stages[s.key] = 0; });
    var mats = {};   // ключ → { name, qty, unit, note }
    var lines = [];  // строки сметы: { stage, name, qty, unit, sum }

    function addMat(key, name, qty, unit, note) {
      if (qty <= 0) { return; }
      if (!mats[key]) { mats[key] = { name: name, qty: 0, unit: unit, note: note || '' }; }
      mats[key].qty += qty;
    }
    function addLine(stage, name, qty, unit, sum) {
      if (sum <= 0 && qty <= 0) { return; }
      stages[stage] += sum;
      lines.push({ stage: stage, name: name, qty: qty, unit: unit, sum: sum });
    }

    state.rooms.forEach(function (room) {
      var g = roomGeom(room);
      var t = ROOM_TYPES[room.type] || ROOM_TYPES.zhilaya;
      total.floor += g.floor; total.walls += g.walls; total.ceiling += g.ceiling;
      total.perim += g.perim; total.doors += g.doors; total.windows += g.windows;
      total.winArea += g.winArea; total.plinth += g.plinth;
      if (t.wet) { total.wet += g.floor; }

      // ── Демонтаж
      if (SC.demo) {
        addLine('demo', 'Демонтаж, ' + room.name, g.floor, 'м²', g.floor * RATES.demoWork * L.work);
      }

      // ── Инженерия
      addLine('eng', 'Электроточки, ' + room.name, room.points, 'шт', room.points * RATES.elecPoint * L.work);
      if (room.type === 'sanuzel') {
        addLine('eng', 'Сантехника, ' + room.name, 1, 'компл', RATES.plumbBath * L.work);
        addLine('eng', 'Гидроизоляция, ' + room.name, g.floor, 'м²',
                g.floor * (RATES.waterproofMat * L.mat + RATES.waterproofWork * L.work));
        addMat('wp', 'Гидроизоляция обмазочная', g.floor * 3.5, 'кг', 'два слоя с заходом на стены');
      }
      if (room.type === 'kuhnya') {
        addLine('eng', 'Подводка кухни, ' + room.name, 1, 'компл', RATES.plumbKitchen * L.work);
      }
      if (room.warmFloor) {
        var wfArea = g.floor * RATES.warmFloorFrac;
        addLine('eng', 'Тёплый пол, ' + room.name, wfArea, 'м²',
                wfArea * (RATES.warmFloorMat * L.mat + RATES.warmFloorWork * L.work));
        addMat('wf', 'Тёплый пол (мат или кабель)', wfArea, 'м²', 'без учёта терморегулятора');
      }

      // ── Черновая
      if (SC.rough) {
        addLine('rough', 'Штукатурка стен, ' + room.name, g.walls, 'м²',
                g.walls * (RATES.plasterMat * L.mat + RATES.plasterWork * L.work));
        addMat('plaster', 'Штукатурка гипсовая', g.walls * 15 * 0.85, 'кг', 'слой 15 мм');
        var fin = FINISH.floor[room.floor];
        if (fin && fin.screed) {
          addLine('rough', 'Стяжка пола, ' + room.name, g.floor, 'м²',
                  g.floor * (RATES.screedMat * L.mat + RATES.screedWork * L.work));
          addMat('screed', 'Смесь для стяжки', g.floor * 40 * 2, 'кг', 'слой 40 мм');
        }
      }

      // ── Чистовая: пол
      var f = FINISH.floor[room.floor];
      if (f) {
        addLine('finish', f.name + ', ' + room.name, g.floor, 'м²',
                g.floor * (f.mat * L.mat + f.work * L.work));
        floorMaterials(addMat, room.floor, g.floor);
      }
      // ── Чистовая: стены
      var wf = FINISH.walls[room.walls];
      if (wf) {
        addLine('finish', wf.name + ' стен, ' + room.name, g.walls, 'м²',
                g.walls * (wf.mat * L.mat + wf.work * L.work));
        wallMaterials(addMat, room.walls, g.walls, g.perim, state.height);
      }
      // ── Чистовая: потолок
      var cf = FINISH.ceiling[room.ceiling];
      if (cf) {
        addLine('finish', cf.name + ' потолок, ' + room.name, g.ceiling, 'м²',
                g.ceiling * (cf.mat * L.mat + cf.work * L.work));
        ceilingMaterials(addMat, room.ceiling, g.ceiling, g.perim);
      }
      // ── Плинтус
      if (room.floor !== 'plitka' || room.type !== 'sanuzel') {
        addLine('finish', 'Плинтус, ' + room.name, g.plinth, 'пог. м',
                g.plinth * (RATES.plinthMat * L.mat + RATES.plinthWork * L.work));
        addMat('plinth', 'Плинтус', g.plinth * 1.05, 'пог. м', 'запас 5% на подрезку');
      }
    });

    // ── Общие по квартире
    if (state.rooms.length) {
      addLine('eng', 'Сборка и подключение щитка', 1, 'шт', RATES.elecPanel * L.work);
    }
    if (SC.demo && total.floor > 0) {
      var m3 = total.floor * RATES.debrisPerM2;
      addLine('demo', 'Вывоз строительного мусора', m3, 'м³', m3 * RATES.debrisPerM3);
    }
    if (total.doors > 0) {
      addLine('units', 'Межкомнатные двери с монтажом', total.doors, 'шт', total.doors * RATES.doorUnit * L.mat);
      addMat('doors', 'Дверной блок с фурнитурой', total.doors, 'компл', '');
    }
    if (state.replaceWindows && total.winArea > 0) {
      addLine('units', 'Замена окон', total.winArea, 'м²', total.winArea * RATES.windowPerM2 * L.mat);
    }

    var grand = 0;
    STAGES.forEach(function (s) { grand += stages[s.key]; });

    return {
      total: total, stages: stages, grand: grand,
      lines: lines, mats: mats,
      schedule: schedule(total.floor, stages),
      level: L, scenario: SC
    };
  }

  /** Материалы пола с запасом на подрезку. */
  function floorMaterials(add, kind, area) {
    if (kind === 'plitka') {
      add('tileFloor', 'Керамогранит на пол', area * 1.1, 'м²', 'запас 10% на подрезку');
      add('tileGlue', 'Клей плиточный C2', area * 6, 'кг', 'гребёнка 10 мм');
      add('grout', 'Затирка', area * 0.5, 'кг', '');
    } else if (kind === 'laminat') {
      add('laminat', 'Ламинат', area * 1.07, 'м²', 'запас 7%');
      add('podlozhka', 'Подложка', area * 1.05, 'м²', '');
    } else if (kind === 'kvarcvinil') {
      add('spc', 'Кварцвинил SPC', area * 1.07, 'м²', 'запас 7%');
      add('podlozhka', 'Подложка', area * 1.05, 'м²', '');
    } else if (kind === 'parket') {
      add('parket', 'Паркетная доска', area * 1.08, 'м²', 'запас 8%');
      add('podlozhka', 'Подложка', area * 1.05, 'м²', '');
    } else if (kind === 'linoleum') {
      add('linoleum', 'Линолеум', area * 1.1, 'м²', 'с учётом ширины рулона');
    }
  }

  /** Материалы стен. Обои считаются полосами, а не площадью. */
  function wallMaterials(add, kind, area, perim, height) {
    if (kind === 'kraska') {
      add('paint', 'Краска для стен', area * 2 / 10, 'л', 'два слоя, укрывистость 10 м²/л');
      add('primer', 'Грунтовка', area * 0.15, 'л', '');
      add('putty', 'Шпаклёвка финишная', area * 1.2, 'кг', '');
    } else if (kind === 'oboi') {
      var rollW = 1.06, rollL = 10.05;
      var strips = Math.ceil(perim / rollW);
      var perRoll = Math.max(1, Math.floor(rollL / (height + 0.1)));
      add('oboi', 'Обои', Math.ceil(strips / perRoll), 'рул', 'ширина 1,06 м, подгонка 10 см');
      add('glue', 'Клей обойный', area / 35, 'уп', 'пачка на 35 м²');
      add('primer', 'Грунтовка', area * 0.15, 'л', '');
      add('putty', 'Шпаклёвка финишная', area * 1.0, 'кг', '');
    } else if (kind === 'plitka') {
      add('tileWall', 'Плитка настенная', area * 1.1, 'м²', 'запас 10%');
      add('tileGlue', 'Клей плиточный C2', area * 5, 'кг', 'гребёнка 8 мм');
      add('grout', 'Затирка', area * 0.4, 'кг', '');
    } else if (kind === 'dekor') {
      add('dekor', 'Декоративная штукатурка', area * 1.5, 'кг', 'зависит от фактуры');
      add('primer', 'Грунт колерованный', area * 0.2, 'л', '');
    } else if (kind === 'shtukaturka') {
      add('primer', 'Грунтовка', area * 0.15, 'л', '');
    }
  }

  function ceilingMaterials(add, kind, area, perim) {
    if (kind === 'natyazhnoy') {
      add('stretch', 'Полотно натяжного потолка', area, 'м²', '');
      add('bagette', 'Багет для натяжного', perim * 1.05, 'пог. м', '');
    } else if (kind === 'pokraska') {
      add('paintCeil', 'Краска для потолка', area * 2 / 10, 'л', 'два слоя');
      add('putty', 'Шпаклёвка финишная', area * 1.2, 'кг', '');
    } else if (kind === 'gkl') {
      add('gkl', 'Гипсокартон потолочный', Math.ceil(area / 3), 'лист', 'лист 1200 × 2500 мм');
      add('profile', 'Профиль и подвесы', area * 3.2, 'пог. м', '');
      add('putty', 'Шпаклёвка и лента', area * 1.4, 'кг', '');
    }
  }

  /** Сроки: рабочие дни по нормам выработки плюс технологические паузы. */
  function schedule(area, stages) {
    var rows = [], work = 0;
    STAGES.forEach(function (s) {
      if (stages[s.key] <= 0) { return; }
      var d = Math.max(1, Math.round(area * s.perM2));
      work += d;
      rows.push({ name: s.name, days: d, hue: s.hue });
    });
    // Стяжка сохнет около недели на первые 40 мм, ждать приходится календарно.
    var pause = stages.rough > 0 ? 14 : 0;
    var calendarDays = Math.round(work * 1.4) + pause;
    return { rows: rows, workDays: work, pause: pause,
             calendar: calendarDays, months: calendarDays / 30 };
  }

  // ── Вывод ─────────────────────────────────────────────────────────────────

  function refresh() {
    state.rooms.forEach(clampCuts);   // до проёмов: вырезы задают свободные участки стен
    clampOpenings();
    draw();
    renderPanel();
    renderResults();
    save();
    syncHistoryButtons();
  }

  function renderResults() {
    var r = calc();
    renderSummary(r);
    renderEstimate(r);
    renderMaterials(r);
    renderSchedule(r);
  }

  function renderSummary(r) {
    var box = el('pl-summary');
    if (!box) { return; }
    if (!state.rooms.length) {
      box.innerHTML = '<p class="pl-empty">План пуст. Нарисуйте первую комнату на холсте или задайте ' +
        'её размеры в панели справа — смета посчитается сразу. Готовые планировки сверху нужны ' +
        'только для быстрого старта.</p>';
      return;
    }
    var t = r.total;
    var cards = [
      ['Комнат', num(state.rooms.length, 0), 'на плане'],
      ['Площадь пола', num(t.floor, 1) + ' м²', 'сумма по комнатам'],
      ['Площадь стен', num(t.walls, 1) + ' м²', 'за вычетом проёмов'],
      ['Потолки', num(t.ceiling, 1) + ' м²', 'высота ' + num(state.height, 2) + ' м'],
      ['Периметр', num(t.perim, 1) + ' м', 'плинтус ' + num(t.plinth, 1) + ' м'],
      ['Проёмы', num(t.doors, 0) + ' / ' + num(t.windows, 0), 'дверей / окон']
    ];
    box.innerHTML = '<div class="pl-cards">' + cards.map(function (c) {
      return '<div class="pl-card"><span class="pl-card-label">' + c[0] + '</span>' +
             '<span class="pl-card-value">' + c[1] + '</span>' +
             '<span class="pl-card-note">' + c[2] + '</span></div>';
    }).join('') + '</div>';
  }

  function renderEstimate(r) {
    var box = el('pl-estimate');
    if (!box) { return; }
    if (!state.rooms.length) { box.innerHTML = ''; return; }

    var reserve = r.grand * 0.12;
    var html = '<div class="pl-total">' +
      '<span class="pl-total-label">Ремонт под ключ, ориентир</span>' +
      '<span class="pl-total-value">' + money(r.grand) + '</span>' +
      '<span class="pl-total-note">' + money(r.grand / Math.max(1, r.total.floor)) + ' за м² · ' +
      r.level.name.toLowerCase() + ' · ' + r.scenario.name.toLowerCase() + '</span></div>';

    // Полоса долей этапов — она же легенда.
    var bar = '', legend = '';
    STAGES.forEach(function (s) {
      var v = r.stages[s.key];
      if (v <= 0) { return; }
      var pct = v / r.grand * 100;
      var color = 'hsl(' + s.hue + ', 55%, ' + (theme.dark ? 58 : 46) + '%)';
      bar += '<span class="pl-bar-seg" style="width:' + pct.toFixed(2) + '%;background:' + color + '" ' +
             'title="' + esc(s.name) + ': ' + money(v) + '"></span>';
      legend += '<li><i style="background:' + color + '"></i>' +
                '<span class="pl-leg-name">' + esc(s.name) + '</span>' +
                '<span class="pl-leg-sum">' + money(v) + '</span>' +
                '<span class="pl-leg-pct">' + num(pct, 0) + '%</span></li>';
    });
    html += '<div class="pl-bar">' + bar + '</div><ul class="pl-legend">' + legend + '</ul>';

    html += '<div class="pl-reserve"><strong>Резерв 12%: ' + money(reserve) + '</strong>' +
            '<span>С резервом — ' + money(r.grand + reserve) + '. Это не запас на всякий случай, ' +
            'а обязательная строка: вскрытые сюрпризы и изменения по ходу есть в любом ремонте.</span></div>';

    // Развёрнутые строки по этапам.
    html += '<details class="pl-details"><summary>Показать смету построчно (' + r.lines.length + ' позиций)</summary>';
    STAGES.forEach(function (s) {
      var rows = r.lines.filter(function (l) { return l.stage === s.key; });
      if (!rows.length) { return; }
      html += '<h4 class="pl-stage-title">' + esc(s.name) + ' — ' + money(r.stages[s.key]) + '</h4>' +
              '<div class="pl-table-wrap"><table class="pl-table"><thead><tr>' +
              '<th>Позиция</th><th>Объём</th><th>Сумма</th></tr></thead><tbody>';
      rows.forEach(function (l) {
        html += '<tr><td>' + esc(l.name) + '</td><td>' + num(l.qty, l.qty < 10 ? 1 : 0) + ' ' +
                esc(l.unit) + '</td><td>' + money(l.sum) + '</td></tr>';
      });
      html += '</tbody></table></div>';
    });
    html += '</details>';

    box.innerHTML = html;
  }

  function renderMaterials(r) {
    var box = el('pl-materials');
    if (!box) { return; }
    var keys = Object.keys(r.mats);
    if (!keys.length) { box.innerHTML = ''; return; }
    var html = '<h3 class="pl-h3">Материалы с запасом</h3>' +
      '<p class="pl-note">Количества уже включают запас на подрезку и слои. Объём берут одной партией: ' +
      'у плитки и ламината оттенок меняется от партии к партии.</p>' +
      '<div class="pl-table-wrap"><table class="pl-table"><thead><tr>' +
      '<th>Материал</th><th>Количество</th><th>Примечание</th></tr></thead><tbody>';
    keys.forEach(function (k) {
      var m = r.mats[k];
      html += '<tr><td>' + esc(m.name) + '</td><td><strong>' + num(m.qty, m.qty < 20 ? 1 : 0) + ' ' +
              esc(m.unit) + '</strong></td><td>' + esc(m.note) + '</td></tr>';
    });
    box.innerHTML = html + '</tbody></table></div>';
  }

  function renderSchedule(r) {
    var box = el('pl-schedule');
    if (!box) { return; }
    if (!r.schedule.rows.length) { box.innerHTML = ''; return; }
    var s = r.schedule, max = 0;
    s.rows.forEach(function (row) { max = Math.max(max, row.days); });
    var html = '<h3 class="pl-h3">Сроки по этапам</h3><ul class="pl-gantt">';
    s.rows.forEach(function (row) {
      var color = 'hsl(' + row.hue + ', 55%, ' + (theme.dark ? 58 : 46) + '%)';
      html += '<li><span class="pl-gantt-name">' + esc(row.name) + '</span>' +
              '<span class="pl-gantt-track"><i style="width:' + (row.days / max * 100).toFixed(1) +
              '%;background:' + color + '"></i></span>' +
              '<span class="pl-gantt-days">' + row.days + ' ' + plural(row.days, 'день', 'дня', 'дней') + '</span></li>';
    });
    html += '</ul><p class="pl-note"><strong>Работы: ' + s.workDays + ' ' +
      plural(s.workDays, 'рабочий день', 'рабочих дня', 'рабочих дней') + '.</strong> ' +
      'Календарно с выходными и технологическими паузами — около ' + s.calendar + ' ' +
      plural(s.calendar, 'дня', 'дней', 'дней') + ', это ' + num(s.months, 1) + ' мес. ' +
      (s.pause ? 'В срок включено ' + s.pause + ' дней на набор прочности стяжки — ждать придётся независимо от числа рабочих.' : '') +
      '</p>';
    box.innerHTML = html;
  }

  // ── Панель выбранного объекта ─────────────────────────────────────────────

  function renderPanel() {
    var box = el('pl-panel');
    if (!box) { return; }

    if (!sel) {
      // Ничего не выбрано — панель не пустует, а предлагает добавить комнату
      // по числам. Рисование мышью остаётся, но не должно быть единственным
      // способом: со снятыми замерами удобнее вводить размеры напрямую.
      box.innerHTML =
        '<h3 class="pl-h3">Своя планировка</h3>' +
        '<p class="pl-hint">Добавьте комнаты по своим замерам — или растяните их на холсте ' +
        'инструментом «Комната». Новая комната встаёт вплотную справа, дальше её можно ' +
        'перетащить куда нужно.</p>' +
        '<div class="pl-add">' +
          field('Название', '<input type="text" id="add-name" value="' + esc(draft.name) +
                '" maxlength="24" placeholder="по типу комнаты">') +
          field('Тип', select('add-type', ROOM_TYPES, draft.type, function (k, v) { return v.name; })) +
          '<div class="pl-row2">' +
            field('Ширина, м', numberInput('add-w', draft.w, MIN_SIDE, 30, 0.1)) +
            field('Глубина, м', numberInput('add-h', draft.h, MIN_SIDE, 30, 0.1)) +
          '</div>' +
          '<button type="button" class="pl-btn pl-btn-wide" id="add-go">+ Добавить комнату</button>' +
        '</div>' +
        (state.rooms.length
          ? '<p class="pl-hint pl-hint-sep">Щёлкните комнату на плане, чтобы задать отделку. ' +
            'Перетаскивайте её за середину, размер меняйте за угловые маркеры.</p>'
          : '<p class="pl-hint pl-hint-sep">План пуст. Добавьте первую комнату — ' +
            'смета и материалы посчитаются сразу.</p>');

      // Черновик пишем на каждый ввод, чтобы перерисовка панели его не стёрла.
      var keep = function (id, key, isNum) {
        var n = el(id);
        if (!n) { return; }
        n.addEventListener('input', function () {
          draft[key] = isNum ? parseFloat(String(this.value).replace(',', '.')) : this.value;
        });
      };
      keep('add-name', 'name', false);
      keep('add-w', 'w', true);
      keep('add-h', 'h', true);
      el('add-type').addEventListener('change', function () { draft.type = this.value; });

      el('add-go').addEventListener('click', function () {
        var w = parseFloat(String(el('add-w').value).replace(',', '.'));
        var h = parseFloat(String(el('add-h').value).replace(',', '.'));
        if (!isFinite(w) || !isFinite(h) || w < MIN_SIDE || h < MIN_SIDE) {
          flash('Стороны комнаты — от ' + num(MIN_SIDE, 1) + ' м');
          return;
        }
        var nm = el('add-name').value.trim();
        addRoomByNumbers(el('add-type').value, w, h, nm);
        draft.name = '';
      });
      return;
    }
    if (sel.kind === 'opening') {
      var op = openingById(sel.id);
      if (!op) { box.innerHTML = ''; return; }
      var d = OPENING[op.type];
      box.innerHTML =
        '<h3 class="pl-h3">' + d.name + '</h3>' +
        field('Ширина, м', numberInput('op-w', op.w, 0.6, 4, 0.1)) +
        field('Высота, м', numberInput('op-h', op.h, 1.0, 3, 0.05)) +
        '<p class="pl-hint">Проём вычитается из площади стен. Перетащите его вдоль стены или на соседнюю.</p>' +
        '<button type="button" class="pl-btn pl-btn-danger" id="op-del">Удалить проём</button>';
      bind('op-w', function (v) { op.w = v; }, true);
      bind('op-h', function (v) { op.h = v; }, true);
      el('op-del').addEventListener('click', function () { snapshot(); removeOpening(op.id); refresh(); });
      return;
    }

    var room = roomById(sel.id);
    if (!room) { box.innerHTML = ''; return; }
    var g = roomGeom(room);

    var html = '<h3 class="pl-h3">Комната</h3>' +
      field('Название', '<input type="text" id="rm-name" value="' + esc(room.name) + '" maxlength="24">') +
      field('Тип', select('rm-type', ROOM_TYPES, room.type, function (k, v) { return v.name; })) +
      '<div class="pl-row2">' +
        field('Ширина, м', numberInput('rm-w', room.w, MIN_SIDE, 30, 0.1)) +
        field('Глубина, м', numberInput('rm-h', room.h, MIN_SIDE, 30, 0.1)) +
      '</div>' +
      '<div class="pl-facts">' +
        '<span>Пол <b>' + num(g.floor, 2) + ' м²</b></span>' +
        '<span>Стены <b>' + num(g.walls, 1) + ' м²</b></span>' +
        '<span>Периметр <b>' + num(g.perim, 1) + ' м</b></span>' +
      '</div>' +
      field('Пол', select('rm-floor', FINISH.floor, room.floor, function (k, v) { return v.name; })) +
      field('Стены', select('rm-walls', FINISH.walls, room.walls, function (k, v) { return v.name; })) +
      field('Потолок', select('rm-ceiling', FINISH.ceiling, room.ceiling, function (k, v) { return v.name; })) +
      field('Электроточек', numberInput('rm-points', room.points, 0, 60, 1)) +
      '<label class="pl-check"><input type="checkbox" id="rm-wf"' + (room.warmFloor ? ' checked' : '') +
        '> Тёплый пол</label>' +
      cutsSection(room) +
      '<button type="button" class="pl-btn pl-btn-danger" id="rm-del">Удалить комнату</button>';

    box.innerHTML = html;
    bindCuts(room);

    // nameEdited защищает своё название: при смене типа оно не затирается.
    bindText('rm-name', function (v) { room.name = v || 'Комната'; room.nameEdited = true; });
    bindSelect('rm-type', function (v) {
      room.type = v;
      var t = ROOM_TYPES[v];
      room.floor = t.def.floor; room.walls = t.def.walls; room.ceiling = t.def.ceiling;
      room.points = t.points; room.warmFloor = (v === 'sanuzel' || v === 'kuhnya');
      if (!room.nameEdited) { room.name = t.name; }
    });
    bind('rm-w', function (v) { room.w = snap(v); }, true);
    bind('rm-h', function (v) { room.h = snap(v); }, true);
    bindSelect('rm-floor', function (v) { room.floor = v; });
    bindSelect('rm-walls', function (v) { room.walls = v; });
    bindSelect('rm-ceiling', function (v) { room.ceiling = v; });
    bind('rm-points', function (v) { room.points = Math.round(v); });
    el('rm-wf').addEventListener('change', function () { snapshot(); room.warmFloor = this.checked; refresh(); });
    el('rm-del').addEventListener('click', function () { snapshot(); removeRoom(room.id); refresh(); });
  }

  /** Блок «Форма комнаты»: угловые вырезы и пристеночные короба. */
  function cutsSection(room) {
    var cuts = room.cuts || [];
    var html = '<div class="pl-cuts"><h4 class="pl-h4">Форма комнаты</h4>';

    if (!cuts.length) {
      html += '<p class="pl-hint">Прямоугольник. Добавьте вырез, если комната Г-образная ' +
              'или в неё заходит вентшахта либо короб.</p>';
    }

    cuts.forEach(function (c, i) {
      var place = c.kind === 'corner'
        ? select('cut-place-' + i, CORNER_LABEL, c.corner, function (k, v) { return v; })
        : select('cut-place-' + i, WALL_LABEL, c.wall, function (k, v) { return v; });
      html += '<div class="pl-cut">' +
        '<div class="pl-cut-head">' +
          '<span>' + (c.kind === 'corner' ? 'Угловой вырез' : 'Короб у стены') + '</span>' +
          '<button type="button" class="pl-cut-del" id="cut-del-' + i + '" ' +
            'aria-label="Удалить вырез">✕</button>' +
        '</div>' + place +
        '<div class="pl-row2">' +
          field(c.kind === 'corner' ? 'По ширине, м' : 'Длина, м', numberInput('cut-w-' + i, c.w, 0.2, 30, 0.1)) +
          field(c.kind === 'corner' ? 'По глубине, м' : 'Выступ, м', numberInput('cut-d-' + i, c.d, 0.2, 30, 0.1)) +
        '</div>' +
        (c.kind === 'wall'
          ? field('Отступ от угла, м', numberInput('cut-at-' + i, c.at, 0, 30, 0.1))
          : '') +
        '</div>';
    });

    html += '<div class="pl-group-row">' +
      '<button type="button" class="pl-btn" id="cut-add-corner">+ Угловой вырез</button>' +
      '<button type="button" class="pl-btn" id="cut-add-wall">+ Короб у стены</button>' +
      '</div></div>';
    return html;
  }

  /** Первый угол без выреза — чтобы новый не лёг поверх существующего. */
  function freeCorner(room) {
    var order = ['ne', 'nw', 'se', 'sw'];
    for (var i = 0; i < order.length; i++) {
      if (!cornerCut(room, order[i])) { return order[i]; }
    }
    return null;
  }

  function bindCuts(room) {
    var cuts = room.cuts || [];

    cuts.forEach(function (c, i) {
      bindSelect('cut-place-' + i, function (v) {
        if (c.kind === 'corner') { c.corner = v; } else { c.wall = v; }
      });
      bind('cut-w-' + i, function (v) { c.w = snap(v); }, true);
      bind('cut-d-' + i, function (v) { c.d = snap(v); }, true);
      if (c.kind === 'wall') { bind('cut-at-' + i, function (v) { c.at = snap(v); }, true); }
      var del = el('cut-del-' + i);
      if (del) {
        del.addEventListener('click', function () {
          snapshot();
          room.cuts.splice(i, 1);
          refresh();
        });
      }
    });

    el('cut-add-corner').addEventListener('click', function () {
      var corner = freeCorner(room);
      if (!corner) { flash('Все четыре угла уже заняты вырезами'); return; }
      snapshot();
      room.cuts = room.cuts || [];
      // По трети габарита: сразу видно Г-образную форму, дальше правится числами.
      room.cuts.push({ kind: 'corner', corner: corner,
                       w: snap(Math.max(0.6, room.w / 3)), d: snap(Math.max(0.6, room.h / 3)) });
      refresh();
      flash('Угловой вырез добавлен — задайте его размеры');
    });

    el('cut-add-wall').addEventListener('click', function () {
      // Ищем самый широкий свободный участок среди всех стен: если ставить
      // вслепую по центру, короб налезет на уже сделанный угловой вырез.
      var best = null;
      ['n', 'e', 's', 'w'].forEach(function (wall) {
        wallFreeIntervals(room, wall).forEach(function (iv) {
          var len = iv[1] - iv[0];
          if (!best || len > best.len) { best = { wall: wall, at: (iv[0] + iv[1]) / 2, len: len }; }
        });
      });
      if (!best || best.len < 0.4) { flash('На стенах не осталось места под короб'); return; }
      snapshot();
      room.cuts = room.cuts || [];
      room.cuts.push({ kind: 'wall', wall: best.wall, at: snap(best.at),
                       w: snap(Math.min(0.6, best.len)), d: 0.4 });
      refresh();
      flash('Короб добавлен — задайте длину, выступ и отступ от угла');
    });
  }

  function field(label, control) {
    return '<label class="pl-field"><span>' + label + '</span>' + control + '</label>';
  }
  function numberInput(id, val, min, max, step) {
    return '<input type="number" id="' + id + '" value="' + val + '" min="' + min +
           '" max="' + max + '" step="' + step + '" inputmode="decimal">';
  }
  function select(id, map, cur, label) {
    var out = '<select id="' + id + '">';
    Object.keys(map).forEach(function (k) {
      out += '<option value="' + k + '"' + (k === cur ? ' selected' : '') + '>' + esc(label(k, map[k])) + '</option>';
    });
    return out + '</select>';
  }
  function bind(id, apply, redrawPanel) {
    var node = el(id);
    if (!node) { return; }
    node.addEventListener('change', function () {
      var v = parseFloat(String(this.value).replace(',', '.'));
      if (!isFinite(v)) { return; }
      snapshot(); apply(v);
      if (redrawPanel) { refresh(); } else { draw(); renderResults(); save(); }
    });
  }
  function bindText(id, apply) {
    var node = el(id);
    if (!node) { return; }
    node.addEventListener('change', function () { snapshot(); apply(this.value.trim()); draw(); save(); });
  }
  function bindSelect(id, apply) {
    var node = el(id);
    if (!node) { return; }
    node.addEventListener('change', function () { snapshot(); apply(this.value); refresh(); });
  }

  // ── Панель инструментов и глобальные настройки ────────────────────────────

  function setTool(t) {
    tool = t;
    Array.prototype.forEach.call(root.querySelectorAll('[data-tool]'), function (b) {
      var on = b.getAttribute('data-tool') === t;
      b.classList.toggle('is-active', on);
      b.setAttribute('aria-pressed', on ? 'true' : 'false');
    });
  }

  var flashTimer = null;
  function flash(msg) {
    var node = el('pl-flash');
    if (!node) { return; }
    node.textContent = msg;
    node.classList.add('is-on');
    window.clearTimeout(flashTimer);
    flashTimer = window.setTimeout(function () { node.classList.remove('is-on'); }, 2600);
  }

  function bindUI() {
    Array.prototype.forEach.call(root.querySelectorAll('[data-tool]'), function (b) {
      b.addEventListener('click', function () { setTool(b.getAttribute('data-tool')); });
    });
    Array.prototype.forEach.call(root.querySelectorAll('[data-preset]'), function (b) {
      b.addEventListener('click', function () { applyPreset(b.getAttribute('data-preset')); });
    });
    var blank = root.querySelector('[data-blank]');
    if (blank) { blank.addEventListener('click', startBlank); }

    el('pl-zoom-in').addEventListener('click', function () { zoomBy(1.25); });
    el('pl-zoom-out').addEventListener('click', function () { zoomBy(1 / 1.25); });
    el('pl-fit').addEventListener('click', function () { fitToPlan(); draw(); });
    el('pl-undo').addEventListener('click', undo);
    el('pl-redo').addEventListener('click', redo);

    el('pl-clear').addEventListener('click', function () {
      if (!state.rooms.length) { return; }
      snapshot();
      var lv = state.level, sc = state.scenario, hh = state.height;
      state = blankState();
      state.level = lv; state.scenario = sc; state.height = hh;
      sel = null; fitToPlan(); refresh();
      flash('План очищен. Отменить — Ctrl+Z');
    });

    el('pl-level').addEventListener('change', function () { snapshot(); state.level = this.value; refresh(); });
    el('pl-scenario').addEventListener('change', function () { snapshot(); state.scenario = this.value; refresh(); });
    el('pl-height').addEventListener('change', function () {
      var v = parseFloat(String(this.value).replace(',', '.'));
      if (!isFinite(v) || v < 2 || v > 5) { this.value = state.height; return; }
      snapshot(); state.height = v; refresh();
    });
    el('pl-windows').addEventListener('change', function () {
      snapshot(); state.replaceWindows = this.checked; refresh();
    });

    el('pl-png').addEventListener('click', exportPng);
    el('pl-print').addEventListener('click', function () { window.print(); });
  }

  function exportPng() {
    // Перерисовываем на непрозрачном фоне: иначе в PNG уходит прозрачность.
    var link = document.createElement('a');
    link.download = 'plan-remonta.png';
    link.href = canvas.toDataURL('image/png');
    link.click();
    flash('План сохранён картинкой');
  }

  function syncGlobalInputs() {
    el('pl-level').value = state.level;
    el('pl-scenario').value = state.scenario;
    el('pl-height').value = state.height;
    el('pl-windows').checked = !!state.replaceWindows;
  }

  // ── Запуск ────────────────────────────────────────────────────────────────

  function boot() {
    readTheme();
    resizeCanvas();

    state = load();
    if (!state) {
      state = blankState();
      bindUI();
      syncGlobalInputs();
      setTool('select');
      applyPreset('odnushka');          // первый экран не должен быть пустым
      flash('Загружена типовая однушка — меняйте под свою квартиру');
      return;
    }
    // Восстановленный план: добиваем поля, которых могло не быть в старой версии.
    if (typeof state.height !== 'number') { state.height = 2.7; }
    if (!state.seq) { state.seq = 1000; }
    bindUI();
    syncGlobalInputs();
    setTool('select');
    fitToPlan();
    refresh();
  }

  // Пересчёт геометрии холста при изменении размеров контейнера.
  if (window.ResizeObserver) {
    new window.ResizeObserver(function () {
      resizeCanvas();
      draw();
    }).observe(canvas);
  } else {
    window.addEventListener('resize', function () { resizeCanvas(); draw(); });
  }

  // Смена темы меняет и цвета холста, и цвета полос в результатах.
  new MutationObserver(function () {
    readTheme();
    draw();
    renderResults();
  }).observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });

  boot();

})(window, document);
