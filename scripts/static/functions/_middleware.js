/**
 * Cloudflare Pages middleware — канонизация хоста.
 *
 * Боевой сайт живёт на apex prodom-expert.com. 301-редиректим на него:
 *  - любой *.pages.dev (технический домен Pages + превью-поддомены) — иначе он
 *    отдавал бы те же страницы 200 (дубль контента, лишняя аналитика/реклама);
 *  - www.prodom-expert.com — чтобы www вёл на apex, как www→non-www 301 на .ru.
 * Для apex (и api/go-функций на нём) просто пропускаем дальше.
 */
export const onRequest = async (context) => {
  const url = new URL(context.request.url);
  if (url.hostname.endsWith('.pages.dev') || url.hostname === 'www.prodom-expert.com') {
    url.hostname = 'prodom-expert.com';
    url.protocol = 'https:';
    url.port = '';
    return Response.redirect(url.toString(), 301);
  }
  return context.next();
};
