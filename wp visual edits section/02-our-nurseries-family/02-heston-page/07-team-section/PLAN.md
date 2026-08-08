# Heston — team section

## Intent and feel

Keep Heston’s current role-led warmth without invented identities, while enabling consent-gated local team overrides later.

## Current public section → exact WP canvas parity

- Seventh section is the sand/grid band with `Meet the team` and `The people who'll care for your child`.
- Two inherited cards: Nursery Manager with teacher-hug image/crop/transform; Montessori Lead Practitioner with friends-two at `50% 50%`; each has current shared note and no name.

## Editable elements and controls

- Heading/style, role/note, image/alt/focal/zoom, order, add/archive, source badge, consent status; named-person fields remain locked until consent policy passes.

## Layers and dragging

- Exact tree: `Team section` → `Grid texture`, `Heading group`, `Team grid` → two `Role card` → `Image`, `Role`, `Note`.
- Heston list reorder only; internal order locked; keyboard parity.

## Responsive behaviour

- One column mobile/two at `sm`, max-w-3xl, 4:3 face-safe crops, natural copy height, zoom resilience.

## Record/template binding and overrides

- Current content/media inherit shared team collection; Heston stores sparse overrides/additions by stable item ID and can revert each field.

## Protected rules

- No invented names/qualifications; named profile requires consent evidence. Media removal is non-destructive; safe text only.

## Accessibility

- Non-identifying accurate alt, proper card headings, keyboard order, contrast, and reduced motion.

## Empty and error states

- Empty list requires deliberate disable or approved general message; missing image uses last published/placeholder with provenance warning.

## Storage and versioning

- Store at `nurseries/{hestonUuid}/sections/team`; consent is immutable audit metadata and diff distinguishes shared/local changes.

## Planned implementation files (future only)

- `am-visual-builder/schema/entities/nursery/sections/team.php`
- `am-visual-builder/admin/pages/nursery/sections/TeamEditor.jsx`
- `am-visual-builder/runtime/nursery/TeamSection.php`
- `am-visual-builder/content/nurseries/heston/team.json`

## Acceptance checklist

- [ ] Current two role cards/crops/copy/layout reproduce.
- [ ] Consent gate, Heston-only override, responsive crop, keyboard reorder, errors, diff, and restore pass.

