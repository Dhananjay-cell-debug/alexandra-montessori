# Calculator Input Card Surface Plan

## Implementation-grade parity contract

### Intent and feel

The input side should feel like one calm, compact decision panel, separating choices from results without looking like a complex application form.

### Exact current React/public evidence

The body is one `container-wide` section with `grid gap-8 pb-16 pt-6 lg:grid-cols-[0.85fr_1.15fr]`. Its first Reveal has `rounded-4xl border border-sage-100 bg-white p-7 shadow-soft`; an inner `space-y-5` contains exactly Nursery branch, Days per week and Funded hours per week in that order. It has no card heading, help block, submit button or explicit section background.

### Current public section -> exact WP canvas parity

| Current public truth | Required WordPress canvas result |
| --- | --- |
| Body layout | Seed one column below `lg`; from `lg`, input/results use exact 0.85fr/1.15fr columns with 32px gap and current top/bottom padding. |
| Input card | Seed white surface, sage-100 border, rounded-4xl, `p-7`, soft shadow and 20px vertical control spacing. |
| Content boundary | Show only the three current controls in this card; no unseeded title, reset, calculate button, price field or decorative media. |

### Editable elements and controls

Container width, section padding, column ratio/gap, mobile region order, card surface/border/radius/shadow/padding, inner gap and optional explanatory heading/help (off by default). Presets remain flow-grid based.

### Layers, reorder, and dragging

Page section > two-region grid > input card > controls group > three functional controls. Whole regions may swap using an approved order setting that also updates DOM order. Controls reorder only through constrained flow handles; absolute/free dragging is prohibited.

### Responsive behavior

Preserve stacked input then results below `lg` and 0.85/1.15 above it. Test 320/390px, tablet, desktop, 200% zoom and long labels. A mobile results-first choice must alter DOM as well as CSS order.

### Data ownership and bindings

This plan owns container/card presentation and region ordering. Individual control content/rules belong to their separate plans. Global width/design tokens remain shared; calculations and Nursery records are not stored in surface settings.

### Protected behavior

Functional control containment, logical DOM order, shared calculation context and no overlap/absolute positioning. Style controls cannot hide the only input region or make a disabled/read-only state appear interactive.

### Accessibility

Section and form-control reading order remain coherent; focus is never clipped by card overflow; border/shadow are supplemental; zoom creates natural height without horizontal scroll. Optional heading must programmatically label the region.

### Relevant state previews

Preview exact three-control seed, long labels/help, results-first mobile preset, no-Nursery disabled surface, keyboard focus across all fields, 320px and 200% zoom.

### Storage and versioning

Store visual/layout data under versioned `fee_calculator.input_surface`; control order uses stable keys (`nursery`, `days`, `funded_hours`). Draft/published revisions record sparse device overrides and rollback, never current input values.

### Planned implementation files (future only)

These paths describe later implementation ownership; they are **not created by this planning phase**.

- `includes/definitions/pages/fee-calculator.php` -- section/card layout schema.
- `includes/renderers/sections/fee-calculator.php` -- shared two-region composition.
- `assets/editor/pages/fee-calculator/input-surface.js` -- grid/card/order controls.
- `tests/parity/fee-calculator/input-surface.spec.js` -- geometry, flow and zoom parity.

### Acceptance checklist

- [ ] Untouched seed matches exact grid, card tokens, spacing and three-control order.
- [ ] Reordering regions/controls keeps visual, DOM, tab and announcement order aligned.
- [ ] No supported viewport or zoom level clips controls or introduces horizontal overflow.


## Exact current section

The left side of the body is a white rounded card with sage border, soft shadow, padding, and vertically spaced controls. The body grid is approximately 0.85/1.15 columns and stacks on mobile.

## Editing controls

Body content width, column ratio, gap, vertical spacing, mobile order, card background/border/radius/shadow/padding, field group gap, and optional section heading/help. Only flow layout is allowed for functional controls.

## Responsive/accessibility

Results may move above/below inputs on mobile only through an explicit order setting; DOM order must match. The calculator must remain understandable at 320px and 200% zoom.

## Acceptance

Default surface and grid match `FeeCalculator.jsx`; style changes do not alter values or calculations; no horizontal overflow at supported widths.
