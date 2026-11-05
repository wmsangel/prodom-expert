/**
 * Cloudflare Pages middleware — канонизация адресов (host + legacy-редиректы).
 *
 * Статический сайт .com не исполняет .htaccess, поэтому 301/404-логику с .ru
 * портируем сюда. Порядок: host → legacy .php?slug= → известные переименования →
 * дальше (context.next() отдаёт либо страницу, либо 404.html со статусом 404).
 */
const APEX = 'https://prodom-expert.com';
const SLUG = /^[A-Za-z0-9-]+$/;

// Старые опечаточные/переименованные слаги → канонические (301). Ключи и значения
// БЕЗ хвостового слэша; сравниваем по декодированному пути (есть кириллический слаг).
const RENAMED = {
  '/article/regulirovka-plastikovyh-okon': '/article/regulirovka-plastikovykh-okon',
  '/article/vybor-remontnoj-brigady': '/article/kak-vybrat-brigadu-dlya-remonta',
  '/article/bojler-nakopitelnyj-ili-protochnyj': '/article/vodonagrevatel-boyler-vybor-montazh',
  '/article/podklyuchie-varochnoy-paneli-i-duhovki': '/article/podklyuchenie-varochnoy-paneli-i-duhovki',
  '/article/podklyuchenie-varochnoy-paneli-duhovki': '/article/podklyuchenie-varochnoy-paneli-i-duhovki',
  '/article/podklyuchenie-varochnoy-paneli-i-dukhovki': '/article/podklyuchenie-varochnoy-paneli-i-duhovki',
  // недотранслит: "raz" (лат.) + "водка" (кир.)
  '/article/kollektornaya-razводка-vody': '/article/kollektornaya-razvodka-vody',
};

// Старые .php-адреса с параметром → ЧПУ (301).
const PHP_PARAM = {
  '/article.php': ['slug', '/article/'],
  '/calc.php': ['slug', '/calc/'],
  '/termin.php': ['slug', '/slovar/'],
  '/embed.php': ['slug', '/embed/calc/'],
};

const redirect = (loc) => Response.redirect(APEX + loc, 301);

export const onRequest = async (context) => {
  const url = new URL(context.request.url);

  // 1) Host: www и *.pages.dev → apex (301), тем же путём.
  if (url.hostname.endsWith('.pages.dev') || url.hostname === 'www.prodom-expert.com') {
    url.hostname = 'prodom-expert.com'; url.protocol = 'https:'; url.port = '';
    return Response.redirect(url.toString(), 301);
  }

  // 2) category.php?cat=foo(&page=N) → /category/foo/(page/N/)
  if (url.pathname === '/category.php') {
    const cat = url.searchParams.get('cat');
    if (cat && SLUG.test(cat)) {
      const page = url.searchParams.get('page');
      const tail = page && /^[0-9]+$/.test(page) && +page > 1 ? `page/${page}/` : '';
      return redirect(`/category/${cat}/${tail}`);
    }
  }

  // 3) article.php / calc.php / termin.php / embed.php ?slug=foo → ЧПУ
  const php = PHP_PARAM[url.pathname];
  if (php) {
    const v = url.searchParams.get(php[0]);
    if (v && SLUG.test(v)) { return redirect(`${php[1]}${v}/`); }
  }

  // 4) Переименованные слаги (по декодированному пути, без хвостового слэша).
  let decoded = url.pathname;
  try { decoded = decodeURIComponent(url.pathname); } catch (e) { /* оставляем как есть */ }
  const key = decoded.replace(/\/+$/, '');
  if (RENAMED[key]) { return redirect(RENAMED[key] + '/'); }

  return context.next();
};
