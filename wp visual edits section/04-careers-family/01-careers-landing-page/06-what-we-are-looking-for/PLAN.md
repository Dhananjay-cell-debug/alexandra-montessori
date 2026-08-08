# Careers landing — What we are looking for plan

## 1. Intent and feel
Keep this candidate-values section encouraging rather than gatekeeping, with balanced icon cards and plain, editable language.

## 2. Current React/public evidence and live-canvas parity
Careers.jsx renders a sand section headed “What We Are Looking For?” and maps careersLookingFor into three centred icon/title/text items. The live canvas must select and style that exact second sand card section, distinct from Why work with us.

## 3. Exact editable elements and controls
Expose heading text and typography; background and padding; grid columns/gaps; per-quality icon, icon colour/size, title and description; add/duplicate/delete/reorder; item max width and spacing; reveal timing; reset item/section. Seed current Passion, Teamwork and Continuous learning entries exactly.

## 4. Layers and reorder rules
Layers: section > container > h2 > qualities collection > quality item > icon/h3/paragraph. Dragging reorders stable quality items only inside this collection. This collection cannot be merged with careers benefits even though their visual shells are similar; they remain separate editable regions and data keys.

## 5. Responsive behavior
Retain single-column mobile and three-column sm+ defaults. Allow bounded 1/2/3-column settings, gaps and sizes per device; preserve consistent reading/order and avoid fixed heights that truncate candid descriptions.

## 6. Data ownership and binding
Current content is careersLookingFor in data/site.js. Migrate it into page-local careers-qualities repeatable content, independent of Jobs and independent of careers-benefits, with approved icons by stable name.

## 7. Protected functional logic
Preserve section identity, semantic h2/h3 structure, item stable IDs and Reveal animation. Do not add application filtering or qualification logic: this section is explanatory page content only.

## 8. Accessibility
Hide decorative icons, require non-empty headings, maintain DOM order equal to visual order, warn about contrast, and keep comfortable line lengths. Publish checks reject an item with description but no title.

## 9. Builder state previews
Preview the seeded three-card state, one/six cards, long title/description, removed icon and each device. No asynchronous state is invented for this synchronous content.

## 10. Storage, publishing, and versioning
Store as careers-qualities in /careers visual-page revisions with ordered stable IDs and responsive styles. Keep a migration seed version distinct from careers-benefits. Draft, autosave recovery, published snapshot and audit operate at this section key.

## 11. Planned implementation files (future only)
Update Careers.jsx to consume the exact qualities payload/fallback; add an independent careers-qualities schema in future class-am-vb-careers-design.php; implement its own layer label and collection editor in assets/editor.js; add parity CSS/tests for order and responsive wrapping.

## 12. Acceptance checklist
- [ ] Canvas begins as the current three qualities, not copied benefits.
- [ ] Edits publish only to this public section.
- [ ] Drag order equals keyboard/screen-reader order.
- [ ] Breakpoints handle long content without clipping.
- [ ] Restore returns content, icons, order and style accurately.
