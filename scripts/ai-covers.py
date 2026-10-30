#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
ai-covers.py — генерация AI-обложек статей prodom-expert через Cloudflare Workers AI
(модель flux-1-schnell, бесплатный дневной лимит). Кладёт assets/img/articles/{slug}.jpg
(1200x630) + .webp. Пропускает уже готовые jpg (докачка).

С 30.10.2026 провайдер — Cloudflare Workers AI (Pollinations закрыл бесплатную генерацию,
стал отдавать HTTP 402). Аккаунт тот же, что под Pages (prodom-expert). Нужен:
  CF_ACCOUNT_ID   — по умолчанию прошит (d6d43b8e...); можно переопределить env.
  CF_API_TOKEN    — токен с правом Workers AI. Если не задан env — берётся oauth_token
                    из конфига wrangler (тот же, что деплоит Pages; права Workers AI есть).

Использование:
  python3 scripts/ai-covers.py            # все статьи из реестра (кроме уже с jpg)
  python3 scripts/ai-covers.py slug1 slug2 ...   # только эти слаги (перегенерация)
  FORCE=1 python3 scripts/ai-covers.py ...       # перезаписать даже существующие jpg

Промпт строится: сцена по категории + перевод ключевых слов из заголовка.
Все картинки ВСЁ РАВНО смотреть глазами перед заливкой (AI иногда даёт артефакты).
"""
import os, sys, time, json, base64, glob, re, subprocess, urllib.parse, urllib.request, urllib.error

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
TSV  = os.path.join(ROOT, "scripts", "articles.tsv")   # slug \t cat \t label \t title
OUT  = os.path.join(ROOT, "assets", "img", "articles")
FORCE = os.environ.get("FORCE") == "1"

CF_ACCOUNT_ID = os.environ.get("CF_ACCOUNT_ID", "d6d43b8e4c7d1d4858991c524843db4c")
CF_MODEL = os.environ.get("CF_AI_MODEL", "@cf/black-forest-labs/flux-1-schnell")

def _cf_token():
    """Токен Workers AI: env CF_API_TOKEN или oauth_token из конфига wrangler."""
    t = os.environ.get("CF_API_TOKEN")
    if t:
        return t.strip()
    pats = ["~/Library/Preferences/.wrangler/config/*.toml",
            "~/.wrangler/config/*.toml", "~/.config/.wrangler/config/*.toml"]
    for pat in pats:
        for p in glob.glob(os.path.expanduser(pat)):
            m = re.search(r'oauth_token\s*=\s*"([^"]+)"', open(p).read())
            if m:
                return m.group(1)
    return None

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
 ("на балконе","cozy glazed balcony with wood-look composite decking floor, plants, warm light"),
 ("балкон","cozy glazed balcony with wood-look composite decking floor, plants, warm light"),
 ("встроенн","modern fitted kitchen with built-in oven, hob, hood and appliances"),("кухонный гарнитур","kitchen cabinets"),("гарнитур","kitchen cabinets"),
 ("компьютерное кресло","ergonomic office chair"),("кресло","armchair"),
 ("диван","sofa"),("шкаф-купе","sliding wardrobe"),("шкаф","wardrobe"),
 ("комод","chest of drawers"),("кровать","bed"),("детск","children's room"),
 ("письменн","writing desk"),("тумба","tv console"),("стеллаж","shelving unit"),
 ("полк","shelves"),("столешниц","kitchen countertop"),("стол","table"),
 ("прихож","hallway with storage"),("мойка","kitchen sink"),("смесител","faucet"),
 ("краска для ванной","modern bathroom with freshly painted walls, paint roller and paint bucket"),("ванн","bathtub"),("унитаз","toilet"),
 ("душевая система","modern shower system with large round rain shower head and hand shower on a wall bar in a tiled bathroom"),
 ("душевая стойка","modern shower system with rain shower head and hand shower bar in a tiled bathroom"),
 ("душев","shower cabin"),("душ","shower"),
 ("фартук","kitchen tile backsplash"),("плитк","ceramic tile"),("ламинат","laminate flooring"),
 ("паркет","parquet floor"),("линолеум","linoleum floor"),("пробков","cork flooring"),
 ("тёплый пол","underfloor heating"),("теплый пол","underfloor heating"),
 ("стяжк","floor screed"),("плинтус","floor baseboard"),("наливн","self-leveling floor"),
 ("потол","ceiling"),("фотообои","accent wall with a large scenic photo mural"),
 ("жидкие обои","wall finished with textured silk-plaster liquid wallpaper coating, soft matte"),
 ("обои","wallpaper on wall"),("покрас","painted walls"),
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
 ("обогреватель","modern white electric convector heater on the wall of a cozy living room"),
 ("конвектор","modern white electric convector heater in a cozy room"),
 ("котёл","heating boiler"),("котел","heating boiler"),("радиатор","heating radiator"),
 ("отоплен","heating radiator"),("водонагреват","water heater"),("бойлер","water heater"),
 ("варочн","cooktop"),("духов","built-in oven"),("посудомо","dishwasher"),
 ("труб","plumbing pipes"),("канализац","sewage pipes"),("водоснабж","water supply pipes"),
 ("гидроизоляц","bathroom waterproofing"),("шумоизоляц","wall soundproofing"),
 ("звукоизоляц","wall soundproofing"),("акустическ","acoustic panels"),
 ("утеплит","wall insulation with mineral wool and rigid foam boards during renovation"),("утеплен","thermal insulation"),("умный дом","smart home devices"),
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
    token = _cf_token()
    if not token:
        print(f"  ✗ {slug}: нет токена Cloudflare (CF_API_TOKEN или wrangler login)", flush=True)
        return 0
    url = f"https://api.cloudflare.com/client/v4/accounts/{CF_ACCOUNT_ID}/ai/run/{CF_MODEL}"
    body = json.dumps({"prompt": prompt, "steps": 6}).encode("utf-8")
    tmp = os.path.join(OUT, slug + ".tmp.jpg")
    dst = os.path.join(OUT, slug + ".jpg")
    for attempt in range(1, 7):
        try:
            req = urllib.request.Request(url, data=body, method="POST", headers={
                "Authorization": f"Bearer {token}",
                "Content-Type": "application/json"})
            with urllib.request.urlopen(req, timeout=120) as r:
                payload = json.loads(r.read())
            # flux-1-schnell отдаёт {"result":{"image":"<base64 jpeg>"}}, ~1024x1024.
            b64 = (payload.get("result") or {}).get("image")
            if not b64:
                raise ValueError("нет image в ответе")
            data = base64.b64decode(b64)
            if len(data) < 6000:
                raise ValueError(f"too small {len(data)}b")
            with open(tmp, "wb") as f:
                f.write(data)
            # Квадрат 1024x1024 → центр-кроп до соотношения 1.905 (1024x538) → ресайз 1200x630.
            # Вотемарка нет (в отличие от Pollinations), верх/низ не срезаем.
            def dim(axis):
                out = subprocess.run(["sips", "-g", axis, tmp], capture_output=True, text=True)
                return int(out.stdout.strip().split()[-1])
            W = dim("pixelWidth")
            ch = round(W * 630 / 1200)
            subprocess.run(["sips", "-c", str(ch), str(W), tmp, "--out", tmp],
                           check=True, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
            subprocess.run(["sips", "-s", "format", "jpeg", "-z", "630", "1200", tmp,
                            "--out", dst], check=True,
                           stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
            os.remove(tmp)
            # WebP-версия рядом с jpg (в 3-4 раза легче) — сайт отдаёт её через <picture>.
            # Без cwebp просто пропускаем.
            try:
                subprocess.run(["cwebp", "-quiet", "-q", "80", dst, "-o", dst[:-4] + ".webp"],
                               check=True, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
            except Exception:
                pass
            return len(data)
        except urllib.error.HTTPError as e:
            detail = ""
            try:
                detail = " " + e.read().decode("utf-8", "ignore")[:160]
            except Exception:
                pass
            if e.code == 429:               # рейт-лимит/исчерпан дневной лимит Workers AI
                if attempt == 6:
                    print(f"  ✗ {slug}: 429 (лимит Workers AI, сдаюсь)", flush=True); return 0
                time.sleep(20 * attempt)
            else:
                if attempt == 6:
                    print(f"  ✗ {slug}: HTTP {e.code}{detail}", flush=True); return 0
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
