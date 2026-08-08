# Careers landing — Closing application band plan

## 1. Intent and feel
End the careers story with a focused, confident invitation that returns applicants to the form without feeling repetitive or overly sales-led.

## 2. Current React/public evidence and live-canvas parity
Careers.jsx renders a final sand section with the h2 “Ready to start your career at Alexandra Montessori?” and an anchor link to #apply labelled “Start your application”. This exact section precedes the conditional GalleryModal portal in the component.

## 3. Exact editable elements and controls
Expose heading and button text; section background/padding; heading colour, size, width and spacing; button fill/text/hover/focus colours, radius and padding; alignment; anchor target selector constrained to page anchors with #apply as default; responsive overrides and reset.

## 4. Layers and reorder rules
Layers: section > container > h2 > application anchor. The two content layers may not separate from the closing band. Keep this band last among careers landing content; GalleryModal remains an interaction overlay rather than a draggable public section.

## 5. Responsive behavior
Preserve current 3xl/4xl heading and centred flow. Permit mobile type/padding/button width overrides; button may become full width within a bounded max but must not use fixed height or overlap long headings.

## 6. Data ownership and binding
Heading, button label and styles are careers-page shell content. #apply resolves to the application section's stable DOM anchor, not to a Job or form-submission record.

## 7. Protected functional logic
Anchor integrity is protected: renaming the application section cannot silently break #apply. No arbitrary JavaScript, submission action or external redirect is exposed in this inspector.

## 8. Accessibility
Ensure h2 hierarchy, descriptive link text, visible focus, adequate target size and colour contrast. Smooth scrolling, if later added, must respect prefers-reduced-motion and move focus appropriately.

## 9. Builder state previews
Preview default, hover, focus, long heading, full-width mobile button and missing-anchor validation. This static CTA has no loading/error state.

## 10. Storage, publishing, and versioning
Store under careers-closing-application with a typed internal-anchor reference and responsive styles in the revisioned /careers document. Publish readiness verifies #apply exists and records link changes in audit history.

## 11. Planned implementation files (future only)
Add a stable careers-closing-application hook to Careers.jsx; register its schema and anchor validation in future class-am-vb-careers-design.php; add anchor picker and button-state inspector to assets/editor.js; add canvas parity/focus CSS and anchor integrity tests.

## 12. Acceptance checklist
- [ ] The canvas section is the live closing band, in the same final position.
- [ ] Clicking the published CTA reaches the real #apply form.
- [ ] Mobile text/button remain readable and keyboard accessible.
- [ ] Broken anchor cannot be published.
- [ ] Undo and revision restore recover link and presentation together.
