# Days per Week Control Plan

## Implementation-grade parity contract

### Intent and feel

The day control should feel effortless and legible: one movement clearly changes attendance hours and the estimate, with no hidden price interpretation.

### Exact current React/public evidence

`days` initializes to 3. The labelled native range input has `min="1"`, `max="5"`, no explicit step (HTML default step 1), full width and sage accent; `onChange` converts the value with `Number`. Supporting text correctly renders `1 day per week` or `{n} days per week`. Current rules multiply every selected day by constant `dayHours = 10`.

### Current public section -> exact WP canvas parity

| Current public truth | Required WordPress canvas result |
| --- | --- |
| Seed/value | Slider range is integers 1-5, default 3, and the adjacent value line reads `3 days per week`. |
| Calculation effect | Every step recalculates weekly hours as days x 10, then updates all three result cards synchronously. |
| Current grammar nuance | The supporting line singularizes 1 day; the separate Attendance summary currently always says `1 days / 10 hours`, which belongs to results copy and must not be silently rewritten during parity seeding. |

### Editable elements and controls

Label/help/value display pattern, track/thumb/tick visuals, focus/hover states, spacing and optional min/max captions. Numeric rules are read-only in the page inspector; authorized rules UI is separate and impact-tested.

### Layers, reorder, and dragging

Input card > Days group > label > range > value text. These remain one labelled functional group; the group can reorder in flow with matching DOM. Thumb/track are control parts, not detachable layers; no absolute overlay may block pointer/keyboard access.

### Responsive behavior

Full-width track with minimum touch-safe height at mobile, no value overlap at 320px/zoom, and native keyboard/pointer behavior. Device styles may change visual thickness/labels but not min/max/value semantics.

### Data ownership and bindings

Field presentation belongs to the calculator page. Current default/min/max/step and `dayHours=10` belong to versioned calculator rules. The visitor's chosen day value is session-only component state.

### Protected behavior

Numeric conversion, integer bounds, default 3, step 1, shared recalculation and no arbitrary script/formula in visual fields. Rule changes require permission, effective date, fixtures and atomic publish.

### Accessibility

Use a persistent label and programmatic numeric value/min/max; preserve arrow/PageUp/PageDown behavior and visible focus. Optional `aria-valuetext` mirrors the adjacent singular/plural phrase without excessive live announcements.

### Relevant state previews

Preview 1, 2, default 3, 5, keyboard focus, pointer drag, rules draft, narrow mobile, 200% zoom and invalid restored value clamped to a valid rule.

### Storage and versioning

Version presentation under `fee_calculator.controls.days` and numeric policy under a separate `fee_calculator_rules` revision with schema/effective date/approver. Never persist the visitor's selected days in page design or analytics by default.

### Planned implementation files (future only)

These paths describe later implementation ownership; they are **not created by this planning phase**.

- `includes/definitions/pages/fee-calculator.php` -- day-control presentation schema.
- `includes/domain/fee-calculator-rules.php` -- validated min/max/default/step/day-hours rules.
- `assets/editor/pages/fee-calculator/days-control.js` -- control styling and rule-impact preview.
- `tests/unit/fee-calculator/days-control.spec.js` -- bounds, grammar, keyboard and recalculation fixtures.

### Acceptance checklist

- [ ] 1-5/default-3 behavior and 10-hours-per-day outputs match current React.
- [ ] Supporting value text pluralizes exactly; current Attendance-card wording is separately documented and parity-tested.
- [ ] Visual or rule edits cannot break keyboard input, bounds, finite outputs or mobile layout.


## Exact current behavior

A range input permits 1-5 days, default 3. Supporting text pluralizes day/days. Each day represents the protected current default of 10 attendance hours.

## Editable presentation

Label/help text, suffix pattern, slider track/thumb/ticks, selected-value presentation, min/max labels, spacing, and focus state. Preview 1, 3, and 5-day cases.

## Protected/configurable rules

The public page designer cannot change numeric range or hours-per-day through text. An authorized Calculator Rules panel may version min/max/default/step and day-hours after validation, impact preview, and effective-date confirmation. Default remains exactly 1-5, 3, step 1, 10 hours.

## Accessibility and acceptance

Keyboard arrows work, current numeric value is announced, visible focus remains, singular/plural is correct, and moving the slider recalculates identical public/WP results.
