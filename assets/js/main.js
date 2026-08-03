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
