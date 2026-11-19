/**
 * Cloudflare Pages Function: /go/<id> — партнёрский редиректор с фильтром
 * «человек vs бот». Зеркало go.php (.ru). Карта id→deeplink лежит в offers.js,
 * которую билд (scripts/static/build.sh) выгружает из includes/affiliate.php,
 * чтобы .com и .ru вели на одни и те же партнёрские ссылки.
 *
 * 302 на партнёра — только если переход пришёл с нашей страницы (реальный клик:
 * Sec-Fetch-Site same-origin/same-site или Referer на наш хост). Боту по прямому
 * URL — 204 без Location, чтобы сеть не засчитывала его как клик. Не клоакинг:
 * href, анкор и пометка «Реклама» одинаковы для всех.
 */
import { OFFERS } from "./offers.js";

const NOINDEX = { "Cache-Control": "no-store", "X-Robots-Tag": "noindex, nofollow" };
const OUR_HOSTS = [
  "prodom-expert.ru", "www.prodom-expert.ru",
  "prodom-expert.com", "www.prodom-expert.com", "prodom-expert.pages.dev",
];

export const onRequest = async ({ params, request }) => {
  const id = Array.isArray(params.id) ? params.id[0] : params.id;
  let url = OFFERS[id];
  if (!url) return new Response("Not found", { status: 404, headers: NOINDEX });

  const h = request.headers;
  const site = h.get("sec-fetch-site");
  const mode = h.get("sec-fetch-mode");
  const ua = h.get("user-agent") ?? "";
  const ref = h.get("referer") ?? "";
  const secPurpose = (h.get("sec-purpose") || "").toLowerCase();
  const legacyPurpose = (h.get("purpose") || h.get("x-moz") || h.get("x-purpose") || "").toLowerCase();

  let sameHost = false;
  try { sameHost = OUR_HOSTS.includes(new URL(ref).host.toLowerCase()); } catch { /* нет/мусор */ }

  // Клик засчитываем ТОЛЬКО если это реальный человеческий переход с нашей страницы.
  // Отсекаем (→204, сеть клик не видит):
  // 1) предзагрузку/предрендер браузера — главный источник накрутки (Sec-Purpose: prefetch,
  //    а также legacy Purpose/X-Moz/X-Purpose). Пользователь ссылку не открывал.
  const isPrefetch = /prefetch|prerender/.test(secPurpose)
    || /prefetch|prerender|preview/.test(legacyPurpose);
  // 2) не-браузер / бот / наши curl-сканы по UA.
  const looksLikeBot = !/Mozilla/i.test(ua)
    || /\b(bot|crawler|spider|scrape|preview|headless|monitor|fetch|curl|wget|python|axios|java|go-http|okhttp|libwww)\b/i.test(ua);
  // 3) переход не с нашей страницы (прямой заход по /go/<id>, чужой реферер).
  const cameFromUs = site === "same-origin" || site === "same-site" || sameHost;
  // 4) не навигация верхнего уровня: реальный клик по ссылке = Sec-Fetch-Mode navigate.
  //    fetch/XHR/iframe/скрипт присылают cors/no-cors/same-origin — это не клик.
  const notNavigation = mode !== null && mode !== "navigate";

  if (isPrefetch || looksLikeBot || !cameFromUs || notNavigation) {
    return new Response(null, { status: 204, headers: NOINDEX });
  }

  // sub-id — помечаем страницу-источник. Формат метки зависит от сети:
  //  • Admitad-deeplink (путь /g/ или /c/) → subid=<p>
  //  • Leads.su (трекер order.zaim-srochno24.ru) → aff_sub1=<p>
  const p = (new URL(request.url)).searchParams.get("p") || "";
  const psafe = p.replace(/[^A-Za-z0-9_-]/g, "");
  if (psafe) {
    try {
      const u = new URL(url);
      if (/\/[gc]\//.test(u.pathname)) {
        url += (url.includes("?") ? "&" : "?") + "subid=" + encodeURIComponent(psafe);
      } else if (u.host.toLowerCase().indexOf("zaim-srochno24") !== -1) {
        url += (url.includes("?") ? "&" : "?") + "aff_sub1=" + encodeURIComponent(psafe);
      }
    } catch { /* оставляем url как есть */ }
  }

  return new Response(null, { status: 302, headers: { Location: url, ...NOINDEX } });
};
