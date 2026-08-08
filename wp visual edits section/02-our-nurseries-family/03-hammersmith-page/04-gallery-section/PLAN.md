# Hammersmith — gallery section

## Intent and feel

Make the smaller two-image Hammersmith gallery feel intentional and centred, not like an incomplete three-card grid.

## Current public section → exact WP canvas parity

- Conditional fourth section renders two images in order: `/assets/organisation/tree-work.webp`, `/assets/organisation/movement-play.webp`.
- Heading is `A peek inside` / `Life at our Hammersmith nursery`.
- Exactly two images select `mx-auto mt-12 grid max-w-3xl gap-4 sm:grid-cols-2`; frames are 4:5 then 3:4 from `sm`.

## Editable elements and controls

- Add/upload/replace/remove, alt/focal/zoom/order, headings, visibility, gap, ratio and columns, with a live count-dependent layout preview and consent/file health.

## Layers and dragging

- Exact tree: `Gallery section` → `Heading group`; `Gallery grid` → two Hammersmith `Gallery image` items.
- Drag/keyboard reorder changes Hammersmith order only; adding third image deliberately transitions to current three-image layout rule.

## Responsive behaviour

- Preserve centred max-w-3xl one/two-column layout for two items and aspect switch; no forced empty third slot.

## Record/template binding and overrides

- Hammersmith owns media/alt/crop/order; template owns count-based grid rules/heading pattern. Empty array hides this section.

## Protected rules

- Safe assets, alt, stable item IDs, consent note, confirmed published removal; media original remains.

## Accessibility

- Actual Hammersmith alt, DOM/visual order, keyboard reorder announcements, reduced motion.

## Empty and error states

- Zero hides section/admin placeholder; one switches to current max-w-md rule; broken item uses last published or clean count reflow with warning.

## Storage and versioning

- Ordered objects at `nurseries/{hammersmithUuid}/sections/gallery`; version crop/order/count-layout resolution and restore independently.

## Planned implementation files (future only)

- `am-visual-builder/schema/entities/nursery/sections/gallery.php`
- `am-visual-builder/admin/pages/nursery/sections/GalleryEditor.jsx`
- `am-visual-builder/runtime/nursery/GallerySection.php`
- `am-visual-builder/content/nurseries/hammersmith/gallery.json`

## Acceptance checklist

- [ ] Two current images/order and centred two-card layout reproduce.
- [ ] One/two/three count rules, upload/crop/alt, reorder, empty/broken state, consent, diff and restore pass.

