# Care-period date controls plan

## Intent and feel

Families should be able to state when care should start and optionally when a fixed period ends without confronting unnecessary date complexity. The editor may clarify wording and presentation but must preserve the local-calendar constraints and conditional relationship.

## Exact current JSX/public evidence

- `Required start date *` is a required controlled date input, named `startDate`, with `min={todayISO()}` using the visitor’s local calendar day.
- If a newly selected start date is after the stored end date, React clears the end date.
- A separate sage-50/40 rounded box contains checkbox `I need care for a fixed period (add an end date)`.
- When checked, `Required end date` appears with minimum `startDate || today`; despite its wording, the current input does **not** have a `required` attribute.
- Unchecking fixed period immediately clears endDate; submission includes end date only while the toggle is true.

## Editable controls

- Start/end labels, fixed-period sentence and optional help copy.
- Field/box surface, border, padding, gap and approved conditional reveal styling.
- Date example/help presentation without replacing the native date input.
- Preview today, future start, fixed period open/closed, end-before-start browser constraint and start-change clearing.
- Required wording must match actual programmatic rules; changing end date to required needs an authorized rule migration.

## Layers, reordering and dragging

Start date remains in the details grid; fixed-period box follows that grid in form flow. Within the box, checkbox precedes conditional end field. These controls cannot be absolutely dragged or separated. Whole-group reorder is schema-aware and preserves DOM/controller relationship.

## Desktop, tablet and mobile

Start field follows one/two-column form grid. End field is full width on mobile and `sm:w-auto` currently. Test native date controls at 320/390px, zoom and browser variations. Conditional reveal adds natural height without overlaying following weekday controls.

## Data ownership and bindings

Dates/toggle are runtime form state and submit as ISO date strings to the availability endpoint. `todayISO` owns local minimum calculation. Copy/styles belong to page form schema. No entered date may be written to page-design revisions or reused as editor content.

## Protected behavior

- Start remains required and cannot accept past dates through ordinary UI.
- End minimum tracks start/today; start change clears an incompatible end.
- Toggle-off clears and excludes end date.
- Do not claim end date is programmatically required unless schema/code changes together.
- Keep native date semantics; no unvalidated free-text substitute.

## Accessibility

Labels remain associated. Checkbox sentence names the controller and conditional end input appears directly after it in DOM order. Reveal respects reduced motion and does not steal focus. Required marker/state must be truthful.

## State previews and failure handling

Preview blank required start, valid future start, toggle open/closed, optional blank end, valid end and start advanced past end. Invalid stored values are cleared/flagged, never silently sent. If local date calculation fails, block submission with a clear message rather than allow an unbounded date.

## Storage and versioning

Store presentation/copy separately from a versioned rule record (`startRequired`, minimum strategy, fixed-period behavior, end-required flag). Runtime dates never persist in design storage. A future change making end required needs schema version, backend validation and migration tests.

## Planned implementation files (future only)

- `src/components/availability/CarePeriodFields.jsx`
- `src/pages/Availability.jsx`
- `src/lib/dates.js`
- `src/lib/availabilityFormModel.js`
- `wordpress/mu-plugins/alexandra-visual-builder/assets/panels/availability-care-period.js`
- `wordpress/mu-plugins/alexandra-visual-builder/tests/availability-care-period.php`

## Current public section -> exact WP canvas parity

| Current public behavior | Exact WP canvas requirement |
| --- | --- |
| Required start date with local-today minimum | Same required/min behavior and exact label |
| Fixed-period checkbox in sage box | Same copy, surface and position after details grid |
| Conditional end input | Same reveal and `min=startDate || today` |
| End date currently optional in markup | Canvas must not falsely mark or validate it as required |
| Toggle/start changes clear incompatible end | Interaction preview reproduces the exact state reset |

## Acceptance checklist

- [ ] Current labels, required states and layout match JSX.
- [ ] Start cannot precede local today.
- [ ] End cannot precede selected start through supported interaction.
- [ ] Toggle/start reset behavior matches public runtime.
- [ ] No sample/visitor dates persist in design storage.
- [ ] Mobile native controls and conditional flow do not overflow.

