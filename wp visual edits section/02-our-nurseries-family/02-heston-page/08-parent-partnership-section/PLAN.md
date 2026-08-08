# Heston — parent partnership section

## Intent and feel

Keep the six family promises practical and calm while giving Heston a safe, local override surface.

## Current public section → exact WP canvas parity

- Eighth section renders shared heading/intro and six cards: Settling-in visits, Daily updates, Home learning, Parent workshops, Termly meetings, Continuous feedback.
- Current grid is 1/2/3 columns and each card is icon, title, text.

## Editable elements and controls

- Heading, item icon/title/text, add/archive/duplicate, order, columns/gaps/style, visibility, source badge, service-confirmation note.

## Layers and dragging

- Exact tree: `Parent partnership section` → `Heading group`; `Partnership grid` → six `Promise card` → `Icon`, `Title`, `Text`.
- Reorder is Heston-local with keyboard parity; card internal order locked.

## Responsive behaviour

- Preserve 1/2/3 columns, natural height/wrap, DOM order, and zoom; no fixed-height truncation.

## Record/template binding and overrides

- Inherit shared collection; Heston stores sparse changed/hidden/added item IDs. Revert resumes shared item without mutating it.

## Protected rules

- Service promises need review; valid title/text/stable ID; hiding inherited item is reversible, raw HTML excluded.

## Accessibility

- Icons decorative, headings meaningful, keyboard reorder announced, contrast/zoom/reduced motion pass.

## Empty and error states

- Empty requires explicit section disable; incomplete new card stays draft; corrupt local override falls back to shared.

## Storage and versioning

- Store `nurseries/{hestonUuid}/sections/parentPartnership`, template revision, overrides, order, and review metadata.
- Diff shared upstream versus Heston edits.

## Planned implementation files (future only)

- `am-visual-builder/schema/entities/nursery/sections/parent-partnership.php`
- `am-visual-builder/admin/pages/nursery/sections/ParentPartnershipEditor.jsx`
- `am-visual-builder/runtime/nursery/ParentPartnershipSection.php`
- `am-visual-builder/content/nurseries/heston/parent-partnership.json`

## Acceptance checklist

- [ ] Current heading, six items/order, and grid reproduce.
- [ ] Heston isolation, review, reorder, wrapping, empty/corrupt state, reset, diff, restore pass.

