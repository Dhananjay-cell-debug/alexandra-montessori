# Hammersmith — testimonials section

## Intent and feel

Retain homepage-matched social proof while localising only the section heading to Hammersmith.

## Current public section → exact WP canvas parity

- Conditional section takes first three shared testimonials and renders `What parents say` / `Families love Hammersmith`.
- Three five-star cards are currently attributed Hammersmith, Heston, Hounslow, followed by `/testimonials` action; no branch filtering occurs.

## Editable elements and controls

- Hammersmith heading/CTA/style, read-only bound selection preview; shared quote edit routes to entity and parity-break selection requires permission.

## Layers and dragging

- Exact tree: section → heading; bound grid → three cards → stars/quote icon/quote/attribution; all-stories action.
- Default order locked to homepage; authorised local selection uses stable IDs and keyboard parity.

## Responsive behaviour

- One column until `md`, then three; long text expands, zoom works, no forced carousel.

## Record/template binding and overrides

- Shared entities/first-three selection; Hammersmith owns heading/CTA/style only by default.

## Protected rules

- Homepage parity, consent/moderation, plain quote text, and non-editable unsupported star rating.

## Accessibility

- Quote attribution readable, stars announced once/decorative, focus/contrast/DOM order/reduced motion pass.

## Empty and error states

- Zero hides section; load failure uses last moderated published snapshot.

## Storage and versioning

- `nurseries/{hammersmithUuid}/sections/testimonials`, shared selection snapshot IDs/revisions and source/local diff.

## Planned implementation files (future only)

- `am-visual-builder/schema/entities/nursery/sections/testimonials.php`
- `am-visual-builder/admin/pages/nursery/sections/TestimonialsEditor.jsx`
- `am-visual-builder/runtime/nursery/TestimonialsSection.php`
- `am-visual-builder/content/nurseries/hammersmith/testimonials.json`

## Acceptance checklist

- [ ] Current first-three/Hammersmith heading/cross-location attributions/action reproduce.
- [ ] Parity, consent, responsive text, empty/error, stars accessibility, diff and restore pass.

