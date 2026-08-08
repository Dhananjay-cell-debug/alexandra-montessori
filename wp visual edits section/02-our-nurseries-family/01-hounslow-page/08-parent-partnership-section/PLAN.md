# Hounslow — parent partnership section

## Intent and feel

Keep the six practical promises clear and welcoming, while ensuring a branch-specific edit cannot rewrite the same promises on other nursery pages.

## Current public section → exact WP canvas parity

- Eighth public section uses eyebrow `Partnership with parents`, title `We work hand in hand with families`, and the current one-sentence intro.
- Six shared cards render in order: Settling-in visits, Daily updates, Home learning, Parent workshops, Termly meetings, Continuous feedback, each with its current icon/title/text.
- Grid is one column, two at `sm`, three at `lg`.

## Editable elements and controls

- Heading group, card icon/title/text, add/archive/duplicate, manual order, columns/gaps, card tokens, visibility, and `Inherited / Hounslow override` per item.
- Promise-sensitive copy shows a service-confirmation prompt and last-reviewed field.

## Layers and dragging

- Exact tree: `Parent partnership section` → `Heading group`; `Partnership grid` → six `Promise card` → `Icon`, `Title`, `Text`.
- Pointer/keyboard reorder stays within the Hounslow resolved collection; internal order is fixed.

## Responsive behaviour

- Preserve 1/2/3-column parity and equal-card rhythm; long text wraps without fixed-height clipping.
- At zoom/mobile, icons remain adjacent to their own headings and DOM order follows visual order.

## Record/template binding and overrides

- Current collection inherits the shared nursery-detail partnership template; Hounslow stores only changed/added/hidden item IDs.
- Reverting an item exposes shared content immediately; branch-local additions never enter Heston/Hammersmith.

## Protected rules

- No unsupported service promises, raw links/HTML, missing titles, or duplicate IDs.
- A visible section needs at least one valid card; removal of inherited cards is a reversible hide override.

## Accessibility

- Icons are decorative; headings label each promise. Keyboard reorder and announcements are required.
- Contrast, readable line length, 200% zoom, and reduced-motion reveal behaviour pass.

## Empty and error states

- Zero resolved cards requires explicit disable; corrupt Hounslow override falls back to valid shared item with warning.
- Incomplete new card remains draft-only and never produces an empty public tile.

## Storage and versioning

- Store at `nurseries/{hounslowUuid}/sections/parentPartnership` with shared template revision, sparse overrides, order, and review metadata.
- Revision diff identifies inherited updates separately from local changes.

## Planned implementation files (future only)

- `am-visual-builder/schema/entities/nursery/sections/parent-partnership.php`
- `am-visual-builder/admin/pages/nursery/sections/ParentPartnershipEditor.jsx`
- `am-visual-builder/runtime/nursery/ParentPartnershipSection.php`
- `am-visual-builder/content/nurseries/hounslow/parent-partnership.json`

## Acceptance checklist

- [ ] Current heading and six cards reproduce in exact order and grid.
- [ ] Hounslow edits never mutate another branch.
- [ ] Promise review, keyboard order, responsive wrapping, empty/corrupt states, reset, diff, and restore pass.

