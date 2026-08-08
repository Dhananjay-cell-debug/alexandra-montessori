# Protected Calculation Rules Plan

## Implementation-grade parity contract

### Intent and feel

The arithmetic should feel boringly dependable: every editor and public viewport uses one explainable hours formula, with no hidden prices or arbitrary code.

### Exact current React/public evidence

`FeeCalculator.jsx` hard-codes `dayHours = 10`; `days` defaults 3 and ranges 1-5; funding defaults 15 with options 0/15/30. On every render it calculates `weeklyHours = days * dayHours`, `chargeableHours = Math.max(weeklyHours - fundedHours, 0)`, and `fundedApplied = Math.min(weeklyHours, fundedHours)`. `useMemo` only constructs display rows; there is currently no external/versioned rule configuration, price multiplication, persistence or API call.

### Current public section -> exact WP canvas parity

| Current public truth | Required WordPress canvas result |
| --- | --- |
| Current formula | 10 hours/day; weekly = days x 10; applied = min(weekly, selected funding); chargeable = max(weekly - selected funding, 0). |
| Current domain | Integer days 1-5 and funding 0/15/30 yield a finite, non-negative matrix; default fixture is weekly 30/applied 15/chargeable 15. |
| No price claim | Output is hours only. No fee rate, currency, eligibility test, invoice, stretched-week calculation or server submission exists. |

### Editable elements and controls

No raw formula editor. Authorized Calculator Rules screen may edit bounded day hours, day range/default/step and funded options/default with plain-language impact table, effective date, reason and approver. Page designers see rule version/read-only formula.

### Layers, reorder, and dragging

Rules are functional configuration, not page layers and cannot be dragged, hidden, duplicated or converted to text. The result card order may change presentation, but calculation dependency order remains fixed.

### Responsive behavior

Rules produce identical values for every viewport/device. The admin impact matrix must fit small screens/zoom and public rounding/formatting cannot differ by breakpoint or locale without a versioned formatter.

### Data ownership and bindings

A dedicated calculator-rule record owns numeric policy. The page owns labels/styles and references the active rule version. Nursery records own documents only; visitor choices/output remain local transient state.

### Protected behavior

Typed numeric schema, credible bounds, unique options, valid defaults, integer steps, finite/non-negative outputs, applied cap, no arbitrary JavaScript, full fixture pass, capability/approval, atomic activation and last-known-valid rollback.

### Accessibility

Plain-language formula explanation and before/after table have headings/captions; rule errors are programmatically associated; keyboard editing is possible. Public results expose understandable labels rather than only mathematical symbols.

### Relevant state previews

Preview full 5x3 current matrix, exact boundaries, proposed rule diff, invalid duplicate option, default not in options, non-finite/out-of-bound input, activation failure and rollback.

### Storage and versioning

Create a separately versioned `fee_calculator_rules` record with schemaVersion, status, effectiveAt, author, reason, approver, immutable published snapshots and rollback pointer. Page revisions reference its ID/version; they do not embed executable formula text.

### Planned implementation files (future only)

These paths describe later implementation ownership; they are **not created by this planning phase**.

- `includes/domain/fee-calculator-rules.php` -- typed validation and canonical calculation.
- `includes/repository/fee-calculator-rules.php` -- draft/published revisions and atomic activation.
- `assets/editor/settings/fee-calculator-rules.js` -- impact matrix, approval and rollback UI.
- `tests/unit/fee-calculator/calculation-rules.spec.js` -- exhaustive current matrix and invalid-config fixtures.

### Acceptance checklist

- [ ] All 15 current day/funding combinations match the React formula exactly.
- [ ] No visual/page edit can alter arithmetic, inject code or add an undisclosed monetary calculation.
- [ ] Invalid activation leaves current published rules intact; approved rollback is atomic and audited.


## Exact current formula

```text
dayHours = 10
weeklyHours = days * dayHours
fundedApplied = min(weeklyHours, fundedHours)
chargeableHours = max(weeklyHours - fundedHours, 0)
```

This estimates hours only; it does not multiply by a price.

## Authorized rules UI

Rules live outside the page layer inspector in a Calculator configuration panel. It shows plain-language inputs, formula preview, before/after scenario table, effective date, reason, approver, and rollback version. It never accepts arbitrary JavaScript or formula text from the client.

## Validation and publishing

- day hours bounded to a credible configured range;
- days/options numeric and unique;
- defaults must exist in allowed options;
- outputs never negative or non-finite;
- all regression fixtures pass before activation;
- config publishes atomically and is versioned separately from visual design.

## Acceptance fixtures

Test every days x funded-hours combination, exact boundary caps, no Nursery data, and restored previous config. The visual editor and public runtime must call one shared rules implementation.
