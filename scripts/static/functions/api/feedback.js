/**
 * Cloudflare Pages Function — приём отзыва/сообщения об ошибке с плавающего виджета.
 * Маршрут: POST /api/feedback (в статическом билде JS формы перенаправлен сюда).
 * Вместо mail() (на статике недоступен) шлёт заявку в Telegram-бот владельца.
 * Секреты — env проекта Pages: TG_BOT_TOKEN, TG_CHAT_ID. Возвращает {ok:bool}.
 */
const json = (o, status = 200) =>
  new Response(JSON.stringify(o), { status, headers: { 'content-type': 'application/json; charset=utf-8' } });

export async function onRequestPost({ request, env }) {
  let p = {};
  const ct = request.headers.get('content-type') || '';
  try {
    if (ct.includes('form')) {
      const fd = await request.formData();
      p = Object.fromEntries(fd.entries());
    } else {
      p = await request.json();
    }
  } catch { /* пустое/битое тело */ }

  // Honeypot: боты заполняют скрытое поле — молча «успех».
  if (String(p.company || '').trim() !== '') return json({ ok: true });

  const message = String(p.message || '').trim();
  if ([...message].length < 5) return json({ ok: false, error: 'short' }, 422);
  const msg = message.slice(0, 5000);

  let email = String(p.email || '').trim().slice(0, 200);
  if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) email = '(невалидный email)';

  const page = (String(p.page || '').replace(/[\r\n]+/g, ' ').slice(0, 300)) || '(страница не указана)';
  const ip = (request.headers.get('cf-connecting-ip') || '—').slice(0, 60);
  const ua = (request.headers.get('user-agent') || '—').slice(0, 300);

  const token = env.TG_BOT_TOKEN, chat = env.TG_CHAT_ID;
  if (!token || !chat) return json({ ok: false, error: 'config' }, 500);

  const text =
    `📝 Отзыв с сайта (ДомЭксперт)\n` +
    `Страница: ${page}\n` +
    `Email: ${email || '—'}\n` +
    `IP: ${ip}\n` +
    `UA: ${ua}\n` +
    `${'—'.repeat(20)}\n${msg}`;

  try {
    const r = await fetch(`https://api.telegram.org/bot${token}/sendMessage`, {
      method: 'POST',
      headers: { 'content-type': 'application/json' },
      body: JSON.stringify({ chat_id: chat, text, disable_web_page_preview: true }),
    });
    const d = await r.json();
    return d.ok ? json({ ok: true }) : json({ ok: false, error: 'send' }, 502);
  } catch {
    return json({ ok: false, error: 'network' }, 502);
  }
}
