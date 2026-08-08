# Vacancy detail — Dynamic page header plan

## 1. Intent and feel
Present each role title with the same composed hierarchy while making it obvious in the editor that the words come from the selected Job, not from template copy.

## 2. Current React/public evidence and live-canvas parity
VacancyDetail.jsx finds an open job by route slug. A live role passes job.title to PageHeader; the missing/closed branch passes “Vacancy not found”. Although crumb="Vacancy" is passed for a live role, PageHeader.jsx currently ignores crumb and renders only the h1 section. The canvas must mirror that exact behavior and must not invent a visible breadcrumb.

## 3. Exact editable elements and controls
Expose header background, padding, alignment, max width and bound h1 typography/colour. Show the title as a locked data token with a sample-Job selector; allow a fallback/not-found title in the States tab. SEO shell controls cover title suffix/pattern, description fallback and social-image fallback; live job values remain read-only.

## 4. Layers and reorder rules
Layers are Section > container > bound Job title h1. Header remains first. The bound title slot cannot be detached, duplicated or converted to static text; no crumb layer appears until the public PageHeader actually renders one.

## 5. Responsive behavior
Use PageHeader's responsive type defaults with per-device size/spacing. Test very long job titles and unbroken acronyms; preserve natural height and centred reading order.

## 6. Data ownership and binding
Jobs owns title, id/slug, summary and optional social context. The visual template owns only typography/layout, SEO patterns and the literal not-found fallback title. Binding displays the currently selected preview Job without storing it.

## 7. Protected functional logic
Protect open-job lookup by id, missing-job branch, single h1, canonical /careers/vacancies/{job.id} and dynamic SEO title/description fallback. Closing a Job must automatically show the existing fallback route, not stale design content.

## 8. Accessibility
Maintain one h1, AA contrast, readable wrapping and no misleading hidden/static title. The selected fixture is announced as preview context, not page content.

## 9. Builder state previews
Offer Bound open role, Long title and Vacancy not found preview states, driven by real/safe fixtures. Do not invent loading/error sections because current lookup is synchronous.

## 10. Storage, publishing, and versioning
Store template shell under /careers/vacancies/:slug section vacancy-detail-header; never store selected Job content. Schema-version SEO patterns and styles in revisioned draft/published snapshots; validate required title token before publish.

## 11. Planned implementation files (future only)
Add stable hooks/token styling to VacancyDetail.jsx and PageHeader.jsx; register vacancy template bindings in future class-am-vb-careers-design.php; add sample-record/state inspector to assets/editor.js and parity CSS; test live and missing slug headers.

## 12. Acceptance checklist
- [ ] Canvas title is the selected Job's real title and cannot be overwritten.
- [ ] Closed/missing preview matches the public fallback header.
- [ ] No breadcrumb is invented.
- [ ] Long titles work on mobile.
- [ ] SEO/canonical bindings remain correct after publish.
