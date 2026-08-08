# Contact Hounslow Page Header Plan

## Implementation-grade parity contract

### Intent and feel

The Hounslow page should open with calm local reassurance and make the selected nursery unambiguous without adding chrome that the current public component does not render.

### Exact current React/public evidence

ContactLocation.jsx passes `crumb="Contact, Hounslow"`, `eyebrow="Contact us"`, title `Contact our Hounslow nursery`, and intro `Book a show-around at Hounslow, ask about availability or funded hours - we'd love to hear from you.`. Important current-code correction: PageHeader.jsx only accepts/renders `title` and `intro`; crumb and eyebrow props are ignored, so the exact public section currently shows only the H1 and intro on white.

### Current public section -> exact WP canvas parity

| Current public truth | Required WordPress canvas result |
| --- | --- |
| Visible copy | Render only H1 `Contact our Hounslow nursery` and the exact show-around/availability/funding intro in the seed. |
| No invented chrome | Do not show breadcrumb or eyebrow in exact-current parity mode; they may exist only as disabled future controls until intentionally enabled/published. |
| Geometry | Preserve centred white header, contained copy, H1 4xl/5xl responsive scale, intro max-width 3xl and current spacing/reveal. |

### Editable elements and controls

Static wording around the bound Nursery name, intro, background, content width, alignment, padding and typography. Optional eyebrow, breadcrumb, media and decoration controls are off by default and require deliberate activation.

### Layers, reorder, and dragging

Seed tree is surface > container > H1 > intro. Both text nodes stay in semantic flow. Only enabled decorative/media layers can be freely dragged within safe bounds; section order changes at the page level, not by moving individual header text.

### Responsive behavior

Preserve current type breakpoint and natural height. Device overrides cover size, line length, alignment and padding, with 320/390px nav-clearance and long-name checks.

### Data ownership and bindings

Nursery-name tokens bind to the Hounslow record; sentence fragments and local style belong to the Contact Location template/optional hounslow override. Brand tokens and header offset are global.

### Protected behavior

One H1, stable route context, token escaping, semantic H1-before-intro order, global header clearance and contrast checks. Enabling breadcrumb must use registered `/contact` rather than arbitrary URL.

### Accessibility

Heading stays an H1 regardless of visual preset; paragraph remains readable at zoom; reveal honors reduced motion; optional decoration is hidden from assistive technology.

### Relevant state previews

Preview exact title+intro, disabled optional eyebrow/breadcrumb, long Nursery name, background image/contrast state, reduced motion, 320px and 200% zoom.

### Storage and versioning

Version `contact_location.page_header` template content/style and sparse `hounslow` overrides. Store bound token IDs rather than the resolved Hounslow string; keep draft/published snapshots, diff and rollback.

### Planned implementation files (future only)

These paths describe later implementation ownership; they are **not created by this planning phase**.

- `includes/definitions/templates/contact-location.php` -- header token/schema and exact-current defaults.
- `includes/renderers/sections/page-header.php` -- shared title/intro renderer with optional features disabled.
- `assets/editor/templates/contact-location/hounslow-header.js` -- Hounslow inline/responsive controls.
- `tests/parity/contact-location/hounslow-header.spec.js` -- title-only-plus-intro screenshot/DOM parity.

### Acceptance checklist

- [ ] Canvas shows no breadcrumb/eyebrow until explicitly enabled because current PageHeader does not render them.
- [ ] At matching viewports the Hounslow H1, intro, spacing and alignment match the public route.
- [ ] Token edits cannot create a second H1, stale branch name, low contrast or mobile overlap.


## Exact current public section

`ContactLocation.jsx` passes breadcrumb `Contact, Hounslow` and eyebrow `Contact us`, but the current `PageHeader.jsx` ignores both props. The exact visible public section is H1 `Contact our Hounslow nursery` plus the introduction about show-arounds, availability, and funded hours; no breadcrumb or eyebrow is rendered.

## Exact editor behavior

The same rendered header is directly editable in the Hounslow canvas. Token chips distinguish bound `Hounslow` from local sentence copy. The editor can change eyebrow, static wording, layout preset, background, spacing, alignment, and optional media while retaining the Hounslow identity token.

## Layers and responsive behavior

Layer tree: header surface -> breadcrumb -> eyebrow -> H1 -> introduction -> optional decoration. Flow order is locked for accessibility; approved decorations may move independently. Desktop/tablet/mobile controls cover padding, text size/width, alignment, and media focal point.

## Guardrails

H1 stays semantic. Breadcrumb destination remains `/contact`. Replacing every Hounslow token with static text shows an inheritance warning. Low contrast or mobile clipping blocks readiness.

## Acceptance

- Canvas and public header are pixel-parity at the same viewport.
- H1 and breadcrumb resolve Hounslow correctly.
- Mobile text does not overlap the site header.
