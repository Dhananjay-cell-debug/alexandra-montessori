# Vacancies archive — Page header plan

## 1. Intent and feel
Open the archive with an unambiguous, quiet title so applicants immediately know they are viewing live roles rather than general careers information.

## 2. Current React/public evidence and live-canvas parity
Vacancies.jsx emits Seo title “Current Vacancies” and PageHeader with the same title before the cream archive section. PageHeader outputs the real centred white h1 section. Canvas selection must point to that live DOM.

## 3. Exact editable elements and controls
Expose visible heading text and its responsive typography, colour, alignment, container width, background and padding. Page settings expose SEO title/description/social image while canonical /careers/vacancies stays route-locked.

## 4. Layers and reorder rules
Section > container > h1. Keep header first and one h1 only; the archive cannot be dragged above it. No breadcrumb/image layers are offered because none are publicly rendered.

## 5. Responsive behavior
Mirror PageHeader's 4xl/5xl defaults with device overrides and inherited-value indicators. Long headings wrap naturally and mobile avoids fixed height.

## 6. Data ownership and binding
Title and SEO are archive shell settings. Job titles and counts remain Jobs data and do not enter this section.

## 7. Protected functional logic
Keep canonical route, React head output and semantic h1. Do not expose current-open-role count in the title as editable static text.

## 8. Accessibility
Enforce h1 uniqueness, AA contrast, readable mobile size and logical first-heading position.

## 9. Builder state previews
Preview normal, long-title and contrast-warning states across devices. Archive loading/empty fixtures belong to the next real section.

## 10. Storage, publishing, and versioning
Store under /careers/vacancies section vacancies-header with schema-versioned responsive styles and SEO settings in revisioned draft/published snapshots.

## 11. Planned implementation files (future only)
Add stable hooks to Vacancies.jsx/PageHeader.jsx; define vacancies-header in future class-am-vb-careers-design.php; add inspector controls to assets/editor.js and canvas.css; test canonical/title parity.

## 12. Acceptance checklist
- [ ] Canvas and live archive header match exactly.
- [ ] Header remains first with one h1.
- [ ] SEO edits do not alter dynamic job data.
- [ ] Responsive wrapping is safe.
- [ ] Revision restore is exact.
