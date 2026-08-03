#!/usr/bin/env python3
"""Перерисовка обложек статей штатным генератором.

Зачем: у 14 статей обложками были фотореалистичные изображения, сгенерированные
нейросетью, — с маркером генератора в углу и нечитаемыми надписями на технике.
Они выдавали себя за фотографии, чего на сайте нет. Заменяем их такими же
схематичными обложками, как у остальных статей: они честно выглядят графикой.

Категория берётся из реестра статей, короткая подпись — из таблицы ниже.
Старые файлы (в том числе .jpg) удаляются, иначе article-cover.php продолжит
отдавать их: расширение .jpg в приоритете перед .png.

Запуск:  .venv-img/bin/python scripts/redraw_covers.py
"""
from __future__ import annotations

import importlib.util
import re
import shutil
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
COVERS = ROOT / "assets" / "img" / "articles"
BACKUP = ROOT / "assets" / "img" / "_replaced-covers"

# Короткая подпись для обложки: она печатается крупно, поэтому 1–3 слова.
TITLES = {
    "cvetovye-akcenty-v-interere":          "Цветовые акценты",
    "dushevaya-lejka-shlang-vybor":          "Душевая лейка",
    "germetik-montazhnyy-shov-okon":         "Монтажный шов окон",
    "holodnoe-i-teploe-osteklenie-balkona":  "Остекление балкона",
    "led-prozhektory-podsvetka-bezopasnost": "LED-прожекторы",
    "mikrocement-pol-i-steny":               "Микроцемент",
    "parketnaya-doska-montazh-i-uhod":       "Паркетная доска",
    "priemka-kvartiry-ot-zastroishchika":    "Приёмка квартиры",
    "remont-zimoy-klimat-materialy":         "Ремонт зимой",
    "uzo-i-avr-v-schitke":                   "УЗО и АВР в щитке",
    "videodomofon-provodka-i-pitanie":       "Видеодомофон",
    "zakupki-stroymaterialov-onlayn":        "Закупки материалов",
    "zapah-kanalizacii-sifon-trap":          "Запах канализации",
    "zerkalo-v-prihozhey-razmer-i-svet":     "Зеркало в прихожей",
}


def load_generator():
    """Берём make_cover и палитру из скрипта публикации, чтобы обложки совпадали."""
    src = ROOT / "scripts" / "publish_july31_articles.py"
    spec = importlib.util.spec_from_file_location("publisher", src)
    mod = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(mod)
    return mod


def article_categories() -> dict[str, str]:
    """Слаг → категория из includes/all-articles-meta.php."""
    meta = (ROOT / "includes" / "all-articles-meta.php").read_text(encoding="utf-8")
    return dict(re.findall(r"^  '([a-z0-9-]+)' => \['cat' => '([a-z]+)'", meta, re.M))


def main() -> int:
    pub = load_generator()
    cats = article_categories()
    BACKUP.mkdir(parents=True, exist_ok=True)

    done, skipped = 0, []
    for slug, short in TITLES.items():
        cat = cats.get(slug)
        if cat is None:
            skipped.append(f"{slug}: нет в реестре статей")
            continue

        # Убираем старые файлы всех расширений, сохранив копию
        removed = []
        for ext in (".jpg", ".jpeg", ".webp", ".png"):
            old = COVERS / f"{slug}{ext}"
            if old.is_file():
                shutil.move(str(old), str(BACKUP / old.name))
                removed.append(ext)

        pub.make_cover(slug, cat, short)
        new = COVERS / f"{slug}.png"
        if not new.is_file():
            skipped.append(f"{slug}: обложка не создалась")
            continue

        done += 1
        print(f"  ▸ {slug:38} [{cat}] «{short}»  "
              f"убрано: {', '.join(removed) or '—'}")

    print(f"\nПерерисовано обложек: {done}")
    if skipped:
        print("Пропущено:")
        for s in skipped:
            print("  !", s)
    print(f"Старые файлы сохранены в {BACKUP.relative_to(ROOT)}/ — "
          f"удалите папку, когда убедитесь, что всё в порядке.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
