# Hounslow — gallery section

## Intent and feel

Let Hounslow staff curate a vivid but orderly three-image peek inside, with image handling that makes cropping pleasant and prevents accidental cross-branch media changes.

## Current public section → exact WP canvas parity

- Conditional fourth public section renders because Hounslow has three valid gallery strings.
- Heading is `A peek inside` / `Life at our Hounslow nursery`; images, in order, are `painting-close.webp`, `water-pouring.webp`, and `hounslow-hi-vis-restored.webp`.
- Three items use the standard `mt-12` grid with three columns at `sm`; frames are 4:5 by default and 3:4 at `sm`.

## Editable elements and controls

- Multi-upload, media library, add/remove/replace, alt, focal drag, zoom, manual order, section visibility, heading text, gap, frame-ratio and column presets.
- Each image shows Hounslow ownership, publish status, file health, consent/usage note, and device crop preview.

## Layers and dragging

- Exact tree: `Gallery section` → `Heading group` → `Eyebrow`, `H2`; `Gallery grid` → three `Gallery image` records.
- Drag reorders only Hounslow gallery IDs; keyboard move controls mirror it. Images cannot be dragged into Heston/Hammersmith records or outside the grid.

## Responsive behaviour

- Preserve current one-column mobile / three-column `sm` layout and aspect transition.
- Focal overrides may be device-specific; no horizontal scrolling, subject clipping, or layout collapse during image loading.

## Record/template binding and overrides

- Hounslow owns gallery asset order and per-image crop/alt. Heading pattern and grid defaults inherit the detail template.
- Empty gallery array hides this exact section as current JSX does; it does not leave spacing or an editor-authored replacement section.

## Protected rules

- Allowed image MIME/size, unique asset instance IDs, non-empty alt for informative photos, and explicit confirmation before removing published items.
- Removing from gallery does not delete the media-library asset.

## Accessibility

- Alt is editable per image and must describe the actual Hounslow scene; gallery order equals DOM order.
- Keyboard reorder announces positions; reveal motion respects reduced-motion settings.

## Empty and error states

- Zero valid images hides the public section and shows an admin-only empty gallery placeholder/action.
- Broken image is skipped only after last-published fallback fails; editor sees exact asset error and the remaining grid reflows.

## Storage and versioning

- Store ordered gallery item objects at `nurseries/{hounslowUuid}/sections/gallery`, referencing media IDs plus alt, crop, transform, and consent metadata.
- Version ordering and crop thumbnails; restore never changes another Hounslow section or media original.

## Planned implementation files (future only)

- `am-visual-builder/schema/entities/nursery/sections/gallery.php`
- `am-visual-builder/admin/pages/nursery/sections/GalleryEditor.jsx`
- `am-visual-builder/runtime/nursery/GallerySection.php`
- `am-visual-builder/content/nurseries/hounslow/gallery.json`

## Acceptance checklist

- [ ] The three current images and order reproduce exactly.
- [ ] 1/3-column responsive parity and aspect ratios pass.
- [ ] Upload, crop, alt, drag/keyboard order, removal confirmation, hidden-empty state, and restore work.
- [ ] No cross-nursery asset-list mutation is possible.

