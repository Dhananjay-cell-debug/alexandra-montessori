# Events archive — Page header plan

## 1. Intent and feel
Open with a simple, composed News & Events title that works for both a lively archive and an empty period without overpromising current activity.

## 2. Current React/public evidence and live-canvas parity
Events.jsx emits Seo title “Events” and PageHeader title “News & Events”. PageHeader.jsx renders one white centred h1 section. The WP live canvas must load the same /events React route and select this exact DOM section; it must not synthesize an event hero.

## 3. Exact editable elements and controls
Expose h1 text, responsive font/weight/line-height/letter-spacing/colour, alignment, section background, top/bottom padding and container width. Page settings expose SEO title, description and social image; canonical /events is locked.

## 4. Layers and reorder rules
Layers: section background > container > h1. Keep the header first with one h1. No media, eyebrow, breadcrumb or intro layers are offered because the current public PageHeader does not render them here.

## 5. Responsive behavior
Mirror current 4xl/5xl PageHeader values with device overrides and inheritance badges. Long wording wraps naturally; no fixed height is allowed.

## 6. Data ownership and binding
Visible title and SEO shell belong to the /events page design. Event records and archive counts are unrelated and remain in Events.

## 7. Protected functional logic
Preserve one h1, canonical path and React Seo output. Header edits cannot alter whether the empty or populated archive branch renders.

## 8. Accessibility
Enforce AA contrast, a single first-level heading, readable mobile sizing and logical first-content order.

## 9. Builder state previews
Preview desktop/tablet/mobile, long title and contrast warning. Empty/populated data state selection affects the following real section, not this one.

## 10. Storage, publishing, and versioning
Persist as events-header in the revisioned /events visual document with responsive overrides and SEO object. Draft/autosave/published snapshots use base-hash conflict protection and section reset.

## 11. Planned implementation files (future only)
Add stable hooks to Events.jsx and PageHeader.jsx; register events-header in future class-am-vb-events-design.php; add its tailored inspector to assets/editor.js and canvas.css; test public/canvas/SEO parity.

## 12. Acceptance checklist
- [ ] Canvas and live /events header match exactly.
- [ ] One h1 remains first.
- [ ] SEO settings remain route-specific.
- [ ] Mobile wrapping is safe.
- [ ] No invented hero layers appear.
