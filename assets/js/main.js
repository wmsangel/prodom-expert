/**
 * ДомЭксперт — main.js
 * Бургер-меню и мобильная адаптация
 */

document.addEventListener('DOMContentLoaded', function () {

  // === Переключатель темы ===
  // Саму тему на <html> ставит инлайн-скрипт в шапке — до отрисовки страницы.
  // Здесь только переключение и запоминание выбора.
  const themeToggle = document.getElementById('themeToggle');
  if (themeToggle) {
    const root = document.documentElement;

    const syncButton = function () {
      const isDark = root.getAttribute('data-theme') === 'dark';
      themeToggle.setAttribute('aria-pressed', String(isDark));
      themeToggle.setAttribute('aria-label', isDark ? 'Включить светлую тему' : 'Включить тёмную тему');
    };
    syncButton();

    themeToggle.addEventListener('click', function () {
      const next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
      root.setAttribute('data-theme', next);
      syncButton();
      try {
        localStorage.setItem('domexpert-theme', next);
      } catch (e) { /* приватный режим — тема продержится до конца сессии */ }
    });

    // Пока выбор не сделан вручную, следуем за системной настройкой:
    // переключение тёмного режима в ОС меняет и сайт, без перезагрузки.
    if (window.matchMedia) {
      const systemDark = window.matchMedia('(prefers-color-scheme: dark)');
      const onSystemChange = function (e) {
        try {
          if (localStorage.getItem('domexpert-theme')) return;
        } catch (err) { /* localStorage недоступен — следуем за системой */ }
        root.setAttribute('data-theme', e.matches ? 'dark' : 'light');
        syncButton();
      };
      if (systemDark.addEventListener) systemDark.addEventListener('change', onSystemChange);
      else if (systemDark.addListener) systemDark.addListener(onSystemChange);
    }
  }

  // === Burger Menu ===
  const burgerBtn = document.getElementById('burgerBtn');
  const mainNav   = document.getElementById('mainNav');

  if (burgerBtn && mainNav) {
    burgerBtn.addEventListener('click', function () {
      const isOpen = mainNav.classList.toggle('open');
      burgerBtn.classList.toggle('open', isOpen);
      burgerBtn.setAttribute('aria-expanded', isOpen);
      document.body.style.overflow = isOpen ? 'hidden' : '';
    });

    // Закрыть при клике вне меню
    document.addEventListener('click', function (e) {
      if (!mainNav.contains(e.target) && !burgerBtn.contains(e.target)) {
        mainNav.classList.remove('open');
        burgerBtn.classList.remove('open');
        burgerBtn.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
      }
    });

    // Закрыть при клике на ссылку в меню
    mainNav.querySelectorAll('a').forEach(function (link) {
      link.addEventListener('click', function () {
        mainNav.classList.remove('open');
        burgerBtn.classList.remove('open');
        document.body.style.overflow = '';
      });
    });

    // Закрыть при ESC
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && mainNav.classList.contains('open')) {
        mainNav.classList.remove('open');
        burgerBtn.classList.remove('open');
        burgerBtn.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
        burgerBtn.focus();
      }
    });
  }

  // === Sticky header shadow ===
  const header = document.querySelector('.site-header');
  if (header) {
    window.addEventListener('scroll', function () {
      header.style.boxShadow = window.scrollY > 20
        ? '0 4px 20px rgba(0,0,0,0.35)'
        : '0 2px 12px rgba(0,0,0,0.3)';
    }, { passive: true });
  }

  // === Подсветка текущего раздела в оглавлении ===
  const tocLinks = document.querySelectorAll('.article-toc-list a');
  if (tocLinks.length) {
    const sections = [];
    tocLinks.forEach(function (link) {
      const heading = document.getElementById(decodeURIComponent(link.getAttribute('href').slice(1)));
      if (heading) sections.push({ heading: heading, link: link });
    });

    let current = null;
    const updateCurrent = function () {
      // Активен последний заголовок, ушедший выше линии чтения: пока читают текст
      // раздела, сам заголовок уже уехал за верх экрана.
      let active = null;
      for (let i = 0; i < sections.length; i++) {
        if (sections[i].heading.getBoundingClientRect().top <= 120) active = sections[i];
        else break;
      }
      // У самого низа страницы подсвечиваем последний раздел: до его заголовка
      // линия чтения может не дойти, если раздел короткий.
      if (sections.length &&
          window.innerHeight + window.scrollY >= document.body.scrollHeight - 40) {
        active = sections[sections.length - 1];
      }
      if (active === current) return;
      if (current) current.link.classList.remove('is-current');
      current = active;
      if (current) current.link.classList.add('is-current');
    };

    let ticking = false;
    const onScroll = function () {
      if (ticking) return;
      ticking = true;
      window.requestAnimationFrame(function () {
        updateCurrent();
        ticking = false;
      });
    };

    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', onScroll, { passive: true });
    updateCurrent();
  }

  // === Smooth reveal on scroll ===
  if ('IntersectionObserver' in window) {
    const cards = document.querySelectorAll('.article-card, .article-list-item, .sidebar-widget');
    const observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.style.opacity = '1';
          entry.target.style.transform = 'translateY(0)';
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.1 });

    cards.forEach(function (card) {
      card.style.opacity    = '0';
      card.style.transform  = 'translateY(16px)';
      card.style.transition = 'opacity 0.45s ease, transform 0.45s ease';
      observer.observe(card);
    });
  }

});

/* Плавающий виджет: отзыв/ошибка + поддержать (footer.php). Независимый модуль. */
(function () {
  var fab = document.getElementById('fab');
  var toggle = document.getElementById('fabToggle');
  if (!fab || !toggle) return;

  function setOpen(open) {
    fab.classList.toggle('open', open);
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    toggle.setAttribute('aria-label', open ? 'Закрыть меню обратной связи' : 'Обратная связь и поддержка');
  }

  // Чтобы кнопку не перекрывал cookie-баннер (fixed, во всю ширину снизу),
  // пока он виден — поднимаем FAB над ним; исчез — возвращаем в угол.
  function avoidBanner() {
    var banner = document.querySelector('.cookie-consent');
    var visible = banner && banner.offsetParent !== null && banner.offsetHeight > 0;
    fab.style.bottom = visible ? (banner.offsetHeight + 16) + 'px' : '';
  }
  avoidBanner();
  try {
    new MutationObserver(avoidBanner).observe(document.body, { childList: true, subtree: true, attributes: true });
  } catch (e) {}
  window.addEventListener('resize', avoidBanner);
  toggle.addEventListener('click', function (e) {
    e.stopPropagation();
    var willOpen = !fab.classList.contains('open');
    setOpen(willOpen);
    if (willOpen && window.duTrack) window.duTrack('fab_open', {});
  });
  document.addEventListener('click', function (e) { if (!fab.contains(e.target)) setOpen(false); });
  var donateLink = fab.querySelector('.fab-action[href="/podderzhat.php"]');
  if (donateLink) donateLink.addEventListener('click', function () { if (window.duTrack) window.duTrack('donate_click', { from: 'fab' }); });

  // Модалка
  var modal = document.getElementById('fbModal');
  var openBtn = document.getElementById('fabFeedback');
  var closeBtn = document.getElementById('fbClose');
  var form = document.getElementById('fbForm');
  var status = document.getElementById('fbStatus');
  var submit = document.getElementById('fbSubmit');
  var msg = document.getElementById('fbMessage');
  var pageField = document.getElementById('fbPage');
  var lastFocus = null;

  function openModal() {
    if (!modal) return;
    lastFocus = document.activeElement;
    if (pageField) pageField.value = location.href;
    modal.hidden = false; modal.classList.add('open');
    setOpen(false);
    if (window.duTrack) window.duTrack('feedback_open', { page: location.pathname });
    setTimeout(function () { if (msg) msg.focus(); }, 40);
  }
  function closeModal() {
    if (!modal) return;
    modal.classList.remove('open'); modal.hidden = true;
    if (status) { status.textContent = ''; status.className = 'fb-status'; }
    if (lastFocus && lastFocus.focus) lastFocus.focus();
  }
  if (openBtn) openBtn.addEventListener('click', openModal);
  if (closeBtn) closeBtn.addEventListener('click', closeModal);
  if (modal) modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') { if (modal && modal.classList.contains('open')) closeModal(); else setOpen(false); }
  });

  if (form) form.addEventListener('submit', function (e) {
    e.preventDefault();
    status.textContent = ''; status.className = 'fb-status';
    submit.disabled = true; submit.textContent = 'Отправляем…';
    var data = new URLSearchParams(new FormData(form));
    fetch('/feedback.php', { method: 'POST', body: data, headers: { 'X-Requested-With': 'fetch' } })
      .then(function (r) { return r.json().catch(function () { return { ok: false }; }); })
      .then(function (res) {
        if (res && res.ok) {
          status.textContent = 'Спасибо! Сообщение отправлено.'; status.className = 'fb-status ok';
          if (window.duTrack) window.duTrack('feedback_sent', { page: location.pathname });
          form.reset();
          setTimeout(closeModal, 1800);
        } else {
          status.textContent = 'Не удалось отправить. Напишите на info@prodom-expert.ru';
          status.className = 'fb-status err';
        }
      })
      .catch(function () {
        status.textContent = 'Ошибка сети. Напишите на info@prodom-expert.ru';
        status.className = 'fb-status err';
      })
      .finally(function () { submit.disabled = false; submit.textContent = 'Отправить'; });
  });
})();

/* ── Аналитика: единый хелпер событий в Метрику (reachGoal) и GA4 (event) ──
   Уважает cookie-согласие (как метрика в footer.php). Ничего не шлёт при 'necessary'. */
(function () {
  var YM_ID = 108673434;
  window.duTrack = function (name, params) {
    try { if (localStorage.getItem('cookie_consent') === 'necessary') return; } catch (e) {}
    try { if (typeof window.ym === 'function') window.ym(YM_ID, 'reachGoal', name, params || {}); } catch (e) {}
    try { if (typeof window.gtag === 'function') window.gtag('event', name, params || {}); } catch (e) {}
  };

  // Делегированный трекинг кликов по партнёрским ссылкам и слайдам главной.
  document.addEventListener('click', function (e) {
    var a = e.target && e.target.closest ? e.target.closest('a') : null;
    if (!a) return;
    // Партнёрская ссылка «Где купить» (rel="sponsored …")
    var rel = (a.getAttribute('rel') || '');
    if (rel.indexOf('sponsored') !== -1) {
      var host = ''; try { host = new URL(a.href).hostname; } catch (_) {}
      window.duTrack('affiliate_click', { page: location.pathname, dest: host });
      return;
    }
    // Слайд на главной
    var slide = a.closest ? a.closest('.hero-slide') : null;
    if (slide) {
      var m = (a.getAttribute('href') || '').match(/\/article\/([a-z0-9-]+)/);
      window.duTrack('slider_click', { slug: m ? m[1] : '' });
    }
  }, true);
})();
