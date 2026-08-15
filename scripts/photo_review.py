#!/usr/bin/env python3
"""Контрольный лист загруженных иллюстраций.

Скрипт fetch_free_photos.py подбирает снимки автоматически, а автоматика
ошибается: под «укладку ламината» может прийти вид с небоскрёба. Просмотреть
триста файлов по папкам невозможно, поэтому собираем одну страницу со всеми
снимками, подписями и правами — по ней видно, что выкидывать.

  .venv-img/bin/python scripts/photo_review.py
  открыть scripts/photo_review.html

Что делать с плохим кадром: удалить папку assets/img/photos/<slug>/ целиком
(блок в статье исчезнет сам) либо поправить запрос в scripts/photo_queries.json
и перекачать: fetch_free_photos.py --only <slug> --force
"""
from __future__ import annotations

import html
import json
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
PHOTOS = ROOT / "assets" / "img" / "photos"
META = ROOT / "includes" / "all-articles-meta.php"
OUT = Path(__file__).resolve().parent / "photo_review.html"


def article_titles() -> dict[str, str]:
    """Заголовки статей из реестра — чтобы в отчёте были не голые слаги."""
    import re
    src = META.read_text(encoding="utf-8")
    rows = re.findall(r"'([a-z0-9\-]+)'\s*=>\s*\['cat'\s*=>\s*'([a-z\-]+)',"
                      r"\s*'title'\s*=>\s*'((?:[^'\\]|\\.)*)'", src)
    return {slug: f"{cat} · {title}" for slug, cat, title in rows}


def main() -> None:
    titles = article_titles()
    folders = sorted(p for p in PHOTOS.iterdir() if p.is_dir()) if PHOTOS.is_dir() else []

    cards, total = [], 0
    for folder in folders:
        manifest = folder / "photos.json"
        if not manifest.is_file():
            continue
        data = json.loads(manifest.read_text(encoding="utf-8"))
        slug = data.get("slug", folder.name)
        photos = data.get("photos", [])
        total += len(photos)

        shots = []
        for ph in photos:
            credit = ph.get("credit") or {}
            # Отчёт лежит в scripts/, пути в манифесте — от корня сайта.
            src = ".." + ph["jpg"]
            shots.append(
                f'<figure><img src="{html.escape(src)}" loading="lazy" alt="">'
                f'<figcaption><b>{html.escape(ph.get("caption") or "— без подписи —")}</b><br>'
                f'<span class="t">{html.escape(ph.get("title", ""))}</span><br>'
                f'<span class="c">{html.escape(credit.get("author") or "автор не указан")} · '
                f'{html.escape(credit.get("license", ""))} · '
                f'{html.escape(credit.get("source", ""))}</span><br>'
                f'<a href="{html.escape(credit.get("page_url", ""))}" target="_blank">оригинал</a>'
                f'</figcaption></figure>'
            )

        cards.append(
            f'<section><h2>{html.escape(titles.get(slug, slug))}</h2>'
            f'<p class="slug">{html.escape(slug)}</p>'
            f'<div class="grid">{"".join(shots)}</div></section>'
        )

    page = f"""<!doctype html>
<html lang="ru"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Проверка иллюстраций — {total} фото в {len(cards)} статьях</title>
<style>
 body {{ font: 15px/1.5 -apple-system, system-ui, sans-serif; margin: 0 auto; padding: 24px;
        max-width: 1200px; background: #faf8f5; color: #2b2622; }}
 h1 {{ font-size: 1.5rem; }}
 section {{ margin: 0 0 2rem; padding-bottom: 1rem; border-bottom: 1px solid #e6ded3; }}
 h2 {{ font-size: 1.05rem; margin: 0 0 .15rem; }}
 .slug {{ margin: 0 0 .75rem; font-family: ui-monospace, monospace; font-size: .8rem; color: #8a8078; }}
 .grid {{ display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1rem; }}
 figure {{ margin: 0; }}
 img {{ width: 100%; height: 200px; object-fit: cover; border-radius: 8px; background: #ece5db; }}
 figcaption {{ font-size: .8rem; margin-top: .4rem; }}
 .t {{ color: #6b625a; }}
 .c {{ color: #8a8078; font-size: .75rem; }}
 a {{ color: #8a6a3a; }}
</style></head><body>
<h1>Проверка иллюстраций</h1>
<p>{total} фото в {len(cards)} статьях. Плохой кадр — удалить папку
<code>assets/img/photos/&lt;slug&gt;/</code> или поправить запрос в
<code>scripts/photo_queries.json</code> и перекачать с <code>--only &lt;slug&gt; --force</code>.</p>
{"".join(cards)}
</body></html>"""

    OUT.write_text(page, encoding="utf-8")
    print(f"Готово: {total} фото в {len(cards)} статьях → {OUT.relative_to(ROOT)}")


if __name__ == "__main__":
    main()
