#!/usr/bin/env python3
"""Подготовка ваших фотографий к публикации.

Как пользоваться:
  1. Создайте папку photos-inbox/<slug-статьи>/ в корне сайта.
  2. Сложите туда снимки как есть — с телефона, любого размера, JPG/PNG/HEIC/WebP.
     Порядок задаётся именем файла: 01-..., 02-... (или просто по алфавиту).
  3. Запустите:  .venv-img/bin/python scripts/prepare_photos.py
  4. Готовые файлы появятся в assets/img/photos/<slug>/ и подхватятся статьёй сами.

Что делает скрипт:
  • разворачивает снимок по EXIF-ориентации (иначе фото с телефона лежит на боку);
  • удаляет метаданные, включая GPS-координаты съёмки — это важно, если снимали дома;
  • ужимает до 1600 px по длинной стороне и сохраняет две версии: JPEG и WebP;
  • пишет photos.json с размерами — из него шаблон берёт width/height,
    чтобы страница не «прыгала» при загрузке.

Подписи к фото задаются в файле captions.txt внутри папки со снимками:
  01-shtroba.jpg | Штроба под кабель в панельной стене, глубина 25 мм
Строки без разделителя игнорируются. Без подписи фото публикуется без неё,
но лучше подписывать: подпись — это то, что отличает живой снимок от стока.
"""
from __future__ import annotations

import json
import sys
from pathlib import Path

from PIL import Image, ImageOps

ROOT = Path(__file__).resolve().parents[1]
INBOX = ROOT / "photos-inbox"
OUT = ROOT / "assets" / "img" / "photos"

MAX_SIDE = 1600          # больше для веба не нужно, но хватает для ретины
JPEG_QUALITY = 82
WEBP_QUALITY = 80
SUPPORTED = {".jpg", ".jpeg", ".png", ".webp", ".heic", ".heif", ".tif", ".tiff"}


def read_captions(folder: Path) -> dict[str, str]:
    """captions.txt: «имя-файла | подпись» построчно."""
    f = folder / "captions.txt"
    if not f.is_file():
        return {}
    caps = {}
    for line in f.read_text(encoding="utf-8").splitlines():
        if "|" not in line:
            continue
        name, _, text = line.partition("|")
        name, text = name.strip(), text.strip()
        if name and text:
            caps[name.lower()] = text
    return caps


def process_folder(folder: Path) -> dict | None:
    slug = folder.name
    photos = sorted(
        (p for p in folder.iterdir() if p.suffix.lower() in SUPPORTED),
        key=lambda p: p.name.lower(),
    )
    if not photos:
        print(f"  = {slug}: снимков не найдено")
        return None

    captions = read_captions(folder)
    dest = OUT / slug
    dest.mkdir(parents=True, exist_ok=True)

    items = []
    for i, src in enumerate(photos, 1):
        try:
            img = Image.open(src)
        except Exception as e:                       # noqa: BLE001
            print(f"  ! {slug}/{src.name}: не открылось ({e}). "
                  f"Для HEIC установите pillow-heif: pip install pillow-heif")
            continue

        img = ImageOps.exif_transpose(img)           # поворот по EXIF
        img = img.convert("RGB")                     # снимаем альфу и метаданные
        img.thumbnail((MAX_SIDE, MAX_SIDE), Image.LANCZOS)

        base = f"{i:02d}"
        jpg = dest / f"{base}.jpg"
        webp = dest / f"{base}.webp"
        # save без exif — метаданные и GPS не попадают в публикацию
        img.save(jpg, "JPEG", quality=JPEG_QUALITY, optimize=True, progressive=True)
        img.save(webp, "WEBP", quality=WEBP_QUALITY, method=6)

        items.append({
            "jpg": f"/assets/img/photos/{slug}/{base}.jpg",
            "webp": f"/assets/img/photos/{slug}/{base}.webp",
            "width": img.width,
            "height": img.height,
            "caption": captions.get(src.name.lower(), ""),
            "source": src.name,
        })
        print(f"  ▸ {slug}/{src.name} → {base}.jpg + .webp  ({img.width}×{img.height})")

    if not items:
        return None

    (dest / "photos.json").write_text(
        json.dumps({"slug": slug, "photos": items}, ensure_ascii=False, indent=2),
        encoding="utf-8",
    )
    no_caption = sum(1 for it in items if not it["caption"])
    if no_caption:
        print(f"  ⚠ {slug}: без подписи {no_caption} из {len(items)} — "
              f"добавьте их в {folder.name}/captions.txt")
    return {"slug": slug, "count": len(items)}


def main() -> int:
    if not INBOX.is_dir():
        INBOX.mkdir(parents=True, exist_ok=True)
        print(f"Создал папку {INBOX.relative_to(ROOT)}/")
        print("Положите снимки в подпапку с именем статьи, например:")
        print(f"  {INBOX.name}/shtroblenie-sten-pod-provodku/01-shtroba.jpg")
        return 0

    folders = sorted(p for p in INBOX.iterdir() if p.is_dir())
    if not folders:
        print(f"В {INBOX.name}/ нет подпапок со статьями. "
              f"Имя подпапки должно совпадать со слагом статьи.")
        return 0

    articles = ROOT / "articles"
    known = {p.stem for p in articles.rglob("*.html")}

    done = []
    for folder in folders:
        if folder.name not in known:
            print(f"  ! {folder.name}: статьи с таким слагом нет — папка пропущена")
            continue
        r = process_folder(folder)
        if r:
            done.append(r)

    if done:
        total = sum(d["count"] for d in done)
        print(f"\nГотово: {total} фото в {len(done)} статьях.")
        print("Файлы лежат в assets/img/photos/<slug>/ и уже подключены к статьям.")
    else:
        print("\nНичего не обработано.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
