# Hammersmith — healthy meals conditional slot (off)

## Intent and feel

Represent the truth that Hammersmith currently has no public meals section, while giving authorised editors a clear, evidence-gated way to enable the existing conditional JSX slot later.

## Current public section → exact WP canvas parity

- `showMealSection: false` on Hammersmith means the JSX conditional returns no `<section>` between Features and Meet the team.
- Therefore the public canvas preview must show no meals DOM, spacing, heading, imagery, or placeholder. The page navigator may show a clearly disabled functional slot labelled `Healthy meals — off`.
- This disabled slot is not draggable in the public section stack and is not an invented replacement section.

## Editable elements and controls

- Primary control is `Enable for Hammersmith`, permissioned and accompanied by facility/food-service verification.
- When off, inheritable future fields may be inspected read-only: current template copy, Breakfast/Lunch assets/crops, layout and accessibility completeness; editing them requires enabling a draft.

## Layers and dragging

- Off state has one navigator item, no public layers. If enabled in draft, exact existing tree becomes `Healthy meals section` → `Heading group`; `Meal grid` → meal cards → image/label.
- Enabling inserts it at the exact JSX position between Features and Team; it cannot be placed elsewhere without a separate page-order change.

## Responsive behaviour

- Off state outputs nothing on every device. Draft-enabled preview uses template stacking at `lg` and two square meal cards.

## Record/template binding and overrides

- Hammersmith owns the explicit false override; template owns potential content but does not override false.
- Enable writes an intentional Hammersmith status and, if necessary, local factual/copy/media overrides; it never changes Hounslow/Heston.

## Protected rules

- Publish-enable requires confirmation that meals/services and allergy handling are accurate, complete visible copy, valid assets/alts, and authorised role.
- Template updates may not flip false to true.

## Accessibility

- Off state adds nothing to accessibility tree. Enabled draft must meet heading, alt, label, keyboard order, contrast and reduced-motion requirements before publish.

## Empty and error states

- Off is a valid intentional state, distinct from missing data/error.
- If enabled but content/assets are invalid, publication is blocked and current public off state remains unchanged.

## Storage and versioning

- Store explicit `enabled:false`, reason, reviewer, timestamp, and template revision at `nurseries/{hammersmithUuid}/sections/meals`.
- Enabling/disabling is a high-salience revision event with before/after public-DOM preview and one-section rollback.

## Planned implementation files (future only)

- `am-visual-builder/schema/entities/nursery/sections/meals.php`
- `am-visual-builder/admin/pages/nursery/sections/ConditionalMealsEditor.jsx`
- `am-visual-builder/runtime/nursery/MealsSection.php`
- `am-visual-builder/content/nurseries/hammersmith/meals.json`

## Acceptance checklist

- [ ] Default public output has no meals section or residual spacing on any device.
- [ ] Admin clearly distinguishes intentional off from error/missing data.
- [ ] Template updates cannot enable it; permission/verification gates work.
- [ ] Draft enable inserts exact section at correct position; invalid draft cannot publish; rollback restores off.

