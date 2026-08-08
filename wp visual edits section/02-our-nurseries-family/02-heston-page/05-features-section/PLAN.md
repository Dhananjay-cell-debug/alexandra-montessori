# Heston — features section

## Intent and feel

Make Heston’s offer scannable while protecting its one crucial branch distinction: ample parking replaces Creative atelier.

## Current public section → exact WP canvas parity

- Fifth section uses `What we offer` and `Everything your child needs to achieve, thrive and belong in Heston`.
- Twelve items render in shared order except final item is `Ample parking` with `CarFront`: School readiness, Montessori Inspired, Practical life, Sensory spaces, Home-cooked meals, Qualified practitioners, Outdoor garden play, Buggy store, Secure entry, Extended hours, Onsite chef, Ample parking.
- Grid is 2/3/4 columns; card layer is icon badge then label.

## Editable elements and controls

- Heading, label/icon, add/archive/duplicate, order, columns/gaps, card tokens, visibility, and facility verification/last-confirmed note.
- Heston-specific difference is highlighted against starter template; editors can revert individual items without importing Creative atelier silently.

## Layers and dragging

- Exact tree: `Features section` → `Heading group`; `Feature grid` → 12 `Feature card` → `Icon`, `Label`.
- Reorder stays within Heston; internal card order locked; keyboard parity and stable IDs required.

## Responsive behaviour

- Preserve 2/3/4 grid, wrap labels, equal rhythm, and zoom; disallow undersized cards.

## Record/template binding and overrides

- Heston owns its feature collection and heading; current `Ample parking` is a record-level override of the shared starter’s final item.
- Clearing all presentation overrides does not remove the Heston content override or substitute another branch.

## Protected rules

- Facility claims including parking, chef, garden, and meals require verification; approved icons/plain text only.
- Visible section needs a valid item; no duplicate IDs or raw HTML.

## Accessibility

- Icons decorative, labels meaningful, DOM/visual order identical, keyboard reorder announced, contrast/zoom pass.

## Empty and error states

- Empty visible grid blocks publish or explicitly disables section; missing icon gets neutral fallback, missing label remains draft-only.

## Storage and versioning

- Store at `nurseries/{hestonUuid}/sections/features` with ordered IDs, per-field origin, verification notes, and sparse layout overrides.
- Diff additions/removals/moves and preserve `Ample parking` lineage.

## Planned implementation files (future only)

- `am-visual-builder/schema/entities/nursery/sections/features.php`
- `am-visual-builder/admin/pages/nursery/sections/FeaturesEditor.jsx`
- `am-visual-builder/runtime/nursery/FeaturesSection.php`
- `am-visual-builder/content/nurseries/heston/features.json`

## Acceptance checklist

- [ ] All 12 Heston items/icons/order/headings and 2/3/4 grid reproduce.
- [ ] Ample parking cannot silently revert to Creative atelier or leak elsewhere.
- [ ] Verification, reorder, missing fields, zoom, reset, diff, and restore pass.

