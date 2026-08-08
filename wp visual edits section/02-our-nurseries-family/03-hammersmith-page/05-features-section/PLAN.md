# Hammersmith — features section

## Intent and feel

Present only verified Hammersmith amenities and make its shorter nine-card offer look deliberately balanced.

## Current public section → exact WP canvas parity

- Fifth section has `What we offer` and fallback heading `Everything your child needs to thrive in Hammersmith` because no `offerHeading` override exists.
- Nine items render: School readiness, Montessori Inspired, Practical life, Sensory spaces, Qualified practitioners, Buggy store, Secure entry, Extended hours, Creative atelier.
- `Home-cooked meals`, `Onsite chef`, and `Outdoor garden play` are deliberately filtered out; grid remains 2/3/4 columns.

## Editable elements and controls

- Heading override/inheritance, item icon/label, add/archive/order, grid/card tokens, visibility, facility verification and comparison against shared starter.

## Layers and dragging

- Exact tree: `Features section` → `Heading group`; `Feature grid` → nine cards → `Icon`, `Label`.
- Reorder only Hammersmith stable IDs; internal order fixed and keyboard parity required.

## Responsive behaviour

- Preserve 2/3/4 grid and natural final-row balance; labels wrap, zoom works, no placeholder cards fill eleven/twelve.

## Record/template binding and overrides

- Hammersmith owns the filtered feature set; heading currently inherits template fallback generated from name. Layout inherits shared template.
- Reverting an item must not re-add excluded meal/chef/garden claims automatically.

## Protected rules

- Facility verification, approved icon/plain label, unique IDs; excluded features require explicit verified addition, not template sync.

## Accessibility

- Icons decorative, labels meaningful, visual/DOM order equal, keyboard reorder, contrast/zoom pass.

## Empty and error states

- Empty visible grid blocks/disable choice; missing icon gets safe fallback, missing label remains draft-only; failed starter comparison cannot alter public list.

## Storage and versioning

- Store `nurseries/{hammersmithUuid}/sections/features` with exclusion lineage, ordered items, field origins, verification and sparse layout.
- Diff must show any re-added excluded amenity prominently.

## Planned implementation files (future only)

- `am-visual-builder/schema/entities/nursery/sections/features.php`
- `am-visual-builder/admin/pages/nursery/sections/FeaturesEditor.jsx`
- `am-visual-builder/runtime/nursery/FeaturesSection.php`
- `am-visual-builder/content/nurseries/hammersmith/features.json`

## Acceptance checklist

- [ ] Exact nine features/fallback heading/order/grid reproduce with no meal/chef/garden.
- [ ] Exclusions survive reset/template updates unless explicitly verified.
- [ ] Reorder, errors, zoom, diff and restore pass.

