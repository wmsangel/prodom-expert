/**
 * Каркас калькуляторов ДомЭксперт.
 *
 * Разметка калькулятора лежит в calculators/<slug>.html и выглядит так:
 *
 *   <form class="calc" data-calc="oboi"> …поля с атрибутом name… </form>
 *   <div class="calc-result" data-calc-result="oboi"></div>
 *   <script>DomCalc.register('oboi', function (v, f) { return '<html-результат>'; });</script>
 *
 * Каркас сам собирает значения полей, вызывает функцию расчёта при любом вводе
 * и подставляет результат. Ничего никуда не отправляется: расчёт идёт в браузере.
 */
(function (window, document) {
  'use strict';

  var NBSP = ' ';

  /** Форматирование чисел и денег в привычном виде: 12 500 ₽, 3,5 м². */
  var format = {
    num: function (value, digits) {
      if (!isFinite(value)) { return '—'; }
      var d = typeof digits === 'number' ? digits : 0;
      var fixed = Math.abs(value).toFixed(d);
      var parts = fixed.split('.');
      parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, NBSP);
      return (value < 0 ? '−' : '') + parts.join(',');
    },
    money: function (value) {
      return format.num(Math.round(value), 0) + NBSP + '₽';
    },
    range: function (from, to) {
      return format.money(from) + ' — ' + format.money(to);
    },
    /** Строка таблицы результата. */
    row: function (label, value, hint) {
      return '<tr><th scope="row">' + label + '</th><td>' + value + '</td>' +
             (hint ? '<td class="calc-hint">' + hint + '</td>' : '<td></td>') + '</tr>';
    },
    /** Таблица результата целиком. */
    table: function (rows, caption) {
      return '<div class="calc-table-wrap"><table class="calc-table">' +
             (caption ? '<caption>' + caption + '</caption>' : '') +
             '<tbody>' + rows.join('') + '</tbody></table></div>';
    },
    /** Крупная итоговая цифра. */
    total: function (label, value, note) {
      return '<div class="calc-total"><span class="calc-total-label">' + label + '</span>' +
             '<span class="calc-total-value">' + value + '</span>' +
             (note ? '<span class="calc-total-note">' + note + '</span>' : '') + '</div>';
    },
    /** Предупреждение или пояснение под результатом. */
    note: function (text) {
      return '<p class="calc-note">' + text + '</p>';
    }
  };

  var DomCalc = {
    _fns: {},

    /** Форматирование доступно снаружи: используется в тестах расчётов. */
    format: format,

    /** Регистрирует функцию расчёта: (values, format) => html. */
    register: function (name, fn) {
      this._fns[name] = fn;
      if (this._ready) { this.run(name); }
    },

    /** Считывает значения всех полей формы. */
    values: function (form) {
      var v = {};
      var fields = form.querySelectorAll('input, select, textarea');
      for (var i = 0; i < fields.length; i++) {
        var el = fields[i];
        if (!el.name) { continue; }
        if (el.type === 'checkbox') {
          v[el.name] = el.checked;
        } else if (el.type === 'radio') {
          if (el.checked) { v[el.name] = el.value; }
        } else if (el.type === 'number' || el.dataset.numeric === 'true') {
          var n = parseFloat(String(el.value).replace(',', '.'));
          v[el.name] = isFinite(n) ? n : 0;
        } else {
          v[el.name] = el.value;
        }
      }
      return v;
    },

    /** Пересчитывает один калькулятор по имени. */
    run: function (name) {
      var form = document.querySelector('form[data-calc="' + name + '"]');
      var out = document.querySelector('[data-calc-result="' + name + '"]');
      var fn = this._fns[name];
      if (!form || !out || !fn) { return; }
      try {
        var html = fn(this.values(form), format);
        out.innerHTML = html || '';
        out.hidden = !html;
      } catch (err) {
        out.innerHTML = '<p class="calc-note">Проверьте введённые значения — расчёт не получился.</p>';
        out.hidden = false;
        if (window.console && console.warn) { console.warn('[calc:' + name + ']', err); }
      }
    },

    /** Навешивает пересчёт на все формы страницы. */
    init: function () {
      var self = this;
      var forms = document.querySelectorAll('form[data-calc]');
      for (var i = 0; i < forms.length; i++) {
        (function (form) {
          var name = form.getAttribute('data-calc');
          form.addEventListener('input', function () { self.run(name); });
          form.addEventListener('change', function () { self.run(name); });
          form.addEventListener('submit', function (e) { e.preventDefault(); self.run(name); });
          // Кнопка «сбросить» возвращает значения по умолчанию и пересчитывает
          form.addEventListener('reset', function () {
            window.setTimeout(function () { self.run(name); }, 0);
          });
          self.run(name);
        })(forms[i]);
      }
      this._ready = true;
    }
  };

  window.DomCalc = DomCalc;

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () { DomCalc.init(); });
  } else {
    DomCalc.init();
  }
})(window, document);
