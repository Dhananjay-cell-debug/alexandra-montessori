# Results Panel and Summary Cards Plan

## Implementation-grade parity contract

### Intent and feel

Results should feel immediate and reassuring, showing the arithmetic plainly without presenting an invented monetary quote.

### Exact current React/public evidence

The second Reveal (delay 100ms) is `rounded-4xl bg-sage-500 p-8 text-white shadow-card`. It shows uppercase `Estimate for {branch?.name}`, then at `mt-7` a grid that becomes three columns from `sm`. Cards appear in exact order: Attendance = `{days} days / {weeklyHours} hours`; Funded hours applied = `{fundedApplied} hours`; Chargeable hours = `{chargeableHours} hours`. Each card is `rounded-3xl bg-white/10 p-5`. Current JSX has no `aria-live` on the result region and Attendance always uses plural `days`.

### Current public section -> exact WP canvas parity

| Current public truth | Required WordPress canvas result |
| --- | --- |
| Default fixture | Hounslow, 3 days and 15 funding produces `3 days / 30 hours`, `15 hours`, `15 hours` under `Estimate for Hounslow`. |
| Boundary fixture | 1 day and 30 funding produces current text `1 days / 10 hours`, 10 applied and 0 chargeable; no negative result. |
| Appearance/order | Preserve sage panel, current padding/shadow, uppercase eyebrow, 1-to-3-column responsive cards, order, labels and typography. |

### Editable elements and controls

Panel surface/gradient/radius/shadow/padding; heading/token pattern; card order, labels, surfaces, gap, columns, typography and optional icons/emphasis. Dynamic branch/numeric tokens are inserted only from the token picker. A future grammar correction (`1 day`) is a reviewed copy-version change, not hidden in initial parity.

### Layers, reorder, and dragging

Results panel > branch eyebrow > summary grid > three cards > label/value, followed by disclaimer/action owned by the next plan. Cards reorder with keyboard/drag only if DOM and announcement order update together. Values cannot be detached or freely positioned.

### Responsive behavior

Seed stacks cards below `sm` and uses three columns from `sm`; panel is the wider 1.15fr region at `lg`. Natural height, wrapping and 200% zoom are mandatory. Result updates should not cause horizontal shift or overflow.

### Data ownership and bindings

Branch name binds to selected Nursery; days/weekly/funded/chargeable values derive from shared rules and ephemeral inputs. Static labels/order/styles belong to results configuration. No numeric output is stored as page content.

### Protected behavior

Derived values, token types, formula source, finite/non-negative caps, result order semantics and no manual numeric overrides. The live initial state cannot be edited into a fake result.

### Accessibility

Add a region label and polite, settled-change announcement in the future renderer without claiming it exists today; do not announce every pointer pixel. Values remain visible text, focus order stays stable, contrast passes and icons are supplemental.

### Relevant state previews

Preview default, 1-day grammar/boundary, 5 days/no funding, 1 day/30 funding, each Nursery, no branch, delayed slider announcement, high zoom, long label and rules-draft comparison.

### Storage and versioning

Version labels/order/style under `fee_calculator.results_panel`; formula/rule version is referenced, not copied. Derived values/selected inputs remain runtime-only. Revisions identify any deliberate grammar migration and allow rollback.

### Planned implementation files (future only)

These paths describe later implementation ownership; they are **not created by this planning phase**.

- `includes/definitions/pages/fee-calculator.php` -- result-card schema/token allow-list.
- `includes/domain/fee-calculator-rules.php` -- single calculation output contract.
- `assets/editor/pages/fee-calculator/results-panel.js` -- fixture/state/layout editor.
- `tests/parity/fee-calculator/results-panel.spec.js` -- screenshots, formula fixtures and announcement checks.

### Acceptance checklist

- [ ] Default and boundary text/numbers reproduce current React exactly, including documented singular-day wording.
- [ ] Canvas/public derive every number from the same rule version; no editable fake values exist.
- [ ] Responsive order, contrast, zoom and polite settled updates meet accessibility acceptance.


## Exact current section

The right sage panel starts with `Estimate for {branch_name}` and three cards: Attendance `{days} days / {weekly_hours} hours`, Funded hours applied `{funded_applied} hours`, and Chargeable hours `{chargeable_hours} hours`.

## Editable presentation

Panel surface/gradient/padding/radius/shadow; eyebrow pattern; card order; label patterns around protected tokens; typography; card surfaces; 1/2/3-column layout; gap; emphasis; optional approved icons. Cards may reorder visually only if DOM order follows.

## Protected binding

Numeric tokens are derived live, never editable text. Token picker prevents malformed formulas. Values are not stored in the page document or sent to analytics as personal information.

## Responsive/accessibility

At mobile cards stack or use a readable grid. Results update in an `aria-live=polite` summary without announcing every slider pixel; announcement is debounced/on settled change.

## Acceptance

Default 3 days, 15 funded produces 30 attendance hours, 15 funded applied, 15 chargeable. 1 day/30 funded caps applied at 10 and chargeable at 0. WP and public values always match.
