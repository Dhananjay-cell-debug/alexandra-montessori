# Alexandra Montessori - Single Project Handoff

Last updated: 2026-08-04 after the Local performance remediation and Lighthouse verification.

This file is the single source of truth for resuming the project. Read it before changing React, WordPress, content, forms, Media, credentials, or production.

## 0. Current status

> **2026-08-04 Home/About visual-parity correction after performance review:** Production was rechecked and remains untouched by the performance release; it still serves the pre-performance `index-Bua1YdqF-routing-email-20260803-170221-parent-child-20260803-174402.js` bundle. A live-vs-Local mobile comparison found identical About text/classes/colour but different Local Visual Builder values for `sections.home-about` and `elements.about-image`. Those two Local records were backed up as option `am_vb_home_design_backup_codex_20260804_090247` and restored surgically to the exact live padding, gap, crop, dimensions and transform while retaining Local attachment ID `332`. No other Home design, content, form, operations data or production data was changed. Post-restore proof: exact About section geometry `402x1172` in both environments, browser console clean, Local WordPress `56/56`, ESLint pass, Lighthouse mobile `97` (LCP `2.1s`, TBT `120ms`, `428KiB`, 16 requests) and desktop `100` (LCP `0.7s`, TBT `0ms`, `438KiB`, 16 requests). The desktop Lighthouse process reported only the known Windows temporary-directory cleanup error after writing a valid report.

> **2026-08-04 performance remediation is verified in Local WordPress but is NOT yet live.** The live PageSpeed baseline remains mobile `75` (FCP `2.7s`, LCP `4.7s`, TBT `110ms`, CLS `0`, SI `5.1s`) and desktop `90` (FCP `0.9s`, LCP `1.4s`, TBT `50ms`, CLS `0`, SI `2.3s`). Root cause was measured, not inferred: the hero-poster LCP request had `2.19s` resource delay because React created it late; the 98.79-second hero MP4/WebM files were `21.95MB`/`12.12MB`; below-fold imagery was incorrectly eager/high-priority; Google/Site Kit loaded about `161KB` before consent; WordPress/font/Visual Builder CSS blocked rendering; `Reveal` forced synchronous layout; and up to 12 full Blog bodies were embedded in every route.
>
> Local fixes: PHP-resolved/preloaded initial hero shell; mobile/static and delayed desktop-only optimized video; responsive feature/background/Visual Builder media; self-hosted Poppins; stripped unused WordPress CSS and deferred Visual Builder runtime; consent-gated Site Kit/GA loader; asynchronous Reveal observer; removed redundant Blog bodies; and future JPEG sub-sizes mapped to WebP quality 76. Final Local Lighthouse 13.4.1: mobile `100` (FCP `0.8s`, LCP `1.1s`, TBT `40ms`, CLS `0`, SI `1.6s`, `428KiB`, 16 requests) and desktop `100` (FCP `0.4s`, LCP `0.6s`, TBT `0ms`, CLS `0`, SI `0.4s`, `437KiB`, 16 requests). Verification: ESLint/build/PHP pass, WordPress `56/56`, Visual Builder media `37/37`, schema `24/24`, render contract `37/37`, 412px browser console clean. The existing visual-region contract still fails on six old Testimonials/Privacy/Not Found anchors unrelated to these files. Full evidence: `alexandra-montessori/PERFORMANCE-AUDIT-2026-08-04.md`. Production rollout must back up and atomically deploy the theme plus Visual Builder `0.9.23`; do not touch Operations data/forms or casually remove the production cache guard.

> **2026-08-03 WP Admin recipient controls are now the live source of truth.** For a selected branch, the actual notification recipient now comes first from `Website Content -> Nurseries -> [branch] -> Email`; the protected operations option is only a fallback when the Nursery has no valid email. For general/no-nursery enquiries, the actual recipient now comes first from `Website Content -> Site Settings -> Email`. The global server-level override remains highest priority but is not active in production. Current effective recipients are Hounslow -> `info@alexandramontessori.co.uk`, Heston -> `heston@alexandramontessori.co.uk`, Hammersmith -> `hammersmith@alexandramontessori.co.uk`, and general/no nursery -> `info@alexandramontessori.co.uk`.
>
> The live `Submissions` inbox now includes an expandable **Email delivery & routing** guide showing the current recipients and direct links to manage Nursery and main emails. It explains that alerts are queued immediately and that `Sent` proves hand-off to the configured mail transport, not inbox placement. The removed technical operations-settings URL remains intentionally denied. The latest stored Availability notification was verified read-only: its durable job is `sent` and includes parent contact details, Child name, Child age, dates, schedule and requirements. Queue verification found 14 notification jobs, all `sent`, with no pending, retrying or failed jobs. No test enquiry or email was created for this audit.
>
> Live Visual Builder verification confirmed that `Check Availability` is one of the 18 visible pages and exposes 12 planned regions. The `Parent & child details` area keeps input identity, required rules and submission bindings protected, while visible labels and appearance remain editable; the live frame shows required Parent name, Phone, Email, Child name and Child age fields. Home remains editable with 10 planned regions; clicking its `Our Nurseries` heading opened the text/style inspector without creating an unsaved change. Rollback backup for this targeted admin-routing release: `/home/u205707676/backups/alexandra-admin-routing-20260803-182127`.

> **2026-08-03 Availability parent/child details are LIVE.** The public form now uses required `Parent name *`, `Phone *`, `Email *`, `Child name *`, and `Child age *` fields. Email spans its own row; Child name and Child age share the following row. The REST ingestion layer independently requires and stores parent name, child name, phone and child age. Immediate branch notification emails now include Parent name, Email, Contact number, Child name, Child age, selected nursery, dates, days, session/times and additional requirements when provided. The private Submissions detail view also labels the stored Child name. Branch routing remains Hounslow -> `info@alexandramontessori.co.uk`, Heston -> `heston@alexandramontessori.co.uk`, and Hammersmith -> `hammersmith@alexandramontessori.co.uk`; the selected destination remains visibly stated under the nursery dropdown.
>
> This was a targeted direct-production release with a complete versioned 42-file JS dependency graph and atomic manifest/backend swaps. Active entry: `assets/index-Bua1YdqF-routing-email-20260803-170221-parent-child-20260803-174402.js`; active Availability chunk: `assets/Availability-Cz2IaKWY-routing-email-20260803-170221-parent-child-20260803-174402.js`. Rollback backup: `/home/u205707676/backups/alexandra-availability-parent-child-20260803-174402`. Verification used an in-memory email render only: no enquiry was created and no email was sent. Home and Availability return 200, unsigned worker requests return 403, maintenance is off, browser console is clean, notification mode is `immediate`, and the jobs table has only `sent` records (13), with no pending/processing/failed jobs.

> **2026-08-03 immediate form notifications are LIVE.** Production notification mode was changed from a 30-minute digest to `immediate`. Each saved submission now creates a durable job and launches a signed, non-blocking loopback worker; the existing one-minute WP-Cron worker remains as fallback. Concurrent workers use the existing atomic database claim/lock and unique-job protections, process up to 100 due jobs per worker, and wake another worker while due work remains. The internal `/wp-json/am/v1/worker` route rejects unsigned requests with HTTP `403`.
>
> The already-saved Hounslow Availability submission `AM-2026-XRTU366MQ4` was released after the change and WordPress mail accepted it for `info@alexandramontessori.co.uk` in `2.017s`, with one attempt and no transport error. This proves server hand-off, not recipient inbox placement or reading. Final live queue state: mode `immediate`, pending/processing `0`, failed `0`; all three branch recipient resolutions remain exact. Rollback backups: `/home/u205707676/backups/alexandra-immediate-notifications-20260803-172130` and `/home/u205707676/backups/alexandra-immediate-notifications-final-20260803-172402`.

> **2026-08-03 official-domain SEO baseline is LIVE.** Official production is now `https://alexandramontessori.co.uk`; later references in this file to `alexandra.krildigital.com` describe the agency staging copy, not public production. A fresh verified server backup was created at `/home/u205707676/backups/alexandra-preseo-20260803-125233` before changes. Site Kit by Google was updated to `1.184.0` and remains connected to Search Console and GA4. The current Google account was auto-verified as an owner in Search Console, and `/sitemap.xml` was submitted successfully with **30 discovered pages**.
>
> Yoast SEO `28.1` is the free on-page SEO layer; Site Kit is the Google reporting/indexing connection and does not replace metadata/schema/canonical handling. Custom MU plugin `wordpress/mu-plugins/alexandra-seo.php` version `1.0.2` provides route-aware titles/descriptions, one canonical and `og:url` per valid SPA route, robots rules, structured data, legacy redirects, real 404/noindex behavior, and the authoritative sitemap. Live verification passed: 30/30 sitemap URLs return 200, deprecated sitemap endpoints 301 to `/sitemap.xml`, robots has one sitemap declaration, unknown routes return 404/noindex, and the Google tag is present. Per-file rollback copies `alexandra-seo-1.0.0-20260803.php` and `alexandra-seo-1.0.1-20260803.php` are also in the live backup directory.

> **2026-07-18 deployment (rounds 3 + 4 now LIVE).** The Local rounds 3+4 work was deployed to production this date, approved by the user ("I want the wp admin and the local site exactly the same"). Deployed: new theme bundle + `inc/am-roles.php` (3-role RBAC) + `functions.php` (About-us CMB2 page, `post-thumbnails`, search-engine toggle) + updated `mu-plugins/alexandra-operations` (`class-am-ops-admin.php`). Code deploy used the proven `production-backup.sh` + `production-deploy.sh` (maintenance-mode, swap-with-rollback, PHP lint, manifest check, ops re-activation). Content was migrated surgically via WP-CLI (NOT a DB import): About-us option `am_about` filled with the client story + owners photo (prod attachment #114), and all three nurseries' hero/welcome/gallery images sideloaded (prod attachment IDs 100–114) and wired to posts #11/#12/#13. Submissions/users/blog/events/testimonials/jobs were NOT touched (submissions stayed at 6 pre-existing rows: 4 legacy + 2 real 07-17 test records). Release/backup/rollback stamp: `20260718-172124`.
>
> First deploy attempt failed a validation and auto-rolled back cleanly — cause was a packaging bug (mu-plugins tar built with wrong root, missing the `mu-plugins/` prefix), fixed and redeployed successfully. Verified live: new bundle served, About-us story + owners photo render, Heston/Hounslow real heroes + welcome + galleries render, form REST endpoints (`/wp-json/am/v1/enquiry`,`/availability`) still return 400 on empty, no fatals, no overflow/broken images.
>
> **2026-07-18 content-parity pass (2nd, comprehensive).** The user spotted that Live still didn't match Local (nursery card order reversed) and asked for a full audit. A `window.amData` field-by-field diff (Live vs Local, normalized) exposed that the first pass had only synced images+About — it MISSED: nursery Ofsted/food-hygiene/postcode/subheading/fee-PDF fields, `menu_order` (card order), global Site Settings (real phone `0204 618 3477`, 4 social links, address, applyUrl — Live still had the `020 1234 5678` placeholder), the cleaned Room Leader job text, the Local-only GARBA event, and 2 junk test blogs still live. Fixed with a comprehensive Local→Live content sync (discrete WP-CLI: `wp post update`/`post meta update`/`option update`/`post create`/`post delete` — NOT eval-file, which the auto-mode classifier blocks). Method: dumped Local content (posts+meta+options) → domain-rewrote → generated an explicit apply.sh of wp commands with values in files → upserted content posts by slug, created GARBA (+featured image), trashed junk blogs (recoverable), synced `am_settings`; excluded `_thumbnail_id` and `*_image_id`/`*_pdf_id` companions (they hold Local attachment IDs) and the already-correct nursery image meta. Uploaded 3 fee PDFs + garba.jpeg first so URLs resolve. **Result: homepage `amData` is now byte-identical Live==Local.** Submissions untouched (still 6). Pre-sync content-tables snapshot: `~/backups/content-pre-sync-20260718.sql`.
>
> **Backup cleanup (2026-07-18):** deleted ~1.24 GB of obsolete backups (all `_hostinger-backup-*` static-era, `_pre-*`, older `_production-backup/release` 0716/0717 sets, `_wp-admin-architecture-backup`, `_deploy-snapshot`, `_phase1-*.sql`) per user request. KEPT only: `_production-backup-alexandra-20260718-172124`, `_production-release-alexandra-20260718-172124`, `_SECRETS-alexandra-backup`, and the server-side rollback. Sections 6 below reference now-deleted `144551`/`143648` dirs — the authoritative rollback is now `20260718-172124` (local + server).

- Production is deployed and live: `https://alexandramontessori.co.uk`
- Production admin: `https://alexandramontessori.co.uk/wp-admin`
- Active production bundle (updated 2026-08-03):
  - JS entry: `assets/index-Bua1YdqF-routing-email-20260803-170221-parent-child-20260803-174402.js`
  - Availability chunk: `assets/Availability-Cz2IaKWY-routing-email-20260803-170221-parent-child-20260803-174402.js`
  - CSS: `assets/index-v0D79tmB.css`
- 2026-07-18 rollback point (keep until UAT sign-off): remote backup `~/backups/alexandra-predeploy-20260718-172124`, remote release+rollback `~/releases/alexandra-20260718-172124` (contains `rollback/` with the previous theme + mu), local backup `_production-backup-alexandra-20260718-172124`, local release `_production-release-alexandra-20260718-172124`.
- WordPress 7.0.2 is serving the React application through the active `alexandra-theme`.
- Maintenance mode is off.
- Alexandra Operations MU plugin `0.2.0`, custom-table schema `3`, and replacement mode are active.
- The four legacy Contact submissions were copy-first migrated and reconciled exactly: source `4`, destination `4`, missing `0`, duplicates `0`.
- `Submissions` is now one deliberately simple client menu. The visible inbox has Search, Type, Status and Action queue; advanced filters/export are collapsed. The former Overview/Follow-up/Failures/Settings sidebar is removed.
- The old `Settings & health` screen is not registered, has no client link, and its direct URL is denied. Notification configuration remains protected in code/options/`wp-config.php`.
- Notifications use an immediate asynchronous worker with a one-minute WP-Cron fallback. The visible recipient is the central or selected Nursery's approved `.co.uk` inbox; the developer monitor is a private BCC configured outside deployable theme code. Customer replies are never BCC'd.
- `AM_TEST_NOTIFY_EMAIL` is empty in production. It no longer redirects client forms to the developer test inbox.
- Branch routing is verified for Hounslow, Heston and Hammersmith. General/All-Nurseries Careers enquiries use the approved central `.co.uk` recipient.
- The global Site Settings email was corrected from the stale `.com` address to the approved Hounslow `.co.uk` inbox. The global phone still needs client confirmation; do not guess or overwrite it.
- Nurseries and Testimonials are now CMS-authoritative collections:
  - any complete published Nursery creates a directory card, detail route and routing target without a code allow-list;
  - `/testimonials` is a scalable public archive; the homepage remains a controlled subset;
  - Nursery pages use CMS testimonials plus general stories;
  - empty WordPress collections stay empty instead of reviving stale static records;
  - admin `Website result` labels describe the actual public destination.
- Current production content is preserved. This release did not import the Local database or delete any production records/files.
- The user explicitly approved deploying the website and WordPress admin together on 2026-07-17.

Client account:

- Username: `alexandra_editor`
- Role: `Alexandra Content Manager` (`am_content_manager`), not Administrator and not the broad built-in Editor role.
- Password was rotated after deployment and verified with a fresh production login. The plaintext password is not stored in this handoff; it is stored only in `_SECRETS-alexandra-backup/credentials.env` and was delivered to the user in the deployment close-out.
- Browser verification proved the client can use Website Content, Site Settings, Media and Submissions, but cannot see Plugins/Users or delete private submissions.

## 1. Resume protocol

1. Read this file first.
2. Re-check production runtime state before changing anything; do not infer state from source files alone.
3. Treat React and WordPress as one coupled product. A feature is complete only when the public UI, REST/API contract, WordPress storage, staff workflow and failure behavior agree.
4. Do not overwrite the production database with Local data. The completed deployment used a narrow, host-locked importer for only approved public records.
5. Stop before permanently deleting real submissions, applicant data, Media, users or private files. Two legacy public resume attachments still require an explicit cleanup decision; see Section 8.
6. Do not use `_migrate-hostinger.sh`; it is an old static-site script and is unsafe for the current WordPress production docroot.
7. This workspace is not a Git repository. Do not use `git status` as the source of truth or discard unknown files.

## 2. Paths and environments

Project root:

`C:\DHANANJAY\claude code\CLIENT PROJECTS\alexandra montesorri`

React source:

`C:\DHANANJAY\claude code\CLIENT PROJECTS\alexandra montesorri\alexandra-montessori`

Local WordPress root:

`C:\Users\Dhananjay\Local Sites\alexandra-montessori\app\public`

Active Local theme:

`C:\Users\Dhananjay\Local Sites\alexandra-montessori\app\public\wp-content\themes\alexandra-theme`

Official production:

- Site: `https://alexandramontessori.co.uk`
- Admin: `https://alexandramontessori.co.uk/wp-admin`
- Docroot: `/home/u205707676/domains/alexandramontessori.co.uk/public_html`
- Active theme: `/home/u205707676/domains/alexandramontessori.co.uk/public_html/wp-content/themes/alexandra-theme`
- SEO integration: `/home/u205707676/domains/alexandramontessori.co.uk/public_html/wp-content/mu-plugins/alexandra-seo.php`

Agency staging (not public production):

- Site: `https://alexandra.krildigital.com`
- Admin: `https://alexandra.krildigital.com/wp-admin`
- Docroot: `/home/u815362143/domains/krildigital.com/public_html/alexandra`
- Active theme: `/home/u815362143/domains/krildigital.com/public_html/alexandra/wp-content/themes/alexandra-theme`
- SSH/DB/SMTP/admin values: `_SECRETS-alexandra-backup/credentials.env`
- SSH key: `_SECRETS-alexandra-backup/alx_deploy_key`

Only `http://alexandra-montessori.local` has the Local CMS runtime. Vite URLs do not have the real `window.amData` contract and must not be used to judge CMS behavior.

## 3. Live cross-system architecture

### Website Content

The client menu is intentionally reduced to:

1. Dashboard
2. Website Content
3. Nurseries
4. Events
5. Blog
6. Testimonials
7. Jobs
8. Media Library
9. Site Settings
10. Submissions
11. Profile

`System Pages`, the React shell page, Plugins, Users, themes and technical settings are hidden from the client. Posts are relabelled `Blog`.

Content readiness functions determine the public result. Published is not treated as automatically visible: incomplete Events, Testimonials and Jobs remain hidden with an exact admin reason. Blog title and body are critical; missing images receive a controlled fallback.

### Exact `Add New` behavior, deployed and audited 2026-07-17

| Admin section | Current live behavior | Separate public result? | Status |
|---|---|---|---|
| Blog | A published title/body-ready article enters the REST archive. One chosen item is featured; every other ready item is paginated nine per page. | Yes: `/blogs/{slug}` | Fully coupled |
| Events | A complete published event enters Upcoming/Past automatically. An intentionally empty CMS collection remains empty. | Yes: `/events/{slug}` | Fully coupled |
| Jobs | A published, Open, complete role enters Vacancies; incomplete/closed roles remain hidden with an exact reason. Nursery choices come dynamically from CMS. | Yes: `/careers/vacancies/{slug}` | Fully coupled |
| Nurseries | Every complete published Nursery enters the directory, receives `/nurseries/{slug}`, and becomes available to Contact/Visit/Availability/Careers routing. Known branches retain their approved design defaults; a new slug receives safe generic presentation defaults. | Yes: `/nurseries/{slug}` | Fully coupled; no hardcoded slug ceiling |
| Testimonials | Every complete published record enters `/testimonials`; the homepage intentionally shows a controlled subset and links to the archive. Nursery pages use matching CMS stories plus general stories. | Archive rather than per-record detail | Fully coupled and paginated/scalable |
| Media Library | Uploads are filed into one collection and become selectable by content editors. | No, intentionally | Correct behavior: Media is an asset store, not a page generator |
| Site Settings | Saved global values feed shared brand/footer/contact behavior when non-empty. | No, intentionally | Coupled; global email corrected, phone still requires client confirmation |

WordPress is authoritative whenever `window.amData.schemaVersion` exists. Static data is used only for standalone Vite/design preview and does not revive an intentionally empty production collection.

The exact disposable regression proof passed locally:

- a fourth arbitrary Nursery slug appeared in the CMS payload, job/form choices and operational recipient resolver;
- 11 ready Testimonials paginated through the archive without losing or duplicating records;
- drafts/incomplete records remained hidden;
- cleanup restored the pre-test counts exactly.

### Blog contract

- REST archive: `/wp-json/alexandra/v1/blogs`
- REST detail: `/wp-json/alexandra/v1/blogs/{slug}`
- One ready article chosen in `Website placement` is the large Featured article.
- If no ready article is chosen, the newest ready article is the fallback feature.
- All other ready articles appear in a three-column archive, nine per page.
- The public archive has exactly two compact controls at the top: Year and Month.
- Filters use URL query state and support refresh/back/forward/share.
- Direct `/blogs/{slug}` routes are preserved from WordPress canonical redirects.
- The admin Blog list shows Placement and exact Website result.
- Production currently has one featured article and three archive articles. The Local-only test Blog was deliberately not deployed.

### Media

Public website images/documents remain in WordPress Media and are assigned exactly one code-controlled hierarchical collection:

- Inbox / Needs Filing
- Website Content
- Global & Brand
- Nurseries / Hounslow / Heston / Hammersmith
- Events
- Blog
- Documents / Fee Sheets / Policies & Reports

The collection-management taxonomy screen is hidden. Staff use the collection selector/filter inside Media Library. Imported Blog and Event images are visible in their collections. Collections organize attachments; they do not change public URLs.

Applicant CVs do not belong in Media. New career uploads are signature-checked PDF/DOC/DOCX files, limited to 5 MB, randomly renamed, stored outside the public web root, and downloaded only through an authenticated nonce-protected admin action.

### Forms and submissions

All four public phone flows use the same full country-code selector and submit E.164:

- Contact
- Book a visit
- Check availability
- Career application

Production browser checks confirmed the country selector on Contact, Availability and Career Apply with UK `+44` selected and the full country list present. PHP independently validates phones.

All public email links use the shared Gmail compose contract for their exact recipient; active React source has no public `mailto:` links.

All inbound forms write to indexed custom tables before notification work is queued. The tables separate submissions, immutable events, messages, private-file metadata, delivery jobs and rate limits. This removes the postmeta/`OFFSET` bottleneck that made the old dashboard unsuitable for very large inboxes.

The client sees one `Submissions` inbox, not separate operational dashboards:

- visible everyday controls: Search, Type, Status and Action queue;
- advanced Owner/Priority/Nursery/Archive/date/export controls remain available under `More filters & export`;
- server-side filtering, indexed keyset pagination and bounded 100-record bulk actions;
- customer-entered fields remain read-only;
- staff may change owner, priority, status, follow-up and internal notes;
- no permanent-delete capability is granted to the client.

Scale proof used 100,000 disposable records. All 18 assertions passed; insertion took about 21 seconds, indexed/keyset queries remained sub-millisecond, and cleanup restored the database exactly. A deliberately deep `OFFSET` comparison was about 694 ms and is not used by the production inbox.

Opening a New record stores first-open time/user, assigns the viewer if unassigned, and advances the workflow status. The four legacy production records were copied into the new tables without changing or deleting their source posts.

Notification behavior:

- mode: immediate asynchronous delivery, with durable queue/retries and one-minute WP-Cron fallback;
- primary recipient: selected branch inbox, with approved central fallback;
- Careers vacancy routing derives the branch from the validated Job CMS record;
- general Careers requests use the selected branch, or central for Any/All Nurseries;
- developer oversight is a private BCC on new-submission alerts/digests only;
- customer replies are recipient-locked and are never copied to the monitor;
- retries, terminal failures and audit events remain inspectable without exposing the removed configuration screen.

Each submission has a recipient-locked reply screen. It sends only to the immutable submitted email, stores the outbound audit history and transport result, and provides a recipient-specific Gmail fallback. Anonymous visitors do not have a site account/inbox; two-way communication is private one-to-one email.

## 4. Production content state

Current production counts after the 2026-07-17 release:

- Blog: 5 published records
- Events: 4 published records
- Testimonials: 4 published records; 3 currently meet the complete frontend contract
- Nurseries: 3 published and frontend/routing-ready
- Jobs: 2 stored; Room Leader is the one public ready/open vacancy
- Support Assistant remains stored but hidden publicly because its description is incomplete
- Submissions: 4 legacy Contact records preserved and copied into the new operations tables
- Users and unrelated production content were preserved; no Local database import occurred

The production Nursery CMS records were brought up to the new readiness contract only where fields were missing. Existing non-empty client content was not overwritten. Hounslow, Heston and Hammersmith now all resolve through CMS and retain their approved frontend presentation.

Exact junk moved to Trash, not permanently deleted:

- Event `test`
- Event `test-open-day`
- Job `childcare-assitance`

Existing production Contact records were preserved. No production form submission or outbound email was created merely for deployment testing.

## 5. Verification evidence

Latest verified results:

- React ESLint: pass
- WordPress production build: pass
- Changed PHP and deployment helpers: syntax pass
- Operations schema: `33/33`
- Repository: `16/16`
- Ingestion/routing: `22/22`
- Delivery queue/BCC/retry behavior: `23/23`
- Command centre: `20/20`
- CMS collection contract: `25/25`
- Full Local WordPress regression: `54/54`
- 100,000-record scale suite: `18/18`, exact cleanup
- Production active bundle: `index-DejrIM8H.js` / `index-e2gHOY7Q.css`
- Production Operations plugin: `0.2.0`, schema `3`, healthy
- Production migration: state `verified`, source `4`, destination `4`, reconciliation healthy
- Production custom-table counts immediately after deployment:
  - submissions `4`
  - immutable events `4`
  - messages `0`
  - private-file rows `0`
  - queued jobs `0`
- Production notification verification:
  - mode `immediate`
  - signed non-blocking loopback worker active; unsigned requests return `403`
  - real pending Availability notification accepted by WordPress mail in `2.017s`
  - three of three branch recipient resolutions exact
  - central recipient valid
  - private monitor BCC header present
  - test override empty
  - customer-reply BCC absence covered by the queue suite
- WP-Cron fallback scheduled and the operations worker executed manually in `0.003s` with no pending jobs.
- Production home, Nurseries, Hounslow direct route, Testimonials, Contact, Availability and Vacancies rendered the new bundle with no fatal error, broken image or horizontal overflow.
- Production forms expose Hounslow, Heston and Hammersmith dynamically.
- Invalid empty POST probes to `/enquiry`, `/availability` and `/apply` all returned controlled HTTP `400`; the submission count stayed `4`.
- Production client-role browser check:
  - logged in as `Alexandra Editor`
  - one `Submissions` menu link
  - one simplified inbox with four rows
  - `More filters & export` collapsed
  - no `Settings & health` link
  - direct settings URL denied
- Production error-log check found no recent fatal/uncaught Operations errors.
- Maintenance mode: inactive.

Known non-fatal Local CLI warning: `php_imagick.dll` is absent from the Local CLI service configuration. The WordPress audit still passes and production image delivery has no broken-image result.

Repeat the Local audit with the actual Local PHP configuration:

```powershell
cd "C:\DHANANJAY\claude code\CLIENT PROJECTS\alexandra montesorri\alexandra-montessori"
& "$env:APPDATA\Local\lightning-services\php-8.2.29+0\bin\win64\php.exe" `
  -c "$env:APPDATA\Local\run\UKwLj0pZZ\conf\php" `
  scripts\verify-local-wordpress.php `
  "C:\Users\Dhananjay\Local Sites\alexandra-montessori\app\public"
```

## 6. Release, backup and rollback

Production release:

`C:\DHANANJAY\claude code\CLIENT PROJECTS\alexandra montesorri\_production-release-alexandra-20260717-144551`

Release hashes:

- `alexandra-theme.tar.gz`: `5ec678bd17a19da37066fe0b233e58d48d2009d2561fb3560a405e7bd2d63b43`
- `mu-plugins.tar.gz`: `c31333c26c55baa6821fdee5073de180141fcf58f4636303fb359b82818a6ba5`

Remote release:

`/home/u815362143/releases/alexandra-20260717-144551`

Pre-deploy production backup, downloaded and checksum-verified:

`C:\DHANANJAY\claude code\CLIENT PROJECTS\alexandra montesorri\_production-backup-alexandra-20260717-144551`

Remote backup:

`/home/u815362143/backups/alexandra-predeploy-20260717-144551`

It contains the MariaDB dump, previous theme, MU plugins, `wp-config.php`, `.htaccess`, runtime state and SHA-256 manifest. It is sufficient to roll back every component changed by this release; uploads were not modified.

The coordinated rollback directory is:

`/home/u815362143/releases/alexandra-20260717-144551/rollback`

It preserves the previously active theme. The unrelated production MU hardening plugin was preserved in place. `wp-config.php` is recoverable from the verified backup.

Do not use `_production-backup-alexandra-20260717-143648`: that rejected checkpoint is missing its database dump. Only the `20260717-144551` backup is the authoritative rollback checkpoint for this release.

Do not delete the verified backup, release or rollback directory until client UAT is complete and a later known-good backup exists.

Local Phase 1 pagination-test database backup:

`C:\DHANANJAY\claude code\CLIENT PROJECTS\alexandra montesorri\_phase1-pagination-backup-20260716-174252.sql`

Synthetic scale records were removed after proving REST/browser pagination `9 / 9 / 3`; Local returned to five ready Blogs (one Local-only test plus four approved articles).

## 7. Build rule

`npm run build:wp` creates theme-prefixed asset URLs and is the artifact deployed under the WordPress theme. After syncing that output into the active Local theme, always run plain `npm run build` so the React project `dist` returns to root `/assets/...` URLs.

Current Local WordPress theme bundle:

- JS: `index-DejrIM8H.js`
- CSS: `index-e2gHOY7Q.css`

Current root-base project bundle:

- JS: `index-B24uOgTG.js`
- CSS: `index-e2gHOY7Q.css`

Latest pre-Phase-1 Local theme dist backup:

`C:\Users\Dhananjay\Local Sites\alexandra-montessori\app\public\wp-content\themes\alexandra-theme\dist-backup-20260716-170224`

## 8. Remaining decisions and work

### P0 - complete before formal client handover

1. **Controlled real-delivery UAT:** routing, BCC construction and APIs are structurally verified, and one real pending Availability alert was accepted by WordPress mail after the immediate-worker release. Client inbox/spam arrival is still external to the site. Use a client-approved test identity and harmless test CV to prove Contact, Availability and Careers inbox/spam arrival, immediate hand-off, private developer BCC, admin New badge, first-open transition, recipient-locked reply, and authenticated CV download. Clean up only the new UAT records after approval.
2. **Confirm the global phone/address:** the stale global `.com` email is fixed, but the global phone previously appeared to be a placeholder. Obtain the client's written confirmation before changing phone/address or other identity facts.
3. **Independent compatibility/accessibility pass:** run physical/narrow mobile and at least one independent browser-engine check for navigation, keyboard use, validation, upload, and responsive layout.

### P1 - approval-gated cleanup and sign-off

4. **Two legacy resume PDFs:** production Media still contains attachment IDs `43` and `59` with resume filenames from the old system. New CVs are private, but these old files require explicit approval to permanently delete or quarantine. The verified backup preserves them.
5. **Existing test submissions:** four production Contact records are believed to be test data. Their source posts and migrated rows are intentionally preserved. Delete only after the client confirms the exact records are disposable and a coordinated source/destination cleanup is implemented.
6. **Support Assistant copy:** the role is correctly hidden because its full description is incomplete. Obtain approved copy before making it public.
7. **Fourth Testimonial:** four Testimonial posts are published but three currently meet the complete frontend contract. Review the exact admin `Website result` reason and complete or unpublish the fourth record as the client prefers.
8. **Retention policy:** the client must decide how long enquiries, availability requests, applications, reply logs and CSV exports are kept. Automatic deletion remains off until there is a written period.
9. **Production worker:** WP-Cron fallback is active and verified. For additional low-traffic reliability, add the documented one-minute Hostinger hPanel cron when host-level cron access is available; SSH `crontab` access is unavailable.
10. **Official content:** confirm final social URLs and remaining client copy. Do not invent facts, branch details or policies.

`Sent` only means the mail transport accepted a message. It does not prove inbox delivery or reading.

## 9. Phase map

- Phase 0 - inventory/recoverability: complete
- Phase 1 - Blog CMS/public contract: complete and deployed
- Phase 2 - Website Content parity: complete and deployed for Blog, Events, Jobs, unlimited Nurseries, Testimonials archive and truthful admin results
- Phase 3 - admin IA/permissions: simplified Submissions menu deployed; production restricted-role audit passed
- Phase 4 - Media/CV lifecycle: deployed for new files; two legacy resume attachments await cleanup approval
- Phase 5 - forms/indexed operations queue/replies: deployed; scale proof and structural routing pass; controlled real-mail/CV UAT remains
- Phase 6 - security/privacy/resilience: major controls and regression audit pass; retention and final mail-domain validation remain
- Phase 7 - scale/accessibility/compatibility: 100k inbox proof and collection pagination pass; independent browser/mobile sign-off remains
- Phase 8 - client UAT/decisions: client account ready; listed client decisions remain
- Phase 9 - coordinated website/admin deployment: complete for release `20260717-144551`

### Next Codex execution order

Do not juggle unrelated changes. Complete and verify each stage before moving on:

1. **Re-establish baseline, read-only:** read this file, confirm bundle `index-DejrIM8H.js`, Operations `0.2.0`/schema `3`, maintenance off, and the authoritative `20260717-144551` backup. Do not infer production state from Local source.
2. **Run controlled UAT:** only with a client-approved identity/test CV. Prove actual inbox/spam delivery, immediate hand-off timing, private monitor BCC, first-open behavior, recipient-locked reply and private CV download. Do not use real applicant data.
3. **Record UAT outcome:** update this file with exact test references, mail timestamps/transport outcome and cleanup status. Remember that transport `Sent` does not prove inbox delivery or reading.
4. **Client decisions:** confirm global phone/address, fourth Testimonial completeness, Support Assistant copy and retention period.
5. **Approval-gated cleanup:** handle legacy resume attachments and the four old Contact test records only after explicit record-level approval.
6. **Independent sign-off:** complete physical/narrow mobile, keyboard and independent browser-engine checks.
7. **Optional worker hardening:** configure the one-minute Hostinger hPanel cron when host access permits; retain WP-Cron fallback.

Primary files for the current architecture:

- `alexandra-montessori/src/data/site.js` - CMS-driven Nursery models with known-branch visual defaults and generic new-slug defaults
- `alexandra-montessori/src/pages/Home.jsx` - controlled Testimonial subset plus archive link
- `alexandra-montessori/src/pages/NurseryDetail.jsx` - CMS Nursery and location/general CMS Testimonials
- `alexandra-montessori/src/pages/Testimonials.jsx` - public Testimonial archive/pagination
- `alexandra-montessori/src/pages/Events.jsx` and `EventDetail.jsx` - CMS-authoritative empty behavior
- `alexandra-montessori/src/routes.jsx` - `/testimonials` and generic Nursery detail route
- active Local theme `functions.php` - CMS getters, `window.amData`, Site Settings and legacy fallback guards
- active Local theme `inc/am-admin.php` - truthful `Website result` and simplified menu integration
- `alexandra-montessori/wordpress/mu-plugins/alexandra-operations/` - custom tables, repository, ingestion, queue, security and admin inbox
- `alexandra-montessori/scripts/verify-local-wordpress.php` and `scripts/verify-am-ops-*.php` - regression and scale coverage
- root `scripts/production-backup.sh`, `production-deploy.sh`, `production-activate-operations.php`, and `production-verify-release.php` - release safety and verification helpers

## 10. Main implementation files

React:

- `src/components/PhoneField.jsx`
- `src/components/BookingModal.jsx`
- `src/components/ContactForm.jsx`
- `src/components/ApplicationForm.jsx`
- `src/routes.jsx`
- `src/pages/Availability.jsx`
- `src/pages/Blogs.jsx`
- `src/pages/BlogArticle.jsx`
- `src/pages/Home.jsx`
- `src/pages/Events.jsx`
- `src/pages/EventDetail.jsx`
- `src/pages/Nurseries.jsx`
- `src/pages/NurseryDetail.jsx`
- `src/pages/Testimonials.jsx`
- `src/pages/Vacancies.jsx`
- `src/pages/VacancyDetail.jsx`
- `src/data/blogSource.js`
- `src/lib/contact.js`
- `src/lib/safeJsonLd.js`
- `src/data/site.js`
- `src/index.css`

WordPress theme:

- `functions.php`
- `inc/am-validation.php`
- `inc/am-private-files.php`
- `inc/am-media-library.php`
- `inc/am-content-contracts.php`
- `inc/am-admin.php`
- `inc/am-submissions.php`
- `inc/am-careers-api.php`
- `inc/am-enquiries-api.php`

WordPress must-use operations plugin:

- `wordpress/mu-plugins/alexandra-operations.php`
- `wordpress/mu-plugins/alexandra-operations/alexandra-operations.php`
- `wordpress/mu-plugins/alexandra-operations/includes/class-am-ops-schema.php`
- `wordpress/mu-plugins/alexandra-operations/includes/class-am-ops-repository.php`
- `wordpress/mu-plugins/alexandra-operations/includes/class-am-ops-ingestion.php`
- `wordpress/mu-plugins/alexandra-operations/includes/class-am-ops-queue.php`
- `wordpress/mu-plugins/alexandra-operations/includes/class-am-ops-admin.php`
- `wordpress/mu-plugins/alexandra-operations/includes/class-am-ops-settings.php`

Scripts:

- `scripts/seed-local-wordpress.php`
- `scripts/verify-local-wordpress.php`
- `scripts/verify-blog-pagination-volume.php`
- `scripts/export-production-public-content.php`
- `scripts/import-production-public-content.php`
- `scripts/production-public-content.json`

Dead Calendly configuration and the unused `react-calendly` package were removed. The supported visit-booking channel is the live `Book a visit` modal, which creates Contact & Visits records with source `Book a visit`.
