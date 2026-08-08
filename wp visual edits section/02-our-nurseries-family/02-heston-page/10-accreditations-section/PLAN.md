# Heston — accreditations section

## Intent and feel

Preserve the current generic trust grid without implying an Ofsted rating that Heston does not yet have.

## Current public section → exact WP canvas parity

- Tenth section renders four shared ShieldCheck cards: Official Ofsted Reports, Montessori Approach, EYFS Curriculum, Food Hygiene Rated.
- It does not publicly render Heston record evidence: Ofsted `Pending` / `No published report yet`, or food hygiene `5` dated 20 January 2025.
- Exact parity means those facts stay editor evidence until deliberately designed/published; especially, “Official Ofsted Reports” must not be interpreted as a positive Heston report.

## Editable elements and controls

- Labels/order/icon/style/spacing, Heston hide/override, source badge; evidence drawer with source URL/date/check status and stale warning.

## Layers and dragging

- Exact tree: `Accreditations section` → `Trust container` → four `Accreditation card` → `Icon`, `Label`.
- Heston-local resolved order with pointer/keyboard parity; internal structure fixed.

## Responsive behaviour

- Preserve compact horizontal mobile cards, two at `sm`, four at `lg`, then centred vertical card internals; long labels wrap.

## Record/template binding and overrides

- Generic labels inherit shared template; Heston’s pending Ofsted and hygiene evidence live only on Heston entity. Sparse display override references evidence revision.

## Protected rules

- No positive Ofsted claim while pending; specific rating requires source/domain/date verification. No misleading logo or raw HTML.

## Accessibility

- Text conveys meaning, icon decorative, visual/DOM order aligned, keyboard order/contrast/zoom pass.

## Empty and error states

- Missing/stale evidence warns admin but retains current generic label; corrupt override falls back shared; zero cards requires disable.

## Storage and versioning

- Display at `nurseries/{hestonUuid}/sections/accreditations`; immutable evidence history with source/checker/date; separate diffs.

## Planned implementation files (future only)

- `am-visual-builder/schema/entities/nursery/sections/accreditations.php`
- `am-visual-builder/admin/pages/nursery/sections/AccreditationsEditor.jsx`
- `am-visual-builder/runtime/nursery/AccreditationsSection.php`
- `am-visual-builder/content/nurseries/heston/accreditations.json`

## Acceptance checklist

- [ ] Current generic cards reproduce with no invented Heston rating.
- [ ] Pending status cannot be mispublished as Good/outstanding.
- [ ] Verification, responsive grid, keyboard order, stale/corrupt states, diff, and restore pass.

