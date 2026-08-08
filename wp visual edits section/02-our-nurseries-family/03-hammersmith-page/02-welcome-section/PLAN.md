# Hammersmith — welcome section

## Intent and feel

Keep the quiet prepared-environment story and protect the child’s head in the current landscape welcome crop.

## Current public section → exact WP canvas parity

- Second section is image-left/text-right at `lg`, stacked below.
- Hammersmith image is `/assets/organisation/shape-work.webp`, crop `center 8%`, no transform, 4:3, with `Nursery moments` badge.
- Text: `Welcome to Hammersmith`, `A second home for your child`, and the exact Ravenscourt Park/prepared Montessori environments paragraph.

## Editable elements and controls

- Media/alt/focal/zoom, badge, eyebrow/title/intro, column ratio/swap, spacing/gap and token presets; branch name/area tokens show source.

## Layers and dragging

- Exact tree: `Welcome section` → `Welcome media card` → `Image`, `Badge`; `Heading group` → `Eyebrow`, `H2`, `Intro`.
- Columns swap only via semantic toggle; image/badge locked together.

## Responsive behaviour

- Stack media then text mobile, two columns at `lg`; retain 4:3 and child head around `center 8%`; sparse device focal override.

## Record/template binding and overrides

- Hammersmith owns image/crop/welcome copy; shared title/layout inherited; reset returns exact Hammersmith values only.

## Protected rules

- Valid media/alt, one h2, safe rich text, bounded transform, accurate Ravenscourt/Hammersmith facts.

## Accessibility

- Actual-scene alt, semantic heading/order, contrast, zoom and column-swap reading order pass.

## Empty and error states

- Missing welcome image falls back to Hammersmith hero; empty intro uses last Hammersmith publication and blocks blank output.

## Storage and versioning

- Store `nurseries/{hammersmithUuid}/sections/welcome` with asset/crop/alt/copy/layout/badge/device overrides and crop diff.

## Planned implementation files (future only)

- `am-visual-builder/schema/entities/nursery/sections/welcome.php`
- `am-visual-builder/admin/pages/nursery/sections/WelcomeEditor.jsx`
- `am-visual-builder/runtime/nursery/WelcomeSection.php`
- `am-visual-builder/content/nurseries/hammersmith/welcome.json`

## Acceptance checklist

- [ ] Shape-work image, `center 8%`, badge and exact copy reproduce.
- [ ] Head-safe responsive crop, inheritance, alt, errors, reset, diff and restore pass.

