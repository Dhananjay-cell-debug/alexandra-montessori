# Funded Hours Control Plan

## Implementation-grade parity contract

### Intent and feel

Funding choices should be plain and non-judgmental: parents select a planning assumption without the UI implying that eligibility has been checked.

### Exact current React/public evidence

`fundedHours` initializes to number 15. A labelled native select contains exactly `No funded hours` -> 0, `15 funded hours` -> 15 and `30 funded hours` -> 30, in that order. `onChange` casts to Number; the choice immediately changes `fundedApplied = min(weeklyHours, fundedHours)` and `chargeableHours = max(weeklyHours - fundedHours, 0)`.

### Current public section -> exact WP canvas parity

| Current public truth | Required WordPress canvas result |
| --- | --- |
| Options/default | Render the exact three labels/values/order and preselect 15 funded hours. |
| Cap behavior | Applied funding never exceeds attendance hours; for 1 day/30 selected the public result is 10 applied and 0 chargeable. |
| Meaning | The selection is explicitly an estimate input, not proof of government eligibility or confirmation a branch will deliver that pattern. |

### Editable elements and controls

Label/help, option display labels, information tooltip content, select style/focus, spacing and optional policy source/review date. Numeric option values/default are managed only in authorized Calculator Rules, not changed by editing labels.

### Layers, reorder, and dragging

Input card > Funded-hours group > label/select > optional help/tooltip. Group reorders only in semantic flow. Options, numeric values and tooltip trigger/content remain associated; no detached overlay may obscure the select.

### Responsive behavior

Keep full-width native select, readable long labels and tooltip placement inside 320px safe bounds. Test zoom, touch, keyboard and forced-colours; no device can hide an option or use a different numeric set.

### Data ownership and bindings

Presentation/review copy belongs to calculator configuration. Numeric options/default and cap formula belong to versioned rules. Eligibility guidance sources are governance metadata; the visitor selection is ephemeral.

### Protected behavior

Exact numeric mapping, finite non-negative values, default membership, cap behavior, no cosmetic relabel that changes meaning, no eligibility claim, and atomic rule publish with regression matrix.

### Accessibility

Persistent label, native select keyboard semantics, visible focus and non-hover-only tooltip. Option labels state hours explicitly; explanatory copy is linked with `aria-describedby` if enabled.

### Relevant state previews

Preview 0, default 15, 30, 1-day cap, 5-day result, retired/future option in draft, invalid stored default, policy-review overdue and narrow/zoom layouts.

### Storage and versioning

Version display configuration at `fee_calculator.controls.funded_hours`; version numeric options separately in `fee_calculator_rules` with effective dates, approver and rollback. Do not persist visitor selections or inferred eligibility.

### Planned implementation files (future only)

These paths describe later implementation ownership; they are **not created by this planning phase**.

- `includes/definitions/pages/fee-calculator.php` -- funded-hours presentation schema.
- `includes/domain/fee-calculator-rules.php` -- validated numeric options/default and cap.
- `assets/editor/pages/fee-calculator/funded-hours-control.js` -- copy/style/state/rules preview.
- `tests/unit/fee-calculator/funded-hours-control.spec.js` -- option mapping, caps and accessibility.

### Acceptance checklist

- [ ] Exact 0/15/30 labels, order and default match the current route.
- [ ] All combinations cap applied hours and keep chargeable hours non-negative.
- [ ] No visual edit changes numeric meaning or implies eligibility; authorized rule changes are versioned and reversible.


## Exact current behavior

The select offers No funded hours (0), 15 funded hours, and 30 funded hours; default is 15.

## Editable presentation

Field label/help, option display labels, select styling, spacing, and optional information tooltip. Preview all three values.

## Protected/configurable rules

Submitted numeric values and eligibility meaning are not editable as cosmetic text. Authorized Calculator Rules may add/retire an option only with numeric validation, effective date, explanatory copy, regression fixtures, and publisher approval. Existing default remains 15.

## Acceptance

Labels map to correct numeric values; selection updates funded-applied/chargeable summaries; keyboard/select semantics work; no option claims user eligibility.
