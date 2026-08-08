# Contact Heston Page Header Plan

## Implementation-grade parity contract

### Intent and feel

The Heston page should open with calm local reassurance and make the selected nursery unambiguous without adding chrome that the current public component does not render.

### Exact current React/public evidence

ContactLocation.jsx passes `crumb="Contact, Heston"`, `eyebrow="Contact us"`, title `Contact our Heston nursery`, and intro `Book a show-around at Heston, ask about availability or funded hours - we'd love to hear from you.`. Important current-code correction: PageHeader.jsx only accepts/renders `title` and `intro`; crumb and eyebrow props are ignored, so the exact public section currently shows only the H1 and intro on white.

### Current public section -> exact WP canvas parity

| Current public truth | Required WordPress canvas result |
| --- | --- |
| Visible copy | Render only H1 `Contact our Heston nursery` and the exact show-around/availability/funding intro in the seed. |
| No invented chrome | Do not show breadcrumb or eyebrow in exact-current parity mode; they may exist only as disabled future controls until intentionally enabled/published. |
| Geometry | Preserve centred white header, contained copy, H1 4xl/5xl responsive scale, intro max-width 3xl and current spacing/reveal. |

### Editable elements and controls

Static wording around the bound Nursery name, intro, background, content width, alignment, padding and typography. Optional eyebrow, breadcrumb, media and decoration controls are off by default and require deliberate activation.

### Layers, reorder, and dragging

Seed tree is surface > container > H1 > intro. Both text nodes stay in semantic flow. Only enabled decorative/media layers can be freely dragged within safe bounds; section order changes at the page level, not by moving individual header text.

### Responsive behavior

Preserve current type breakpoint and natural height. Device overrides cover size, line length, alignment and padding, with 320/390px nav-clearance and long-name checks.

### Data ownership and bindings

Nursery-name tokens bind to the Heston record; sentence fragments and local style belong to the Contact Location template/optional heston override. Brand tokens and header offset are global.

### Protected behavior

One H1, stable route context, token escaping, semantic H1-before-intro order, global header clearance and contrast checks. Enabling breadcrumb must use registered `/contact` rather than arbitrary URL.

### Accessibility

Heading stays an H1 regardless of visual preset; paragraph remains readable at zoom; reveal honors reduced motion; optional decoration is hidden from assistive technology.

### Relevant state previews

Preview exact title+intro, disabled optional eyebrow/breadcrumb, long Nursery name, background image/contrast state, reduced motion, 320px and 200% zoom.

### Storage and versioning

Version `contact_location.page_header` template content/style and sparse `heston` overrides. Store bound token IDs rather than the resolved Heston string; keep draft/published snapshots, diff and rollback.

### Planned implementation files (future only)

These paths describe later implementation ownership; they are **not created by this planning phase**.

- `includes/definitions/templates/contact-location.php` -- header token/schema and exact-current defaults.
- `includes/renderers/sections/page-header.php` -- shared title/intro renderer with optional features disabled.
- `assets/editor/templates/contact-location/heston-header.js` -- Heston inline/responsive controls.
- `tests/parity/contact-location/heston-header.spec.js` -- title-only-plus-intro screenshot/DOM parity.

### Acceptance checklist

- [ ] Canvas shows no breadcrumb/eyebrow until explicitly enabled because current PageHeader does not render them.
- [ ] At matching viewports the Heston H1, intro, spacing and alignment match the public route.
- [ ] Token edits cannot create a second H1, stale branch name, low contrast or mobile overlap.


## Exact current section

`ContactLocation.jsx` passes breadcrumb `Contact, Heston` and eyebrow `Contact us`, but the current `PageHeader.jsx` ignores both props. The exact visible public section is H1 `Contact our Heston nursery` plus the show-around/availability/funded-hours introduction; no breadcrumb or eyebrow is rendered.

## Editor behavior

Edit the real header inline. Bound Heston tokens remain visually identified. Controls cover static copy, background, optional media, width, spacing, typography, alignment, and approved decoration. Layer order stays breadcrumb -> eyebrow -> H1 -> intro; decorative layers alone may use free movement.

## Responsive and guardrails

Tablet/mobile may override type size, padding, alignment, and crop. H1 semantics and Contact breadcrumb route stay protected. Publish readiness checks visible H1, contrast, clipping, and token resolution.

## Acceptance

The WP canvas is pixel-equivalent to `/contact/heston` at matching viewport and the Heston name is never stale static copy.
