# Careers landing — Gallery preview and modal plan

## 1. Intent and feel
Make photo curation tactile: the client should see the five public tiles, drag images into order, set focus and alt text, and confidently preview the real full-screen gallery without losing context.

## 2. Current React/public evidence and live-canvas parity
Careers.jsx renders one white “View Gallery” section: four square buttons open matching indexes and a fifth dimmed View more tile opens the grid. GalleryModal receives galleryImages, then offers masonry grid, single image, Back, Close, previous/next, keyboard arrows/Escape, counter and scroll lock. The iframe must run these same components.

## 3. Exact editable elements and controls
Expose heading; section/background/padding; grid columns/gap; preview count fixed to the current five-slot pattern unless public JSX changes; media replace/upload/library, drag order, crop focus, focal point and alt text per image; fifth-overlay colour/opacity, Plus icon, View more label; hover zoom; modal background/blur, title, Back label, controls/counter styles and image radius. Provide Manage all gallery images in one sortable tray.

## 4. Layers and reorder rules
Page layers: section > container > heading > preview grid > tiles 1–4 and View-more tile > image and overlay. Modal layers: overlay > top bar > grid or single-view stage > navigation/counter. Images reorder as stable media items; the overlay stays attached to slot five; modal chrome is locked above images.

## 5. Responsive behavior
Preserve current 2/3/5 preview columns and square tiles. Allow bounded gaps/padding and per-image focal point by device. Modal retains 2/3/4 masonry columns, reachable edge controls and max 76vh image; no control may sit off the mobile viewport.

## 6. Data ownership and binding
careersGallery and galleryImages currently come from data/site.js. Migrate them into one page-local ordered gallery collection with stable attachment IDs, URLs, alt text and focal data; generate the first five preview slots from that collection. Media remains in WordPress Media Library.

## 7. Protected functional logic
Lock index mapping, modal portal, open/close state, Back grid behavior, wraparound previous/next, Escape/arrow keys, body scroll restoration and safe asset URL resolution. Deleting below five images must switch editor to a publish-blocked incomplete state rather than causing undefined careersGallery[4].

## 8. Accessibility
Require meaningful per-image alt text, unique descriptive button labels, visible focus, focus containment/restoration in the modal, Escape close, keyboard navigation, dialog name and background inertness. The current modal needs a future dialog/focus-trap hardening task before acceptance.

## 9. Builder state previews
Preview page grid, hover/focus, modal masonry grid, modal single image, first/last wrap, long modal title, missing-media fallback and fewer-than-five validation. Modal preview uses real interactions but suppresses canvas selection shortcuts while open.

## 10. Storage, publishing, and versioning
Store gallery shell and ordered media references under careers-gallery in the revisioned /careers design; never duplicate binaries in JSON. Revisions preserve attachment IDs/order/alt/focal data. Publish readiness checks at least five valid accessible images and uses an atomic snapshot.

## 11. Planned implementation files (future only)
Update Careers.jsx and GalleryModal.jsx with stable region/item hooks and accessible dialog behavior; add media schema/sanitizer in future class-am-vb-careers-design.php; add sortable media tray/focal controls and modal preview orchestration to assets/editor.js; add canvas modal CSS and integration tests for keyboard, indexing and public parity.

## 12. Acceptance checklist
- [ ] Five-tile public preview and full collection use one ordered source.
- [ ] Clicking each tile opens the correct real image in canvas and live page.
- [ ] Keyboard, focus return, scroll unlock and wraparound all work.
- [ ] Alt/focal/media validation blocks unsafe publishing.
- [ ] Revision restore recovers deleted/reordered images without copying files.
