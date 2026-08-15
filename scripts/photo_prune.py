#!/usr/bin/env python3
"""Удаление неудачных кадров из блока фотографий статьи.

Подбор автоматический, поэтому часть кадров всегда мимо: клипарт вместо фото,
антикварный документ вместо современного, узнаваемые люди в кадре. Смотреть
удобнее через scripts/photo_review.py, а удалять — этим скриптом.

  .venv-img/bin/python scripts/photo_prune.py slug#1 slug#2 другой-slug#2

Скрипт убирает запись из photos.json и сами файлы. Если в статье не осталось
ни одного снимка, папка удаляется целиком — блок в статье исчезает сам.
Нумерация оставшихся файлов не меняется: её задаёт photos.json, а не имена.

Ключ --dry-run показывает, что будет удалено, ничего не трогая.
"""
from __future__ import annotations

import json
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
PHOTOS = ROOT / "assets" / "img" / "photos"


def main() -> int:
    args = [a for a in sys.argv[1:] if not a.startswith("--")]
    dry = "--dry-run" in sys.argv
    if not args:
        print(__doc__)
        return 1

    # Группируем по статье: за один заход из статьи может уйти несколько кадров.
    targets: dict[str, set[int]] = {}
    for spec in args:
        if "#" not in spec:
            print(f"! {spec}: нужен формат slug#номер")
            return 1
        slug, _, num = spec.partition("#")
        try:
            targets.setdefault(slug, set()).add(int(num))
        except ValueError:
            print(f"! {spec}: номер должен быть числом")
            return 1

    removed_photos = removed_dirs = 0
    for slug, drop in sorted(targets.items()):
        folder = PHOTOS / slug
        manifest = folder / "photos.json"
        if not manifest.is_file():
            print(f"! {slug}: манифеста нет — пропуск")
            continue

        data = json.loads(manifest.read_text(encoding="utf-8"))
        photos = data.get("photos", [])
        keep, gone = [], []
        for i, ph in enumerate(photos, 1):
            (gone if i in drop else keep).append(ph)

        if not gone:
            print(f"= {slug}: указанных номеров нет (всего {len(photos)})")
            continue

        for ph in gone:
            for key in ("jpg", "webp"):
                f = ROOT / str(ph.get(key, "")).lstrip("/")
                if f.is_file():
                    if not dry:
                        f.unlink()
            print(f"  − {slug}: {ph.get('title') or ph.get('caption') or '(без названия)'}")
            removed_photos += 1

        if keep:
            if not dry:
                data["photos"] = keep
                manifest.write_text(
                    json.dumps(data, ensure_ascii=False, indent=2), encoding="utf-8")
            print(f"▸ {slug}: осталось {len(keep)}")
        else:
            if not dry:
                for f in folder.iterdir():
                    f.unlink()
                folder.rmdir()
            print(f"✗ {slug}: снимков не осталось — папка удалена, блок пропадёт")
            removed_dirs += 1

    print(f"\n{'(пробный прогон) ' if dry else ''}"
          f"Удалено кадров: {removed_photos}, статей без блока: {removed_dirs}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
