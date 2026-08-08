# Calculator Missing and Incomplete Data States Plan

## Implementation-grade parity contract

### Intent and feel

Incomplete data must feel honest and recoverable, never exposing `undefined`, a wrong fee sheet or a confident-looking estimate with no branch context.

### Exact current React/public evidence

Current code initializes `branchId` from `locations[0]?.id || ""`. With zero locations the select has no options, `branch` is undefined, the panel prints `Estimate for `, calculations still show the default 30/15/15 hours, and no sheet button renders. Missing age range would interpolate as `undefined`; missing PDF removes that record from `feeSheets` and therefore omits only the action. There is currently no malformed-rules state because rules are hard-coded.

### Current public section -> exact WP canvas parity

| Current public truth | Required WordPress canvas result |
| --- | --- |
| Zero Nurseries (current risk) | Current React leaves an empty select and branchless estimate; the WP plan must reproduce this in a labelled legacy fixture, then use a designed unavailable state for the future published renderer. |
| Missing age/PDF | Never print `undefined`; missing age gets an explicit unavailable display policy. Missing PDF keeps hours calculation and current action omission unless an approved fallback is enabled. |
| Rules/config failure | Future versioned rules use last known valid published configuration; this is planned protection, not a claim about today's hard-coded React. |

### Editable elements and controls

Empty/incomplete state headings, explanatory copy, icon/surface, registered Contact/Fees action, missing-age display label and optional missing-PDF fallback. Data errors themselves are fixed at source; state copy cannot invent facts.

### Layers, reorder, and dragging

State is a mutually exclusive renderer branch for the calculator region, not a duplicate draggable section. Within fallback, heading/copy/actions stay semantic flow. Editor state selector injects fixtures only and never modifies live Nursery records.

### Responsive behavior

Fallback and partial states must fit 320px/200% zoom, with wrapped actions and no empty select overflow. State selection is identical across devices; a breakpoint cannot conceal a data failure.

### Data ownership and bindings

Nursery completeness/status/PDF belongs to Nursery records; rule validity belongs to Calculator Rules; fallback copy/style/route references belong to this page state configuration. Error logs belong to operations, not page content.

### Protected behavior

No fabricated branch/age/PDF, no wrong-first-record substitution, no `undefined`/NaN/dead links, stable registered fallback routes, last-known-valid rule rollback and publish-readiness dependency checks.

### Accessibility

Unavailable state uses a heading/status message and keyboard links; incomplete controls are disabled or removed with explanation, not merely grey. Focus moves predictably on a record disappearing and status changes are announced once.

### Relevant state previews

Preview exact current zero-record risk, planned zero-record fallback, selected Nursery removed, missing age, missing PDF, invalid URL, duplicate name migration, malformed future rules, last-known-valid restoration and no-JS initial output.

### Storage and versioning

Version fallback copy/style/policies under `fee_calculator.data_states`; store only binding/status keys, not copied source facts or visitor selections. Rule and Nursery revisions remain separate and dependency-linked with rollback.

### Planned implementation files (future only)

These paths describe later implementation ownership; they are **not created by this planning phase**.

- `includes/definitions/pages/fee-calculator.php` -- incomplete/empty state schema.
- `includes/data/fee-calculator-readiness.php` -- Nursery/document/rule completeness checks.
- `assets/editor/pages/fee-calculator/data-states.js` -- safe fixture selector and fallback editor.
- `tests/integration/fee-calculator/data-states.spec.js` -- zero/missing/removed/malformed and recovery fixtures.

### Acceptance checklist

- [ ] Legacy fixture documents the exact current empty-select/branchless-result behavior without presenting it as acceptable future output.
- [ ] Published future renderer never emits `undefined`, NaN, wrong PDF, blank select or unexplained confident result.
- [ ] Every fallback is responsive, accessible, source-honest, versioned and linked to a valid recovery action.


## Exact risk

Current React assumes at least one Nursery for a useful selector. Future draft/unpublish or missing fee assets can produce no branch, no age, or no PDF.

## State editor

Preview: no published Nurseries, selected Nursery disappears, missing age range, missing fee PDF, malformed rules config, and restored safe config. Edit state heading/copy/icon/contact action and surface styling.

## Protected fallback

Malformed config uses last known valid published config and logs an alert. No Nursery shows a safe Contact/Fees action instead of broken calculations. Missing PDF does not prevent hours calculation.

## Acceptance

No state displays `undefined`, NaN, negative hours, blank selects, or dead download buttons. Each fallback remains accessible and links to a registered internal page.
