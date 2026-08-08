# Hammersmith — philosophy strip section

## Intent and feel

Retain the shared sage philosophy statement as a concise pause, while treating any Hammersmith wording as an explicit exception.

## Current public section → exact WP canvas parity

- Third section is sage-500 with grid texture, Sprout badge, and the exact shared statement about curiosity, independence, confidence, care, affection and respect.
- Hammersmith currently inherits all values; no public branch override exists.

## Editable elements and controls

- Statement, approved icon, background/grid/icon tokens, spacing, width and type scale; clear source/override/reset control.

## Layers and dragging

- Exact tree `Philosophy strip` → `Grid texture`, `Icon badge`, `Statement`; stacking locked, no invented subcards.

## Responsive behaviour

- Preserve centred 2xl→3xl statement; long-copy/mobile/zoom warnings prevent clipping.

## Record/template binding and overrides

- Shared template owns current content; Hammersmith stores sparse changes and template revision only.

## Protected rules

- One statement/icon, plain text, token colours, no links/HTML, required contrast.

## Accessibility

- Icon decorative, statement real text, reduced motion and 200% zoom pass.

## Empty and error states

- Empty/corrupt local override falls back inherited; invalid shared data retains last publication and blocks publish.

## Storage and versioning

- `nurseries/{hammersmithUuid}/sections/philosophy`, with separate upstream/local revision provenance and isolated restore.

## Planned implementation files (future only)

- `am-visual-builder/schema/entities/nursery/sections/philosophy.php`
- `am-visual-builder/admin/pages/nursery/sections/PhilosophyStripEditor.jsx`
- `am-visual-builder/runtime/nursery/PhilosophyStrip.php`
- `am-visual-builder/content/nurseries/hammersmith/philosophy.json`

## Acceptance checklist

- [ ] Current inherited strip reproduces.
- [ ] Override/reset, lock, wrap, contrast, errors, diff and restore pass.

