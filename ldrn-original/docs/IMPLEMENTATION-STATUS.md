# Implementation status

## Current status — 4 October 2026

This section supersedes the historical initial-port notes below.

- Local WordPress is installed and populated with the approved nursery content. The LDRN theme, native CMS contracts and visual workspace are connected locally.
- INU's full-screen saved-session UI has been ported and visually compared. Save, rename, search, preview and saved-site navigation were exercised in Chrome. Actual restore/delete and the broader editing acceptance checks remain in `LDRN-WORKSPACE-TASK-NOTE.md`.
- Button/link text renders as an independent React-owned layer. Original page-row controls are always visible. Inserted-label clone/delete and separate target edits were exercised; CMS-record-row parity still needs completion.
- Latest frontend build and 1,849 workspace backend checks pass. The Curriculum shared-anchor bug is fixed and deployed; independent local image replacement/alt/Undo and live visual checks pass. Existing user live/draft/revision documents were preserved.
- SSH and FTPS authenticated successfully. Release `20261003T175621Z-ce5dd3a4` is staged outside `public_html`; 4,888 server file hashes and the SQL hash were verified.
- The actual deployment installer passed an isolated database import, WordPress/admin setup, atomic activation, backup retention and private-file checks. The unauthorized request test also passed.
- **Production is live WordPress.** Release `20261004T054547Z-fb1c80c0` activated over FTPS with official SQLite Database Integration 3.0.2. PHP/SQLite compatibility and the isolated 1,832-check rehearsal passed. The database and consistent backups are outside `public_html`; the original static site is retained privately. Existing hosting MySQL is not connected.
- A private deployment configuration is prepared outside source/webroot at `C:/Users/Dhananjay/Local Sites/lrdn/private-releases/deployment-config.json`. It contains the verified hosting connection and a generated production administrator password; the production database fields remain blank.
- Live WordPress Publish was verified through the browser: a temporary header-label mark appeared on the visitor site, then the original label was published and the mark disappeared. Sessions preserve the marked version and original version; Open, search, saved-site navigation and rename were verified. Settled DOM comparisons match all 21 routes, including seven testimonials on each archive.
- Thirty actual template insertions/Undo pass. Split-template Desktop/Tablet/Phone checks show no overflow. The runtime also avoids offering image replacement when a whole containing section is selected. Latest reusable-link override correction passes local checks and awaits its final browser/deployment checks.
- Local Alexandra access is recovered; actual submissions inbox/detail and Visual Builder sessions were inspected. Appearance is open for the user to identify the missing code-editor reference; local file editing is disabled. No separate code-session feature has been verified.
- Latest user scope adds local Alexandra access recovery and visual inspection of its submissions/code-editor/code-session flows, matching LDRN implementation, Gmail delivery connection and one reusable private Hostinger credentials file. Ordered acceptance tasks are in `LDRN-WORKSPACE-TASK-NOTE.md`.
- Existing private Gmail SMTP settings were located. Forms remain disabled while the LDRN-specific field contracts, private uploads, queue/delivery verification and accurate public privacy notice are completed; the unadapted operations handlers must not be activated as-is.

## Historical initial-port notes

## Built

### Frontend source recovered

The LDRN React source did not exist locally — only the compiled bundle
deployed to `ldrn.krildigital.com`. It has been reconstructed into
`frontend/` from the live build (`index-40N6gh3B-lr20260814224500.js` and its
route chunks), which is the variant `index.html` actually loads.

- 25 routes, matching the live bundle exactly.
- 577 of 577 Tailwind classes used by the live JavaScript are present in the
  rebuilt stylesheet.
- Design tokens, the two `data-ldrn-theme` surface treatments, the rainbow
  accents and the whole component layer recovered from the compiled CSS.
- Verified content differences against the older non-deployed chunks: the
  live site is **Hounslow only** (Chelsea and Chiswick were withdrawn), the
  age range is **6 months – 5 years**, the nav reads **Our Nursery**, and the
  hero and footer carry the approved group affiliation.

### WordPress layer ported

All of Alexandra's architecture, re-namespaced to LDRN — 57 PHP files,
`php -l` clean:

| Component | Status |
| --- | --- |
| Theme (SPA shell, CMS getters, Vite manifest enqueue, security headers) | Ported |
| Content contracts and publish-readiness rules | Ported |
| Enquiries, availability and careers REST APIs | Ported |
| Submissions, private files, rate limiting, validation | Ported |
| Roles, capabilities, admin floor, media library | Ported |
| Operations plugin (schema, repository, ingestion, queue, admin inbox, migration) | Ported |
| Visual Builder (access, documents, locks, media, REST, style schema) | Ported, registry rebuilt for LDRN routes |
| SEO (metadata, canonicals, redirects, schema, sitemap) | Ported, route table rewritten |
| Admin hardening, cache headers | Ported |

Zero occurrences of `alexandra`, `montessori`, `hammersmith` or `heston`
remain in any PHP, JS or CSS file.

### LDRN-specific corrections made during the port

These are places where a mechanical rename would have left something wrong:

- **Recipient safety.** `ldrn_branch_fallback_email()` returned Alexandra's
  branch inboxes with the domain swapped. It now returns an empty string —
  guessing an address would send a parent's enquiry to an inbox nobody reads.
- **Sending identity.** The mail queue defaulted to
  `website@forms.<domain>`. LDRN has not confirmed a sending domain, so the
  defaults are empty and WordPress's own default is used until one is set.
- **Content model.** `am_nursery` became `ldrn_setting`, and wp-admin now
  says "Setting" everywhere the public site says setting.
- **Name collision.** `am_get_nurseries()` and `am_get_settings()` both
  renamed onto `ldrn_get_settings()`. The global blob is now
  `ldrn_get_site_settings()`, and `window.ldrnData` exposes `site` for
  globals and `settings` for the records.
- **Routes.** `/nurseries/:slug` → `/settings/:slug`; the per-branch
  `/contact/:slug` branch was removed because LDRN has no such route; the
  legacy redirect table was rewritten for LDRN's own former URLs.
- **Media library.** Sub-collections are now derived from the published
  setting records instead of a hardcoded Hounslow/Heston/Hammersmith list.
- **Hero preload.** The theme preloaded a promo-video poster that does not
  exist in LDRN; it now preloads the real hero photograph and the two
  self-hosted variable fonts.

### One real defect found and fixed

The live site authors the parent-stories scrim as `bg-sky-900/78`. That step
is not in Tailwind's default opacity scale, so the rule was never generated
and the white "What Parents Say" heading currently sits on a bare, bright
photograph. The rebuild adds the step so the authored value works.

### CMS wiring

`frontend/src/lib/content.js` adapts WordPress records into the shapes the
components already render, falling back to the approved baseline outside
WordPress. Settings, events, testimonials, food hygiene ratings, vacancies,
articles and the global identity are all editor-owned. Fixed editorial copy
(page headings, form labels, the EYFS narrative) stays in `data/site.js`.

## Deliberately not active

- No personal data is stored or emailed. Both submission locks are closed;
  `window.ldrnData.submissions.enabled` is `false`.
- No production site, database, host or DNS record was touched.
- No content record has been seeded. Nothing appears publicly until an
  administrator creates it and it passes its readiness check.

## Next

1. Stand up a Local WP instance for LDRN and install the theme and
   mu-plugins.
2. Create the Hounslow setting record and compare every route against the
   live reference at desktop and mobile.
3. Seed the confirmed content, then walk the client through review mode
   (`localStorage.setItem("ldrn-review-mode", "true")`) to collect the
   outstanding answers.
4. Only after the privacy policy and approved recipients exist: open the two
   submission locks on staging and test each form end to end.
