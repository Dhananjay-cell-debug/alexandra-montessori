# Fee Calculator Page Header Plan

## Implementation-grade parity contract

### Intent and feel

The opening should feel simple and honest: families understand what the calculator does and its limit before interacting with any control.

### Exact current React/public evidence

`FeeCalculator.jsx` calls `PageHeader` with H1 `Funding Estimate` and exact intro `A simple planning tool for funded hours. It estimates chargeable weekly hours only; exact fees are confirmed by the branch using the latest fee sheet.` Current `PageHeader.jsx` renders a white section with centred H1, optional intro, `pt-12 sm:pt-16`, `pb-6`, H1 `text-4xl sm:text-5xl`, and intro `max-w-3xl`; it has no breadcrumb, eyebrow, media or action.

### Current public section -> exact WP canvas parity

| Current public truth | Required WordPress canvas result |
| --- | --- |
| Visible content | Render exactly the H1 and intro above, with no seeded breadcrumb, eyebrow, background image, button or badge. |
| Geometry | Preserve the white full-width header, centred contained copy, current responsive typography, intro measure and vertical spacing. |
| DOM/motion | Keep one H1 followed by its paragraph through the current Reveal wrappers, immediately before the calculator body. |

### Editable elements and controls

H1, intro, surface, content width, alignment, vertical padding and heading/body presets. Optional eyebrow, breadcrumb, media or decoration controls may exist only disabled in the exact-current preset and require deliberate activation; financial-copy changes receive review status.

### Layers, reorder, and dragging

Seed tree is header surface > container > H1 > intro. H1/intro stay in semantic flow and cannot be individually dragged around the page. Enabled decoration/media may move only inside clipped safe bounds; the header remains first in the page outline.

### Responsive behavior

Seed keeps 4xl below `sm`, 5xl from `sm`, natural height and current line measure. Device overrides may adjust size/width/alignment/padding while preserving 320/390px nav clearance, 200% zoom and no clipped disclaimer wording.

### Data ownership and bindings

Header copy/style belongs to the Fee Calculator page. Typography/colour tokens and shell clearance are global. It contains no Nursery or calculation-result binding; any page-count/brand token remains global.

### Protected behavior

Exactly one H1, H1-before-intro order, sanitization, route context, skip-target/global header clearance, contrast and truthfulness review for financial language. Optional breadcrumb must use a registered route.

### Accessibility

H1 remains semantic regardless of visual preset; intro stays readable with sufficient contrast/line length; reveal respects reduced motion; decoration is hidden unless meaningful text is supplied.

### Relevant state previews

Preview exact seed, long title, long intro, intro intentionally hidden with clarity warning, optional background contrast, reduced motion, 320/390/768px and 200% zoom.

### Storage and versioning

Version `fee_calculator.page_header` independently with content/style, sparse device deltas, claim-review metadata, draft/published snapshots, diff and rollback. No calculator inputs belong here.

### Planned implementation files (future only)

These paths describe later implementation ownership; they are **not created by this planning phase**.

- `includes/definitions/pages/fee-calculator.php` -- header schema and exact current defaults.
- `includes/renderers/sections/page-header.php` -- shared semantic title/intro renderer.
- `assets/editor/pages/fee-calculator/page-header.js` -- inline copy and responsive controls.
- `tests/parity/fee-calculator/page-header.spec.js` -- DOM and viewport screenshot parity.

### Acceptance checklist

- [ ] New seed shows only current H1 and intro; optional eyebrow/breadcrumb/media/action are absent.
- [ ] Canvas and `/fee-calculator` match at the same viewport before and after publish.
- [ ] Editing cannot introduce a second H1, misleading price promise, low contrast or mobile overlap.


## Exact current section

H1 `Funding Estimate` and introduction explaining the tool estimates weekly chargeable hours only and final fees come from the latest branch fee sheet.

## Editable controls

Title/introduction, optional eyebrow/breadcrumb, background/media, width, alignment, spacing, typography, and decoration. Disclaimer-relevant sentence editing carries a financial-review badge.

## Guardrails and acceptance

One semantic H1, no misleading guaranteed-cost language without publisher review, no mobile clipping, and exact seeded visual parity with the current header.
