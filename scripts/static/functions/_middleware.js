/**
 * Cloudflare Pages middleware — закрывает зеркало *.pages.dev.
 *
 * Боевой сайт живёт на prodom-expert.com. Технический домен Pages
 * (prodom-expert.pages.dev и превью-поддомены *.pages.dev) отдавал те же
 * страницы 200 — это дубль контента для поиска и лишний запуск аналитики/рекламы.
 * Здесь любой запрос на *.pages.dev 301-редиректим на тот же путь на .com.
 * Для боевого домена (и api/go-функций на нём) просто пропускаем дальше.
 */
export const onRequest = async (context) => {
  const url = new URL(context.request.url);
  if (url.hostname.endsWith('.pages.dev')) {
    url.hostname = 'prodom-expert.com';
    url.protocol = 'https:';
    url.port = '';
    return Response.redirect(url.toString(), 301);
  }
  return context.next();
};
