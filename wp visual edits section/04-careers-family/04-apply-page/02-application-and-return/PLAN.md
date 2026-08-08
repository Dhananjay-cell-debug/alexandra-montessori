# Apply page — Application, unavailable-role notice and return section plan

## 1. Intent and feel
Make the dedicated application page feel calm, explicit and forgiving: context is clear, invalid role links do not dead-end, and the full application can be completed confidently.

## 2. Current React/public evidence and live-canvas parity
Apply.jsx renders one white section. If a job query exists but has no match, it shows a polite unavailable-role paragraph. It then renders ApplicationForm selectedJob={job}, followed by Back to vacancies. The form displays a selected-job banner/read-only position for valid context or position/branch selectors for general context, plus validation, CV upload, sending/error/success branches. Canvas must run this exact section/component.

## 3. Exact editable elements and controls
Expose unavailable-role notice; selected-job banner prefix/styles; all labels/placeholders/help/state copy and visual controls listed for the real ApplicationForm; dedicated-page section padding/max widths; Back to vacancies label/style. General and selected-role copy may have separate variants. Show Manage Jobs/Nurseries links beside read-only sources, and token badges for selectedJob.title/location.

## 4. Layers and reorder rules
Section > optional unavailable notice > form > optional selected-job banner/hidden context > identity grid > qualification plus position or branch > message > CV > alert > consent > submit > Back link. Notice condition, hidden fields, semantic field group order, consent-before-submit and final Back link are protected. Visual reordering is limited to safe field groups and always changes DOM order too.

## 5. Responsive behavior
Keep current sm two-column form and mobile single column. Provide bounded form width, gap, padding and button-width controls per device; long Job/location, filenames, errors and consent wrap without horizontal scrolling.

## 6. Data ownership and binding
Jobs owns selected role fields/status; Nurseries owns location/name/email options; controlled qualification/position sources remain operational lists until deliberately migrated. Applications owns submitted values/files/reference. Only shell copy/styles are stored in the apply visual document.

## 7. Protected functional logic
Protect query lookup/fallback, jobSlug/jobTitle hidden context, read-only selected position, general branch routing, Other fields, email/phone validation, required CV formats/5 MB cap, consent, spam/timing fields, idempotency header, timeout, REST endpoint and success reference. Preview cannot issue submissions; labels cannot rename payload keys.

## 8. Accessibility
Preserve labels, autocomplete, required/invalid/alert semantics, phone control behavior, keyboard focus, privacy link and status announcements. When success/error replaces the form, focus should move to its heading in future hardening; source badges are editor-only.

## 9. Builder state previews
Offer General idle, Valid job banner, Unknown job notice plus general form, Qualification Other, Position Other, Branch selected, file chosen/rejected, validation errors, sending, server error, success with/without reference. All use inert fixture data and the real conditional rendering.

## 10. Storage, publishing, and versioning
Store page-specific form presentation under apply-application-section in /careers/apply revisions. Reusable copy may inherit from a global form-presentation base but this page keeps explicit overrides; inherited status is visible. Operational records/files are excluded. Schema migrations preserve new field defaults and audit consent/support text.

## 11. Planned implementation files (future only)
Instrument Apply.jsx and ApplicationForm.jsx with page-specific region/state hooks and safe design props; define allow-listed copy/style/state schema in future class-am-vb-careers-design.php; add form/query state controls to assets/editor.js and canvas.css; test form contract, routing, uploads, focus and parity.

## 12. Acceptance checklist
- [ ] Every actual query/form state is previewable without a network submission.
- [ ] Valid Job context and unknown-Job fallback remain correct.
- [ ] Form endpoint, field names, validation, routing and file rules are untouched.
- [ ] Canvas/public match at all devices, including long errors and filenames.
- [ ] Revisions store no applicant, Job or attachment payload.
