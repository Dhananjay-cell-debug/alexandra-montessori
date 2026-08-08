# Alexandra Montessori — Round 4 changes (LOCAL ONLY — NOT DEPLOYED)

All items below were built AND verified in the browser on the Local site before
being ticked. Nothing has been pushed to production.

Build note: `npm run build:wp` → copy dist into Local theme → `npm run build`.
Current bundle: index-c6_azjLi.js.

---

## A. Blog & Events image handling (the amateur-look root cause)
- [x] A1. Added `.blog-richtext img/figure/figcaption/iframe/video/blockquote/table`
      styling in src/index.css. VERIFIED: an injected body image is capped at 34rem
      height, rounded (25.5px), block-centred. No more raw full-bleed dumps.
- [x] A2. Verified the readiness gate is correct-by-design: a blog with no featured
      image goes "Live … with fallback image"; an EVENT with no featured image is
      HIDDEN with a yellow "Website result: Hidden: missing featured image" notice
      (that is why the garba event didn't show — no bug). Sharpened Blog + Events
      guidance to tell the client to set the picture via the **Featured image** box.

## B. Homepage "About us" as an editable CMS section (admin menu, after Jobs)
- [x] B1. New "About us" editor under Website Content, positioned AFTER Jobs.
      VERIFIED in wp-admin: Heading / Body / Image / Milestones / Upcoming fields.
- [x] B2. Localized to `amData.about`; Home.jsx AboutUsBrief reads CMS with fallback.
      VERIFIED: amData.about present; empty fields fall back to built-in copy.
- [x] B3. About-us image defaults to owners photo, rendered as a clean circle.

## C. Nursery pages — meals (mealMoments in NurseryDetail.jsx)
- [x] C1. Lunch → "Bake Together" (bake-together.png). VERIFIED both children framed.
- [x] C2. Breakfast crop biased up (50% 32%). VERIFIED.

## D. Nursery pages — staff cards
- [x] D1. Montessori Lead (xylophone-floor.webp = the boy/ball photo) pos 50% 28%.
      VERIFIED head fully in frame.
- [x] D2. Nursery Manager (teacher-hug) pos 56% 22%. VERIFIED faces in frame, warm.

## E. Nursery testimonials lock
- [x] E1. Each nursery now shows the SAME three testimonials as the homepage.
      VERIFIED on Heston (was 1 card, now 3 matching the home section).

## F. Specific image swaps
- [x] F1. Home "What Parents Say" background → playground-balance.jpg. VERIFIED.
- [x] F2. Heston welcome → monkey-bars (CMS-managed; uploaded to media library,
      meta updated). Portrait photo in a landscape slot, so added welcomePosition
      `center 18%` so the climbing child is the subject. VERIFIED.
- [x] F3. Hounslow gallery slot #3 → hi-vis toddler (CMS-managed; media upload +
      gallery meta swap, first two kept). VERIFIED.
      NOTE: F2/F3 images live in the CMS (Nurseries → branch → Welcome image /
      Gallery), NOT in code — the client can change them there too. Old media kept
      (revert: Heston welcome att.74; Hounslow gallery-3 att.71).

## G. Curriculum — heads being cut
- [x] G1. "Where They Meet" (collage-activity) pos center 8%. VERIFIED headroom.
- [x] G2. "The Montessori Approach" (shape-work) pos center 15%. VERIFIED headroom.

## H. Curriculum philosophy + About circle
- [x] H1. "Our Philosophy" image → owners photo, clean circle (rounded-full,
      aspect-square), no white box. VERIFIED.
- [x] H2. Homepage About-us image also a clean circle (owners). VERIFIED.

---

### New asset files (public/assets/organisation)
- owners.webp (B3, H1, H2) · bake-together.png (C1) · playground-balance.jpg (F1)
- monkey-bars.jpg (F2 static/preview) · hi-vis-toddler.jpg (F3 static/preview)
- F2/F3 live images uploaded to WP media library: heston-welcome-monkey-bars.jpg
  (att.202), hounslow-gallery-hi-vis.jpg (att.203)

### Follow-ups before any production deploy
- Compress bake-together.png (~2.1 MB) to webp.
- Client to fill in the About us CMS fields (or leave blank to keep the defaults).
- Optional: log in AS a Viewer for a hands-on click-through (server rules already
  verified in round 3; the new am_about option is Viewer-protected too).

### Progress log
- Built every item, then verified each in the browser on Local. All 17 items pass.
- Files changed: src/index.css, src/pages/{Home,Curriculum,NurseryDetail}.jsx,
  src/data/site.js; theme functions.php, inc/am-admin.php, inc/am-roles.php.
- NOT deployed to production.
