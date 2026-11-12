# Little Rainbows SEO

The SEO screen for Little Rainbows Day Nursery (wp-admin → **SEO**, directly
below the Site Editor, `admin.php?page=ldrn-seo`). It is the INU Media /
Welcome Woods SEO suite fitted to a nursery and to a site that draws its pages
in the browser.

## How it fits the existing SEO layer

`mu-plugins/ldrn-seo.php` already writes each address's title, description,
canonical, robots, share tags, JSON-LD and `/sitemap.xml`, and answers 404 for
unknown addresses. This plugin **never writes a second copy** of any of that.
It feeds that layer through its own filters:

- `ldrn_seo_static_routes` – words, share image and "Hide from Google" chosen
  on the Pages tab, for the fixed pages
- `ldrn_seo_route_meta` – the same for each setting, article, event, vacancy

and adds what it lacks: Bing/Yandex/Pinterest/Facebook verification, the
nursery's facts in the structured data (ChildCare, Ofsted URN, company number,
hours, pin), a `<noscript>` reading of each page for crawlers that do not run
JavaScript, the AI-crawler policy in robots.txt, `/llms.txt`, redirects
managed from the screen, and 404 and crawler logs. GA4 and the Google
verification code are read from and written to the theme's Site Settings
(`ldrn_site_settings`), so the two screens never disagree.

## Principles

1. **Every number is measured, never estimated.** Each figure names its source:
   the audit of the served and rendered pages, the server logs, consented
   visitors, the submissions inbox, Search Console, or Lighthouse/PageSpeed. A
   block with no data is not drawn.
2. **Nothing about the nursery is invented.** Business defaults come from the
   site's own content and public records (Ofsted URN EY545805, LRDN Limited
   10610476, the Google Maps place). Unknowns stay empty.
3. **Consent first.** Visitor measurement and GA4 load only after the visitor
   allows analytics (`localStorage "ldrn-consent"`, event `ldrn:consent`). The
   site has no cookie panel yet, so nothing is measured until it has one.

## Tabs

| Tab | What it answers |
|---|---|
| Overview | Health dial, whole-site speed, every page against 13 on-page checks, fix-first |
| Pages | Each page as a plate with SEO + readability; the editor with the Google result, the WhatsApp preview and analysis of the rendered text |
| Audit | Health score, every issue with its fix and pages, per-page answer time and weight |
| Speed | Lighthouse / PageSpeed per page, phone and desktop |
| Traffic | Search Console, enquiries from the inbox, consented visitors, Core Web Vitals, crawlers |
| Business | The nursery's facts with a Google panel preview, listings (Google, Ofsted, Companies House, childcare directories) with consistency checks, the JSON-LD graph |
| Settings | Setup board, connections, indexing, measurement, redirects and the 404 log |

## Speed numbers

Google's keyless PageSpeed quota is usually exhausted, so either paste a
PageSpeed API key in Settings, or run Lighthouse here with PageSpeed's settings
and import it:

```bash
# Git Bash: MSYS_NO_PATHCONV=1, or "/" becomes a Windows path
node tools/lighthouse-run.mjs https://ldrn.krildigital.com <out-dir> "/,/about,..."
wp eval 'echo ldrnseo_lh_import_dir("<out-dir>", "local");'
```

## Install

Copy this folder to `wp-content/plugins/` and activate **Little Rainbows SEO**.
Do not run Yoast or Rank Math alongside it.
