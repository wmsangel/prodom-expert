#!/usr/bin/env python3
"""Пересборка sitemap.xml.

Чинит две поломки, накопившиеся при генерации партиями:
  1) записи, дописанные ПОСЛЕ </urlset> — из-за них XML невалиден целиком;
  2) старый формат ссылок article.php?slug=X вместо ЧПУ /article/X/.

Все <url> собираются из файла в любом месте, нормализуются, дедуплицируются
по <loc> (побеждает запись со свежим lastmod) и записываются обратно
в корректной XML-обёртке.
"""
from __future__ import annotations

import re
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
SITEMAP = ROOT / "sitemap.xml"

HEADER = """<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
        http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">
"""


def main() -> int:
    raw = SITEMAP.read_text(encoding="utf-8")
    blocks = re.findall(r"<url>.*?</url>", raw, re.S)
    if not blocks:
        print("не найдено ни одного <url> — файл не тронут")
        return 1

    seen: dict[str, tuple[str, str]] = {}
    normalized = 0
    for b in blocks:
        fixed = re.sub(r"/article\.php\?slug=([a-z0-9\-]+)", r"/article/\1/", b)
        if fixed != b:
            normalized += 1
        loc = re.search(r"<loc>(.*?)</loc>", fixed).group(1)
        lastmod = (re.search(r"<lastmod>(.*?)</lastmod>", fixed) or [None, ""])[1]
        # при дубле оставляем запись с более свежим lastmod
        if loc not in seen or lastmod > seen[loc][0]:
            seen[loc] = (lastmod, fixed)

    # разделы сайта наверх, статьи ниже — по убыванию lastmod
    sections = [v[1] for k, v in seen.items() if "/article/" not in k]
    articles = sorted(
        [(v[0], v[1]) for k, v in seen.items() if "/article/" in k],
        key=lambda t: t[0],
        reverse=True,
    )

    out = [HEADER]
    out.extend("  " + b + "\n" for b in sections)
    out.append("\n")
    out.extend("  " + b + "\n" for _, b in articles)
    out.append("</urlset>\n")
    SITEMAP.write_text("".join(out), encoding="utf-8")

    dropped = len(blocks) - len(seen)
    print(f"было записей: {len(blocks)}")
    print(f"  переведено на ЧПУ: {normalized}")
    print(f"  удалено дублей:    {dropped}")
    print(f"стало: {len(seen)} ({len(sections)} разделов + {len(articles)} статей)")
    return 0


if __name__ == "__main__":
    sys.exit(main())
