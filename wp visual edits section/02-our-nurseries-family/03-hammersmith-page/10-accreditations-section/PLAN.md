# Hammersmith — accreditations section

## Intent and feel

Preserve the current generic trust badges while clearly warning editors that Hammersmith’s food-hygiene public listing is still awaiting confirmation.

## Current public section → exact WP canvas parity

- Current section renders the four shared ShieldCheck cards and no specific rating/link: Official Ofsted Reports, Montessori Approach, EYFS Curriculum, Food Hygiene Rated.
- Hammersmith record evidence is Ofsted `Good` with official report, but hygiene values say `Check current local authority record` / `Awaiting public FHRS listing` with general FSA URL.
- Exact default must not insert these undisplayed facts or present a numeric hygiene score.

## Editable elements and controls

- Labels/order/icon/style, local override/hide, evidence drawer and source/check-date/staleness controls; public specific fact addition requires deliberate verified edit.

## Layers and dragging

- Exact tree: `Accreditations section` → `Trust container` → four cards → `Icon`, `Label`; Hammersmith-local reorder, keyboard parity, fixed internals.

## Responsive behaviour

- Preserve horizontal compact mobile, two at `sm`, four at `lg`, then vertical centred internals; wrap long labels.

## Record/template binding and overrides

- Generic labels inherit template; Ofsted/hygiene evidence belongs only to Hammersmith. Sparse display overrides reference evidence revision.

## Protected rules

- No numeric hygiene claim until authoritative listing; specific Ofsted claim needs source/date. No misleading logo/HTML.

## Accessibility

- Meaning in text, decorative icon, DOM order, keyboard order, contrast and zoom pass.

## Empty and error states

- Missing/stale hygiene evidence warns and preserves generic label; corrupt local override falls back shared; zero cards requires disable.

## Storage and versioning

- `nurseries/{hammersmithUuid}/sections/accreditations`; immutable evidence source/check history and separate display/evidence diff.

## Planned implementation files (future only)

- `am-visual-builder/schema/entities/nursery/sections/accreditations.php`
- `am-visual-builder/admin/pages/nursery/sections/AccreditationsEditor.jsx`
- `am-visual-builder/runtime/nursery/AccreditationsSection.php`
- `am-visual-builder/content/nurseries/hammersmith/accreditations.json`

## Acceptance checklist

- [ ] Four generic cards reproduce; no invented hygiene rating appears.
- [ ] Good Ofsted and unavailable hygiene states stay distinct and branch-owned.
- [ ] Verification, responsive grid, reorder, stale/corrupt state, diff/restore pass.

