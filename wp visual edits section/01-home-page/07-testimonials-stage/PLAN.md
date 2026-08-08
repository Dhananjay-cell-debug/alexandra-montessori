# Home testimonials stage plan

## Intent and feel

The testimonial stage should feel immersive and reassuring: real play-area photography darkened to support a strong white heading, warm testimonial cards and a restrained archive link. This plan owns the stage around the cards; card content and frames belong to `08-testimonial-cards`.

## Current evidence

- `Testimonials` returns `null` when the resolved testimonial collection is empty.
- Otherwise `data-am-vb-region="home-testimonials"` uses 96px desktop/tablet and 64px mobile vertical padding.
- Background defaults to `playground-balance.jpg`, is decorative (`alt=""`), full-cover and priority loaded.
- A deep-green overlay defaults to 72%; content sits at z-20 above background/overlay.
- Heading is “What Parents Say”; archive CTA is “Read all parent stories” → `/testimonials` with a translucent white-outline treatment.
- Section content width defaults to 1200px and card-grid gap to 24px.

## Editable elements and controls

- Section visibility, responsive padding/content width/grid gap and overall preview state.
- Background image picker/replace, decorative status, focal x/y, scale/offset and per-device crop preview.
- Overlay strength 0–100%, approved overlay colour token and automatic heading/card contrast check.
- Heading text, scale/offset and alignment; keep one visible section heading.
- Archive CTA label, internal Testimonials-page reference, accessible description and bounded frame/text offset.
- Coupling indicator showing resolved visible-card count; zero-card state explains why the complete public stage is absent.

## Selection, layers and dragging

Locked stage order: background media → overlay → content (heading → card collection → CTA). Background dragging separates frame movement from crop. Heading and CTA support bounded optical movement, but cannot be dragged beneath the overlay or outside content width. Card-grid selection deep-links to the card plan; stage drag never reorders card records.

## Desktop, tablet and mobile

Exact device padding defaults are 96/96/64px. Background crop may vary per device; overlay is shared unless a justified device override is introduced. Heading and CTA remain centred. Preview one/two/three cards because content height changes naturally. Test 320px, long heading/CTA, light background photo and reduced-motion Reveal behaviour.

## Data ownership and bindings

Stage presentation and Home-only heading/CTA overrides belong to the Home design model. Background is a WordPress media attachment. CTA binds to the published Testimonials archive by stable page reference. Testimonial records come from CMS/static fallback and are not owned here.

## Protected rules

- Overlay/contrast must keep heading and CTA readable; block dangerously low contrast for a light photo.
- Background remains decorative; testimonial meaning lives in cards.
- CTA target cannot use unsafe or missing routes.
- Preserve stage layer order and prevent page/social/global overlays from entering it.
- If zero visible testimonials, do not render an empty photographic band.

## Accessibility and failure states

Decorative background keeps empty alt. Heading maintains correct hierarchy. CTA has visible focus over every overlay setting. If background fails, show a solid deep-sage fallback that preserves white-copy/card contrast. If testimonials fail to load but static fallback is allowed, use it; truly empty data hides the stage and the builder presents a repair action. Reduced motion disables nonessential card reveal transitions.

## Storage and versioning

Persist section design, background media/focal data and heading/CTA elements under stable keys. Store the Testimonials page reference rather than only `/testimonials`. Revisions distinguish background replacement/crop, overlay, copy and destination changes. Current v3 values remain exact defaults.

## Planned implementation files (future only)

- `src/components/home/HomeTestimonialsStage.jsx`
- `src/pages/Home.jsx`
- `src/lib/homeVisual.js`
- `wordpress/mu-plugins/alexandra-visual-builder/assets/panels/home-testimonials-stage.js`
- `wordpress/mu-plugins/alexandra-visual-builder/tests/home-testimonials-stage.php`

## Current public section → exact WP canvas parity

| Current public stage layer | WP canvas requirement |
| --- | --- |
| `playground-balance.jpg` full-cover background | Same default attachment, crop, priority/fallback behaviour |
| Deep-green 72% overlay | Same exact initial opacity and layer position |
| “What Parents Say” centred white heading | Same text, type scale and spacing |
| Card grid between heading and CTA | Same real cards from the separate card model/markup |
| “Read all parent stories” → `/testimonials` | Same current label, route and translucent outline treatment |
| Entire section absent at zero testimonials | Same public conditional, with an editor-only empty-state explanation |

## Acceptance checklist

- [ ] Untouched stage matches current public layers, copy, spacing and CTA.
- [ ] Background crop/overlay controls maintain text/card contrast on all devices.
- [ ] Card data is referenced, never duplicated in this panel.
- [ ] Zero/missing media/data states remain composed and understandable.
- [ ] CTA route/focus and heading semantics stay protected.
- [ ] Stage revision/reset does not alter testimonial records.

