# Hounslow — team section

## Intent and feel

Present roles with warmth without inventing staff identities, while preparing an editor that can safely add consented people later.

## Current public section → exact WP canvas parity

- Seventh public section is the sand band with grid texture, eyebrow `Meet the team`, and title `The people who'll care for your child`.
- Two shared cards render: Nursery Manager with `teacher-hug.webp`, crop `50% 0%` plus `scale(1.1) translateX(4%)`; Montessori Lead Practitioner with `friends-two.webp`, crop `50% 50%`.
- Cards contain role and note only; current JSX deliberately has no staff names.

## Editable elements and controls

- Heading, section/card tokens, shared-role inheritance, role/note, approved photo, alt, focal/zoom, order, add/archive, and consent-status metadata.
- A named-person mode remains unavailable until consent and publication policy fields are complete; current role-based mode is the default.

## Layers and dragging

- Exact tree: `Team section` → `Grid texture`, `Heading group`, `Team grid` → two `Role card` → `Image`, `Role`, `Note`.
- Reorder role cards only inside Hounslow’s resolved list; internal order is locked and keyboard controls mirror drag.

## Responsive behaviour

- One column mobile and two at `sm`, max-width matching current section; 4:3 crops retain full heads.
- Per-device focal override warns if face-safe area leaves frame; text grows naturally at zoom.

## Record/template binding and overrides

- Current roles/content/media inherit shared team collection; Hounslow may store sparse card or collection overrides without changing other branches.
- Override UI shows source per field and offers `Revert to shared`; a new Hounslow-only role receives a new stable ID.

## Protected rules

- No invented names/qualifications; named profiles require consent evidence, valid role, and authorised publish.
- Photo removal does not delete media original; unsafe/freeform markup is excluded.

## Accessibility

- Alt describes nursery care context without identifying unnamed children/staff; icon-free card content has clear headings.
- DOM order, focus, keyboard reorder, contrast, and reduced motion pass.

## Empty and error states

- Empty resolved team requires intentional section disable or approved general-team message; never shows blank cards.
- Missing shared image uses last published media/approved placeholder with provenance warning.

## Storage and versioning

- Store sparse Hounslow overrides at `nurseries/{hounslowUuid}/sections/team`; consent metadata and asset versions are immutable audit events.
- Revision diff distinguishes shared upstream change from Hounslow override change.

## Planned implementation files (future only)

- `am-visual-builder/schema/entities/nursery/sections/team.php`
- `am-visual-builder/admin/pages/nursery/sections/TeamEditor.jsx`
- `am-visual-builder/runtime/nursery/TeamSection.php`
- `am-visual-builder/content/nurseries/hounslow/team.json`

## Acceptance checklist

- [ ] Current two role cards, photos, crops, copy, and layout reproduce.
- [ ] No names appear without consent workflow.
- [ ] Shared source versus Hounslow override, responsive face crops, keyboard reorder, errors, diff, and restore pass.

