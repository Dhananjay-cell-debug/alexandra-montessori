# Heston — philosophy strip section

## Intent and feel

Keep the shared sage philosophy pause visually strong, while making any future Heston-specific wording an obvious, reversible exception.

## Current public section → exact WP canvas parity

- Third section is the sage-500 strip with grid texture, circular Sprout icon, and the exact shared curiosity/independence/confidence statement.
- Heston currently has no override; output is inherited from JSX.

## Editable elements and controls

- Statement, approved icon, icon-badge/background/grid tokens, spacing, text width, and approved type scale.
- Per-field source badge and intentional `Create Heston override` action.

## Layers and dragging

- Exact tree: `Philosophy strip` → `Grid texture`, `Icon badge`, `Statement`; stacking is locked and no new cards/columns are introduced.

## Responsive behaviour

- Preserve centred layout and 2xl→3xl text; long-copy/mobile/200%-zoom warnings prevent clipping.

## Record/template binding and overrides

- Template owns all current values; Heston stores only sparse changed fields and template revision provenance.

## Protected rules

- One plain-text statement, one approved icon, token colours, no links/raw HTML, and safe contrast.

## Accessibility

- Decorative icon is hidden from assistive tech; statement remains readable text; reduced motion and zoom pass.

## Empty and error states

- Empty override falls back to valid inherited copy; corrupt inherited data blocks publish and uses last published output.

## Storage and versioning

- Store at `nurseries/{hestonUuid}/sections/philosophy`; revision history distinguishes template changes from Heston overrides.

## Planned implementation files (future only)

- `am-visual-builder/schema/entities/nursery/sections/philosophy.php`
- `am-visual-builder/admin/pages/nursery/sections/PhilosophyStripEditor.jsx`
- `am-visual-builder/runtime/nursery/PhilosophyStrip.php`
- `am-visual-builder/content/nurseries/heston/philosophy.json`

## Acceptance checklist

- [ ] Current inherited strip reproduces exactly.
- [ ] Source/override/reset states, layer lock, wrapping, contrast, error fallback, and restore pass.

