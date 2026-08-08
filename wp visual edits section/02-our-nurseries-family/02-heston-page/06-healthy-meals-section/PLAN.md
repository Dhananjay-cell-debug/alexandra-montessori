# Heston — healthy meals section

## Intent and feel

Support Heston’s current home-cooked-meals promise with clear nutritional copy, branch visibility, and pleasant image/crop controls.

## Current public section → exact WP canvas parity

- Sixth section renders because Heston’s `showMealSection` is not false.
- Current heading/intro describe healthy meals, snacks, allergies, and dietary needs; right grid shows Breakfast `practical-kitchen.webp` at `50% 100%` and Lunch `bake-together.webp` at `50% 34%`.
- Both images are square with bottom pill labels.

## Editable elements and controls

- Visibility, copy, repeatable meal cards, upload/alt/focal/zoom, labels, order, columns and tokens; claim verification and source badges.

## Layers and dragging

- Exact tree: `Healthy meals section` → `Heading group`; `Meal image grid` → `Breakfast card`, `Lunch card` → `Image`, `Label`.
- Reorder remains inside Heston; columns swap only via semantic layout control.

## Responsive behaviour

- Stack main columns below `lg`, keep two image cards side-by-side, protect focal positions and label visibility; sparse device crop allowed.

## Record/template binding and overrides

- Heston owns visibility/factual overrides; current visuals/copy inherit template. Turning off is branch-only and does not delete inherited content.

## Protected rules

- Allergy/nutrition claims require confirmation; valid asset/alt/label and safe crop; no raw medical guarantees.

## Accessibility

- Accurate alt, readable label contrast, visual/DOM order match, keyboard reorder, and reduced motion.

## Empty and error states

- Zero cards requires approved text-only state or disabling; broken shared asset uses last publication with source-specific warning.

## Storage and versioning

- Store at `nurseries/{hestonUuid}/sections/meals` with visibility, sparse overrides, claim review, order, crop, and inherited template revision.
- Branch-only revision/restore.

## Planned implementation files (future only)

- `am-visual-builder/schema/entities/nursery/sections/meals.php`
- `am-visual-builder/admin/pages/nursery/sections/MealsEditor.jsx`
- `am-visual-builder/runtime/nursery/MealsSection.php`
- `am-visual-builder/content/nurseries/heston/meals.json`

## Acceptance checklist

- [ ] Current Heston copy/assets/labels/crops and visible conditional reproduce.
- [ ] Claim review, branch visibility, responsive crop, keyboard order, errors, reset, and restore pass.

