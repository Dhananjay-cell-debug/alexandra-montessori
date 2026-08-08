# Hounslow — philosophy strip section

## Intent and feel

Retain the short, confident sage-green pause between introduction and gallery; editing should feel constrained enough to protect its visual impact.

## Current public section → exact WP canvas parity

- Third public section is the sage-500 strip with low-opacity grid background, centred circular icon holder using `Sprout`, and one statement: `We build curiosity, independence and confidence through consistent care, affection and respect.`
- Hounslow currently inherits every value from the shared JSX; there is no branch-specific public override.

## Editable elements and controls

- Statement, icon from approved library, icon-holder style, background/grid tokens, section spacing, maximum text width, and approved type scale.
- Default control state says `Inherited`; creating Hounslow-specific copy is an explicit sparse override with before/after preview.

## Layers and dragging

- Exact tree: `Philosophy strip` → `Grid texture`, `Icon badge`, `Statement`.
- Stacking is locked; icon and statement remain centred and cannot become extra cards or columns. The whole current section can reorder only within page-level section rules.

## Responsive behaviour

- Preserve centred layout and 2xl→3xl text scale; long edits surface mobile wrapping/height warnings rather than clipping.

## Record/template binding and overrides

- Shared default belongs to nursery-detail template; optional Hounslow override stores only changed fields at its section path.
- Reset removes the override and immediately previews the inherited statement/icon.

## Protected rules

- One statement, one approved icon, token-only colours, no links/raw HTML, and WCAG-safe contrast.

## Accessibility

- Icon is decorative unless given meaningful supplementary purpose; statement remains normal text, not an image.
- Reduced-motion and zoom to 200% must preserve reading order and visibility.

## Empty and error states

- Empty inherited statement blocks section publication; a corrupt override is ignored in favour of the valid shared value with an admin warning.

## Storage and versioning

- Store sparse overrides at `nurseries/{hounslowUuid}/sections/philosophy`; record template revision used for inheritance.
- Restore can remove or reinstate only the Hounslow override.

## Planned implementation files (future only)

- `am-visual-builder/schema/entities/nursery/sections/philosophy.php`
- `am-visual-builder/admin/pages/nursery/sections/PhilosophyStripEditor.jsx`
- `am-visual-builder/runtime/nursery/PhilosophyStrip.php`
- `am-visual-builder/content/nurseries/hounslow/philosophy.json`

## Acceptance checklist

- [ ] Default equals the current shared Hounslow strip.
- [ ] Override and inherit states are visibly different and reversible.
- [ ] Layer lock, mobile wrapping, contrast, zoom, validation, and restore pass.

