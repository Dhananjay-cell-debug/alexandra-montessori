# Home custom section layer canvas plan

## Intent and feel

Inside a custom section, direct manipulation should feel precise and playful: select a layer, drag it, resize it, type directly and switch device views without losing control. The system must still communicate boundaries, semantic order and the difference between a picture, a logo, a button, a shape and purely visual “tabs”.

## Current evidence

- `CustomHomeItem` supports six types: text, image, logo, button, shape and tabs.
- Items are absolutely positioned inside a relative stage capped at 1440px, with independent desktop/tablet/mobile x, y, width, height and fontSize.
- Images use cover; logos use contain. Missing media shows “Add photo” or “Add logo”.
- Shapes support rectangle, circle, pill and line; buttons have label/href; tabs split newline/pipe labels into styled pills but currently have no tab-panel interaction.
- Layer array order supplies visual stacking; editor exposes Backward, Forward, Duplicate and Delete.
- Sanitizer allows at most 30 items/section and bounded coordinates/sizes/colours/radius.

## Editable elements and controls

- Add-layer toolbar with separate Text, Photo, Logo, Button, Shape and Visual tabs entries; each explains behaviour and accessibility.
- Common: clear layer name, visibility, lock, duplicate/delete, z-order, x/y, width/height and per-device inheritance/copy controls.
- Text: content, colour, font role/size, alignment, line height and safe plain multi-line formatting.
- Image/photo: attachment, alt, cover crop/focal point and radius; Logo: attachment, alt/decorative choice, contain fit and transparent-background guidance.
- Button: label, typed link picker, accessible description, fill/text colour, radius and style-role shortcut.
- Shape: type, fill/border/opacity/radius; decorative and excluded from reading order.
- Visual tabs: newline-delimited labels, gap/pill styling and explicit warning “visual labels only—no content switching”.
- Guides, snap-to-edge/centre/sibling, numerical entry, nudge keys, multi-select alignment/distribution and undo/redo.

## Selection, layers and dragging

Pointer drag updates x/y for the active device; resize handles update width/height; keyboard arrows nudge and Shift accelerates. Crop mode moves media inside a fixed frame. Layer panel drag changes z-order, while DOM/reading order is shown separately and can be intentionally arranged. Locked layers ignore canvas movement but remain selectable from Layers. Prevent handles from escaping the stage and show overflow/overlap warnings in real time.

## Desktop, tablet and mobile

Every layer has explicit geometry for desktop, tablet and mobile. “All devices” applies a value intentionally; “copy desktop to tablet/mobile” creates editable values rather than hidden inheritance. Safe-area guides use actual stage widths. Device changes do not proportionally guess coordinates after the initial template unless the editor invokes a responsive auto-layout helper and confirms the preview.

## Data ownership and bindings

Layer geometry/type/name belongs to its owning Home custom-section record; content/media/link values use the layer’s stable `item.key` in the same Home document. WordPress owns attachments. Global tokens/style roles may be referenced but layer-specific overrides are stored explicitly. No layer can point to another section’s element key.

## Protected rules

- Enforce allowed types, per-section count, bounds, colour/URL sanitization and stable unique IDs.
- Button links cannot publish as `#` or unsafe protocols.
- Shapes stay decorative and cannot carry hidden essential text.
- Visual tabs may not use tab ARIA roles until real tab panels/keyboard behaviour exist.
- Minimum readable text/button sizes and 44px interactive target; logo aspect and photo source preserved.
- Deleting a group/section removes only owned keys after confirmation/revision snapshot.

## Accessibility and failure states

Canvas layer order must expose reading-order warnings when visual and DOM order conflict. Text cannot be baked into images as the only message. Meaningful photos need alt; redundant logos/icons may be decorative. Overlap checks flag hidden focus targets and insufficient contrast. Missing media keeps an editor placeholder and blocks publication only when essential; public fallback remains composed. Corrupt geometry clamps to safe defaults and marks the affected device for review.

## Storage and versioning

Persist numeric device geometry plus semantic content, not generated CSS. Layer IDs/keys are immutable across reorder; duplicate generates new IDs/keys. Store lock/visibility/readingOrder when introduced through a schema migration. Revisions and undo group a drag gesture into one operation, not hundreds of slider snapshots.

## Planned implementation files (future only)

- `src/components/home/CustomHomeItem.jsx`
- `src/lib/homeCustomSections.js`
- `wordpress/mu-plugins/alexandra-visual-builder/assets/canvas/custom-layer-controller.js`
- `wordpress/mu-plugins/alexandra-visual-builder/assets/panels/custom-layer-inspector.js`
- `wordpress/mu-plugins/alexandra-visual-builder/tests/home-custom-layers.php`

## Current public section → exact WP canvas parity

| Current custom-layer output | WP canvas requirement |
| --- | --- |
| Absolute x/y/width/height/fontSize per device | Canvas handles/sliders update the exact CSS variables consumed publicly |
| Photo cover versus logo contain | Same fit, radius, placeholder and resulting crop in iframe/runtime |
| Button label/href and shape variants | Same current element tags, colours and geometry after save |
| Tabs render styled label pills only | Canvas labels them Visual tabs and produces identical non-interactive public markup |
| Array order controls stacking | Layer panel Backward/Forward matches public z/DOM stacking exactly |
| No custom layers by default | Tool does not invent output until a custom section/layer is added |

## Acceptance checklist

- [ ] Six current types render identically between editor iframe and public runtime.
- [ ] Drag/resize/crop/z-order produce bounded, stable per-device values.
- [ ] Duplicate/delete never reuse or orphan element keys.
- [ ] Reading-order, contrast, overlap, alt and button-target issues are surfaced.
- [ ] Visual tabs are never misrepresented as functional accessible tabs.
- [ ] Undo groups gestures and corrupt/missing values recover predictably.

