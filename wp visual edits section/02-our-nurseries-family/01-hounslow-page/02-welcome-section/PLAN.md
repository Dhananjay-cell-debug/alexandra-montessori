# Hounslow — welcome section

## Intent and feel

Keep the warm two-column “second home” introduction and its candid Hounslow moment, with gentle direct editing and crop protection around the people in the photo.

## Current public section → exact WP canvas parity

- Second public section is a two-column container with image left and `SectionHeading` right.
- Image resolves Hounslow `welcomeImage` `/assets/organisation/teacher-hug.webp`, crop `50% 0%`, transform `scale(1.1) translateX(4%)`, priority loading, 4:3 frame, and `Nursery moments` badge.
- Text is eyebrow `Welcome to Hounslow`, title `A second home for your child`, and the Hounslow welcome paragraph about Montessori practice, natural materials, and key-person care.

## Editable elements and controls

- Media upload/replace, alt, focal drag, zoom/pan, badge label/visibility; eyebrow, title, rich-text intro; column ratio, gap, spacing, and approved card tokens.
- Linked text tokens preview Hounslow name; “inherit template / override Hounslow” appears per field.

## Layers and dragging

- Exact tree: `Welcome section` → `Welcome media card` → `Image`, `Badge`; `Heading group` → `Eyebrow`, `H2`, `Intro`.
- Desktop columns may swap only through a layout toggle; DOM order is recalculated accessibly. Image and badge cannot escape their card.

## Responsive behaviour

- Stack media then text on mobile; preserve side-by-side at `lg`, 4:3 crop, readable measure, and no badge collision.
- Per-device focal position may override `50% 0%`; transform preview must show the full Hounslow subject at every breakpoint.

## Record/template binding and overrides

- Hounslow owns welcome copy, image, crop, and transform. Eyebrow nursery name is a live Hounslow token; shared title/layout inherit the detail template.
- Clearing an override returns exactly to Hounslow’s current approved record values, not generic or Heston assets.

## Protected rules

- Valid media/alt, one section `h2`, safe rich text, and approved crop/transform limits.
- Do not allow the record token to be rebound to another nursery from this page.

## Accessibility

- Informative alt names the Hounslow context without claiming unverified identities; heading precedes intro semantically.
- Column swap preserves logical reading order; badge contrast and zoomed-text resilience pass.

## Empty and error states

- Missing welcome image falls back to Hounslow hero with explicit provenance; broken asset retains last published media.
- Empty intro uses last published Hounslow welcome and blocks accidental blank publication.

## Storage and versioning

- Store `nurseries/{hounslowUuid}/sections/welcome` with sparse text/media/layout overrides, asset ID, crop, transform, alt, and badge state.
- Revision compare includes visual crop thumbnails; restore is isolated to welcome.

## Planned implementation files (future only)

- `am-visual-builder/schema/entities/nursery/sections/welcome.php`
- `am-visual-builder/admin/pages/nursery/sections/WelcomeEditor.jsx`
- `am-visual-builder/runtime/nursery/WelcomeSection.php`
- `am-visual-builder/content/nurseries/hounslow/welcome.json`

## Acceptance checklist

- [ ] Current teacher-hug asset, `50% 0%` crop, transform, badge, and copy reproduce exactly.
- [ ] Mobile/desktop composition and subject-safe cropping pass.
- [ ] Inheritance, direct editing, alt validation, reset, revision thumbnail, and isolated restore work.

