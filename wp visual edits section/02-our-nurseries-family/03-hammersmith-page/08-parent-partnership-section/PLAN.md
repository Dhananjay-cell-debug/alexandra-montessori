# Hammersmith — parent partnership section

## Intent and feel

Maintain six clear family promises and allow only explicit Hammersmith-local exceptions.

## Current public section → exact WP canvas parity

- Current section renders shared eyebrow/title/intro and six cards in order: Settling-in visits, Daily updates, Home learning, Parent workshops, Termly meetings, Continuous feedback.
- Grid is 1/2/3 columns; card internals are icon/title/text.

## Editable elements and controls

- Heading, icon/title/text, add/archive/duplicate, order, grid/style/visibility, inherited/local badge, service review.

## Layers and dragging

- Exact tree: `Parent partnership section` → `Heading group`; `Partnership grid` → six promise cards → `Icon`, `Title`, `Text`.
- Hammersmith-only reorder with keyboard parity; internal structure fixed.

## Responsive behaviour

- Preserve 1/2/3 grid, natural wrapping, DOM order, zoom and reduced-motion behaviour.

## Record/template binding and overrides

- Shared collection inherited; sparse Hammersmith changed/hidden/added IDs revert independently and never affect other branches.

## Protected rules

- Service promises reviewed, titles/text/IDs valid; hide is reversible; no raw HTML.

## Accessibility

- Icons decorative, meaningful card headings, keyboard announcements, contrast and zoom pass.

## Empty and error states

- Empty needs explicit disable; incomplete card draft-only; corrupt override falls back shared.

## Storage and versioning

- `nurseries/{hammersmithUuid}/sections/parentPartnership` with template revision, overrides/order/review and separate diff.

## Planned implementation files (future only)

- `am-visual-builder/schema/entities/nursery/sections/parent-partnership.php`
- `am-visual-builder/admin/pages/nursery/sections/ParentPartnershipEditor.jsx`
- `am-visual-builder/runtime/nursery/ParentPartnershipSection.php`
- `am-visual-builder/content/nurseries/hammersmith/parent-partnership.json`

## Acceptance checklist

- [ ] Current six cards/headings/order/grid reproduce.
- [ ] Branch isolation, review, reorder, wrap, empty/corrupt state, reset/diff/restore pass.

