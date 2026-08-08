# Calculator Nursery Selector Plan

## Implementation-grade parity contract

### Intent and feel

Choosing a branch should feel immediate and factual: families see the correct age range, result heading and matching document without maintaining duplicate branch data.

### Exact current React/public evidence

State initializes to `locations[0]?.id || ""`. The labelled select maps every `locations` record in current order and displays `{name} - ages {ageRange}` with stable option value `location.id`. `branch` is found by ID, but the current fee-sheet association is then found with `feeSheets.find((item) => item.name === branch?.name)`; changing selection immediately updates result heading and conditional download. Current fallback options are Hounslow/Heston (6 months to 5 years) and Hammersmith (12 months to 5 years).

### Current public section -> exact WP canvas parity

| Current public truth | Required WordPress canvas result |
| --- | --- |
| Default/options | With current records select Hounslow first, then Heston, then Hammersmith, using exact name/age pattern and no placeholder option. |
| Result binding | Selected branch name appears in `Estimate for ...` immediately; no submit/reload is required. |
| Fee-sheet compatibility | Seed resolves the same current PDFs, while the planned data contract upgrades name-based lookup to the same stable Nursery ID without changing visible output. |

### Editable elements and controls

Field label/help, option display token pattern, select surface/type/focus, spacing, source order/manual display order and default-selection policy. Fact chips open the Nursery source; no raw PDF destination is entered in this page inspector.

### Layers, reorder, and dragging

Input card > Nursery label/select as one functional group. The group may move among other input groups only in DOM-matched flow order; option rows and bound label/value cannot become canvas layers or be freely dragged.

### Responsive behavior

Select remains full width and native/accessible on narrow screens; long names/age ranges do not push outside card. Test 320px, zoom, Windows/macOS/mobile select UI and translated display labels without altering stable values.

### Data ownership and bindings

Nursery ID/name/age range/fee-sheet asset belong to each Nursery record and `locations`/`feeSheets` projection. Label/token pattern/style/default policy belong to calculator configuration. Branch selection is ephemeral local state.

### Protected behavior

Stable ID option values, published-record filtering, selected-branch/result binding, valid document association and sanitization. During migration, parity tests prove ID-based lookup returns the same PDF as current name matching; page designers cannot paste destinations.

### Accessibility

Persistent visible label, native select semantics, keyboard operation, visible focus and option text that distinguishes each Nursery. Help/error status must be programmatically associated; age text cannot be placeholder-only.

### Relevant state previews

Preview exact three records, each selection, future fourth record, no records, missing age, missing PDF, duplicate display name migration, selected record unpublished and long option text.

### Storage and versioning

Version source query/order/default/token/style under `fee_calculator.nursery_selector`; store no selected branch in page revisions. Nursery data and PDFs stay record-owned. Record a data-contract version for the name-to-ID association migration.

### Planned implementation files (future only)

These paths describe later implementation ownership; they are **not created by this planning phase**.

- `includes/definitions/pages/fee-calculator.php` -- selector query/display/default schema.
- `includes/data/fee-sheet-resolver.php` -- stable Nursery-ID-to-document resolver with legacy parity.
- `assets/editor/pages/fee-calculator/nursery-selector.js` -- bound option/state preview controls.
- `tests/parity/fee-calculator/nursery-selector.spec.js` -- current options, selection and PDF association.

### Acceptance checklist

- [ ] Current three options, order, ages, initial Hounslow and live result updates match React exactly.
- [ ] Stable-ID resolver returns each current branch PDF and handles duplicate names safely.
- [ ] No Nursery record state produces a blank interactive select, `undefined` option text or wrong-branch download.


## Exact current behavior

The `Nursery branch` select defaults to the first published Nursery and lists `{name} - ages {ageRange}`. Selection chooses the matching fee sheet and names the results panel.

## Editable presentation

Label/help, first/default behavior display, option token pattern, field style, focus state, and spacing. Data panel controls published Nursery order and missing-fee-sheet treatment. Factual name/age/PDF edits link to the Nursery record.

## Protected behavior

Option values remain stable Nursery IDs. Selected branch association and fee-sheet lookup are locked. No page-level raw PDF URL exists.

## States and acceptance

Preview three branches, future branch, missing age range, missing PDF, and no Nursery records. The current three options resolve exact data; selection updates heading and download without reload.
