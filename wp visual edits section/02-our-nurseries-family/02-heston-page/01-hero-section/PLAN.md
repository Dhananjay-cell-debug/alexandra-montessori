# Heston — hero section

## Intent and feel

Retain the welcoming photo-led Heston opening, with direct control over its branch message and actions while preserving safe contrast and route identity.

## Current public section → exact WP canvas parity

- First public section uses `/assets/organisation/friends-two.webp` full bleed, default hero framing, black 40% overlay, bottom gradient, `min-h-[70vh]`, and `pt-24`.
- Exact order/copy: Home/Our Nurseries/Heston breadcrumb; `Quality childcare in Heston`; `Montessori Inspired care and education for Babies to 5 Years, in the heart of Hounslow.`; booking action; phone `0203 627 6707`.
- `heart of Hounslow` is current output because Heston record `area` is Hounslow; WP preview must show it until the record is deliberately edited.

## Editable elements and controls

- Hero media/alt/focal/zoom, overlays, height/spacing, breadcrumb labels, tagline, descriptor tokens, action labels/order, and linked phone.
- A fact preview labels `Heston name`, `Hounslow area`, and global brand age source separately to avoid accidental replacement.

## Layers and dragging

- Exact tree: `Hero` → `Background image`, `Dark overlay`, `Bottom gradient`, `Content` → `Breadcrumb`, `H1`, `Descriptor`, `Actions` → `Book visit`, `Phone`.
- Background/overlay/content stacking is locked; actions reorder only inside their row with keyboard equivalent.

## Responsive behaviour

- Preserve 70vh/top offset; action stack below `sm`, row above it. Crop previews account for fixed navigation and safe viewport.
- Device crop override is optional and sparse; no content can be hidden per device.

## Record/template binding and overrides

- Heston owns image, tagline, area, and phone; descriptor age currently inherits global brand. Template owns geometry/overlay defaults.
- Branch overrides are field-level; reset returns to Heston defaults, never Hounslow imagery/phone.

## Protected rules

- One h1, stable Heston breadcrumb route, validated tel link, safe image MIME/size, approved contrast, and functioning modal trigger.

## Accessibility

- Informative alt, breadcrumb nav label, visible focus, 44px targets, logical order, and reduced-motion behaviour.

## Empty and error states

- Broken image retains last published Heston asset then safe fallback; empty required copy/phone blocks publish or uses Heston’s own last value.

## Storage and versioning

- Store `nurseries/{hestonUuid}/sections/hero` with asset/crop/alt, text/style overrides, device overrides, and action order.
- Asset/crop revision thumbnails and isolated restore are required.

## Planned implementation files (future only)

- `am-visual-builder/schema/entities/nursery/sections/hero.php`
- `am-visual-builder/admin/pages/nursery/sections/HeroEditor.jsx`
- `am-visual-builder/runtime/nursery/HeroSection.php`
- `am-visual-builder/content/nurseries/heston/hero.json`

## Acceptance checklist

- [ ] Current Heston image, descriptor sources, phone, overlays, and default crop reproduce.
- [ ] Crop/upload, action order, device preview, validation, focus/contrast, reset, and restore pass.
- [ ] “Heston” and area “Hounslow” remain independently owned facts.

