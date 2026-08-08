# Hounslow — hero section

## Intent and feel

Preserve the immersive, reassuring first impression while making Hounslow’s photo, message, phone, and booking action directly editable without exposing brittle layout code.

## Current public section → exact WP canvas parity

- First public section: `min-h-[70vh]`, top padding, full-bleed `/assets/organisation/classroom-main.webp`, black 40% overlay, and bottom gradient.
- Exact content order: Home/Our Nurseries/Hounslow breadcrumb; `Quality childcare in Hounslow`; `Montessori Inspired care and education for Babies to 5 Years, in the heart of Hounslow.`; booking button; phone link `0208 001 5165`.
- No explicit Hounslow `heroPosition` exists, so the image component’s default framing is authoritative.

## Editable elements and controls

- Replace/upload hero media, alt text, focal-point drag, zoom, overlay/gradient token presets, height/spacing, breadcrumb labels, tagline, descriptor token choices, CTA label, and phone display.
- Phone and branch/area tokens show linked-record provenance; previews cover actual crop behind the fixed navigation.

## Layers and dragging

- Exact tree: `Hero` → `Background image`, `Dark overlay`, `Bottom gradient`, `Content` → `Breadcrumb`, `H1`, `Descriptor`, `Actions` → `Book visit`, `Phone`.
- Layer order for image/overlays/content is protected. Text/action layers can be selected and actions reordered only inside their row with keyboard parity.

## Responsive behaviour

- Maintain minimum 70vh and `pt-24`; actions stack mobile and align in a row at `sm`.
- Mobile focal override is allowed only when explicit; safe-area preview ensures subject, h1, and actions are not hidden by nav or viewport edges.

## Record/template binding and overrides

- Hounslow owns image, tagline, area, phone, accessible description, and sparse crop override; global brand owns the current age phrase unless explicitly switched to record age.
- Section geometry inherits the nursery-detail template. Hounslow overrides are field-level and visibly revertible, never a forked whole template.

## Protected rules

- One `h1`, valid `/nurseries/hounslow` breadcrumb context, validated telephone link, safe media MIME/size, approved overlay contrast, and working booking action.
- Slug/record identity and link destinations cannot be edited as free text.

## Accessibility

- Meaningful hero alt, breadcrumb nav label, visible focus, 44px actions, adequate text contrast, and reduced-motion handling.
- Decorative overlays stay hidden from assistive technology.

## Empty and error states

- Broken image uses last published media then approved fallback with editor warning; it never leaves white text on no background.
- Empty tagline/phone blocks publication or falls back to Hounslow’s last published record, never another branch.

## Storage and versioning

- Store at `nurseries/{hounslowUuid}/sections/hero`: asset ID, crop/transform, alt, text overrides, style tokens, device overrides, and action order.
- Version asset and crop together; preview draft separately from the published Hounslow revision.

## Planned implementation files (future only)

- `am-visual-builder/schema/entities/nursery/sections/hero.php`
- `am-visual-builder/admin/pages/nursery/sections/HeroEditor.jsx`
- `am-visual-builder/runtime/nursery/HeroSection.php`
- `am-visual-builder/content/nurseries/hounslow/hero.json`

## Acceptance checklist

- [ ] Untouched output matches the current Hounslow hero and default crop.
- [ ] Upload, focal drag, zoom, overlay preview, action order, and reset work on all devices.
- [ ] Hounslow phone/area never cross-bind; one h1, contrast, focus, and reduced motion pass.

