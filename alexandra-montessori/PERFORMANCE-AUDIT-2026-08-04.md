# Alexandra Montessori performance audit — 4 August 2026

## Outcome

The low mobile score has specific, measured causes. It is not a generic React or hosting problem. The largest cause is that the hero poster—the LCP image—was not present in the initial HTML and was discovered only after React mounted. The next largest costs were the autoplay hero video, oversized/eager images, render-blocking styles/fonts, and Google Analytics loading before consent.

The fixes are implemented and verified in Local WordPress. They are not yet deployed to `https://alexandramontessori.co.uk`.

## Live baseline

The live PageSpeed report captured at 12:32 pm on 4 August 2026 has no Chrome UX Report field data, so its scores are lab measurements rather than a statistically stable real-user baseline.

| Metric | Live mobile | Live desktop |
|---|---:|---:|
| Performance | 75 | 90 |
| First Contentful Paint | 2.7 s | 0.9 s |
| Largest Contentful Paint | 4.7 s | 1.4 s |
| Total Blocking Time | 110 ms | 50 ms |
| Cumulative Layout Shift | 0 | 0 |
| Speed Index | 5.1 s | 2.3 s |

## Proven causes

1. **The hero LCP request was discovered 2.19 seconds late.** Lighthouse identified the `<video>` poster as LCP. Its mobile breakdown was approximately 220 ms TTFB, 2,190 ms request delay, 270 ms download and 40 ms render delay. The delay, not the poster's file size, dominated LCP. Lighthouse explicitly failed all but the non-lazy part of its LCP discovery check because the image was created by React rather than present in the initial document.
2. **The hero video was much too large for an autoplay background.** The MP4 is 21.95 MB and the WebM is 12.12 MB; both are 98.79 seconds at 1280×720. MP4 was listed first, so Chrome selected the larger file. The live desktop audit also recorded the MP4 request failing with `ERR_CONNECTION_FAILED`.
3. **Below-fold media competed with the hero.** All three feature images and the testimonials background were marked `priority`, which mapped to `loading="eager"` and `fetchpriority="high"` even though they were not the LCP element.
4. **Images were served at their full source dimensions.** The live image-delivery audit estimated roughly 550 KiB mobile and 628 KiB desktop savings. Examples included 1,800×1,200 and 1,200×1,800 photographs rendered in 208–224 px feature frames.
5. **Several stylesheets sat in the critical path.** The app CSS, theme stylesheet, Visual Builder stylesheet and Google Fonts CSS were all render-blocking. The live mobile audit estimated 350 ms of avoidable blocking.
6. **Analytics loaded before consent.** Site Kit injected the Google tag on every first visit (about 161 KiB), while the cookie notice promised analytics would run only after consent. This was both unnecessary first-load work and a consent-contract mismatch. Lighthouse attributed about 65 KiB of unused JavaScript to the Google tag.
7. **Every `Reveal` instance forced synchronous layout.** `useLayoutEffect()` called `getBoundingClientRect()` for each instance. Lighthouse measured the resulting forced-reflow work at about 35–43 ms.
8. **Every page embedded full Blog bodies in `window.amData`.** The frontend already uses the paginated REST/archive contract, so up to 12 full articles were redundant. This would make every route's HTML grow as editors publish longer articles.

## Implemented fixes

- The saved Visual Builder hero poster is resolved in PHP, preloaded with `fetchpriority="high"`, and rendered in a minimal initial Home shell before React starts.
- Mobile/tablet, reduced-motion, Save-Data and slow-connection visitors receive the static poster and no video source at all.
- Desktop video starts only after load plus an idle delay. WebM is first, and optimized 960×540/20 fps variants are about 6.9 MB instead of 12–22 MB. The original files remain preserved.
- Feature images now use purpose-cropped 320/480/768 px WebP candidates. The selected files are about 6–37 KB rather than 113–183 KB originals.
- The testimonials background has 480/640/800 px WebP candidates and is lazy/automatic priority.
- Visual Builder attachment media now exposes WordPress `srcset` candidates to React; Logo, feature, About and testimonial media consume them.
- Future JPEG upload sub-sizes are generated as WebP at quality 76, including a 480×640 Home portrait candidate. WordPress documents [`image_editor_output_format`](https://developer.wordpress.org/reference/hooks/image_editor_output_format/) as the supported format-mapping hook for generated sub-sizes.
- Poppins is self-hosted as Latin WOFF2 files under the SIL Open Font License. Google Fonts DNS, CSS and font requests are gone.
- The redundant theme stylesheet and public WordPress block/global/classic/emoji styles are removed. Visual Builder CSS is folded into the required app stylesheet, and its small runtime is deferred.
- Site Kit remains connected for reporting, but its public tag is blocked. The React analytics loader now downloads/configures Google Analytics only after stored or newly granted analytics consent; SPA page views remain controlled by `Analytics.jsx`.
- `Reveal` now uses asynchronous Intersection Observer entries without synchronous geometry reads.
- Full Blog bodies were removed from the global page payload; the existing archive/detail REST contract remains authoritative.

## Local before/after verification

Local and live results are not interchangeable: Local has a different server and no production Site Kit/Yoast overhead. Local results are valid for measuring the code change under an identical Lighthouse profile.

| Metric | Mobile before | Mobile after | Desktop before | Desktop after |
|---|---:|---:|---:|---:|
| Performance | 55 | **100** | 97 | **100** |
| First Contentful Paint | 2.8 s | **0.8 s** | 0.7 s | **0.4 s** |
| Largest Contentful Paint | 6.8 s | **1.1 s** | 1.2 s | **0.6 s** |
| Total Blocking Time | 600 ms | **40 ms** | 0 ms | **0 ms** |
| Cumulative Layout Shift | 0 | **0** | 0 | **0** |
| Speed Index | 4.4 s | **1.6 s** | 1.1 s | **0.4 s** |
| Transferred | 3,683 KiB | **428 KiB** | 3,939 KiB | **437 KiB** |
| Requests | 22 | **16** | 22 | **16** |

The final mobile audit passes LCP discovery and forced-reflow checks. It makes no hero-video request. The LCP resource delay fell from seconds to approximately 17 ms in the preceding repeat, and the final score repeated at 100. The only meaningful remaining image opportunity is one existing editor-uploaded 600×800 JPEG; it is lazy and does not affect LCP.

Validation completed:

- ESLint: pass
- WordPress production build: pass
- Theme/PHP syntax: pass
- Local WordPress architecture regression: 56/56 pass
- Visual Builder media portability: 37/37 pass
- Visual Builder schema parity: 24/24 pass
- Visual Builder render contract: 37/37 pass
- Browser QA at 412×915: responsive layout and images render; console warnings/errors: none
- A pre-existing visual-region contract test still reports six missing exact-route anchors in Testimonials, Privacy and Not Found. Those page/test files predate this work and no performance change touches them.

## Production rollout and future outlook

After the user flagged a possible Home/About visual change, production and Local were compared again at a 412 px viewport. The production bundle was still the pre-performance release. The About text, classes and colour were identical, but Local had unrelated saved Visual Builder spacing/crop values. Only `sections.home-about` and `elements.about-image` were backed up and restored to exact live values. The section then matched production at `402x1172`, with no console errors. A fresh post-restore Lighthouse run scored **97 mobile** (LCP 2.1 s, TBT 120 ms, 428 KiB/16 requests) and **100 desktop** (LCP 0.7 s, TBT 0 ms, 438 KiB/16 requests), confirming the visual correction preserved the 95+ Local target.

The live score remains 75/90 until this release is deployed. A 95+ live score is a realistic target based on the size of the measured LCP/TBT improvements, but no fixed PageSpeed number can be guaranteed on every run because Lighthouse throttling, Hostinger origin/edge latency and third-party availability vary.

Do not enable broad full-page caching just to chase TTFB: this site has a production cache guard because Hostinger previously served a stale site for about 24 hours. The current live TTFB is not the dominant problem, and the static hashed assets already have long immutable caching.

After approval, production rollout should:

1. create a verified backup of the current theme, Visual Builder MU plugin and database;
2. atomically deploy the theme plus Visual Builder `0.9.23` (the responsive media contract is coupled to the React build);
3. leave the Operations/Forms MU plugin and production database records untouched;
4. verify Site Kit remains connected but no Google tag request occurs before consent;
5. accept analytics and verify exactly one initial page view plus SPA route views;
6. verify Home, Availability, forms, Visual Builder Edit/View and direct routes;
7. rerun PageSpeed against the live domain after confirming the CDN is fresh.

Without the guardrails above, performance would regress as the media library and Blog grow. With the new responsive/WebP pipeline, consent-gated analytics, bounded global payload and delayed desktop-only video, normal content growth should not recreate the current failure mode. Existing legacy uploads can still be regenerated once after deployment if the remaining image-delivery warning needs to be removed.
