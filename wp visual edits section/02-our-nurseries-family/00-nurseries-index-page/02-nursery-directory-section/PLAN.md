# Nurseries index — nursery directory section

## Intent and feel

Preserve the current friendly white directory: circular child portraits, clear branch facts, and an obvious route into each nursery. Growth controls should remain simple even if more branches are added later.

## Current public section → exact WP canvas parity

- This is the second and final public `<section className="bg-white py-14 sm:py-16">` in `Nurseries.jsx`; directory grid, repeated cards, and zero-record CTA all stay inside it.
- Populated layer parity: `container-wide` grid with 1 column by default, 2 at `sm`, 3 at `lg`; each location renders circular image link, name-pill link, address, age/hours line, and `More details ->` link.
- Image resolves `cardImage || image`; crop resolves `cardPosition || "50% 30%"`; route resolves `/nurseries/{loc.id}`. Hammersmith currently uses the explicit crop `50% 16%`.
- Zero-record parity: a centred `Contact our team` button to `/contact` appears before the empty grid; no replacement illustration or invented content renders.

## Editable elements and controls

- Section/template controls: background, spacing, width, breakpoint columns, gaps, alignment, hover/reveal preset, and manual/alphabetic ordering.
- Bound card controls: record picker/status, display name, card image, accessible description, focal-point drag, zoom, address visibility, age/hours visibility, CTA label, and route preview.
- State tab previews `Populated`, `No published nurseries`, `Record load error`, and `Incomplete card`; the empty action exposes only current message/action styling and internal destination.
- Upload/replace uses media library, file validation, original restore, crop-safe preview, and per-device override indicator.

## Layers and dragging

- Exact tree: `Nursery directory section` → conditional `Empty action`; `Nursery collection` → `Nursery card [record]` → `Image`, `Name`, `Address`, `Age & hours`, `Details link`.
- Drag cards only to alter directory order; stable record IDs, content, and routes do not move between records. Internal semantic order is fixed though each layer remains selectable.
- Keyboard reorder mirrors pointer drag and announces the new collection position; visual order equals DOM/tab order.

## Responsive behaviour

- Default parity is 1/2/3 columns with the current 176px→192px circular image sizing and naturally wrapping facts.
- Controls prevent horizontal scrolling, clipped circles, unreadable card widths, and touch targets below 44px.
- Per-device image focal overrides are sparse; mobile/tablet otherwise inherit desktop crop.

## Record/template binding and overrides

- Collection membership and factual fields bind to published `nursery:{uuid}` records; the page owns only ordering, visibility references, and shared card presentation.
- Card image falls back `cardImage → hero image`; crop falls back record `cardPosition → card-template default`. Explicit record/instance overrides carry a badge and can revert independently.
- Removing a card from this directory never deletes the nursery record. Slug is immutable here and only previews the derived route.

## Protected rules

- A published card requires stable ID/slug, name, valid internal detail route, and a valid image or approved fallback.
- No arbitrary HTML/CSS/JS; token ranges protect contrast and spacing. Hiding facts is explicit, permissioned, and never blanks the underlying record.
- The empty state activates only from runtime collection state; editors cannot drag it into populated output.

## Accessibility

- Meaningful image descriptions, descriptive duplicate link names, visible focus, sufficient contrast, and reduced-motion hover behaviour are mandatory.
- Address and age/hours remain readable text, not image content. Broken optional data omits cleanly with no orphan punctuation.
- Reorder controls work without dragging; layer and canvas selection expose record name and status.

## Empty and error states

- True zero records reproduces only the current centred `/contact` action in this public section, paired with the intro state above.
- Load failure uses the last published directory snapshot where safe, otherwise the same contact action with an editor diagnostic; it is logged distinctly from zero records.
- Missing image uses approved fallback; missing optional text creates an admin completeness warning and stable public card geometry.

## Storage and versioning

- Store layout/template at `pages/nurseries/sections/directory`, ordering by stable UUID, and sparse instances at `pages/nurseries/cardInstances/{uuid}`.
- Nursery-owned facts/assets remain on the entity; version asset ID, crop, transform, alt text, visibility, order, and template tokens with field-level provenance.
- Restore supports directory layout/order without rolling back the underlying nursery records.

## Planned implementation files (future only)

- `am-visual-builder/schema/pages/nurseries/directory.php`
- `am-visual-builder/schema/entities/nursery/card-fields.php`
- `am-visual-builder/admin/pages/nurseries/DirectorySectionEditor.jsx`
- `am-visual-builder/admin/components/BoundNurseryCard.jsx`
- `am-visual-builder/admin/components/FocalPointCropControl.jsx`
- `am-visual-builder/runtime/pages/nurseries/DirectorySection.php`

## Acceptance checklist

- [ ] Default public output matches the one current white section and its exact internal order.
- [ ] 1/2/3-column parity, circular masks, current copy, links, and Hammersmith crop are preserved.
- [ ] Card dragging changes order only; no record, field, slug, or route contamination occurs.
- [ ] Zero, load-error, missing-image, and incomplete-record states behave distinctly and safely.
- [ ] Upload, crop, device preview, keyboard reorder, focus, reduced motion, reset, revision compare, and restore pass.

