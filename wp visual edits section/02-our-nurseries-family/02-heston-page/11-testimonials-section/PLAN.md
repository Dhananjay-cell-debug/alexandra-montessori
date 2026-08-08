# Heston — testimonials section

## Intent and feel

Keep testimonial proof consistent with the homepage while giving the Heston heading a clear local identity.

## Current public section → exact WP canvas parity

- Eleventh conditional section takes the first three shared CMS/static testimonials and renders `What parents say` / `Families love Heston`.
- Three five-star quote cards currently attribute Hammersmith, Heston, and Hounslow; CTA `Read all parent stories` targets `/testimonials`.
- It is intentionally not filtered to Heston.

## Editable elements and controls

- Heston heading/CTA/style; bound list preview. Shared quote editing opens entity editor; changing selection requires explicit homepage-parity break permission.

## Layers and dragging

- Exact tree: `Testimonials section` → `Heading group`; `Bound grid` → three cards → `Stars`, `Quote icon`, `Quote`, `Attribution`; `All stories action`.
- Default shared order locked; any authorised Heston selection has keyboard/pointer parity and stable IDs.

## Responsive behaviour

- One column to `md`, three after; quotes expand without truncation; 200% zoom and no carousel dependency.

## Record/template binding and overrides

- Entities/default first-three selection shared; Heston owns only heading/CTA/presentation unless selection policy explicitly overridden.

## Protected rules

- Preserve homepage match by default; consent/moderation required; stars not freely changed into unsupported ratings.

## Accessibility

- Quote/attribution readable, stars announced once or decorative, focus/contrast/DOM order/reduced motion pass.

## Empty and error states

- Zero hides section exactly; CMS failure uses last moderated published snapshot, never drafts.

## Storage and versioning

- Store `nurseries/{hestonUuid}/sections/testimonials` and selection snapshot IDs/revisions; diff source versus local changes.

## Planned implementation files (future only)

- `am-visual-builder/schema/entities/nursery/sections/testimonials.php`
- `am-visual-builder/admin/pages/nursery/sections/TestimonialsEditor.jsx`
- `am-visual-builder/runtime/nursery/TestimonialsSection.php`
- `am-visual-builder/content/nurseries/heston/testimonials.json`

## Acceptance checklist

- [ ] Current first-three, Heston title, cross-location attribution, and CTA reproduce.
- [ ] Parity guard, consent, responsive expansion, empty/error state, stars accessibility, diff, and restore pass.

