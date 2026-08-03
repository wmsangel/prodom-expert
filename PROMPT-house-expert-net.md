# Промпт для создания сайта house-expert.net

> **Как использовать:** открой новую сессию Claude (или другого агента-кодера: Cursor/Codex/Copilot Workspace), создай пустую папку `house-expert.net/` и скопируй весь блок ниже как первое сообщение. Агент должен на выходе отдать готовый к деплою сайт. После завершения — деплой на любой PHP-хостинг (Beget, Timeweb, Hostinger, Bluehost). Для AdSense нужен домен старше нескольких недель и 20+ опубликованных статей.

---

## ПРОМПТ (копировать всё, что ниже, до маркера END)

You are a senior full-stack developer + SEO specialist + content writer. Build a complete, deploy-ready home improvement content website for the English-speaking market (primary audience: US, UK, Canada, Australia). Domain: **house-expert.net**. The site will be monetized with **Google AdSense** + display ads + affiliate links (Amazon Associates, Home Depot, Lowe's affiliate programs).

The site must be production-ready: I should be able to upload the files to any standard PHP hosting and have a working website immediately.

---

### 1. Tech stack & constraints

- **Pure PHP 7.4+**, no frameworks, no Composer, no build step.
- **Vanilla JS only** (no React/Vue). All CSS in one file.
- **File-based content**: each article is a plain HTML fragment in `articles/{category}/{slug}.html`. Metadata stored in PHP arrays inside `article.php`, `category.php`, `search.php`, `rss.php` (we explicitly don't want a database).
- **No external runtime dependencies.** Fonts via Google Fonts, ads via AdSense `<script>` snippets only.
- All `.php` files must be valid syntax (run `php -l` mentally before output).
- All XML files (`sitemap.xml`, `sitemap-feeds.xml`, `sitemap_index.xml`, `rss.php` output) must be well-formed.
- All HTML must be valid HTML5, all `<img>` tags must have `alt`, `width`, `height`, `loading="lazy"` (except above-the-fold).

---

### 2. Branding & visual identity

- **Site name:** House Expert
- **Tagline:** "Practical guides for renovation, repair, and home design"
- **Logo:** text "House" + colored "Expert" with a small house icon emoji (🏠) — same pattern as the reference site
- **Color palette (warm, magazine-style, NOT generic Bootstrap):**
  - Primary accent: brick red `#C0392B`
  - Charcoal text: `#2C2C2C`
  - Beige background: `#E8E4DD`
  - Warm white: `#FAF8F4`
  - Muted text: `#6B6B6B`
  - Border: `#D9D5CD`
- **Typography:**
  - Headlines: Playfair Display (serif), 600/700 weight
  - Body: Source Sans 3 (sans-serif), 400/500 weight
- **Style direction:** clean editorial layout (think: Apartment Therapy, Bob Vila, This Old House, Family Handyman). NOT a corporate/SaaS look.

---

### 3. Site structure

```
house-expert.net/
├── index.php                     ← homepage with hero + 9 featured cards + 6 category cards
├── article.php                   ← single article page (?slug=...)
├── category.php                  ← category listing with pagination (?cat=...&page=N)
├── search.php                    ← internal search across all articles
├── about.php                     ← About us page
├── contacts.php                  ← Contact page (with mailto)
├── privacy.php                   ← Privacy Policy (GDPR + CCPA mentions)
├── terms.php                     ← Terms of Use (NEW — required for AdSense)
├── disclaimer.php                ← Affiliate disclosure (NEW — FTC requires it)
├── 404.php
├── rss.php                       ← RSS 2.0 feed
├── sitemap.xml
├── sitemap-feeds.xml
├── sitemap_index.xml
├── robots.txt
├── favicon.ico                   ← multi-size .ico in root
├── ads.txt                       ← AdSense authorization file (placeholder)
├── .htaccess                     ← security headers, gzip, expires, www→non-www, http→https
├── includes/
│   ├── header.php                ← <head> with all SEO + ad scripts; <header> with nav
│   ├── footer.php                ← <footer> + analytics
│   ├── menu.php
│   ├── sidebar.php               ← popular posts widget + category widget + ad slot
│   ├── article-cover.php         ← helper to find cover image per slug
│   ├── seo-config.php            ← SITE_CANONICAL constant
│   └── load-seo.php              ← safe loader for seo-config
├── articles/
│   ├── renovation/               ← 8 articles
│   ├── kitchen-bath/             ← 8 articles
│   ├── plumbing/                 ← 8 articles
│   ├── electrical/               ← 8 articles
│   ├── interior/                 ← 8 articles
│   └── diy-tips/                 ← 8 articles
└── assets/
    ├── css/style.css             ← single stylesheet, mobile-first
    ├── js/main.js                ← burger menu, sticky header, lazy-load polyfill
    └── img/
        ├── favicon.svg
        ├── favicon-16.png
        ├── favicon-32.png
        ├── favicon-120.png
        ├── apple-touch-icon.png
        ├── og-default.jpg        ← 1200×630 site default OG image
        └── articles/             ← cover image per article slug, .jpg or .png, 1200×630
```

---

### 4. Categories (use these slugs and labels exactly)

| Slug | Label | Icon | Description (used as meta description for category page) |
|---|---|---|---|
| `renovation` | Renovation | 🔨 | Step-by-step guides for whole-home renovation projects: walls, floors, ceilings, drywall, painting, and finishes. |
| `kitchen-bath` | Kitchen & Bath | 🛁 | Kitchen remodels, bathroom upgrades, tile work, cabinets, countertops, and fixture selection. |
| `plumbing` | Plumbing | 🚿 | DIY plumbing repairs, fixture installation, pipe materials, water filtration, and leak prevention. |
| `electrical` | Electrical | 💡 | Safe home electrical work: outlets, switches, lighting, smart home wiring, and panel basics. |
| `interior` | Interior Design | 🛋️ | Color theory, layout, lighting, textiles, and decor strategies for every room. |
| `diy-tips` | DIY Tips | 🧰 | Tools, techniques, budget tips, and pro advice for the home handyman or handywoman. |

---

### 5. Pages — what each must contain

#### `index.php` (homepage)
- `<h1>` with site tagline
- Hero section with eyebrow text, large headline, two CTA buttons
- "Latest Articles" section: grid of 9 featured article cards (image + category badge + title + excerpt + date + "Read More")
- 728×90 horizontal ad slot below the grid
- "Browse Topics" section: grid of 6 category cards with icon, label, description, article count (computed live from filesystem with `glob()`)
- Sidebar with popular posts widget
- JSON-LD: `WebPage` + `BreadcrumbList`

#### `article.php`
- `?slug=` query param, sanitize with `preg_replace('/[^a-z0-9\-]/', '', strtolower($slug))`
- Look up article in `$articlesData` array (keyed by slug). If not found and file doesn't exist → 404.
- Look up article HTML file in `articles/{slug}.html` first, then `articles/{cat}/{slug}.html`
- Render: breadcrumbs → article header (category badge, H1, meta: date, author, read time) → cover image (1200×630, fetchpriority="high") → 728×90 ad slot above content → article body (loaded via `file_get_contents`) → 728×90 ad slot below content → "Related Articles" (3 cards from same category)
- JSON-LD: `Article` (with author Person, publisher Organization, image with width/height) + `BreadcrumbList`
- Open Graph + Twitter Card tags from article meta

#### `category.php`
- `?cat=` and `?page=` params
- Validate category against whitelist
- Filter `$allArticlesMeta` by category, sort by date desc
- Pagination: 8 per page
- Render: category header (icon, H1, description, article count) → list view (image left, content right) with 8 items → ad slot → pagination
- JSON-LD: `BreadcrumbList` + `ItemList` of articles on the page
- For `page > 1` add `<link rel="prev">` and `<link rel="next">`

#### `search.php`
- `?q=` param (trimmed, max 100 chars, strip_tags)
- If `q` is empty: list all articles
- Else: case-insensitive match across title and description, render results
- **Set `robotsMeta = 'noindex, follow'` when query is present** (SEO best practice)

#### `about.php`, `contacts.php`, `privacy.php`, `terms.php`, `disclaimer.php`
- All include `BreadcrumbList` JSON-LD
- `about.php` adds `AboutPage` schema with `mainEntity` Organization
- `contacts.php` adds `ContactPage` schema with two `ContactPoint` (general, advertising)
- `privacy.php` must mention: cookies, Google Analytics, Google AdSense, GDPR (EU users), CCPA (California users), opt-out instructions
- `terms.php` must cover: content licensing, no warranty (DIY safety disclaimer), affiliate links disclosure pointer, governing law placeholder
- `disclaimer.php` must contain explicit FTC affiliate disclosure ("As an Amazon Associate, House Expert earns from qualifying purchases…")

#### `404.php`
- Sets `http_response_code(404)` and `robotsMeta = 'noindex, follow'`
- Friendly message + popular links + return-to-home button

#### `rss.php`
- `Content-Type: application/rss+xml; charset=UTF-8`
- RSS 2.0 with all articles, `pubDate` in RFC 2822 format

#### `sitemap.xml`
- All static pages + all category pages + all article URLs
- `lastmod` per item, `changefreq` and `priority`

#### `sitemap-feeds.xml`
- Minimal valid sitemap pointing to `/` and `/rss.php` (so a Yandex-style "feeds" lookup never errors)

#### `sitemap_index.xml`
- Lists `sitemap.xml` and `sitemap-feeds.xml`

#### `robots.txt`
```
User-agent: *
Allow: /
Disallow: /includes/
Disallow: /assets/js/
Disallow: /search.php?q=
Disallow: /*?utm_

Sitemap: https://house-expert.net/sitemap.xml
Sitemap: https://house-expert.net/sitemap-feeds.xml
```

#### `ads.txt` (placeholder for AdSense)
```
google.com, pub-XXXXXXXXXXXXXXXX, DIRECT, f08c47fec0942fa0
```
(Add a comment instructing the owner to replace `pub-XXXX` with their real AdSense publisher ID before going live.)

#### `.htaccess`
- `Options -Indexes`
- Custom 404 → `/404.php`
- Security headers: `X-Content-Type-Options`, `X-Frame-Options`, `X-XSS-Protection`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy: interest-cohort=()`
- gzip via `mod_deflate` for HTML, CSS, JS, JSON, XML, SVG
- `mod_expires`: 1 month for CSS/JS, 6 months for images, 1 year for fonts
- Block direct access to `/includes/*.php`
- 301: `www.` → no-www
- 301: `http://` → `https://` (with `X-Forwarded-Proto` check so it doesn't loop behind a Cloudflare/Nginx proxy)

---

### 6. SEO requirements (non-negotiable)

1. **Single canonical URL** per page via `<link rel="canonical">` and `og:url`.
2. **Unique `<title>` and `<meta name="description">`** per page. Title format: `Article Title | House Expert`.
3. Meta description: 150–160 chars, descriptive, includes the primary keyword.
4. **Schema.org JSON-LD** on every page:
   - All pages: `Organization` + `WebSite` (with `SearchAction` for sitelinks search box)
   - Homepage: `WebPage` + `BreadcrumbList`
   - Article: `Article` + `BreadcrumbList`
   - Category: `BreadcrumbList` + `ItemList`
   - About: `AboutPage`
   - Contact: `ContactPage`
5. **Open Graph + Twitter Card** (`summary_large_image`) on every page with proper image dimensions (1200×630, set `og:image:width` and `og:image:height`).
6. **Favicon stack:** `/favicon.ico` (multi-size, root level — Yandex/Google convention), plus SVG, plus PNG 16/32/120, plus apple-touch-icon.
7. **Hreflang:** `<link rel="alternate" hreflang="en" href="...">` and `<link rel="alternate" hreflang="x-default" href="...">` (English-only site, but signal language explicitly).
8. **Mobile-first** CSS, responsive grid, no horizontal scroll on 320px viewport.
9. **Core Web Vitals friendly:** preload LCP image on article pages, defer all JS, lazy-load below-fold images, use modern image formats where possible.
10. **No duplicate content:** if the same slug exists in `articles/` root and in `articles/{cat}/`, the category folder version wins (kill the duplicate or redirect).
11. **404 returns 404 status code** (not 200 — important for indexing hygiene).
12. **Trailing slash policy:** consistent (no trailing slash on `.php` files, slash on directory roots).

---

### 7. AdSense + ad placements

Every page that loads AdSense must include the AdSense auto-ads script in `<head>`:

```html
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-XXXXXXXXXXXXXXXX" crossorigin="anonymous"></script>
```

(Comment in code: "Replace ca-pub-XXXXXXXXXXXXXXXX with your AdSense publisher ID before deployment.")

Manual ad slot placements (in addition to auto-ads):
- Homepage: 1× horizontal slot below the article grid
- Article: 1× horizontal slot above article body, 1× horizontal slot below article body, 1× rectangle in sidebar
- Category: 1× horizontal slot below the listing
- Sidebar: 1× rectangle 300×250 (medium rectangle) + 1× rectangle 300×600 (half-page) lower

Each manual slot uses the standard AdSense responsive `<ins class="adsbygoogle">` template, with placeholder `data-ad-client` and `data-ad-slot` and a comment reminding to replace with real IDs.

**Affiliate setup:**
- Footer disclaimer: "House Expert is a participant in the Amazon Services LLC Associates Program…"
- Encourage in-content affiliate links inside DIY product recommendations using `rel="nofollow sponsored"`.

---

### 8. Initial content — 48 articles (8 per category)

Write **8 original, full-length articles per category** (48 total), targeted at the US/UK home improvement search market. Each article must:
- Be **800–1200 words** of original prose (not lorem ipsum, not generic — write like a knowledgeable home blogger)
- Have a clear `<h1>`-equivalent title (rendered by `article.php`, so the HTML fragment starts with a `<p>` lead paragraph, then `<h2>`/`<h3>` subsections)
- Include 2–4 internal links to other articles in the site (for SEO link equity)
- Use US English spelling (color, organize, neighbor)
- Reference real-world brands and product categories where relevant (Kohler, Moen, Behr, Sherwin-Williams, Square D, Leviton, Pella, Andersen, etc.) but NEVER make up specific prices or fake reviews
- Have a real, useful "Bottom Line" or "Takeaway" closing paragraph

#### Article topics — generate exactly these 48

**renovation/** (8)
1. `drywall-installation-and-finishing` — Drywall installation and finishing for DIYers
2. `interior-painting-prep-and-technique` — Interior painting prep and technique
3. `crown-molding-installation-guide` — Crown molding installation guide
4. `hardwood-floor-refinishing-step-by-step` — Hardwood floor refinishing step by step
5. `replacing-baseboards-without-tearing-up-walls` — Replacing baseboards without tearing up walls
6. `popcorn-ceiling-removal-safely` — Popcorn ceiling removal safely
7. `installing-laminate-flooring-over-concrete` — Installing laminate flooring over concrete
8. `acoustic-insulation-for-apartments` — Acoustic insulation for apartments and condos

**kitchen-bath/** (8)
1. `subway-tile-backsplash-step-by-step` — Subway tile backsplash step by step
2. `installing-a-bathroom-vanity` — Installing a bathroom vanity
3. `quartz-vs-granite-countertops` — Quartz vs granite countertops: an honest comparison
4. `walk-in-shower-conversion-guide` — Walk-in shower conversion guide
5. `kitchen-cabinet-refacing-vs-replacing` — Kitchen cabinet refacing vs replacing
6. `bathroom-exhaust-fan-installation` — Bathroom exhaust fan installation done right
7. `under-cabinet-led-lighting` — Under-cabinet LED lighting installation
8. `regrouting-tile-without-removing-it` — Regrouting tile without removing it

**plumbing/** (8)
1. `replacing-a-bathroom-faucet-without-a-plumber` — Replacing a bathroom faucet without a plumber
2. `pex-vs-copper-pipe-comparison` — PEX vs copper pipe: which to choose
3. `under-sink-water-filter-buying-guide` — Under-sink water filter buying guide
4. `fixing-a-running-toilet-step-by-step` — Fixing a running toilet step by step
5. `garbage-disposal-installation-and-replacement` — Garbage disposal installation and replacement
6. `clearing-a-clogged-drain-without-chemicals` — Clearing a clogged drain without chemicals
7. `installing-a-frost-free-hose-bibb` — Installing a frost-free hose bibb (sillcock)
8. `tankless-water-heater-pros-and-cons` — Tankless water heater pros and cons

**electrical/** (8)
1. `replacing-a-light-switch-safely` — Replacing a light switch safely
2. `gfci-outlets-where-and-how-to-install` — GFCI outlets: where and how to install
3. `led-recessed-can-lights-installation` — LED recessed can lights installation
4. `dimmer-switch-led-compatibility-guide` — Dimmer switch + LED compatibility guide
5. `home-wiring-basics-for-diyers` — Home wiring basics for DIYers
6. `surge-protector-vs-whole-house-surge-protection` — Surge protector vs whole-house surge protection
7. `smart-light-switches-with-no-neutral-wire` — Smart light switches with no neutral wire
8. `outdoor-outlets-and-weatherproof-covers` — Outdoor outlets and weatherproof covers

**interior/** (8)
1. `60-30-10-color-rule-explained` — 60-30-10 color rule explained
2. `mixing-wood-tones-without-clashing` — Mixing wood tones without clashing
3. `small-living-room-layout-ideas` — Small living room layout ideas
4. `picking-the-right-area-rug-size` — Picking the right area rug size
5. `gallery-wall-arrangement-formulas` — Gallery wall arrangement formulas
6. `curtains-vs-blinds-vs-shades` — Curtains vs blinds vs shades
7. `lighting-layers-ambient-task-accent` — Lighting layers: ambient, task, accent
8. `home-office-setup-in-a-small-space` — Home office setup in a small space

**diy-tips/** (8)
1. `essential-tools-for-the-home-handyman` — Essential tools for the home handyman
2. `creating-a-realistic-renovation-budget` — Creating a realistic renovation budget
3. `how-to-vet-a-contractor` — How to vet a contractor before signing
4. `home-improvement-permits-when-you-need-one` — Home improvement permits: when you need one
5. `seasonal-home-maintenance-checklist` — Seasonal home maintenance checklist
6. `working-around-asbestos-and-lead-paint-safely` — Working around asbestos and lead paint safely
7. `where-to-buy-building-materials-online` — Where to buy building materials online
8. `protecting-your-home-during-a-renovation` — Protecting your home during a renovation

For each article, also:
- Generate a **1200×630 cover image** as PNG (use Python/Pillow with category-color blocks + title text + brand mark; same approach as the reference site)
- Add the slug + metadata to `$articlesData` in `article.php`
- Add to `$allArticlesMeta` in `category.php`
- Add to `$allArticles` in `search.php`
- Add to `$articles` array in `rss.php`
- Add a `<url>` entry in `sitemap.xml` with today's `lastmod`

---

### 9. Tone, audience, and content rules

- Audience: US/UK homeowners, ages 25–65, doing DIY projects or hiring contractors. Mix of beginners and experienced DIYers.
- Tone: friendly expert. Confident but not preachy. Use "you" naturally. Short paragraphs (2–4 sentences).
- **No fluff intros** like "In today's modern world…" — get to the point in the first sentence.
- **No fake stats** — don't invent percentages or studies.
- **Safety first**: any electrical, plumbing, or structural article must include a paragraph about when to call a licensed pro and reference local code compliance.
- **Avoid medical, legal, or financial absolutes.** Hedge appropriately for liability ("Building codes vary by jurisdiction; check with your local authority.").
- **Honest about limitations**: if a project is genuinely beyond DIY scope, say so.
- **Affiliate-friendly without being spammy**: when mentioning a tool or material, frame it as "what to look for" rather than "buy this exact one."

---

### 10. Quality checklist (the agent must verify before finishing)

- [ ] All PHP files have balanced brackets and would pass `php -l`
- [ ] All XML files would pass `xmllint --noout`
- [ ] Every article slug in `sitemap.xml` has a corresponding HTML file
- [ ] Every article HTML file has a sitemap entry
- [ ] Every article has metadata in `article.php`, `category.php`, `search.php`, `rss.php`
- [ ] Every article has a cover image at `assets/img/articles/{slug}.png` (or `.jpg`)
- [ ] Internal links in articles all resolve to real slugs
- [ ] No `localStorage`, `sessionStorage`, or browser storage APIs in JS (incompatible with some embed contexts)
- [ ] No emojis in code files (only in display strings where intentional, e.g., category icons and the logo)
- [ ] All images have `alt`, `width`, `height`
- [ ] AdSense `client` and ad slot IDs are clearly marked as placeholders
- [ ] `ads.txt` is in the root
- [ ] `.htaccess` works behind both bare-Apache and Apache-behind-Nginx/Cloudflare (HTTPS detection via `X-Forwarded-Proto`)
- [ ] Privacy policy mentions GDPR + CCPA + AdSense + Analytics by name
- [ ] Affiliate disclaimer page exists and is linked from the footer

---

### 11. Deliverable format

Produce **all files** above with their full final content. For long files (article HTML, JSON-LD blobs), include the complete content — no "..." truncation, no "rest is similar." When a file is structurally repeated (e.g., 48 article HTML fragments), write each one individually with unique content.

If you need to produce hundreds of files in a single response and run into length limits, batch by category (one batch = all 8 articles in one category + their cover images + their sitemap entries + their `$articlesData` additions), then move to the next category. Always maintain a running summary at the end of each batch of "files written so far."

When done, end with a single deployment instruction block:

```
DEPLOYMENT
==========
1. Upload all files to the document root of house-expert.net.
2. In header.php, replace ca-pub-XXXXXXXXXXXXXXXX with your real AdSense publisher ID (5 places to update: AdSense script in <head>, plus each <ins> ad slot).
3. In footer.php, replace G-XXXXXXXXXX (Google Analytics) with your real GA4 ID.
4. In ads.txt, replace pub-XXXXXXXXXXXXXXXX with your real publisher ID.
5. Verify domain ownership in Google Search Console + Bing Webmaster Tools.
6. Submit https://house-expert.net/sitemap.xml in both.
7. Apply for Google AdSense once you have 20+ articles indexed and 1+ month of traffic.
```

---

### END OF PROMPT

Now build the entire site. Start with the foundational files (config, header, footer, .htaccess, robots.txt), then the page templates (index, article, category, search, about, contacts, privacy, terms, disclaimer, 404, rss, sitemap), then all 48 articles + cover images by category, then the final deployment instruction.

---

## Подсказки для запуска промпта

1. **Чем больше у тебя будет терпения — тем лучше результат.** За один проход даже у Claude Sonnet 4.6 будет ограничение по длине ответа. Лучше всего работать так:
   - Дать промпт целиком как первое сообщение.
   - Потом отправлять короткие команды типа: «Continue with the renovation category articles», «Now generate the cover images», «Now write the sitemap.xml», «Now show me the .htaccess», и т. д.
2. **Запускать в Cowork mode или в Claude Code.** В Cowork mode агент сразу пишет файлы в папку `/Users/.../Sites/house-expert.net/`. В Claude Code — то же самое плюс возможность сразу запустить локальный PHP-сервер и проверить.
3. **После генерации — пройди quality checklist руками** (он есть в промпте, секция 10). Особенно: PHP-синтаксис, валидность XML, что все картинки реально существуют.
4. **Для AdSense** не подавай заявку сразу — дай сайту минимум 3–4 недели и убедись, что в Google проиндексировалось 20+ статей. Иначе придёт типовой отказ «Insufficient content».
5. **ads.txt**, заголовки в `header.php` и `footer.php` — после деплоя пройди и замени все плейсхолдеры (`ca-pub-XXXX`, `G-XXXX`, `pub-XXXX`) на свои реальные ID. В промпте они оставлены как метки.
