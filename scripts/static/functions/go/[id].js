/**
 * Cloudflare Pages Function: /go/<id> — партнёрский редиректор с жест-гейтом
 * «человек vs бот» (сетевой стандарт 20.11.2026). Карта id→deeplink лежит в
 * offers.js, которую билд (scripts/static/build.sh) выгружает из includes/affiliate.php.
 *
 * 302 на партнёра — только на реальный клик человека с нашей страницы, иначе 204
 * без Location (сеть клик не засчитывает). Не клоакинг: href, анкор и пометка
 * «Реклама» одинаковы для всех.
 *
 * Правила (по порядку):
 *  1. бот-UA, предзагрузка (Sec-Purpose/Purpose: prefetch), Sec-Fetch-Mode ≠ navigate → 204;
 *  2. браузер, который умеет Sec-Fetch-User (Chrome/Edge/Opera 76+, Firefox 90+,
 *     Safari 16.4+, все iOS-браузеры на WebKit 16.4+): нет ни одного Sec-Fetch-* при
 *     UA с Chrome/ → 204 (ua-mismatch); нет Sec-Fetch-User: ?1 → 204 (no-user-gesture);
 *  3. мягкий путь по Referer на наш хост — только для версий старше перечисленных
 *     (разбор версии в UA), считается отдельно как click-legacy.
 * Каждый исход шлётся в сетевой маячок событием go_hit{reason, net} — без IP и UA.
 */
import { OFFERS } from "./offers.js";

const NOINDEX = { "Cache-Control": "no-store", "X-Robots-Tag": "noindex, nofollow" };
const OUR_HOSTS = [
  "prodom-expert.ru", "www.prodom-expert.ru",
  "prodom-expert.com", "www.prodom-expert.com", "prodom-expert.pages.dev",
];
const BEACON = "https://b.izntools.com/e";

/** Сеть по хосту deeplink — для счёта переходов по сетям. */
function netOf(url) {
  try {
    const u = new URL(url); const host = u.host.toLowerCase();
    if (/\/[gc]\//.test(u.pathname)) return "admitad";
    if (host.includes("zaim-srochno24")) return "leadssu";
    if (/^ru\.|\.xyz$|trackerpro|gettracker|io0\d|io10|z0\d|i0\d/.test(host)) return "indoleads";
  } catch { /* мусор */ }
  return "other";
}

/** Умеет ли браузер слать Sec-Fetch-User — по семейству и версии в UA. */
function supportsUserGesture(ua) {
  const ios = ua.match(/(?:iPhone|iPad|iPod)[^)]*OS (\d+)_(\d+)/);
  if (ios) return +ios[1] > 16 || (+ios[1] === 16 && +ios[2] >= 4);
  const chrome = ua.match(/(?:Chrome|Chromium)\/(\d+)/);
  if (chrome) return +chrome[1] >= 76;
  const ff = ua.match(/Firefox\/(\d+)/);
  if (ff) return +ff[1] >= 90;
  const safari = /Safari\//.test(ua) && ua.match(/Version\/(\d+)\.(\d+)/);
  if (safari) return +safari[1] > 16 || (+safari[1] === 16 && +safari[2] >= 4);
  return false; // неизвестный/старый движок — мягкий путь по Referer
}

export const onRequest = async ({ params, request, waitUntil }) => {
  const id = Array.isArray(params.id) ? params.id[0] : params.id;
  let url = OFFERS[id];
  if (!url) return new Response("Not found", { status: 404, headers: NOINDEX });

  const h = request.headers;
  const site = h.get("sec-fetch-site");
  const mode = h.get("sec-fetch-mode");
  const secUser = h.get("sec-fetch-user");
  const ua = h.get("user-agent") ?? "";
  const ref = h.get("referer") ?? "";
  const secPurpose = (h.get("sec-purpose") || "").toLowerCase();
  const legacyPurpose = (h.get("purpose") || h.get("x-moz") || h.get("x-purpose") || "").toLowerCase();
  const anySecFetch = site !== null || mode !== null || secUser !== null || h.get("sec-fetch-dest") !== null;

  let sameHost = false;
  try { sameHost = OUR_HOSTS.includes(new URL(ref).host.toLowerCase()); } catch { /* нет/мусор */ }

  let reason;
  if (!/Mozilla/i.test(ua)
      || /\b(bot|crawler|spider|scrape|preview|headless|monitor|fetch|curl|wget|python|axios|java|go-http|okhttp|libwww)\b/i.test(ua)) {
    reason = "bot-ua";
  } else if (/prefetch|prerender/.test(secPurpose) || /prefetch|prerender|preview/.test(legacyPurpose)) {
    reason = "prefetch";
  } else if (mode !== null && mode !== "navigate") {
    reason = "not-navigation";
  } else if (supportsUserGesture(ua)) {
    if (!anySecFetch) reason = /Chrome\//.test(ua) ? "ua-mismatch" : "no-sec-fetch";
    else if (secUser !== "?1") reason = "no-user-gesture";
    else if (!(site === "same-origin" || site === "same-site" || sameHost)) reason = "not-from-us";
    else reason = "click";
  } else {
    reason = sameHost ? "click-legacy" : "not-from-us";
  }

  const net = netOf(url);
  // Счёт исходов в сетевой маячок (без IP и UA): go_hit{reason, net}.
  try {
    waitUntil(fetch(BEACON, {
      method: "POST",
      headers: { "Origin": "https://prodom-expert.com", "Content-Type": "text/plain", "User-Agent": "Mozilla/5.0 (prodom-go-counter)" },
      body: JSON.stringify({ e: "go_hit", p: "/go/" + id, d: { reason, net, offer: id } }),
    }).catch(() => {}));
  } catch { /* счёт не должен ломать переход */ }

  if (reason !== "click" && reason !== "click-legacy") {
    return new Response(null, { status: 204, headers: NOINDEX });
  }

  // sub-id — помечаем страницу-источник. Формат метки зависит от сети:
  //  • Admitad-deeplink (путь /g/ или /c/) → subid=<p>
  //  • Leads.su (трекер order.zaim-srochno24.ru) → aff_sub1=<p>
  //  • Indoleads → p1=<p>
  const p = (new URL(request.url)).searchParams.get("p") || "";
  const psafe = p.replace(/[^A-Za-z0-9_-]/g, "");
  if (psafe) {
    const param = net === "admitad" ? "subid" : net === "leadssu" ? "aff_sub1" : net === "indoleads" ? "p1" : "";
    if (param) url += (url.includes("?") ? "&" : "?") + param + "=" + encodeURIComponent(psafe);
  }

  return new Response(null, { status: 302, headers: { Location: url, ...NOINDEX } });
};
