#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
ai-covers.py — генерация AI-обложек статей prodom-expert через Pollinations (бесплатно, без ключа).
Кладёт assets/img/articles/{slug}.jpg (1200x630). Пропускает уже готовые jpg (докачка).

Использование:
  python3 scripts/ai-covers.py            # все статьи из реестра (кроме уже с jpg)
  python3 scripts/ai-covers.py slug1 slug2 ...   # только эти слаги (перегенерация)
  FORCE=1 python3 scripts/ai-covers.py ...       # перезаписать даже существующие jpg

Промпт строится: сцена по категории + перевод ключевых слов из заголовка.
Все картинки ВСЁ РАВНО смотреть глазами перед заливкой (AI иногда даёт артефакты).
"""
import os, sys, time, subprocess, urllib.parse, urllib.request, urllib.error

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
TSV  = os.path.join(ROOT, "scripts", "articles.tsv")   # slug \t cat \t label \t title
OUT  = os.path.join(ROOT, "assets", "img", "articles")
FORCE = os.environ.get("FORCE") == "1"

BASE = ("photorealistic, professional architectural interior photography, DSLR photo, warm natural light, "
        "ultra sharp focus, crisp, high detail, 4k, realistic textures, "
        "no text, no watermark, no people, no logo, not blurry")

SCENE = {
  "remont":     "home renovation and interior finishing, {t}, clean modern apartment",
  "okna":       "modern windows and doors in a bright apartment, {t}",
  "santehnika": "clean modern bathroom, {t}",
  "elektrika":  "home electrical work, {t}, modern apartment",
  "interer":    "stylish modern interior design, {t}, cozy apartment",
  "mebel":      "modern furniture in a cozy stylish home, {t}",
  "sovety":     "cozy modern home interior, {t}",
}
DEFAULT_TOPIC = {
  "remont":"renovation and finishing materials","okna":"windows and balcony",
  "santehnika":"bathroom fixtures","elektrika":"sockets, wiring and lighting",
  "interer":"interior design details","mebel":"furniture","sovety":"home improvement",
}
# перевод частых русских терминов -> английская предметная сцена (по вхождению в заголовок)
KW = [
 ("кухонный гарнитур","kitchen cabinets"),("гарнитур","kitchen cabinets"),
 ("компьютерное кресло","ergonomic office chair"),("кресло","armchair"),
 ("диван","sofa"),("шкаф-купе","sliding wardrobe"),("шкаф","wardrobe"),
 ("комод","chest of drawers"),("кровать","bed"),("детск","children's room"),
 ("письменн","writing desk"),("тумба","tv console"),("стеллаж","shelving unit"),
 ("полк","shelves"),("столешниц","kitchen countertop"),("стол","table"),
 ("прихож","hallway with storage"),("мойка","kitchen sink"),("смесител","faucet"),
 ("ванн","bathtub"),("унитаз","toilet"),("душев","shower cabin"),("душ","shower"),
 ("фартук","kitchen tile backsplash"),("плитк","ceramic tile"),("ламинат","laminate flooring"),
 ("паркет","parquet floor"),("линолеум","linoleum floor"),("пробков","cork flooring"),
 ("тёплый пол","underfloor heating"),("теплый пол","underfloor heating"),
 ("стяжк","floor screed"),("плинтус","floor baseboard"),("наливн","self-leveling floor"),
 ("потол","ceiling"),("обои","wallpaper on wall"),("покрас","painted walls"),
 ("штукатурк","decorative plaster wall"),("шпакл","wall putty"),("грунтов","wall priming"),
 ("микроцемент","microcement wall"),("венециан","venetian plaster wall"),
 ("декоративный камень","decorative stone accent wall"),("камень","stone wall"),
 ("кирпич","exposed brick wall"),("панел","wall panels"),("рейк","wooden slat wall"),
 ("гипсокартон","drywall construction"),("перегородк","room partition"),
 ("двер","interior door"),("окн","apartment window"),("балкон","glazed balcony"),
 ("лоджи","glazed loggia"),("откос","window reveal"),
 ("розетк","electrical socket on wall"),("выключател","light switch"),
 ("проводк","electrical wiring"),("щит","electrical distribution panel"),
 ("автоматическ","circuit breakers panel"),("заземлен","electrical grounding"),
 ("освещен","modern home lighting"),("подсветк","led strip lighting"),
 ("люстр","ceiling chandelier"),("светильник","light fixture"),("лампа","light bulb"),
 ("кондиционер","wall air conditioner"),("вентиляц","ventilation"),("вытяжк","kitchen range hood"),
 ("котёл","heating boiler"),("котел","heating boiler"),("радиатор","heating radiator"),
 ("отоплен","heating radiator"),("водонагреват","water heater"),("бойлер","water heater"),
 ("варочн","cooktop"),("духов","built-in oven"),("посудомо","dishwasher"),
 ("труб","plumbing pipes"),("канализац","sewage pipes"),("водоснабж","water supply pipes"),
 ("гидроизоляц","bathroom waterproofing"),("шумоизоляц","wall soundproofing"),
 ("звукоизоляц","wall soundproofing"),("акустическ","acoustic panels"),
 ("утеплен","thermal insulation"),("умный дом","smart home devices"),
 ("смет","renovation budget planning"),("планировк","apartment floor plan"),
 ("зониров","open plan room zoning"),("гостин","living room"),("спальн","bedroom"),
 ("кухн","modern kitchen"),("санузл","bathroom"),("гардероб","walk-in closet"),
 ("нож","kitchen knife"),("шторы","window curtains"),("карниз","curtain rod"),
]

def topic(title, cat):
    low = title.lower()
    for ru, en in KW:
        if ru in low:
            return en
    return DEFAULT_TOPIC.get(cat, "modern home interior")

def build_prompt(cat, title):
    scene = SCENE.get(cat, SCENE["sovety"]).format(t=topic(title, cat))
    return scene + ", " + BASE

def fetch(slug, prompt, seed=7):
    enc = urllib.parse.quote(prompt, safe="")
    url = (f"https://image.pollinations.ai/prompt/{enc}"
           f"?width=1200&height=630&nologo=true&seed={seed}&model=flux")
    tmp = os.path.join(OUT, slug + ".tmp.jpg")
    dst = os.path.join(OUT, slug + ".jpg")
    for attempt in range(1, 7):
        try:
            req = urllib.request.Request(url, headers={"User-Agent": "Mozilla/5.0 prodom-cover"})
            with urllib.request.urlopen(req, timeout=120) as r:
                data = r.read()
            if len(data) < 6000:
                raise ValueError(f"too small {len(data)}b")
            with open(tmp, "wb") as f:
                f.write(data)
            # нормализуем до точных 1200x630 (sips — родной для macOS)
            subprocess.run(["sips", "-s", "format", "jpeg", "-z", "630", "1200", tmp,
                            "--out", dst], check=True,
                           stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
            os.remove(tmp)
            return len(data)
        except urllib.error.HTTPError as e:
            if e.code == 429:               # рейт-лимит Pollinations — долгая пауза
                if attempt == 6:
                    print(f"  ✗ {slug}: 429 (сдаюсь)", flush=True); return 0
                time.sleep(20 * attempt)
            else:
                if attempt == 6:
                    print(f"  ✗ {slug}: HTTP {e.code}", flush=True); return 0
                time.sleep(5 * attempt)
        except Exception as e:
            if attempt == 6:
                print(f"  ✗ {slug}: {e}", flush=True); return 0
            time.sleep(4 * attempt)
    return 0

def main():
    only = set(a.strip() for a in sys.argv[1:] if a.strip())
    rows = []
    with open(TSV, encoding="utf-8") as f:
        for line in f:
            p = line.rstrip("\n").split("\t")
            if len(p) >= 4:
                rows.append(p[:4])
    if only:
        rows = [r for r in rows if r[0] in only]
    total = len(rows); done = 0; made = 0
    print(f"К генерации: {total} (FORCE={FORCE})", flush=True)
    for slug, cat, label, title in rows:
        done += 1
        dst = os.path.join(OUT, slug + ".jpg")
        if os.path.exists(dst) and not FORCE:
            continue
        prompt = build_prompt(cat, title)
        n = fetch(slug, prompt)
        if n:
            made += 1
            print(f"[{done}/{total}] ✓ {slug}  ({topic(title,cat)})", flush=True)
        time.sleep(4)
    print(f"Готово. Создано: {made}", flush=True)

if __name__ == "__main__":
    main()
