# Vacancies archive — Filters, results, cards and states plan

## 1. Intent and feel
Give the client detailed control over the real archive shell while applicants retain a fast, trustworthy way to find open roles. The canvas should make every branch—roles, no match and no open jobs—easy to inspect without confusing presentation edits with vacancy management.

## 2. Current React/public evidence and live-canvas parity
Vacancies.jsx has one cream section. With zero open jobs it shows message plus speculative-application action. Otherwise it shows location/type ChipRows, “Showing n open role(s)”, location headings, JobCard grids, optional Other group, no-filter-match text, and the always-present Back to Careers link. allJobs comes from window.amData.jobs filtered to status=open. The canvas must run this exact section/branch code.

## 3. Exact editable elements and controls
Shell controls: section colour/padding/container; chip default labels “All Locations/All Types”, active/inactive/hover/focus styles, spacing and wrap; result-count sentence template with singular/plural tokens; location/Other heading typography/divider; grid columns/gaps; card border/background/padding; metadata chip styles; summary line clamp; View role label/button styles; no-jobs heading/body and speculative CTA copy/link presentation; no-match copy; Back to Careers label/style. Provide card-shell selection from any fixture card and Manage Jobs deep link, but not record text editors.

## 4. Layers and reorder rules
Exact section layers are filter bar > result count > grouped results/no-match branch > Back link; all-empty branch replaces filters/results but not the section itself. Each group owns heading > card grid > repeated cards > title/meta/summary/action. Editors may reorder top-level shell blocks only within dependency constraints: filters precede derived count/results, action remains inside card, and Back link remains last. Job order/grouping is data logic, not drag-layer order.

## 5. Responsive behavior
Retain wrapping chips and card grids of one column mobile, two at sm, three at lg. Expose bounded columns/gaps/padding and card type sizes per device. Long locations, job types, salaries and titles must wrap without overflow; actions align sensibly when summaries are absent.

## 6. Data ownership and binding
Jobs owns id, status, title, location, jobType, hours, salary, summary/shortDescription and route. Known filter ordering remains preferred, while unknown WP values append. The visual document owns labels, sentence templates and shell styles only. Tokens such as {count}, {location} and record field slots are read-only bindings.

## 7. Protected functional logic
Protect status=open filtering, orderedUnique behavior, location/type exact-match filters, stable grouping, ungrouped Other branch, count grammar, JobCard links to /careers/vacancies/{id}, summary fallback and three-line clamp semantics. No visual edit can publish/close/reorder a Job or turn a token into static fake data. Current code has no async loading/error branch; do not invent one on the public route.

## 8. Accessibility
Chip buttons need visible focus and aria-pressed for active state in the planned hardening; filter rows need accessible group labels; count changes should be polite live status; h2 group hierarchy and link names remain meaningful. Contrast and 44px target checks cover filters/actions; source/visual order remain identical.

## 9. Builder state previews
Use non-persisted Jobs fixtures for: populated mixed groups, one role, no open jobs, filters with no match, ungrouped role, missing optional metadata, long content and singular/plural count. Preview filter clicks must exercise the real React state. State switching never changes Jobs.

## 10. Storage, publishing, and versioning
Store shell under vacancies-archive in the revisioned /careers/vacancies route document. Keep state fixture selection editor-only. Sentence tokens are allow-listed and validated; dynamic records are excluded from JSON/revisions. Published shell version declares compatible Jobs contract version and falls back safely when fields are absent.

## 11. Planned implementation files (future only)
Instrument Vacancies.jsx with stable section/slot/card-template hooks and design-token reads; add route schema/token sanitizer in future class-am-vb-careers-design.php; add archive Layers, States and Manage Jobs panels to assets/editor.js; style real iframe selection in canvas.css; test populated/empty/no-match/group/route behavior.

## 12. Acceptance checklist
- [ ] Every canvas branch is produced by the real Vacancies component.
- [ ] Job edits remain in Jobs and immediately populate the unchanged shell.
- [ ] Filters, grouping, count and card links still work after visual edits.
- [ ] Empty/no-match/ungrouped/missing-field fixtures are accurate.
- [ ] Public and canvas match at desktop, tablet and mobile.
- [ ] Revisions contain no copied Job records.
