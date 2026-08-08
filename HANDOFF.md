# Alexandra Montessori — Session Handoff

**Updated:** 2026-07-15
**For:** the next Claude session — read this, then wait for the user's next change request.

---

## Where WordPress stands right now

The site is a **React SPA** (in `alexandra-montessori/`) wired into a **WordPress custom theme** (`alexandra-theme`) that acts as the CMS + form backend. WordPress currently runs **only on the user's Local machine** (Local by Flywheel). The public Hostinger site is a **static export** with no WordPress behind it yet.

### ✅ Working & verified (all tested end-to-end today)
- **All 4 forms** save to WordPress *and* email the branch, in **~1 second**:
  - Contact → `/wp-json/am/v1/enquiry`
  - Check Availability → `/wp-json/am/v1/availability`
  - Book a Visit (popup) → `/wp-json/am/v1/enquiry` with `kind=booking` (email-based; replaced the broken Calendly)
  - Careers + CV upload → `/wp-json/am/v1/apply` (CV attached to email + stored in Media Library)
- Each form saves a **private-post CPT first** (nothing is ever lost), then emails via **Gmail SMTP**.
- **Speed fix:** email now sends via a **non-blocking loopback worker** (`am_send_mail_async` → `POST /wp-json/am/v1/dispatch-mail`), so the visitor never waits on the ~4.5s Gmail handshake.
- **Careers CV** uses `wp_handle_upload` + `wp_insert_attachment` (skips slow PDF→image rendering).
- **Flood/abuse protection:** honeypot + timing guard + per-IP rate limits (`inc/am-rate-limit.php`). Skipped on the local dev site; active in production.
- **Availability page image** fixed (was pointing at the wrong path on the WP build).
- **"View in WordPress Admin" button removed** from ALL form notification emails (careers + enquiries) — emails are now just the details table.

### Key files
- **Frontend forms:** `src/components/ContactForm.jsx`, `src/pages/Availability.jsx`, `src/components/BookingModal.jsx`, `src/components/ApplicationForm.jsx`
- **Backend:** `alexandra-theme/inc/am-enquiries-api.php`, `am-careers-api.php`, `am-rate-limit.php`; mail plumbing in `alexandra-theme/functions.php`
- **Credentials:** `wp-config.php` (Gmail SMTP; currently the test Gmail `dhananjaychitmila@gmail.com`)

### Build & deploy notes
- `npm run build:wp` → copy `dist/` into the theme → `npm run build` (restore Hostinger base). **Backend PHP changes are live immediately — no rebuild.**
- After a bundle change, hard-refresh the browser (`Ctrl+Shift+R`) — old JS caches.
- Local has **no wp-cli / php on PATH**. To run PHP against the DB: use Local's bundled php.exe with `-d extension=mysqli` and `define('DB_HOST','127.0.0.1:10005')` before `wp-load.php`.

---

## ⏳ Still pending (in priority order)
1. 🔴 **Production delivery decision** — forms POST to `/wp-json`, but live Hostinger has no WordPress, so forms only reach an inbox on Local today. Decide: (a) host WordPress publicly, or (b) route forms via a mail service. **Needs a user decision before go-live.**
2. 🟠 **Swap the test Gmail** for the client's real per-branch inboxes (Hammersmith / Heston / Hounslow) + their SMTP creds. Routing is already built (one-line change).
3. 🟡 **Phase 7 — CMS clarity:** helper text, hide unused admin menus, restricted Editor login, seed real content.
4. 🟡 **Clean slate:** clear leftover test entries + mail logs before handover.

Full detail: `STATUS.md` and `IMPLEMENTATION-PHASES.md` in the project root.

---

## How to work here (important)
- **Make ONLY the exact change the user asks for.** Don't restructure, don't add extras, ask before restructuring. No deploy without review.
- Backend PHP edits go live instantly; frontend changes need `build:wp` + sync + hard-refresh.

**Next step:** wait for the user's next change request.
