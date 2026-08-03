"""Общая часть скриптов публикации: регистрация статей в реестре сайта.

Раньше каждый publish_*.py вписывал новую статью в четыре места: реестр
includes/all-articles-meta.php плюс собственные копии списка в article.php,
rss.php и search.php. Копии разъезжались — страница статьи успела показывать
заголовок и дату той версии материала, которую давно переписали.

Теперь список статей живёт только в includes/all-articles-meta.php, а страница
статьи, поиск и RSS читают его. Регистрация — эта функция.

Использование в очередном publish_<дата>_articles.py:

    from publish_common import register_articles

    rc = register_articles(
        root=ROOT,
        articles=[{"slug": a["slug"], "cat": a["cat"], "title": a["title"],
                   "desc": a["desc"], "readTime": a["readTime"],
                   "author": AUTHORS[a["cat"]]} for a in ARTS],
        date=DATE,   # «31 июля 2026»
        iso=ISO,     # «2026-07-31»
    )
"""

from __future__ import annotations

from pathlib import Path

# Автор по умолчанию: совпадает с DOMEXPERT_DEFAULT_AUTHOR в all-articles-meta.php.
# Такие статьи автора в реестре не хранят — значение подставляется само.
DEFAULT_AUTHOR = "Редакция ДомЭксперт"

SITE = "https://prodom-expert.ru"


def php_quote(value: str) -> str:
    """Экранирование строки для одинарных кавычек PHP."""
    return value.replace("\\", "\\\\").replace("'", "\\'")


def _registered(text: str, slug: str) -> bool:
    return f"'{slug}'" in text


def register_articles(root: Path, articles: list[dict], date: str, iso: str) -> int:
    """Добавляет статьи в реестр и sitemap.xml. Возвращает 0 при успехе.

    Идемпотентна: уже зарегистрированные слаги пропускаются.
    """
    meta_php = root / "includes" / "all-articles-meta.php"
    sitemap = root / "sitemap.xml"

    if not meta_php.is_file():
        print(f"  ! нет файла реестра: {meta_php}")
        return 1

    meta_text = meta_php.read_text(encoding="utf-8")
    meta_lines: list[str] = []

    for a in articles:
        slug = a["slug"]
        if _registered(meta_text, slug):
            continue
        fields = [
            f"'cat' => '{php_quote(a['cat'])}'",
            f"'title' => '{php_quote(a['title'])}'",
            f"'desc' => '{php_quote(a['desc'])}'",
            f"'date' => '{php_quote(date)}'",
            f"'readTime' => '{php_quote(a['readTime'])}'",
        ]
        author = a.get("author") or DEFAULT_AUTHOR
        if author != DEFAULT_AUTHOR:
            fields.append(f"'author' => '{php_quote(author)}'")
        meta_lines.append(f"  '{slug}' => [{', '.join(fields)}],\n")

    if meta_lines:
        anchor = "  return ["
        idx = meta_text.find(anchor)
        if idx == -1:
            print(f"  ! ЯКОРЬ НЕ НАЙДЕН в all-articles-meta.php: {anchor!r}")
            return 1
        cut = meta_text.index("\n", idx) + 1
        meta_php.write_text(meta_text[:cut] + "".join(meta_lines) + meta_text[cut:],
                            encoding="utf-8")
        print(f"  + all-articles-meta.php: {len(meta_lines)} записей")
    else:
        print("  = all-articles-meta.php: всё уже зарегистрировано")

    if sitemap.is_file():
        xml = sitemap.read_text(encoding="utf-8")
        new_urls = [
            f"  <url><loc>{SITE}/article/{a['slug']}/</loc>"
            f"<changefreq>monthly</changefreq><priority>0.8</priority>"
            f"<lastmod>{iso}</lastmod></url>\n"
            for a in articles if f"/article/{a['slug']}/" not in xml
        ]
        if new_urls:
            sitemap.write_text(xml.replace("</urlset>", "".join(new_urls) + "</urlset>"),
                               encoding="utf-8")
            print(f"  + sitemap.xml: {len(new_urls)} записей")
        else:
            print("  = sitemap.xml: всё уже зарегистрировано")

    # Кэш похожих статей инвалидируется сам — по времени правки реестра и статей,
    # см. domexpert_corpus_stamp(). Чистить cache/ вручную не нужно.
    return 0
