# Careers landing — Why work with us plan

## 1. Intent and feel
Preserve the warm sand pause and clear three-benefit rhythm while making each promise, icon and card spacing enjoyable to edit directly.

## 2. Current React/public evidence and live-canvas parity
Careers.jsx renders one sand section headed “Why work with us?” and maps careersBenefits from data/site.js into three centred icon/title/text items. The canvas must target that exact section and repeat shell on /careers.

## 3. Exact editable elements and controls
Expose heading text/style/alignment; section background/padding; grid columns/gaps; for each benefit, icon picker, icon size/colour, title, description, widths and vertical spacing; add, duplicate, delete and drag-card controls; reveal delay/order; card-level reset. Preserve an optional maximum of six items to prevent an unusable editor/public grid.

## 4. Layers and reorder rules
Layers: section > container > heading > benefit collection > benefit item > icon/title/description. Items reorder as whole records; title and description may reorder only inside their item with explicit controls. The collection cannot be dragged into the gallery or application form.

## 5. Responsive behavior
Match current single-column mobile and three-column sm+ grid. Permit 1/2/3-column choices per device, bounded gaps, icon scale and type sizes; order must remain identical across devices unless an explicit accessible device-order override is enabled.

## 6. Data ownership and binding
The current source is careersBenefits in data/site.js. In the planned builder it becomes page-local repeatable careers benefit content because it is presentation content, not a Job record collection. Migration seeds the exact current three items once.

## 7. Protected functional logic
Keep stable item IDs, safe approved icon names, Reveal behavior and semantic h2/h3 hierarchy. Do not bind these cards to Jobs or turn them into links unless the public JSX is deliberately extended later.

## 8. Accessibility
Decorative icons are hidden from assistive technology; headings retain hierarchy; card order matches reading order; contrast warnings cover sand/background and text/icon colours. Empty titles are publish blockers.

## 9. Builder state previews
Preview normal, one-item, six-item, long-copy, missing-icon fallback and desktop/tablet/mobile grid states. There is no live loading/error state because the current collection is synchronous page content.

## 10. Storage, publishing, and versioning
Store stable item IDs and ordered item data under careers-benefits in the /careers revisioned design. Migration metadata records the static seed version; revisions restore order and deleted items; published snapshots are sanitized and bounded.

## 11. Planned implementation files (future only)
Update Careers.jsx to read a careersBenefits shell payload with exact fallback; add schema/migration/sanitization in future class-am-vb-careers-design.php; add collection/layer controls to assets/editor.js; add responsive parity selectors to assets/canvas.css; test seed parity and repeat-item operations.

## 12. Acceptance checklist
- [ ] Initial seeded canvas exactly matches the current three cards.
- [ ] Add/duplicate/delete/reorder updates the same public section.
- [ ] Heading hierarchy, reading order and icon accessibility remain correct.
- [ ] Long text and all supported item counts remain stable on mobile.
- [ ] Undo/revision restore recovers item content and order.
