# Heston — gallery section

## Intent and feel

Let Heston curate its own three-image peek inside with quick visual ordering and precise crops, never sharing mutable image lists with another nursery.

## Current public section → exact WP canvas parity

- Conditional fourth section renders with three Heston images in order: `sensory-box.webp`, `toddler-smile.webp`, `materials-shelf.webp`.
- Heading is `A peek inside` / `Life at our Heston nursery`; grid becomes three columns at `sm`; images are 4:5 then 3:4 at `sm`.

## Editable elements and controls

- Add/upload/replace/remove, alt, focal/zoom, order, visibility, headings, gaps, ratio and approved columns; consent/file health shown per Heston asset.

## Layers and dragging

- Exact tree: `Gallery section` → `Heading group`; `Gallery grid` → three Heston `Gallery image` items.
- Pointer/keyboard reordering changes only Heston’s ordered media IDs; heading and grid remain in their existing section.

## Responsive behaviour

- Preserve one-column mobile/three-column `sm` and aspect switch; allow sparse focal overrides without horizontal scrolling.

## Record/template binding and overrides

- Heston owns asset order, alt, crop; template owns heading pattern/grid defaults. Empty array hides this exact public section.

## Protected rules

- Safe image types/sizes, non-empty informative alt, stable item IDs, confirmation before published removal; removal never deletes media original.

## Accessibility

- Alt describes actual Heston scene, visual/DOM order match, keyboard reorder announces positions, reduced motion respected.

## Empty and error states

- Zero images hides public section and gives an admin-only action; broken item uses last published asset or reflows remaining grid with exact diagnostic.

## Storage and versioning

- Store ordered item objects at `nurseries/{hestonUuid}/sections/gallery`, including media ID, alt, crop, transform, consent.
- Version order/crop thumbnails; isolated restore.

## Planned implementation files (future only)

- `am-visual-builder/schema/entities/nursery/sections/gallery.php`
- `am-visual-builder/admin/pages/nursery/sections/GalleryEditor.jsx`
- `am-visual-builder/runtime/nursery/GallerySection.php`
- `am-visual-builder/content/nurseries/heston/gallery.json`

## Acceptance checklist

- [ ] Current three Heston images/order/headings/layout reproduce.
- [ ] Upload/crop/alt, pointer/keyboard reorder, empty/broken states, consent, reset, and restore pass.
- [ ] No cross-branch gallery mutation occurs.

