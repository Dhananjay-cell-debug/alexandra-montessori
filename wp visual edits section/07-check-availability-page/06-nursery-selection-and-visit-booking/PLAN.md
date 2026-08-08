# Nursery selection and visit-booking plan

## Intent and feel

This control should connect an enquiry to the right nursery and offer a visit only after a real branch is selected. The editor should make CMS-bound facts obvious so names, areas and opening hours are not copied into page-local text.

## Exact current JSX/public evidence

- Label is `Preferred nursery`; the field is optional and controlled by local `branch` state.
- First option is `Choose a nursery`; options map `locations` as `{name}, {area}` with stable `location.id` values.
- When selected, helper text renders `{name} is open {hours}.`
- The full-width outline button is disabled with no selection and reads `Choose a nursery to book a visit`.
- With selection it reads `Book a visit to {name}` and opens `CalendlyModal`; note underneath says `Booking opens securely in Calendly.`
- The branch value is included in the availability API payload even if empty.

## Editable controls

- Label, first-option copy, helper/button/note patterns using protected `{name}`, `{area}` and `{hours}` tokens.
- Select/button/helper spacing, field/button style and bounded width.
- Data-source filter/order controls referencing published nursery records, with exact impact preview.
- Enabled/disabled/selected/missing-hours previews.
- Booking destination itself is not a free URL field here; it deep-links to the Calendly configuration plan.

## Layers, reordering and dragging

Protected flow is select -> conditional hours helper -> visit button -> security note. These remain one functional field group; individual parts cannot be freely dragged. The whole group can only move through schema-aware form-group ordering, updating DOM/focus order and retaining its binding.

## Desktop, tablet and mobile

The group occupies one cell of the `sm` two-column details grid, but its button/note remain full width of that cell. On mobile it becomes full width. Test long branch names/hours, 320px width, disabled/selected states and native select behavior.

## Data ownership and bindings

Nursery ID/name/area/hours/status come from the WordPress-authoritative `locations` contract. This page stores only copy patterns and presentation. Selection state is runtime; the API payload carries the stable ID. Booking URL is derived by `calendlyUrl(selectedLocation)` from protected configuration.

## Protected behavior

- Option values remain stable nursery IDs; display edits cannot change IDs.
- Button stays disabled when no valid selected location exists.
- Do not permit page-local edits to canonical names, areas, hours or Calendly URLs.
- Unpublished/invalid nurseries cannot enter the public options.
- Token patterns must retain required factual tokens and escape text safely.

## Accessibility

Select retains a real label and native keyboard behavior. Disabled state is programmatic and visually distinct. Helper/note remain adjacent to the field/button. The button’s accessible name includes the selected nursery. Focus passes to the modal only under the modal plan’s behavior.

## State previews and failure handling

Preview no selection, each current branch, long/missing hours, missing booking configuration, a future branch and zero locations. Missing hours omits or uses reviewed helper copy rather than displaying `undefined`. Missing booking config disables visit booking while leaving the availability enquiry usable. Zero locations shows a safe source warning/fallback, not an empty broken select.

## Storage and versioning

Store stable source query, presentation and tokenized copy in the form schema; do not copy nursery records. Version Calendly configuration separately. Revision diffs identify pattern/order changes and affected branches. Migration seeds the current three-option rendering.

## Planned implementation files (future only)

- `src/components/availability/NurseryVisitSelector.jsx`
- `src/pages/Availability.jsx`
- `src/lib/nurseryModel.js`
- `src/lib/calendly.js`
- `wordpress/mu-plugins/alexandra-visual-builder/assets/panels/availability-nursery-selector.js`
- `wordpress/mu-plugins/alexandra-visual-builder/tests/availability-nursery-selector.php`

## Current public section -> exact WP canvas parity

| Current public state | Exact WP canvas requirement |
| --- | --- |
| Optional `Preferred nursery` select | Same optional state, label and controlled value |
| `{name}, {area}` options from `locations` | Same current records/order and stable IDs |
| Selected helper `{name} is open {hours}.` | Same conditional rendering/pattern |
| Disabled choose-a-nursery visit button | Same label, disabled semantics and full width |
| Enabled `Book a visit to {name}` + Calendly note | Same copy/order and modal invocation |

## Acceptance checklist

- [ ] Current nursery options/facts match CMS-resolved public data.
- [ ] Selected ID, helper, payload and modal nursery remain synchronized.
- [ ] Page-local editing cannot overwrite canonical branch facts/configuration.
- [ ] Missing/zero-source states do not emit `undefined` or dead actions.
- [ ] Mobile and long-copy layouts stay readable.
- [ ] Disabled and enabled states are keyboard/screen-reader correct.

