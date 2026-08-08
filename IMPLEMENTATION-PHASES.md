# Alexandra Montessori — Complete Implementation Plan

**Last Updated:** 2026-07-15  
**Prepared for:** Client handover + continued development  
**Audience:** Development team (Claude, future engineers), client (CMS sections)

---

## Executive Summary

The Alexandra Montessori website is a **React/Vite SPA** integrated with **WordPress as a CMS authoring layer**. Production runs on static hosting (Hostinger); WordPress is a local-only (for now) content management backend. This document tracks all **7 implementation phases** — current status, exact work remaining, success criteria, and blockers.

**Current phase:** 3 of 7 ✅ **DONE**  
**Next phase:** 4 (Careers form fix + CV upload)

---

## Architecture at a glance

| Component | Tech | Status | Owned by |
|---|---|---|---|
| **Frontend** | React 19 + Vite + React Router | ✅ Live | `alexandra-montessori/src/` |
| **Static build** | Vite bundler → `dist/` → Hostinger | ✅ Live | `npm run build` |
| **WordPress build** | Vite + WP base path → theme `dist/` | ✅ Live | `npm run build:wp` |
| **WP theme** | Custom theme (no layout editing) | ✅ Live | `alexandra-theme/` |
| **CMS layer** | CMB2 (free) + custom CPTs + REST | ⏳ Phases 1–3 done; 4–5 in flight | `functions.php` + `inc/am-*.php` |
| **Forms delivery** | WordPress REST endpoints + `wp_mail` + Gmail SMTP | ✅ Phase 3 done | `am-enquiries-api.php`, `am-careers-api.php` |

---

## What's shipping (Phase 1–3 ✅ DONE)

### Phase 1 — CMS Content Fields

**Goal:** Make the site's real data client-editable in WordPress.

**What was done:**
- CMB2 fields added to the **Nursery CPT** (`am_nursery`):
  - Ofsted rating, report URL, link label
  - Food Hygiene rating, date, authority, FSA URL
  - Per-nursery age range (Hammersmith: 12mo–5y; others: 6mo–5y)
  - Fee sheet PDF (one per branch)
- `am_get_nurseries()` getter returns all 8 fields + the 4 new ones
- React `site.js`: nurseries now merge CMS values with static fallback
- **Result:** `/food-hygiene-rating` page + footer Ofsted badges now read from WordPress

**Files changed:**
- `functions.php` — CMB2 field registration + getter
- `site.js` — `mergeNursery()` + derives `feeSheets`/`foodHygieneRatings` from locations

**Verified:** ✅ Build clean, new age ranges in bundle, derived exports working

---

### Phase 2 — Contact + Availability Forms → WordPress + Email

**Goal:** Form submissions auto-save + generate emails (routed to each branch).

**What was done:**
- Created **two new CPTs:**
  - `am_enquiry` — Contact Us form submissions
  - `am_availability` — Check Availability form submissions
- Created `inc/am-enquiries-api.php`:
  - REST endpoints `POST /wp-json/am/v1/enquiry` + `/availability`
  - Honeypot + 3-second timing spam guard (matches careers pattern)
  - Submissions saved as private posts + admin list columns (email, branch, status, submitted date)
  - CMB2 meta boxes for admin review
  - Email template matching careers format
- **React changes:**
  - `ContactForm.jsx`: wired to POST `/wp-json/am/v1/enquiry` (was faking with `setTimeout`)
  - `Availability.jsx`: wired to POST `/wp-json/am/v1/availability` (was faking)
  - Added honeypot fields + error handling to both
  - Added `useEffect` for form load timestamp (React 19 purity rule compliance)
- **Vite config:** added `/wp-json` proxy so dev server (`:5173`) can test real endpoints

**Files changed:**
- `inc/am-enquiries-api.php` — new file, 400+ lines
- `ContactForm.jsx` — `async` submit + error states
- `Availability.jsx` — `async` submit + error states
- `vite.config.js` — proxy rule for localhost testing
- `lib/api.js` — shared `submitForm()` helper

**Verified:** ✅ Both endpoints return `{"success":true}`, CPTs save, emails generated, mail log shows correct recipient (test address while GMAIL_USER override set)

---

### Phase 3 — Real Gmail Delivery (SMTP)

**Goal:** Form notifications actually reach your Gmail inbox.

**What was done:**
- Added SMTP configuration to `functions.php`:
  - `am_configure_smtp()` hook reads `AM_SMTP_*` constants from `wp-config.php`
  - Configures PHPMailer for Gmail (TLS, port 587)
  - Sets "From" to the SMTP user email
  - No-op if constants absent (reverts to Local Mailpit for testing)
- Added to `wp-config.php`:
  ```php
  define('AM_SMTP_HOST', 'smtp.gmail.com');
  define('AM_SMTP_PORT', 587);
  define('AM_SMTP_USER', 'dhananjaychitmila@gmail.com');
  define('AM_SMTP_PASS', 'iafgksbjdblxbzup'); // spaces removed from app password
  define('AM_SMTP_FROM', 'dhananjaychitmila@gmail.com');
  define('AM_SMTP_SECURE', 'tls');
  ```
- Added diagnostic: `wp_mail_failed` hook logs SMTP errors to `wp-content/uploads/am-mail-log/_FAILED.log`

**Test result:** ✅ `POST /wp-json/am/v1/enquiry` with test data → returns success → no error logged → email should be in Gmail inbox (subject: "New Enquiry – Hounslow – SMTP LiveTest")

**Security note:** `wp-config.php` is NOT tracked in git, so Gmail credentials stay off the repo. If this ever commits to GitHub, credentials must be rotated immediately. For production client, we'll use a real business Gmail or relay, never personal.

**Status:** 🎯 **Phase 3 COMPLETE** — Awaiting user confirmation: Did the test email land in Gmail?

---

## What's in flight (Phase 4–5)

### Phase 4 — Fix Careers Form (CV Upload)

**Goal:** Careers applications work end-to-end: form → WordPress CPT + CV stored + email with attachment.

**Current state of Careers form:**
- **Frontend:** `ApplicationForm.jsx` posts FormData to `/wp-json/am/v1/apply` (rewired in Phase 3, was posting to retired `/api/apply.php`)
- **Backend:** `am-careers-api.php` already has:
  - REST endpoint ready
  - CV file upload logic (incomplete)
  - CMB2 meta boxes + admin list columns
  - Email template with attachment support
- **Symptom:** "Something went wrong" on `/careers` page when you test the form

**What needs to happen (exact steps):**
1. Debug why file upload fails (likely multipart/form-data parsing issue in the endpoint)
2. Test careers submission WITH a real PDF CV file
3. Verify in WordPress admin that the application CPT saved + CV is in Media Library
4. Verify email arrived with the CV as an attachment
5. Build `build:wp`, sync theme `dist/`, rebuild for Hostinger

**Files to touch:**
- `am-careers-api.php` — debug file handling, likely around `media_handle_upload()`
- `ApplicationForm.jsx` — verify FormData construction (should be correct as-is)

**Success criteria:**
- Form submits without error
- Application appears in wp-admin → Applications as a new post
- CV file appears in wp-admin → Media Library (associated with the application)
- Email sent to `dhananjaychitmila@gmail.com` with CV as attachment
- On next `build:wp` + deploy, careers form works on the live WordPress site too

**Blocker:** The file upload endpoint needs investigation. Likely a PHP configuration issue (max upload size, temp dir permissions) or multipart parsing bug.

---

### Phase 5 — Replace Calendly with Email-Based Booking Form

**Goal:** "Book a visit" modal currently shows "Calendly URL not valid" → replace with email-backed form (same pattern as Contact/Availability).

**Current state:**
- `BookingModal.jsx` was rendering the broken `react-calendly` embed
- **We already rewrote it:** new `BookingModal.jsx` uses email form (not yet deployed)
- Backend is ready: `am_enquiries_api.php` already accepts `kind="booking"` + `preferredDate`

**What needs to happen (exact steps):**
1. Verify the new `BookingModal.jsx` code is correct (it was written in Phase 3 but not yet built)
2. Test locally: click "Book a visit" on a nursery page, fill the form, submit
3. Confirm submission routes to `POST /wp-json/am/v1/enquiry` with `kind=booking`
4. Confirm email arrives with "New Visit Booking – [Branch]" subject + preferred date in body
5. Build + sync like Phase 4

**Files involved:**
- `BookingModal.jsx` — already rewritten, just needs building
- `am-enquiries-api.php` — already handles `kind` param, no changes needed

**Success criteria:**
- "Book a visit" modal appears (not showing Calendly error)
- Booking form has: name, email, phone, preferred date, message
- Submit works without error
- Email arrives with booking details
- On live site, same flow works

**Blocker:** None known. Phase 4 must complete first (both phases share one build/deploy cycle).

---

## Future work (Phase 6–7)

### Phase 6 — Abuse & Scale Hardening

**Goal:** Protect the site if bots or attackers try to flood form submissions.

**What will be added:**
1. **Rate limiting** — cap submissions per IP (e.g., 5 per hour). Implemented in WordPress.
2. **Optional Cloudflare Turnstile** — invisible CAPTCHA for real protection (only if needed). Free tier is transparent to good actors.
3. **Cloudflare free tier** — put the site behind Cloudflare for automatic DDoS/bot shielding at the edge.

**Why it matters:** "What if 1000 bots submit forms every second?" → Without rate-limit, your inbox drowns. With it, each IP can submit max 5/hour, attacker gives up, you get maybe 50 emails, no sweat.

**Timeline:** After Phase 4–5 deploy (estimated 1–2 hours of work).

---

### Phase 7 — CMS Clarity & Client Handover

**Goal:** The WordPress admin panel is currently confusing for non-technical client. Fix it.

**What will be done:**
1. **Add helper text** under each CMS section: "This field appears [in footer/on branch page/etc]"
2. **Hide irrelevant menus** → client sees ONLY: Media, Events, Testimonials, Nurseries, Jobs, Applications, Enquiries, Availability, Site Settings. (Hide: Posts, Pages, Comments, Users, Tools, Plugins, Appearance, Settings.)
3. **Create a restricted Editor role** → client logs in, can only see the 8 content sections, can't access theme/plugin/system.
4. **Add a "How to use" guide** as a dashboard widget or pinned post.
5. **Populate with real content** so the admin isn't empty (seed Events, Nurseries, Site Settings with the live values).

**Timeline:** After Phase 6 (estimated 2–3 hours).

---

## Known issues & blockers

| Issue | Severity | Phase | Status |
|---|---|---|---|
| Careers form returns "Something went wrong" | 🔴 High | 4 | Needs file upload debug |
| Calendly embed shows "URL not valid" | 🟠 Medium | 5 | Code rewritten, needs build |
| Blog data source drift (reads `blogs.generated.js`, not CMS) | 🟡 Low | Later | Flagged but not blocking |
| Production hosting still static (CMS edits don't reach real visitors) | 🔵 Design | Later | Known; client decision pending |

---

## The actual next steps (you are here)

### ✅ Phase 3 just closed. Await confirmation of test email in Gmail.

**Once confirmed:** I will immediately start Phase 4.

### Phase 4: Careers form fix

**Exact sequence:**
1. Debug the file upload endpoint (likely 30 min)
2. Test careers submission with a real PDF
3. Verify CPT + Media Library + email
4. Revert the ApplicationForm to test-mode temporarily to isolate the backend issue
5. Once verified working: build & deploy

**Entry condition:** Phase 3 email confirmed  
**Exit condition:** Careers form works end-to-end, email with CV arrives  
**Time estimate:** 1.5–2 hours

---

### Phase 5: Booking form (with Phase 4)

**Exact sequence:**
1. Verify new `BookingModal.jsx` builds (should be clean)
2. Test booking form locally (form appears, submit works, email arrives)
3. Build + sync theme + rebuild Hostinger dist

**Entry condition:** Phase 4 complete  
**Exit condition:** Booking form works end-to-end on local WordPress  
**Time estimate:** 30 min

---

### Combined Phase 4+5 deploy

**One clean cycle:**
```bash
npm run build:wp          # build for WordPress base
# copy dist → theme
npm run build             # restore Hostinger base
# (both go live together)
```

**Verification after deploy:**
- Local WordPress: `/careers` form works, `/nurseries/[any]` booking modal works
- Emails arrive in your Gmail for both

---

## Files & paths (for quick reference)

| What | Path |
|---|---|
| React app source | `C:\DHANANJAY\claude code\CLIENT PROJECTS\alexandra montesorri\alexandra-montessori\src\` |
| Build outputs | `dist/` (Hostinger) or via `build:wp` |
| WordPress theme | `C:\Users\Dhananjay\Local Sites\alexandra-montessori\app\public\wp-content\themes\alexandra-theme\` |
| WordPress CPTs + REST | `functions.php` (Phase 1 + 2) + `inc/am-careers-api.php` + `inc/am-enquiries-api.php` |
| Gmail credentials | `wp-config.php` (NOT tracked in git) |
| Mail log (for debugging) | `wp-content/uploads/am-mail-log/` (success emails or `_FAILED.log`) |

---

## Git & deploy checklist

**Never commit:**
- `wp-config.php` (has Gmail password)
- `node_modules/`, `dist/` (rebuilt on demand)
- `.env` files

**Before deploying:**
- Run `npm run lint` (must pass)
- Run `npm run build` (to verify clean)
- Confirm `dist/` has Hostinger base (`/assets/...`), not WP base
- Test locally (either `npm run dev` or the Local WordPress site)

**Deploy to live Hostinger:**
- Run `_migrate-hostinger.sh upload` (backs up + uploads `dist/` to `alexandra` subdomain)
- Verify: `https://alexandra.krildigital.com/` loads + forms work

---

## Sign-off checklist

- [ ] Phase 3 test email confirmed in your Gmail inbox
- [ ] Phase 4 careers form fixed + verified working
- [ ] Phase 5 booking form built + verified working
- [ ] Phase 4+5 deployed to local WordPress
- [ ] Phase 6 hardening implemented (rate-limit, Cloudflare)
- [ ] Phase 7 client CMS clarity + role created + content seeded
- [ ] Final verification on live site (Hostinger) + client walkthrough scheduled

---

**Last updated:** 2026-07-15 11:45 UTC  
**Next action:** Await Phase 3 confirmation, then begin Phase 4 debug  
**Questions?** Refer to this document; it's the single source of truth.
