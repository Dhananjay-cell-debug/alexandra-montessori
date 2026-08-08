# Hammersmith Location and Gallery Card Plan

## Implementation-grade parity contract

### Intent and feel

The gallery card should provide a small, recognisable sense of place for Hammersmith, with the location label readable over the photograph and no ambiguity about image provenance.

### Exact current React/public evidence

ContactLocation.jsx selects `loc.gallery?.[0] || loc.image`. For the fallback Hammersmith model this is `tree-work.webp`; it renders at `h-40 w-full` with contextual children-at-Hammersmith alt, a bottom black gradient, MapPinned icon, and uppercase `Hammersmith W6 0EU` overlay in the lower 16px inset.

### Current public section -> exact WP canvas parity

| Current public truth | Required WordPress canvas result |
| --- | --- |
| Media fallback | Use first published Hammersmith gallery item (tree-work.webp in the current fallback); if absent, use the bound primary Nursery image. |
| Overlay | Preserve full inset gradient, lower-left MapPinned icon and exact name/postcode token output. |
| Card geometry | Seed overflow-hidden card, square-cornered image within card, fixed 160px image height, full width and current overlay spacing/type. |

### Editable elements and controls

First-item vs named-gallery binding, Media replacement at source, explicit local override, focal point/crop/height/aspect, overlay gradient/opacity, icon, tokenized label, card surface/radius/shadow and optional validated directions link.

### Layers, reorder, and dragging

Card > image > gradient > overlay group(icon + label). Image/gradient stacking is locked; overlay group may move among approved corners/safe offsets and text stays paired. Gallery source items reorder at the Nursery record; choosing first-item mode intentionally follows that order.

### Responsive behavior

Seed stays 160px high across current breakpoints; device overrides can change height/focal point/overlay inset without clipping label. Check subject visibility, contrast and label wrapping at 320/390px and zoom.

### Data ownership and bindings

Gallery, primary image, name and postcode bind to the Hammersmith Nursery record. Overlay pattern/style and selection mode are template/instance-owned. Optional directions URL belongs to a validated location field, not raw design copy.

### Protected behavior

Fallback order, token escaping, sanitized media, responsive image generation and external-link safety. Missing postcode must omit its token cleanly; no invented value or blank linked overlay.

### Accessibility

Meaningful image keeps contextual alt unless deliberately marked decorative with adjacent equivalent context. Overlay text meets contrast, icon is decorative, optional link has a clear name/focus.

### Relevant state previews

Preview current gallery first item, reordered gallery, empty gallery fallback, missing primary image, missing postcode, long branch name, low-contrast crop and optional directions action.

### Storage and versioning

Version selection mode, overlay pattern and styles under `contact_location.hammersmith.gallery_card`. Store Media/record IDs and focal metadata; source gallery revisions remain on the Nursery record with dependency diff/rollback.

### Planned implementation files (future only)

These paths describe later implementation ownership; they are **not created by this planning phase**.

- `includes/definitions/templates/contact-location.php` -- gallery-card binding/fallback schema.
- `includes/renderers/sections/contact-gallery-card.php` -- image, fallback and overlay renderer.
- `assets/editor/templates/contact-location/hammersmith-gallery-card.js` -- focal/overlay and source controls.
- `tests/parity/contact-location/hammersmith-gallery-card.spec.js` -- current image, fallback, tokens and contrast checks.

### Acceptance checklist

- [ ] tree-work.webp and `Hammersmith W6 0EU` reproduce the current seeded card.
- [ ] Empty/reordered gallery behavior follows the selected binding mode and never emits a broken image.
- [ ] Overlay remains legible and inside responsive safe bounds with meaningful accessible media treatment.


## Exact current section

The card uses Hammersmith gallery item 1 (`tree-work.webp` in the current fallback), gradient overlay, map pin, and `Hammersmith W6 0EU` label.

## Editing model

Control image binding/selection, focal point, crop, height, overlay, icon, tokenized label, radius, shadow, and optional directions link. A missing-gallery preview shows the primary-image fallback and record warning.

## Accessibility and acceptance

Overlay contrast passes; meaningful image has contextual alt; token label remains visible at 390px. WordPress and `/contact/hammersmith` use the identical image and crop.
