# Careers landing — Page header plan

## 1. Intent and feel
Give the editor a calm, immediate opening control: a confident Careers title with generous white space, matching the understated public page rather than turning it into a campaign hero.

## 2. Current React/public evidence and live-canvas parity
Careers.jsx renders Seo followed by PageHeader title="Careers". PageHeader.jsx outputs one white section containing a centred h1; no image, eyebrow or breadcrumb is currently rendered. The WP canvas must load this exact section from the real /careers route and select the same DOM node, not a reconstructed preview.

## 3. Exact editable elements and controls
Expose the h1 text; heading font family, weight, size, line height, letter spacing and colour by device; section background; top/bottom padding; container width; and alignment. Put SEO title, meta description, canonical path lock and social image in a separate Page settings tab. Show Reset this device and Reset section actions.

## 4. Layers and reorder rules
Layers are Section background > container > Careers h1. The h1 may be selected directly but cannot be dragged outside its header. The whole header is pinned first so editors cannot accidentally place content above the page h1.

## 5. Responsive behavior
Preview the existing 4xl/5xl breakpoint behavior. Desktop, tablet and mobile may override padding and typography; mobile must preserve centred alignment and avoid forced fixed height. Inherited values are visibly marked.

## 6. Data ownership and binding
The visible title is page-shell content stored with the /careers design. SEO fields are route settings. No Jobs data is read by this section.

## 7. Protected functional logic
Keep one semantic h1, the /careers canonical route and React Seo head emission. Style controls must not replace PageHeader with unrelated markup or add public regions absent from the JSX.

## 8. Accessibility
Enforce WCAG AA contrast warnings, a single h1 check, logical document order and a minimum readable mobile size. Decorative background choices receive no alt control because there is no public media layer.

## 9. Builder state previews
Only the normal section state exists. Preview desktop/tablet/mobile, long-title stress text and contrast warning; do not invent loading or error variants for this static header.

## 10. Storage, publishing, and versioning
Persist under route /careers, section key careers-header, with schema version, responsive overrides and SEO subobject. Use the revision-enabled visual-page draft, published snapshot, autosave recovery, base-hash conflict detection and per-section reset; publishing updates the live React payload atomically.

## 11. Planned implementation files (future only)
Modify Careers.jsx and PageHeader.jsx to expose stable careers-header canvas hooks without changing output; extend the visual-builder route schema/sanitizer in a future class-am-vb-careers-design.php; add the tailored inspector to assets/editor.js and parity styling to assets/canvas.css; add route-contract tests under alexandra-visual-builder/tests.

## 12. Acceptance checklist
- [ ] Canvas and /careers show the same title, spacing and breakpoint values.
- [ ] One h1 remains and contrast checks run.
- [ ] SEO edits preview separately from body text.
- [ ] Save, undo, revision restore and publish survive reload.
- [ ] No extra hero, breadcrumb or media layer appears.
