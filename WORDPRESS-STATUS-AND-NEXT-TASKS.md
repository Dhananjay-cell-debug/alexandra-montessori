# Alexandra Montessori — WordPress Status & Next Tasks

_Last reviewed: 2026-07-15. Purpose: let any fresh Claude terminal catch up on the WordPress
side in one read and start working. Honor scope discipline — make only the change asked, ask
before restructuring, never deploy to production without review._

---

## TL;DR (read this first)

- The **WordPress backend is basically fully built** (Events, Testimonials, Nurseries, Jobs,
  Settings, Careers application API) in `functions.php` + `inc/am-careers-api.php`.
- The **React frontend reads all of it** via `window.amData.*` with a static `site.js` fallback.
- **THE ONE BIG GAP:** the theme's bundled `dist/` is from **Jun 25** and is ~3 weeks stale.
  The React app has changed massively since (all the client's July feedback + many new pages).
  So the local WordPress site is serving an **old version of the site**. It needs a `build:wp`
  re-sync to catch up. This is the "work Claude probably has to do."
- **THE ONE BIG OPEN QUESTION:** production is the **static Hostinger** build
  (`alexandra.krildigital.com`), which has **no `window.amData`** → it always uses static
  `site.js`. So **CMS edits never reach the live site** unless production becomes WordPress-hosted.
  This is unresolved and is a business decision, not a code task.

---

## File locations (absolute paths)

| Thing | Path |
|---|---|
| React app (source of truth) | `C:\DHANANJAY\claude code\CLIENT PROJECTS\alexandra montesorri\alexandra-montessori\` |
| WordPress site (Local WP) | `C:\Users\Dhananjay\Local Sites\alexandra-montessori\app\public\` |
| Theme | `…\wp-content\themes\alexandra-theme\` |
| Theme `functions.php` (all CMS logic) | `…\alexandra-theme\functions.php` |
| Careers REST API | `…\alexandra-theme\inc\am-careers-api.php` |
| Theme bundled build | `…\alexandra-theme\dist\` (**stale — Jun 25**) |
| CMB2 plugin | `…\wp-content\plugins\cmb2\` (present; confirm it's **activated** in wp-admin) |
| Full CMS architecture plan | `C:\Users\Dhananjay\.claude\plans\we-need-to-step-indexed-jellyfish.md` |
| Local WP domain | `https://alexandra-montessori.local/` |

There is **no wp-cli / php / composer on PATH** in this environment — plugins can only be
activated manually in wp-admin (or Local's "Open site shell"). Verify the data layer with:
`curl -sk https://alexandra-montessori.local/ | grep amData`

---

## What has been DONE on WordPress (verified 2026-07-15)

### 1. SPA-into-theme integration (`functions.php`)
- Manifest-driven enqueue: reads `dist/.vite/manifest.json` → enqueues the hashed CSS + entry
  JS as an ES module (survives every rebuild, no hardcoded hashes).
- `template_redirect` returns **HTTP 200 on `is_404()`** front-end routes → SPA deep-links /
  refresh work. **New React routes need NO WordPress change** — the catch-all covers them.
- `index.php` is just the `<div id="root">` shell. Admin bar hidden on front end.

### 2. CMS data layer — all wired to `window.amData` (Phases 1–3 code all present)
`am_localize_cms_data()` injects `window.amData` with: `events`, `blogs`, `testimonials`,
`settings`, `nurseries`, `jobs`. Every getter returns the **exact shape** of the matching
`src/data/site.js` export, and React reads `window.amData.X || staticX`.

| Area | WP structure | Editable in admin | React consumer |
|---|---|---|---|
| Events | `am_event` CPT + native meta box | ✅ | `pages/Events.jsx` |
| Testimonials | `am_testimonial` CPT + native meta box | ✅ | `pages/Home.jsx` |
| Nurseries | `am_nursery` CPT + **CMB2** fields (area, tagline, address, phone, email, hours, welcome, hero/welcome image, gallery `file_list`, fee-sheet PDF) | ✅ (needs CMB2) | `site.js` `mergeNursery` → `Nurseries.jsx`, `NurseryDetail.jsx`, `ContactLocation.jsx` |
| Jobs / vacancies | `am_job` CPT + **CMB2** fields (status, location, type, hours, salary, short/full desc, apply URL/email) | ✅ (needs CMB2) | `pages/Vacancies.jsx`, `pages/Apply.jsx` |
| Global settings | **CMB2 options page** `am_settings` (phone, email, hours, address, apply URL, social-links repeater) | ✅ (needs CMB2) | `site.js` `wpSettings` |
| Careers applications | REST endpoint + CPT storage + email (`inc/am-careers-api.php`) | reads submissions in admin | `components/ApplicationForm.jsx` |

`functions.php.bak-phase1` and `*.bak-phase1` React files are the Phase-1 snapshots — keep.

---

## What still needs to be DONE (WordPress side)

### ✅ TASK 1 — Re-sync the theme `dist/` — **DONE 2026-07-15**
Completed: backed up old theme dist → `themes/alexandra-theme-dist-backup-2026-07-15-114010`;
ran `build:wp`; replaced theme `dist/`; ran `build` to restore the Hostinger-base project dist.
Verified on `https://alexandra-montessori.local/`: serves the fresh bundle `index-D2SMcKtc.js`,
all new routes (fees, fee-calculator, food-hygiene-rating, funded-childcare, careers/vacancies)
return **200**, and `var amData` injects all 6 keys (events/blogs/testimonials/settings/
nurseries/jobs) — values empty so React falls back to `site.js` as designed. Project `dist/`
restored to Hostinger base `/` (`index-dbO5KQRR.js`). _Redo this re-sync after every future
React change that should appear in WordPress._

<details><summary>Original task (kept for reference)</summary>

The theme was serving the **Jun 25** build (`index-B2QvAf3v.js`). The current app
(`dist/index.html` = `index-dbO5KQRR.js`, Jul 14) is 3 weeks newer and includes ALL of:
the client's July feedback (lighter homepage, bigger logo, reordered nurseries
Hounslow→Heston→Hammersmith, age-range copy, removed "Family Owned"/"On-site Chef", etc.) and
the **new pages**: Fees, Fee Calculator, Funded Childcare, Blog + Blog article, Food Hygiene
Rating, Vacancies, Apply, Check Availability.

**Steps:**
```bash
cd "C:/DHANANJAY/claude code/CLIENT PROJECTS/alexandra montesorri/alexandra-montessori"
npm run build:wp            # base = /wp-content/themes/alexandra-theme/dist/
# copy the fresh dist into the theme (back up the old one first):
#   theme dist → …\alexandra-theme\dist\
npm run build               # ⚠️ RESTORE the Hostinger-base dist afterwards (base '/')
```
**⚠️ FOOTGUN:** `build:wp` overwrites `dist/` with **WP-base** asset paths
(`/wp-content/themes/...`). If that dist is ever uploaded to Hostinger the site breaks. Always
run a plain `npm run build` afterwards so the project `dist/` goes back to Hostinger base `/`.
Better: build:wp, copy to theme, then immediately rebuild for Hostinger. Keep a theme backup
(pattern: `alexandra-theme-backup-YYYY-MM-DD`) before overwriting.

**Verify after:** load `https://alexandra-montessori.local/`, confirm new pages render and the
served JS hash matches the fresh build.

</details>

### 🟡 TASK 2 — Confirm CMB2 is activated, then check the data contract still holds
- CMB2 is installed but nursery/job/settings boxes only register if `new_cmb2_box()` exists
  (i.e. plugin **activated**). Confirm in wp-admin → Plugins.
- The app has grown; re-verify getter shapes vs current `site.js` exports before relying on CMS
  overrides. Known drift to check: **per-branch age range** (client wants Hammersmith 12mo–5y,
  others 6mo–5y) is handled in `site.js` code but there is **no `ageRange` CMB2 field** on
  `am_nursery`, and `am_get_nurseries()` doesn't return it. Add the field only if the client
  wants age range CMS-editable; otherwise leave in code.

### 🟡 TASK 3 — Decide the blog data source (architecture drift)
`pages/Blogs.jsx` now imports from **`src/data/blogs.generated.js`** (produced by
`npm run sync:blogs` → `scripts/sync-official-blogs.mjs`), **NOT** from `window.amData.blogs` /
`am_get_blogs()`. So the CMS blog getter is currently **unused by the frontend**. Decide: keep
the build-time generated-file approach (simpler, but not client-editable in WP) or wire Blogs
back to the CMS getter. Flag to the user — don't change silently.

### 🟢 TASK 4 — Populate CMS content (optional, only if client will self-edit)
All CPTs/fields exist but are empty, so the site runs entirely on static `site.js` fallback.
To actually demo/enable client editing, enter nursery + settings + job content in wp-admin so
`amData` overrides the fallback. This is content entry, not code.

### 🔵 TASK 5 — Crawler/social SEO (Phase 3+, later)
Pure client-rendered SPA → Facebook/LinkedIn/WhatsApp scrapers don't run JS, so React's
runtime meta/OG are invisible to them. Fix = route-aware PHP meta injector in
`index.php`/`functions.php`. Not started. Only relevant if production becomes WordPress-hosted.

---

## 🚩 THE strategic open question (needs the user / client, not code)

**Is production going to be WordPress-hosted, or is WordPress only a local authoring/preview tool?**
Today production = static Hostinger (`alexandra.krildigital.com`) with **no `window.amData`**,
so every CMS edit is invisible to real visitors. The whole CMS only becomes "live" if the
production site is served by WordPress. Until this is decided, the CMS is a local-only capability.

---

## CMS HANDOVER GAP ANALYSIS (added 2026-07-15)

Audit of what the current site renders vs what the client can actually edit from wp-admin
(sections today: Posts, Media, Events, Testimonials, Nurseries, Jobs, Applications, Site
Settings). Prioritized.

### A. Site shows it, but there is NO field to edit it (missing controls)
1. 🔴 **Ofsted rating + report link** — per nursery (`site.js` `ofstedRating`/`ofstedUrl`/
   `ofstedLabel`). Client explicitly asked "Ofsted Good on every page + footer." Changes on
   re-inspection. No CMB2 field on `am_nursery`. → add 3 nursery fields + return in
   `am_get_nurseries()` + merge in `site.js`.
2. 🔴 **Food Hygiene rating + date + FSA link** — per nursery (`foodHygieneRatings` export;
   drives the new `/food-hygiene-rating` page the client requested). Changes on re-inspection.
   No field. → add nursery fields (rating/date/href) + getter + wire the page.
3. 🔴 **Fee sheets (PDFs)** — the `/fees` page imports the standalone `feeSheets` list, **not**
   the nursery `feeSheetPdf` CMB2 field that already exists. So the field the client would edit
   does NOT drive the Fees page. Fees change yearly. → point Fees page at the nursery field
   (single source) so the existing CMS control actually works.
4. 🟠 **Age range per nursery** — client explicitly: Hammersmith 12mo–5y, others 6mo–5y. Code
   only, no CMB2 field. → add `ageRange` nursery field + getter.
5. 🟠 **Funded childcare content** (`funding`/`fundingSteps`/`fundingResources`) — policy-driven,
   changes. Currently code-only. → optional Settings section or leave locked (decision).
6. 🟡 **FAQs** (`faqs` export) — hardcoded. → optional repeater/CPT.

### B. Traps that will mislead the client
7. 🔴 **Blog is a trap.** The "Posts" menu looks editable, but `Blogs.jsx` reads the build-time
   `src/data/blogs.generated.js`, so anything the client writes in Posts **never appears on the
   site**. → either wire the blog to the CMS (`am_get_blogs()` already exists) or hide/relabel
   Posts. Must resolve before handover.
8. 🟠 **Contact enquiry form doesn't send.** `ContactForm.jsx` fakes success with
   `setTimeout(...,1200)` — no email is sent anywhere, despite the UI saying "routed to
   {branch email}". Client explicitly cares which email enquiries divert to → today: none.
   (Careers `ApplicationForm` DOES work → POST `/api/apply.php`.) → wire contact form to a real
   endpoint like the careers one.

### C. Handover safety (protects the design — arguably most important)
9. 🔴 **Client is being given an Admin account** (screenshot shows Appearance/Plugins/Users/
   Tools/Settings). A non-technical client with admin can switch themes, delete the CMB2 plugin,
   or edit theme files and break the SPA. → create a **restricted Editor/custom role** that sees
   only Media + the `am_*` sections + Site Settings. This is the core of "give control without
   letting them break the design."
10. 🟠 **Admin is empty + cluttered.** Every section shows "No posts found," and default menus
    (Comments, Posts, Tools) are noise. → (a) **seed each CPT with the current real site
    content** so the client edits their actual data instead of a blank panel; (b) hide
    irrelevant menus + add short helper text per section.

### D. Strategic (unchanged, still open)
All of the above only reaches real visitors if **production becomes WordPress-hosted**. Live is
still the static Hostinger build (`alexandra.krildigital.com`) with no `window.amData`. Confirm
the hosting model before investing further in CMS polish.

---

## Guardrails (from project memory)
- Two sites live on the Hostinger host — only `alexandra.krildigital.com` /
  `domains/krildigital.com/public_html/alexandra` may be touched. Never the other one.
- Make ONLY the exact change asked; ask before restructuring; **no production deploy without
  explicit user review**. Updating the **Local** theme dist is fine (reversible, backups exist).
