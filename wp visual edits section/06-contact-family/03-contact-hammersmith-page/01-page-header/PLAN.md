# Contact Hammersmith Page Header Plan

## Implementation-grade parity contract

### Intent and feel

The Hammersmith page should open with calm local reassurance and make the selected nursery unambiguous without adding chrome that the current public component does not render.

### Exact current React/public evidence

ContactLocation.jsx passes `crumb="Contact, Hammersmith"`, `eyebrow="Contact us"`, title `Contact our Hammersmith nursery`, and intro `Book a show-around at Hammersmith, ask about availability or funded hours - we'd love to hear from you.`. Important current-code correction: PageHeader.jsx only accepts/renders `title` and `intro`; crumb and eyebrow props are ignored, so the exact public section currently shows only the H1 and intro on white.

### Current public section -> exact WP canvas parity

| Current public truth | Required WordPress canvas result |
| --- | --- |
| Visible copy | Render only H1 `Contact our Hammersmith nursery` and the exact show-around/availability/funding intro in the seed. |
| No invented chrome | Do not show breadcrumb or eyebrow in exact-current parity mode; they may exist only as disabled future controls until intentionally enabled/published. |
| Geometry | Preserve centred white header, contained copy, H1 4xl/5xl responsive scale, intro max-width 3xl and current spacing/reveal. |

### Editable elements and controls

Static wording around the bound Nursery name, intro, background, content width, alignment, padding and typography. Optional eyebrow, breadcrumb, media and decoration controls are off by default and require deliberate activation.

### Layers, reorder, and dragging

Seed tree is surface > container > H1 > intro. Both text nodes stay in semantic flow. Only enabled decorative/media layers can be freely dragged within safe bounds; section order changes at the page level, not by moving individual header text.

### Responsive behavior

Preserve current type breakpoint and natural height. Device overrides cover size, line length, alignment and padding, with 320/390px nav-clearance and long-name checks.

### Data ownership and bindings

Nursery-name tokens bind to the Hammersmith record; sentence fragments and local style belong to the Contact Location template/optional hammersmith override. Brand tokens and header offset are global.

### Protected behavior

One H1, stable route context, token escaping, semantic H1-before-intro order, global header clearance and contrast checks. Enabling breadcrumb must use registered `/contact` rather than arbitrary URL.

### Accessibility

Heading stays an H1 regardless of visual preset; paragraph remains readable at zoom; reveal honors reduced motion; optional decoration is hidden from assistive technology.

### Relevant state previews

Preview exact title+intro, disabled optional eyebrow/breadcrumb, long Nursery name, background image/contrast state, reduced motion, 320px and 200% zoom.

### Storage and versioning

Version `contact_location.page_header` template content/style and sparse `hammersmith` overrides. Store bound token IDs rather than the resolved Hammersmith string; keep draft/published snapshots, diff and rollback.

### Planned implementation files (future only)

These paths describe later implementation ownership; they are **not created by this planning phase**.

- `includes/definitions/templates/contact-location.php` -- header token/schema and exact-current defaults.
- `includes/renderers/sections/page-header.php` -- shared title/intro renderer with optional features disabled.
- `assets/editor/templates/contact-location/hammersmith-header.js` -- Hammersmith inline/responsive controls.
- `tests/parity/contact-location/hammersmith-header.spec.js` -- title-only-plus-intro screenshot/DOM parity.

### Acceptance checklist

- [ ] Canvas shows no breadcrumb/eyebrow until explicitly enabled because current PageHeader does not render them.
- [ ] At matching viewports the Hammersmith H1, intro, spacing and alignment match the public route.
- [ ] Token edits cannot create a second H1, stale branch name, low contrast or mobile overlap.


## Exact current section

`ContactLocation.jsx` passes `Contact, Hammersmith` and `Contact us` as breadcrumb/eyebrow props, but the current `PageHeader.jsx` ignores both. The exact visible public section is H1 `Contact our Hammersmith nursery` plus the bound-name show-around/availability/funding introduction; no breadcrumb or eyebrow is rendered.

## Editor behavior

Direct-edit static language around the Hammersmith token. Control background/media, typography, width, spacing, alignment, and approved decorative layers. Breadcrumb/H1/intro stay in semantic flow; decorative shapes alone may be dragged freely.

## Responsive and acceptance

Per-device type, padding, alignment, and crop must retain H1 visibility below the fixed nav. The exact live route and canvas match, token distinction remains clear, and breadcrumbs return to Contact.
