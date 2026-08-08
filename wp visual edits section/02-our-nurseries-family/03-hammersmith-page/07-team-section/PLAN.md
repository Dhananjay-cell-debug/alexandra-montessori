# Hammersmith — team section

## Intent and feel

Keep current role-based warmth without invented identities, with a safe path for consented Hammersmith-specific staff later.

## Current public section → exact WP canvas parity

- After the absent meals slot, the next public section is the sand/grid Meet the team band.
- It renders the same two unnamed roles/photos/crops/notes as other branches: Nursery Manager/teacher-hug and Montessori Lead Practitioner/friends-two.

## Editable elements and controls

- Heading/style, role/note, media/alt/focal, order/add/archive, inheritance, consent status; named profiles locked until consent.

## Layers and dragging

- Exact tree: `Team section` → `Grid texture`, `Heading group`, `Team grid` → two role cards → `Image`, `Role`, `Note`.
- Reorder Hammersmith resolved list only; internal order locked; keyboard parity.

## Responsive behaviour

- One/two columns at `sm`, 4:3 face-safe crops, natural height and zoom.

## Record/template binding and overrides

- Current collection inherited; Hammersmith sparse overrides/new IDs do not mutate shared roles or other branches.

## Protected rules

- No invented name/qualification; consent gate; non-destructive media removal; safe text.

## Accessibility

- Accurate non-identifying alt, proper headings, DOM order, keyboard reorder, contrast/reduced motion.

## Empty and error states

- Empty requires explicit disable/general message; broken image uses last/placeholder with source warning.

## Storage and versioning

- `nurseries/{hammersmithUuid}/sections/team`; immutable consent audit and shared/local revision provenance.

## Planned implementation files (future only)

- `am-visual-builder/schema/entities/nursery/sections/team.php`
- `am-visual-builder/admin/pages/nursery/sections/TeamEditor.jsx`
- `am-visual-builder/runtime/nursery/TeamSection.php`
- `am-visual-builder/content/nurseries/hammersmith/team.json`

## Acceptance checklist

- [ ] Current two cards/copy/crops/layout reproduce after no-meals transition.
- [ ] Consent, overrides, responsive crop, reorder, error, diff and restore pass.

