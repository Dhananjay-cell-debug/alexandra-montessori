# Alexandra Montessori — Where We Are

**Updated:** 2026-07-15
**In one line:** All four website forms now save to WordPress *and* email you, fast. A few handover items remain before the client gets it.

---

## ✅ Done & verified working

Everything below was tested end-to-end today (real submissions against the WordPress site, real emails delivered to `dhananjaychitmila@gmail.com`).

| Area | Status | Notes |
|---|---|---|
| **Contact form** | ✅ Works | Saves to *Enquiries* + emails the branch |
| **Check Availability form** | ✅ Works | Saves to *Availability Requests* + emails the branch |
| **Book a Visit popup** | ✅ Works | Email-based (replaced the broken Calendly) — saves + emails with preferred date |
| **Careers form + CV upload** | ✅ Works | Saves to *Applications*, stores the CV, emails it as an attachment |
| **Real Gmail delivery (SMTP)** | ✅ Works | Configured in `wp-config.php` (test Gmail for now) |
| **Speed** | ✅ Fixed | Forms went from **4–8 seconds** to **~1 second**. Email now sends in the background so nobody waits on "Sending…" |
| **Availability page broken image** | ✅ Fixed | Was pointing at the wrong path on the WordPress build |
| **Flood / abuse protection** | ✅ Added | Honeypot + timing + per-IP rate limits. Silent on the dev site; active in production |

### What made it slow (now fixed)
- Sending the email inside the form request meant the visitor waited the full ~4.5s Gmail handshake (8s+ with a CV attached). **Now** the form saves and responds instantly, and a separate background process delivers the email.
- A CV upload was making WordPress render the PDF into preview images (slow, pointless). **Now** the file is just stored — no rendering.

---

## ⏳ Still pending (in priority order)

### 1. 🔴 Production delivery — the one real decision needed
The forms POST to WordPress (`/wp-json/...`). Today that WordPress lives **only on your Local machine**. The public site on Hostinger is a **static export with no WordPress behind it**, so the forms won't reach an inbox *there* yet.
**Options:** (a) host the WordPress site publicly and point the live site's forms at it, or (b) route forms through a lightweight mail service. This needs one decision before go-live — everything else is ready.

### 2. 🟠 Swap the test email for the client's real inboxes
Right now every form emails the test Gmail. Before handover, switch to the real per-branch addresses (Hammersmith / Heston / Hounslow). The routing is already built — it's a one-line change plus the client's SMTP credentials.

### 3. 🟡 CMS clarity for the client (Phase 7)
Make the WordPress admin friendly for a non-technical owner: helper text under each section, hide the menus they don't need, a restricted "Editor" login, and seed real content so it's not empty.

### 4. 🟡 Clean slate before handover
Clear the leftover test entries and mail logs so the client opens a tidy admin.

---

## How it's wired (quick reference)

```
Visitor fills form (React)
   → POST /wp-json/am/v1/{enquiry|availability|apply}
   → WordPress saves it as a post (nothing is ever lost)
   → fires a fast background job
        → sends the email via Gmail SMTP (~4.5s, but the visitor never waits)
   → returns "success" in ~1 second
```

- **Frontend forms:** `src/components/ContactForm.jsx`, `src/pages/Availability.jsx`, `src/components/BookingModal.jsx`, `src/components/ApplicationForm.jsx`
- **Backend:** `alexandra-theme/inc/am-enquiries-api.php`, `am-careers-api.php`, `am-rate-limit.php`; mail plumbing in `functions.php`
- **Build:** `npm run build:wp` → copy into theme `dist/` → `npm run build` (restore Hostinger base). Backend PHP changes are live immediately, no rebuild.
- **Credentials:** `wp-config.php` (never committed). Test Gmail today; client's own before go-live.

For the full phased plan, see `IMPLEMENTATION-PHASES.md`.
