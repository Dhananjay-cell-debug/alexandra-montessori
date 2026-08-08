# Availability form-card header plan

## Intent and feel

The form header should clearly announce the task inside the white card and visually balance the CalendarHeart badge, without competing with the page H1 or behaving like a second page hero.

## Exact current JSX/public evidence

- It is the first child of the right white form card, before either success or form state.
- Wrapper is a flex column with 1rem gap and bottom sage border; from `sm` it becomes a row with start alignment and space-between.
- Text is eyebrow `Availability enquiry` followed by H2 `Tell us about your childcare needs` at `mt-3`, 3xl deep sage.
- Right badge is 56px round, sand background, containing a 32px `CalendarHeart` icon.
- Header bottom padding is 1.5rem; it remains visible in both idle/form and success states.

## Editable controls

- Eyebrow, H2 and approved icon selection/replacement.
- Badge size, icon size, sand/sage tokens, header gap, border and bottom padding.
- H2 type scale/max width and existing mobile-stack/`sm` row alignment.
- Preview idle, sending, error and success body beneath the same header.
- No form-field insertion or arbitrary CTA controls in this header.

## Layers, reordering and dragging

Protected group order is text block then icon badge. They stay in responsive flex flow; canvas can select either but not freely drag it over the other. The complete header is locked before the conditional form/success body and cannot be reordered among field groups.

## Desktop, tablet and mobile

Default is stacked on narrow screens and horizontal from `sm`. Device controls may tune gap/padding/type/badge size within bounds, but cannot reverse reading order. Test long heading, 320px width and 200% zoom.

## Data ownership and bindings

Header content/presentation belongs to the Availability form component, not the global PageHeader. Form status is runtime state referenced only for preview; it does not own header copy. Approved icon references are sanitized.

## Protected behavior

- Keep H2 as the form-region heading after the page H1.
- Header remains before all fields and success content in DOM order.
- Badge icon is decorative; no nested button/link behavior.
- Clamp sizes/spacing and maintain contrast against white/sand.

## Accessibility

The H2 gives form purpose before keyboard focus enters controls. Eyebrow supplements rather than replaces it. Badge is `aria-hidden` when decorative. Responsive reflow must not change reading order or introduce a tab stop.

## State previews and failure handling

Preview narrow/row layout, long copy, missing custom icon, high zoom, idle and success states. Missing icon falls back to CalendarHeart; empty H2 blocks publication and restores current text. Empty eyebrow omits only that line and its dependent margin.

## Storage and versioning

Store a stable `availability-form-header` record with copy, icon and bounded responsive style values. Revisions are separate from field schema and submission state. Current JSX values are migration defaults.

## Planned implementation files (future only)

- `src/components/availability/AvailabilityFormHeader.jsx`
- `src/pages/Availability.jsx`
- `src/lib/availabilityFormModel.js`
- `wordpress/mu-plugins/alexandra-visual-builder/assets/panels/availability-form-header.js`
- `wordpress/mu-plugins/alexandra-visual-builder/tests/availability-form-header.php`

## Current public section -> exact WP canvas parity

| Current public element | Exact WP canvas requirement |
| --- | --- |
| `Availability enquiry` eyebrow | Same exact text/style above H2 |
| Current form-purpose H2 | Same heading level, copy and 3xl scale |
| 56px sand CalendarHeart badge | Same icon, size, colour and right-side position from `sm` |
| Stacked mobile / horizontal `sm+` | Same real flex break behavior |
| Header visible above success and form | State tabs retain the exact shared header |

## Acceptance checklist

- [ ] Canvas matches current header in every runtime state.
- [ ] H2 remains before all form controls semantically.
- [ ] Mobile/desktop reflow preserves visual and DOM order.
- [ ] Icon failure restores CalendarHeart.
- [ ] No field/CTA can be dragged into this header.
- [ ] Header edits do not mutate form rules or success copy.
