#!/usr/bin/env python3
"""Загрузка иллюстраций со свободной лицензией для статей сайта.

Зачем: у статей есть обложки, но нет живых фотографий. Брать снимки с чужих
сайтов нельзя — указание автора само по себе права не даёт. Поэтому источники
только те, где лицензия известна машинно и разрешает коммерческое использование
с изменениями:

  • Openverse (api.openverse.org) — агрегатор CC-контента, включая Flickr.
    Основной источник: практических ремонтных сюжетов там на порядок больше.
  • Викисклад (Wikimedia Commons) — запасной источник, сильнее в технике.

Оба API отдают автора и лицензию вместе с файлом, то есть всё нужное для
корректной атрибуции берётся из ответа, а не додумывается.

Как пользоваться:
  1. Запросы лежат в scripts/photo_queries.json:
       "slug-статьи": [{"q": "поисковый запрос", "caption": "Русская подпись"}]
  2. Запустите:  .venv-img/bin/python scripts/fetch_free_photos.py
     Ключи:  --only slug1,slug2   только эти статьи
             --force              перекачать даже там, где фото уже есть
             --limit N            максимум статей за прогон
             --per-article N      сколько снимков на статью (по умолчанию 2)
             --dry-run            ничего не качать, только показать, что нашлось
  3. Файлы появятся в assets/img/photos/<slug>/ и подхватятся статьёй сами.

Что делает скрипт:
  • берёт ТОЛЬКО файлы с разрешённой лицензией (общественное достояние, CC0,
    CC BY, CC BY-SA — список ниже); всё с NC/ND, «добросовестным
    использованием» и любыми ограничениями отбрасывается, даже если картинка
    идеально подходит;
  • сверяет каждого кандидата с запросом по названию, описанию и тегам:
    поиск по обоим источникам ранжирует слабо и без этой проверки на «укладку
    ламината» приходит смотровая площадка небоскрёба;
  • отсекает мелкие файлы, схемы и карты — нужны фотографии, а не иконки;
  • ужимает до 1600 px, сохраняет JPEG + WebP без метаданных;
  • пишет photos.json с блоком credit: автор, лицензия, ссылка на оригинал
    и отметка о масштабировании — это то, что печатается под снимком.

Отчёт о прогоне сохраняется в scripts/photo_fetch_report.json.
"""
from __future__ import annotations

import argparse
import json
import os
import re
import sys
import time
import urllib.parse
import urllib.request
from html import unescape
from io import BytesIO
from pathlib import Path

from PIL import Image, ImageOps

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / "assets" / "img" / "photos"
QUERIES = Path(__file__).resolve().parent / "photo_queries.json"
REPORT = Path(__file__).resolve().parent / "photo_fetch_report.json"

API = "https://commons.wikimedia.org/w/api.php"
OPENVERSE = "https://api.openverse.org/v1/images/"
UA = "ProDomExpertPhotoBot/1.0 (https://prodom-expert.ru/; info@prodom-expert.ru)"
# CDN Flickr отвечает ботам 502, поэтому сами файлы качаем с обычным
# браузерным заголовком. К API это не относится — там UA честный.
DOWNLOAD_UA = ("Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) "
               "AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36")
DOWNLOAD_TRIES = 3

# Провайдеры, у которых лежат не фотографии: рендеры 3D-моделей, иконки.
SKIP_PROVIDERS = {"sketchfab", "thingiverse", "iconfinder", "svgsilh"}

# Читаемые названия лицензий Openverse: в ответе приходят коды вида "by-sa".
OV_LICENSE_NAMES = {
    "by": "CC BY", "by-sa": "CC BY-SA", "cc0": "CC0",
    "pdm": "Общественное достояние",
}
OV_SOURCE_NAMES = {
    "flickr": "Flickr", "wikimedia": "Wikimedia Commons",
    "smithsonian": "Smithsonian", "rawpixel": "Rawpixel",
}

MAX_SIDE = 1600
JPEG_QUALITY = 82
WEBP_QUALITY = 80
MIN_WIDTH = 900          # мельче — на десктопе видно мыло
PAUSE = 1.0              # держимся ниже минутного лимита Openverse

# Разрешённые лицензии. Сверяем по машинному коду License из extmetadata:
# он стабильнее человекочитаемого названия. Всё, чего нет здесь, не берём.
ALLOWED_LICENSE_RE = re.compile(
    r"^(cc0|cc-by-\d|cc-by-sa-\d|pd(-|$)|public\s*domain)", re.I
)
# Явные стоп-сигналы, даже если код лицензии показался знакомым.
FORBIDDEN_RE = re.compile(r"(-nc-|-nc\b|-nd-|-nd\b|fair\s*use|non-?free)", re.I)

ALLOWED_MIME = {"image/jpeg", "image/png"}

# Схемы, карты, гербы и логотипы — не иллюстрируют «как это выглядит».
BAD_TITLE_RE = re.compile(
    r"(logo|icon|coat.of.arms|flag|map\b|diagram|chart|scheme|symbol|"
    r"stamp|banknote|seal\b|screenshot|portrait|postcard|painting|"
    r"museum|monument|church|cathedral|castle|railway|locomotive)",
    re.I,
)

# Поиск Викисклада ранжирует слабо: по запросу «laminate flooring installation»
# он способен выдать смотровую площадку небоскрёба. Поэтому каждый кандидат
# дополнительно сверяется с запросом по названию, описанию и категориям файла.
STOPWORDS = {"the", "and", "for", "with", "installation", "interior", "wall",
             "home", "house", "room", "construction", "work", "working"}
STEM_LEN = 5             # floor / flooring / floors считаем одним словом
MIN_TERM_LEN = 4


def json_get(url: str, timeout: int = 45, token: str = "") -> dict:
    headers = {"User-Agent": UA}
    if token:
        headers["Authorization"] = f"Bearer {token}"
    req = urllib.request.Request(url, headers=headers)
    with urllib.request.urlopen(req, timeout=timeout) as resp:
        return json.loads(resp.read().decode("utf-8"))


def openverse_token() -> str:
    """Токен Openverse из client_id/secret в окружении.

    Без ключа анонимный лимит — единицы запросов в час, и прогон по всем
    статьям не проходит. Ключ бесплатный; как его получить — см. заголовок
    файла. Читаем из OPENVERSE_CLIENT_ID и OPENVERSE_CLIENT_SECRET.
    """
    cid = os.environ.get("OPENVERSE_CLIENT_ID", "").strip()
    secret = os.environ.get("OPENVERSE_CLIENT_SECRET", "").strip()
    if not cid or not secret:
        return ""
    body = urllib.parse.urlencode({
        "client_id": cid,
        "client_secret": secret,
        "grant_type": "client_credentials",
    }).encode()
    req = urllib.request.Request(
        "https://api.openverse.org/v1/auth_tokens/token/",
        data=body,
        headers={"User-Agent": UA,
                 "Content-Type": "application/x-www-form-urlencoded"},
    )
    try:
        with urllib.request.urlopen(req, timeout=45) as resp:
            return json.loads(resp.read().decode("utf-8")).get("access_token", "")
    except Exception as e:                            # noqa: BLE001
        print(f"! Токен Openverse не получен ({e}) — работаем анонимно")
        return ""


OV_TOKEN = ""            # заполняется в main()
OV_ENABLED = True        # --no-openverse выключает основной источник


def api_get(params: dict) -> dict:
    """Запрос к API Викисклада с вежливым User-Agent."""
    params = {**params, "format": "json", "formatversion": "2"}
    return json_get(API + "?" + urllib.parse.urlencode(params))


def strip_html(value: str, limit: int = 120) -> str:
    """Artist и ImageDescription приходят кусками HTML — чистим до текста."""
    text = re.sub(r"<[^>]+>", " ", value or "")
    text = unescape(text)
    text = re.sub(r"\s+", " ", text).strip()
    return text[:limit]


def meta_value(extmeta: dict, key: str) -> str:
    item = extmeta.get(key)
    if isinstance(item, dict):
        return str(item.get("value", "")).strip()
    return ""


def license_ok(extmeta: dict) -> bool:
    """Пропускаем только свободные лицензии без дополнительных ограничений."""
    code = meta_value(extmeta, "License")
    short = meta_value(extmeta, "LicenseShortName")
    haystack = f"{code} {short}"
    if FORBIDDEN_RE.search(haystack):
        return False
    if meta_value(extmeta, "Restrictions"):
        # trademarked, personality rights и прочее — не связываемся
        return False
    return bool(ALLOWED_LICENSE_RE.match(code) or ALLOWED_LICENSE_RE.match(short))


def query_terms(query: str) -> list[str]:
    """Значимые слова запроса, по которым проверяем попадание в тему."""
    words = re.findall(r"[a-z]+", query.lower())
    return [w[:STEM_LEN] for w in words
            if len(w) >= MIN_TERM_LEN and w not in STOPWORDS]


def relevance(terms: list[str], title: str, description: str,
              categories: str) -> int:
    """Сколько слов запроса реально встретилось у файла; название весит вдвое."""
    if not terms:
        return 0
    name = title.lower()
    rest = f"{description} {categories}".lower()
    score = 0
    for t in terms:
        if t in name:
            score += 2
        elif t in rest:
            score += 1
    return score


def search_openverse(query: str, terms: list[str], threshold: int,
                     limit: int = 30) -> list[dict]:
    """Кандидаты из Openverse. license_type отсекает NC и ND на стороне API."""
    url = OPENVERSE + "?" + urllib.parse.urlencode({
        "q": query,
        "license_type": "commercial,modification",
        "page_size": str(limit),
    })
    data = None
    for attempt in range(1, 4):
        try:
            data = json_get(url, token=OV_TOKEN)
            break
        except Exception as e:                        # noqa: BLE001
            if "429" in str(e) and attempt < 3:
                time.sleep(attempt * 5)               # упёрлись в лимит — ждём
                continue
            print(f"    ! Openverse «{query}»: {e}")
            return []
    if data is None:
        return []

    out = []
    for r in data.get("results", []):
        code = (r.get("license") or "").lower()
        if code not in OV_LICENSE_NAMES:
            continue
        if r.get("mature"):
            continue
        if (r.get("provider") or "").lower() in SKIP_PROVIDERS:
            continue
        title = (r.get("title") or "").strip()
        if not title or BAD_TITLE_RE.search(title):
            continue
        if int(r.get("width") or 0) < MIN_WIDTH:
            continue

        tags = " ".join(t.get("name", "") for t in (r.get("tags") or []))
        score = relevance(terms, title, tags, r.get("source", ""))
        if score < threshold:
            continue

        version = (r.get("license_version") or "").strip()
        name = OV_LICENSE_NAMES[code]
        # У CC0 и public domain номера версии в подписи не пишут.
        license_name = f"{name} {version}" if version and code in ("by", "by-sa") else name

        out.append({
            "title": title[:160],
            "url": r.get("url", ""),
            "page_url": r.get("foreign_landing_url", "") or r.get("detail_url", ""),
            "author": strip_html(r.get("creator") or ""),
            "license": license_name,
            "license_url": r.get("license_url", ""),
            "source": OV_SOURCE_NAMES.get(r.get("source", ""), r.get("source") or "Openverse"),
            "width": int(r.get("width") or 0),
            "score": score,
        })
    out.sort(key=lambda c: c["score"], reverse=True)
    return out


def search_commons(query: str, terms: list[str], threshold: int,
                   limit: int = 30) -> list[dict]:
    """Кандидаты по запросу: свободная лицензия, годный размер, по теме."""
    try:
        data = api_get({
            "action": "query",
            "generator": "search",
            "gsrsearch": f"filetype:bitmap {query}",
            "gsrnamespace": "6",
            "gsrlimit": str(limit),
            "prop": "imageinfo",
            "iiprop": "url|extmetadata|size|mime",
            "iiurlwidth": str(MAX_SIDE),
        })
    except Exception as e:                            # noqa: BLE001
        print(f"    ! запрос «{query}» не прошёл: {e}")
        return []

    out = []
    for page in data.get("query", {}).get("pages", []):
        info = (page.get("imageinfo") or [{}])[0]
        if not info:
            continue
        title = page.get("title", "")
        if BAD_TITLE_RE.search(title):
            continue
        if info.get("mime") not in ALLOWED_MIME:
            continue
        if int(info.get("width") or 0) < MIN_WIDTH:
            continue
        extmeta = info.get("extmetadata") or {}
        if not license_ok(extmeta):
            continue

        clean_title = re.sub(r"^File:|\.\w+$", "", title).replace("_", " ")
        score = relevance(
            terms,
            clean_title,
            strip_html(meta_value(extmeta, "ImageDescription"), 600),
            meta_value(extmeta, "Categories"),
        )
        if score < threshold:
            continue

        author = strip_html(meta_value(extmeta, "Artist"))
        if not author:
            author = strip_html(meta_value(extmeta, "Credit"))
        out.append({
            "title": clean_title,
            "url": info.get("thumburl") or info.get("url"),
            "page_url": info.get("descriptionurl", ""),
            "author": author,
            "license": meta_value(extmeta, "LicenseShortName") or meta_value(extmeta, "License"),
            "license_url": meta_value(extmeta, "LicenseUrl"),
            "source": "Wikimedia Commons",
            "width": int(info.get("width") or 0),
            "score": score,
        })
    out.sort(key=lambda c: c["score"], reverse=True)
    return out


def search(query: str) -> list[dict]:
    """Openverse как основной источник, Викисклад — если тот ничего не дал."""
    terms = query_terms(query)
    # Порог: каждое значимое слово должно подтвердиться хотя бы описанием,
    # но не меньше двух очков — одно случайное совпадение темой не делает.
    threshold = max(2, len(terms))

    found = []
    if OV_ENABLED:
        time.sleep(PAUSE)
        found = search_openverse(query, terms, threshold)
    if not found:
        time.sleep(PAUSE)
        found = search_commons(query, terms, threshold)
    return found


def download_image(url: str) -> Image.Image | None:
    """Скачивание с ретраями: CDN отдают временные 502 и 429 довольно часто."""
    last = None
    for attempt in range(1, DOWNLOAD_TRIES + 1):
        try:
            req = urllib.request.Request(url, headers={"User-Agent": DOWNLOAD_UA})
            with urllib.request.urlopen(req, timeout=60) as resp:
                raw = resp.read()
            return Image.open(BytesIO(raw))
        except Exception as e:                        # noqa: BLE001
            last = e
            if attempt < DOWNLOAD_TRIES:
                time.sleep(attempt * 1.5)
    print(f"    ! не скачалось за {DOWNLOAD_TRIES} попытки: {last}")
    return None


def save_photo(img: Image.Image, dest: Path, base: str) -> tuple[int, int]:
    img = ImageOps.exif_transpose(img)
    img = img.convert("RGB")
    img.thumbnail((MAX_SIDE, MAX_SIDE), Image.LANCZOS)
    # save без exif — метаданные исходника в публикацию не попадают
    img.save(dest / f"{base}.jpg", "JPEG", quality=JPEG_QUALITY, optimize=True, progressive=True)
    img.save(dest / f"{base}.webp", "WEBP", quality=WEBP_QUALITY, method=6)
    return img.width, img.height


def process_article(slug: str, queries: list[dict], per_article: int,
                    dry_run: bool) -> dict:
    """Подбирает и сохраняет снимки одной статьи."""
    picked: list[dict] = []
    seen_pages: set[str] = set()

    for entry in queries:
        if len(picked) >= per_article:
            break
        q = entry.get("q", "").strip()
        if not q:
            continue
        for cand in search(q):
            if cand["page_url"] in seen_pages:
                continue
            seen_pages.add(cand["page_url"])
            cand["caption"] = entry.get("caption", "")
            picked.append(cand)
            break                                     # по одному снимку на запрос

    if not picked:
        print(f"  ✗ {slug}: подходящих свободных фото не нашлось")
        return {"slug": slug, "count": 0, "status": "not_found"}

    if dry_run:
        for c in picked:
            print(f"  · {slug}: [{c['score']}] {c['title']} — {c['license']} — {c['author'] or '?'}")
        return {"slug": slug, "count": len(picked), "status": "dry_run",
                "photos": [c["title"] for c in picked]}

    dest = OUT / slug
    dest.mkdir(parents=True, exist_ok=True)
    items = []
    for i, cand in enumerate(picked, 1):
        img = download_image(cand["url"])
        if img is None:
            continue
        base = f"{i:02d}"
        try:
            w, h = save_photo(img, dest, base)
        except Exception as e:                        # noqa: BLE001
            print(f"    ! {slug}/{base}: не сохранилось ({e})")
            continue
        items.append({
            "jpg": f"/assets/img/photos/{slug}/{base}.jpg",
            "webp": f"/assets/img/photos/{slug}/{base}.webp",
            "width": w,
            "height": h,
            "caption": cand["caption"],
            "title": cand["title"],
            "credit": {
                "author": cand["author"],
                "license": cand["license"],
                "license_url": cand["license_url"],
                "page_url": cand["page_url"],
                "source": cand.get("source") or "Wikimedia Commons",
                "modified": True,                     # мы ужали снимок под веб
            },
        })
        print(f"  ▸ {slug}/{base}: {cand['title']} ({w}×{h}, {cand['license']})")

    if not items:
        return {"slug": slug, "count": 0, "status": "download_failed"}

    (dest / "photos.json").write_text(
        json.dumps({"slug": slug, "photos": items}, ensure_ascii=False, indent=2),
        encoding="utf-8",
    )
    missing_author = [it["title"] for it in items if not it["credit"]["author"]]
    return {
        "slug": slug,
        "count": len(items),
        "status": "ok",
        "licenses": sorted({it["credit"]["license"] for it in items}),
        "missing_author": missing_author,
    }


def main() -> int:
    ap = argparse.ArgumentParser(description=__doc__)
    ap.add_argument("--only", default="", help="слаги через запятую")
    ap.add_argument("--force", action="store_true", help="перекачать уже готовые")
    ap.add_argument("--limit", type=int, default=0, help="максимум статей за прогон")
    ap.add_argument("--per-article", type=int, default=2, help="снимков на статью")
    ap.add_argument("--dry-run", action="store_true", help="только показать находки")
    ap.add_argument("--no-openverse", action="store_true",
                    help="искать только по Викискладу")
    args = ap.parse_args()

    global OV_TOKEN, OV_ENABLED
    OV_ENABLED = not args.no_openverse
    if OV_ENABLED:
        OV_TOKEN = openverse_token()
        print("Openverse: " + ("ключ активен" if OV_TOKEN else "анонимно (лимит запросов низкий)"))
    else:
        print("Openverse отключён — только Викисклад")

    if not QUERIES.is_file():
        print(f"Нет файла запросов: {QUERIES.relative_to(ROOT)}")
        return 1
    plan: dict[str, list[dict]] = json.loads(QUERIES.read_text(encoding="utf-8"))

    known = {p.stem for p in (ROOT / "articles").rglob("*.html")}
    only = {s.strip() for s in args.only.split(",") if s.strip()}

    todo = []
    for slug, queries in plan.items():
        if only and slug not in only:
            continue
        if slug not in known:
            print(f"  ! {slug}: статьи с таким слагом нет — пропуск")
            continue
        if not args.force and (OUT / slug / "photos.json").is_file():
            continue
        todo.append((slug, queries))

    if args.limit:
        todo = todo[: args.limit]
    if not todo:
        print("Нечего делать: всё уже загружено (или используйте --force).")
        return 0

    print(f"К обработке статей: {len(todo)}\n")
    results = []
    for n, (slug, queries) in enumerate(todo, 1):
        print(f"[{n}/{len(todo)}] {slug}")
        results.append(process_article(slug, queries, args.per_article, args.dry_run))

    ok = [r for r in results if r["status"] == "ok"]
    empty = [r for r in results if r["count"] == 0]
    total = sum(r["count"] for r in results)
    print(f"\nГотово: {total} фото в {len(ok)} статьях.")
    if empty:
        print(f"Без фото осталось {len(empty)}: "
              + ", ".join(r["slug"] for r in empty[:20])
              + (" …" if len(empty) > 20 else ""))

    if not args.dry_run:
        REPORT.write_text(json.dumps(results, ensure_ascii=False, indent=2), encoding="utf-8")
        print(f"Отчёт: {REPORT.relative_to(ROOT)}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
