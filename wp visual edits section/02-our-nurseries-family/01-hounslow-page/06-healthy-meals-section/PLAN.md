# Hounslow — healthy meals section

## Intent and feel

Keep the current reassuring nutrition story and two warm activity images while exposing honest branch visibility and dietary wording controls.

## Current public section → exact WP canvas parity

- Sixth public section is rendered because Hounslow does not set `showMealSection` false.
- Left heading group: `Healthy meals & nutrition`, `Freshly prepared meals and snacks`, and the exact allergies/dietary-needs sentence.
- Right grid has `Breakfast` using `practical-kitchen.webp` at `50% 100%` and `Lunch` using `bake-together.webp` at `50% 34%`; each square image has a bottom pill label.

## Editable elements and controls

- Visibility toggle, eyebrow/title/intro, two repeatable meal moments, media/alt/focal/zoom, meal label, column ratio, gap and token presets.
- Dietary claims display a verification note; current shared content can be inherited or overridden specifically for Hounslow.

## Layers and dragging

- Exact tree: `Healthy meals section` → `Heading group`; `Meal image grid` → `Breakfast card` and `Lunch card` → `Image`, `Label`.
- Meal cards can reorder within the two-card grid by pointer/keyboard; label remains attached to its image. Text/image columns can swap only via explicit layout control.

## Responsive behaviour

- Stack major columns below `lg`; keep two square meal cards side-by-side and preserve focal crops.
- Long labels wrap safely without covering the subject; device crop overrides are sparse and previewable.

## Record/template binding and overrides

- Hounslow owns visibility and any factual/copy override; current images/copy inherit the nursery-detail meal template.
- Turning off creates a Hounslow boolean override; it does not remove the template or affect Heston/Hammersmith.

## Protected rules

- Nutrition/allergy claims require authorised confirmation; no medical guarantees or raw HTML.
- Visible cards need valid image, label, alt, and safe crop; destructive media deletion is separate from section removal.

## Accessibility

- Alt describes actual activities; labels are text and maintain contrast. DOM order follows chosen card order.
- Controls and reorder work by keyboard; reduced-motion hover scaling is suppressed.

## Empty and error states

- Visible section with zero valid meal cards is blocked or explicitly converted to text-only through an approved state.
- Broken shared asset uses last published fallback and identifies whether the error is template- or Hounslow-owned.

## Storage and versioning

- Store Hounslow visibility/sparse overrides at `nurseries/{hounslowUuid}/sections/meals`, retaining inherited template revision and asset provenance.
- Version claim confirmation, content, order, crop, and visibility; restore is branch-local.

## Planned implementation files (future only)

- `am-visual-builder/schema/entities/nursery/sections/meals.php`
- `am-visual-builder/admin/pages/nursery/sections/MealsEditor.jsx`
- `am-visual-builder/runtime/nursery/MealsSection.php`
- `am-visual-builder/content/nurseries/hounslow/meals.json`

## Acceptance checklist

- [ ] Current Hounslow section, copy, two assets, labels, and focal positions reproduce.
- [ ] Visibility is Hounslow-specific and inheritance is explicit.
- [ ] Responsive columns, crop, alt, keyboard order, dietary validation, broken asset, reset, and restore pass.

