# Admissions highlights plan

## Intent and feel

The three highlights should quickly explain what the enquiry captures—start date, session pattern and branch fit—without becoming marketing cards or suggesting those fields are all mandatory.

## Exact current JSX/public evidence

- Highlights are a local three-record array in `Availability.jsx`, ordered Preferred start date, Session pattern, Best nursery fit.
- Their icons are `CalendarDays`, `Clock` and `MapPin` respectively.
- Each renders a white `rounded-2xl` soft-shadow card, flex row, with 44px circular sage badge, H3 and short paragraph.
- Container is one column by default, `sm:grid-cols-3`, then returns to one column inside the `lg` left rail through `lg:grid-cols-1`.
- Cards are not links and have no hover action.

## Editable controls

- Per item: H3, short paragraph and approved icon; add/duplicate/archive/reorder only within a bounded collection.
- Card surface, radius, padding, gap, badge size/colour and type scale using approved tokens.
- Container gap and existing breakpoint layout, with copy-length/height warnings.
- Reset each core item and preview exact one/three/future-item counts.
- No link field by default because current public cards are informational.

## Layers, reordering and dragging

Each item is a grouped icon -> text stack inside a flow card. Collection drag reorders complete records and updates DOM order; keyboard Move up/down is equivalent. Icon/text cannot be freely pixel-dragged or layered over each other. The highlight group stays below the visual card in the left rail.

## Desktop, tablet and mobile

Current responsive pattern is deliberately 1 column mobile, 3 columns from `sm`, then 1 column again when it becomes the narrow `lg` rail. The canvas must reproduce that non-monotonic grid rather than assume desktop always has three columns. Long copy and future count previews test natural height and 320px wrapping.

## Data ownership and bindings

Highlight content/presentation belongs to this page and does not alter actual form rules. Stable IDs must replace title-as-React-key if client reordering/additions are implemented. Icons are approved component references, with optional media only if accessible and visually compatible.

## Protected behavior

- Informational wording cannot claim a selected date/session/branch guarantees availability.
- Preserve H3 hierarchy and card DOM order.
- Core three can be restored; zero visible items removes the group and its gap cleanly.
- Clamp copy, badge and card dimensions; never truncate essential wording silently.
- No arbitrary script/link behavior in these current non-interactive cards.

## Accessibility

Built-in icons are decorative because headings/text carry meaning. Cards require no tab stops while non-interactive. Text contrast, zoom and line wrapping remain valid; visual reorder always changes DOM order too.

## State previews and failure handling

Preview the exact three current cards, one card, long copy, missing/unknown icon, zero items and narrow rail. Unknown icon falls back to a neutral approved glyph while preserving text; invalid items remain repairable in admin and are not silently deleted.

## Storage and versioning

Store an ordered stable-ID collection plus group/card presentation in the Availability page document. Revisions move whole records. Migration seeds the exact current titles/text/icons/order and records whether a field is default or override.

## Planned implementation files (future only)

- `src/components/availability/AdmissionsHighlights.jsx`
- `src/pages/Availability.jsx`
- `src/lib/availabilityContentModel.js`
- `wordpress/mu-plugins/alexandra-visual-builder/assets/panels/admissions-highlights.js`
- `wordpress/mu-plugins/alexandra-visual-builder/tests/admissions-highlights.php`

## Current public section -> exact WP canvas parity

| Current public item/layout | Exact WP canvas requirement |
| --- | --- |
| CalendarDays / Preferred start date | Same first card, title and explanatory sentence |
| Clock / Session pattern | Same second card and wording |
| MapPin / Best nursery fit | Same third card and wording |
| `1 -> 3 at sm -> 1 at lg` grid | Same exact breakpoint behavior in the real page context |
| Informational non-links | No fabricated CTA/hover interaction in canvas or output |

## Acceptance checklist

- [ ] Current three records render in exact order and style.
- [ ] Reorder moves icon/title/text and DOM order together.
- [ ] Responsive preview reproduces 1/3/1 columns.
- [ ] Unknown/empty states remain composed and diagnosable.
- [ ] Cards remain non-interactive unless a future behavior is explicitly approved.
- [ ] Content edits do not change form fields or submission rules.

