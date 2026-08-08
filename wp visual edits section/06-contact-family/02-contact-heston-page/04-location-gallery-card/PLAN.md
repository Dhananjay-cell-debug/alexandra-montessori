# Heston Location and Gallery Card Plan

## Implementation-grade parity contract

### Intent and feel

The gallery card should provide a small, recognisable sense of place for Heston, with the location label readable over the photograph and no ambiguity about image provenance.

### Exact current React/public evidence

ContactLocation.jsx selects `loc.gallery?.[0] || loc.image`. For the fallback Heston model this is `sensory-box.webp`; it renders at `h-40 w-full` with contextual children-at-Heston alt, a bottom black gradient, MapPinned icon, and uppercase `Heston TW5 9EJ` overlay in the lower 16px inset.

### Current public section -> exact WP canvas parity

| Current public truth | Required WordPress canvas result |
| --- | --- |
| Media fallback | Use first published Heston gallery item (sensory-box.webp in the current fallback); if absent, use the bound primary Nursery image. |
| Overlay | Preserve full inset gradient, lower-left MapPinned icon and exact name/postcode token output. |
| Card geometry | Seed overflow-hidden card, square-cornered image within card, fixed 160px image height, full width and current overlay spacing/type. |

### Editable elements and controls

First-item vs named-gallery binding, Media replacement at source, explicit local override, focal point/crop/height/aspect, overlay gradient/opacity, icon, tokenized label, card surface/radius/shadow and optional validated directions link.

### Layers, reorder, and dragging

Card > image > gradient > overlay group(icon + label). Image/gradient stacking is locked; overlay group may move among approved corners/safe offsets and text stays paired. Gallery source items reorder at the Nursery record; choosing first-item mode intentionally follows that order.

### Responsive behavior

Seed stays 160px high across current breakpoints; device overrides can change height/focal point/overlay inset without clipping label. Check subject visibility, contrast and label wrapping at 320/390px and zoom.

### Data ownership and bindings

Gallery, primary image, name and postcode bind to the Heston Nursery record. Overlay pattern/style and selection mode are template/instance-owned. Optional directions URL belongs to a validated location field, not raw design copy.

### Protected behavior

Fallback order, token escaping, sanitized media, responsive image generation and external-link safety. Missing postcode must omit its token cleanly; no invented value or blank linked overlay.

### Accessibility

Meaningful image keeps contextual alt unless deliberately marked decorative with adjacent equivalent context. Overlay text meets contrast, icon is decorative, optional link has a clear name/focus.

### Relevant state previews

Preview current gallery first item, reordered gallery, empty gallery fallback, missing primary image, missing postcode, long branch name, low-contrast crop and optional directions action.

### Storage and versioning

Version selection mode, overlay pattern and styles under `contact_location.heston.gallery_card`. Store Media/record IDs and focal metadata; source gallery revisions remain on the Nursery record with dependency diff/rollback.

### Planned implementation files (future only)

These paths describe later implementation ownership; they are **not created by this planning phase**.

- `includes/definitions/templates/contact-location.php` -- gallery-card binding/fallback schema.
- `includes/renderers/sections/contact-gallery-card.php` -- image, fallback and overlay renderer.
- `assets/editor/templates/contact-location/heston-gallery-card.js` -- focal/overlay and source controls.
- `tests/parity/contact-location/heston-gallery-card.spec.js` -- current image, fallback, tokens and contrast checks.

### Acceptance checklist

- [ ] sensory-box.webp and `Heston TW5 9EJ` reproduce the current seeded card.
- [ ] Empty/reordered gallery behavior follows the selected binding mode and never emits a broken image.
- [ ] Overlay remains legible and inside responsive safe bounds with meaningful accessible media treatment.


## Exact current section

The location card uses Heston gallery item 1 (`sensory-box.webp` in the current fallback), a bottom gradient, map-pin icon, and `Heston TW5 9EJ` overlay.

## Editing model

Choose bound first-gallery mode or a named Heston image, then control focal point, crop, height/aspect, overlay, label token pattern, icon, radius, and shadow. Optional directions action uses a validated map destination.

## States and accessibility

If no Heston gallery image exists, preview and use the defined primary-image fallback while raising a source warning. Overlay contrast is measured. Postcode/name remain actual text and stay inside mobile safe bounds.

## Acceptance

The same Heston image/crop/overlay renders in WP and public output, with no `undefined` value or illegible overlay in any breakpoint.
