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

  const site = request.headers.get("sec-fetch-site");
  const ua = request.headers.get("user-agent") ?? "";
  const ref = request.headers.get("referer") ?? "";
  let sameHost = false;
  try { sameHost = OUR_HOSTS.includes(new URL(ref).host.toLowerCase()); } catch { /* нет/мусор */ }
  const cameFromUs = site === "same-origin" || site === "same-site" || sameHost;
  const looksLikeBot = !/Mozilla/i.test(ua)
    || /\b(bot|crawler|spider|scrape|preview|headless|monitor)\b/i.test(ua);

  if (!cameFromUs || looksLikeBot) {
    return new Response(null, { status: 204, headers: NOINDEX });
  }

  // subid — помечаем страницу-источник, только для Admitad-deeplink (/g/ или /c/).
  const p = (new URL(request.url)).searchParams.get("p") || "";
  const psafe = p.replace(/[^A-Za-z0-9_-]/g, "");
  if (psafe) {
    try {
      if (/\/[gc]\//.test(new URL(url).pathname)) {
        url += (url.includes("?") ? "&" : "?") + "subid=" + encodeURIComponent(psafe);
      }
    } catch { /* оставляем url как есть */ }
  }

  return new Response(null, { status: 302, headers: { Location: url, ...NOINDEX } });
};
