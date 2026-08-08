# Alexandra Montessori — Client Changes Round 2 (Plan & Tracker)

_Started 2026-07-16. Source brief: `changes by them` (17 items). Honor scope discipline:
make only the change asked, ask before restructuring, **no production deploy without user
review**._

## Decisions locked with the user (2026-07-16)
1. **Form verification (items 6 & 7):** Free **client-side validation only** — format + trim +
   country-code selector + length + clear error messages on all forms. **No paid OTP / SMS.**
2. **Vacancy fields (item 11):** Add a short **Job summary** field + a **Benefits** field; keep
   the existing rich **Full description** editor for Responsibilities/Skills/etc. as headed
   sections. Build the detail page to render them.
3. **Menu download (item 5):** **Skipped this round** (file not ready).
4. **Availability (items 8 & 16):** Enforce no-past / **T+2** / end-date ≥ start + structured
   dynamic session fields. **No live-availability backend.** Team confirms by reply.

## Architecture reminders
- React SPA reads `window.amData.*` (WP) with `src/data/site.js` fallback.
- Production is **WP-hosted on Hostinger** (`alexandra.krildigital.com`) since 15 Jul → CMS edits
  reach live.
- Deploy: React → `build:wp` → copy theme `dist/` → `build` (Hostinger base) → deploy. PHP/theme
  changes deploy directly. Theme at
  `C:\Users\Dhananjay\Local Sites\alexandra-montessori\app\public\wp-content\themes\alexandra-theme`.

## Phase tracker

### Phase 0 — Baseline
- [ ] Backups / snapshot, verify live vs code for items 2/3/4, before-shots.

### Phase 1 — Quick frontend wins (no CMS) ✅ DONE 2026-07-16
- [x] #1 Removed Prospectus button (`NurseryDetail.jsx`) + removed unused `Download` import
- [x] #5(partial) Removed the dead "Download the menu" button too (user asked to remove both)
- [x] #2 Footer email mailto — already `mailtoHref`, confirmed correct (verify on live)
- [x] #4 `tel:` on every phone display sitewide — audited; only non-link was
      `Availability.jsx` "You can also call …" → now a `tel:` link. All others already linked.
- [x] #9 Social links — all render sites (`SocialSidebar`, `Contact`) already use
      `target=_blank rel=noopener`. FB URL is a proper public page. Login wall is FB's own
      behaviour (brief acknowledges). No code change. → confirm exact URLs with client.
- [x] #17 Blog thumbnails — `Blogs.jsx` cards now `fit="contain"` + `bg-sage-50` letterbox so
      text-bearing marketing graphics are never cropped (consistent 16/10 box kept).
- [x] #3 Cookie consent — already fully functional (`CookieBar.jsx`: persists, categories,
      gtag consent gating). No tracking scripts load pre-consent (none load at all). Verified in
      code; verify on live (client likely saw a stale deploy).

### Phase 2 — Shared form validation (items 6 & 7) ✅ DONE 2026-07-16
- [x] `src/lib/validation.js` — `isValidEmail`/`emailError`/`normalizeEmail` (regex + trim + msg)
- [x] `src/components/PhoneField.jsx` — country selector (UK-first curated list), digits-only,
      per-country max-length cap + validity via `libphonenumber-js` (added dep), as-you-type
      format, imperative `validate()` for the parent, submits E.164 via hidden input.
- [x] Wired into ContactForm, Availability, BookingModal, ApplicationForm (email trimmed +
      validated, phone validated on submit; inline error messages).
- Note: `libphonenumber-js` bundles as a lazy 37 KB-gzip chunk (form pages only). Build green.

### Phase 3 — Date/booking logic
- [x] #8 BookingModal `preferredDate` min = T+2 (`src/lib/dates.js` `addDaysISO`) + helper note.
- [x] #16 Availability rebuilt: `startDate` min = today, dynamic fixed end-date (min = start),
      day-of-week chips, session select (Morning/Afternoon/Full Day/Other + describe), dynamic
      start/end times. Composed into `sessions` summary + discrete keys (backend-safe). ✅

### Phase 4 — Careers (React) ✅ DONE 2026-07-16 (CMS fields → Phase 7)
- [x] #10 Vacancy card `line-clamp-3` + CTA changed to "View role" → detail page.
- [x] #11 New `pages/VacancyDetail.jsx` + route `/careers/vacancies/:slug`: meta chips, summary,
      full description (`blog-richtext`), benefits list, inline `ApplicationForm` (title auto-
      attaches) / external applyUrl button. Not-found fallback included.
- [x] #12 Qualification "Other" → required "Please specify your qualification"; mapped into
      submitted value.
- [x] #13 Position "Other" → required "What position…"; mapped into value. General apps already
      stored + emailed by backend (`$pos_store` fallback). Email also validated server-side.

### Phase 5 — Events (React) ✅ DONE 2026-07-16 (CMS fields → Phase 7)
- [x] #14 `Events.jsx` rebuilt: auto past/upcoming split (`todayISO`), Upcoming + Past sections,
      6 cards then "View more"/"View less", responsive 3×2 grid. Empty state kept.
- [x] #15 Event cards redesigned (thumbnail/title/date/time/location/clamped excerpt/"View
      details") + new `pages/EventDetail.jsx` + route `/events/:slug`: full image, date, start/end
      time, location, description, booking info, "Book a place" → `/contact?event=<title>` which
      prefills the enquiry message (title attached). `lib/slug.js` gives stable ids.

### Phase 6 — Menu download
- [ ] #5 SKIPPED this round (awaiting file)

### Phase 7 — WordPress CMS fields (`functions.php`) ✅ DONE 2026-07-16 (local; deploy = Phase 8)
- [x] Event meta box: added "Booking information" textarea + save + helper text. Getter now
      returns `id` (slug), `description` (main editor body → sanitised HTML), `bookingInfo`;
      `posts_per_page` 20→100 so past events aren't truncated.
- [x] EventDetail renders `description` as HTML (dangerouslySetInnerHTML, matches blog/job).
- [x] Job fields: relabeled "Short description" → "Job summary" (same id, data preserved) +
      added "Benefits" (one per line). Getter returns `summary` + `benefits`.
- Note: availability discrete fields (endDate/days/etc.) reach the team via the composed
  `sessions` summary; discrete CPT storage deferred (optional, not needed for delivery).
- [ ] Redeploy theme → Phase 8.

### Email anti-fake (added on user request 2026-07-16) ✅
Decision: free extras, keep 1-step (no confirmation code). In `lib/validation.js`:
- [x] Disposable/throwaway-domain blocklist (mailinator, tempmail, …) → rejected.
- [x] Typo detection with **OSA distance** (transpositions = 1 edit) + `.com` TLD-typo map →
      "did you mean …?" suggestion; blocks submit. Verified live: `test@gmail` rejected,
      `parent@gmial.com` → "did you mean parent@gmail.com?".
- Note: valid-format-but-nonexistent inboxes can't be caught client-side (would need the
  confirmation-code step the user declined). Documented for the client.

### Round-2b follow-ups (user feedback 2026-07-16, tested on 127.0.0.1:4173 by mistake)
- [x] **Email → Gmail compose** (not Outlook): `lib/contact.js` `gmailHref()` (mail.google.com
      compose, new tab) replaces `mailtoHref` on Footer, Contact, ContactLocation, NurseryDetail,
      Privacy, ApplicationForm.
- [x] **Blogs relational (wp-admin)**: `am_get_blogs()` now returns `slug` + full `content`;
      new `data/blogSource.js` prefers `window.amData.blogs` over the generated file; Blogs.jsx +
      BlogArticle.jsx use it. Verified: WP Post "trial" flows into amData.blogs with content.
      NOTE: once ≥1 WP Post exists, the 4 generated fallback blogs stop showing (correct
      relational behaviour) — client should author real blogs in wp-admin → Posts (title,
      content, **featured image = thumbnail**, category). Offer to seed the existing 4 later.
- [x] **KEY CLARIFICATION**: user tested `127.0.0.1:4173` = static Vite preview, `amData`
      occurrences = 0 → no jobs, placeholder events. Relational data ONLY on the WP site
      `alexandra-montessori.local`. Verified amData.jobs has both jobs there, amData.events has
      the CMS events. Vacancies/events were already relational — just wrong test URL.
- Phone "direct call": `tel:` dials directly on MOBILE (what the brief asked). Desktop shows an
  OS app-chooser because desktops have no dialer — not fixable in code, expected.
- Facebook login wall: link is the correct public page + new tab; the wall is Facebook's own
  policy for logged-out users (Instagram varies). Cannot bypass another site's login from a link.
- Directive: **do NOT push to production until fully tested on Local + user approval.**

### Phase 8 — Build, QA, deploy (LOCAL done; PRODUCTION pending review)
- [x] `build:wp` → theme dist → `build` (Hostinger base) — LOCAL theme synced with all new work.
- [x] Runtime QA on dev server + Local: events past/upcoming + View details + EventDetail;
      Book-a-place → prefilled contact; PhoneField (country selector, digits-only); email
      validation (invalid + typo + disposable); cookie Accept-all dismiss; tel:/mailto: links.
- [x] Relational verified: Local `curl` → HTTP 200, `amData` carries new event/job fields.
- [ ] 🔴 DEPLOY TO HOSTINGER PRODUCTION — needs explicit user go-ahead (theme dist + functions.php
      + cmb2). Production currently serves the OLD build. Rollback backup pattern per
      [[alexandra-production-deploy]]. Then clear test data.
- [ ] Confirm official social URLs with client (#9); menu PDF dropped (#5).

### Email routing to client branch inboxes (2026-07-17) ✅ LOCAL
Client wants form notifications off the developer inbox and split per branch.
Mapping: Hounslow→`info@`, Heston→`heston@`, Hammersmith→`hammersmith@` (all
`@alexandramontessori.co.uk`). Confirmed present in `amData` (relational).
- [x] `am_branch_label()` now matches slug OR display name + `am_branch_fallback_email()`
      safety map (wp-admin `_am_nursery_email` still wins → relational).
- [x] `am_notify_email()` default fallback changed `admin_email` → `info@` (never the dev).
- [x] Careers routing: new `am_application_route_email()` priority = per-job
      `_am_job_apply_email` → job `_am_job_location` branch → applicant's picked
      nursery (`_am_app_branch`) → `info@`. "Routed to (production)" + Nursery rows
      added to the application email; `_am_app_branch` CMB2 field added.
- [x] `ApplicationForm.jsx`: general (no `selectedJob`) form gained a "Which nursery
      are you applying to?" select (Any + 3 branches) with a routed-to hint.
- [x] **Developer monitoring BCC**: `AM_MONITOR_BCC` constant → every form email BCCs
      the dev (skipped when it would duplicate `$to`). Remove at full hand-off.
- [x] **SWITCH FLIPPED ON LOCAL (2026-07-17, user OK'd):** `AM_TEST_NOTIFY_EMAIL=''`
      → branch routing is now ACTIVE on Local. Recipient = branch inbox, BCC = dev.
      User accepted that Local test submissions now hit the client's REAL inboxes;
      keep any test content clearly labelled "pre-launch test — please disregard".
- [x] Verified all 4 forms via curl (each `success`+reference): Contact→Hounslow,
      Availability→Heston, Booking→Hammersmith, Careers(general,Heston pick)→Heston.
- ⚠️ **PRODUCTION:** same `AM_TEST_NOTIFY_EMAIL=''` change ships with the next deploy →
      client + dev both receive every live email. **Sender is still the dev Gmail**
      until the client provides per-branch app passwords (recipient first, sender
      later — user's chosen order). Test CPT posts + dummy CV left on Local (clear before prod).

## Open confirmations (non-blocking, gather from client)
- #9 exact official public URLs for Facebook / Instagram / any others.
- #5 final menu PDF + intended button placement.
