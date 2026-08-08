# Heston — welcome section

## Intent and feel

Show Heston’s outdoor warmth and branch-specific welcome clearly, with a crop editor designed around the climbing-child portrait.

## Current public section → exact WP canvas parity

- Second public section is image-left/text-right at `lg`, stacked below.
- Heston media is `/assets/organisation/monkey-bars.jpg`, 4:3, crop `center 18%`, no transform override, with `Nursery moments` badge.
- Text is `Welcome to Heston`, `A second home for your child`, and the exact Heston paragraph mentioning prepared environments, home-cooked meals, and secure garden.

## Editable elements and controls

- Media/alt/focal/zoom, badge, heading fields, safe rich text, spacing, gap, column ratio, and approved card tokens.
- Crop overlay highlights head-safe and important-subject zones; branch fact warnings cover meal/garden claims.

## Layers and dragging

- Exact tree: `Welcome section` → `Welcome media card` → `Image`, `Badge`; `Heading group` → `Eyebrow`, `H2`, `Intro`.
- Columns may swap only through a semantic layout toggle; image/badge association is locked.

## Responsive behaviour

- Stack media before text on mobile and preserve two columns at `lg`; maintain 4:3 and keep the climbing child visible around `center 18%`.
- Sparse device focal overrides and long-copy wrapping are previewable.

## Record/template binding and overrides

- Heston owns welcome text/image/crop; shared title/layout inherit the detail template, eyebrow name resolves from Heston.
- Clearing a local override returns the exact Heston values and keeps other branches untouched.

## Protected rules

- One h2, valid image/alt, safe rich text, verified facility claims, and bounded crop/transform.

## Accessibility

- Accurate Heston-scene alt, semantic heading order, logical column order, contrast, and zoom resilience.

## Empty and error states

- Missing welcome image falls back to Heston hero with visible provenance; empty intro uses Heston last-published value and blocks blank publication.

## Storage and versioning

- Store `nurseries/{hestonUuid}/sections/welcome` with asset ID, crop, alt, copy/layout overrides, badge, and device states.
- Crop thumbnail diff and section-only restore.

## Planned implementation files (future only)

- `am-visual-builder/schema/entities/nursery/sections/welcome.php`
- `am-visual-builder/admin/pages/nursery/sections/WelcomeEditor.jsx`
- `am-visual-builder/runtime/nursery/WelcomeSection.php`
- `am-visual-builder/content/nurseries/heston/welcome.json`

## Acceptance checklist

- [ ] Monkey-bars image, `center 18%`, badge, and exact Heston copy reproduce.
- [ ] Subject-safe mobile/desktop crop, claim diagnostics, alt, reset, diff, and restore pass.

