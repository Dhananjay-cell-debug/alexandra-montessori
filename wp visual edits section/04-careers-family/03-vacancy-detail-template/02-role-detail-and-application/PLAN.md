# Vacancy detail — Role detail and application section plan

## 1. Intent and feel
Create a readable, credible role page where applicants can scan facts, understand the role and apply without friction, while editors style the template around live Jobs data.

## 2. Current React/public evidence and live-canvas parity
For a matched open Job, VacancyDetail.jsx renders one white section containing optional metadata chips, optional summary, sanitized fullDescription HTML, benefits parsed from newline text, an Apply for this role block, either an external Apply now link or ApplicationForm selectedJob, and Back to vacancies. The canvas must run this exact section and both application branches.

## 3. Exact editable elements and controls
Expose section/container spacing; metadata chip/icon mapping and styles; bound slot typography for summary/rich content/benefits; literal “Benefits” and “Apply for this role” labels; bullet colour/layout; divider; external-application explanatory text and Apply now label/button; embedded form presentation copy/styles defined for selected-job context; Back label/style. Provide sample Job selector and Manage this Job link. Record content fields appear read-only with source badges.

## 4. Layers and reorder rules
Layers: section > metadata > summary > full description > benefits > application area > Back link. Optional bound blocks disappear when fields are empty. Editors may reorder metadata/summary/description/benefits as whole blocks, but application remains after role information and Back remains last. Within application, heading precedes mutually exclusive external action or embedded form.

## 5. Responsive behavior
Maintain max-w-4xl flow, wrapping chips, two-column benefits at sm and single-column mobile. Allow bounded per-device spacing/type/card width; rich HTML/media must not overflow; external actions and form fields remain tap-friendly.

## 6. Data ownership and binding
Jobs owns id, title, location, jobType, hours, salary, summary/shortDescription, fullDescription, benefits, applyUrl and status. The builder owns literal labels and shell styles only. Embedded submissions remain Applications and automatically attach selected jobSlug/jobTitle/location context.

## 7. Protected functional logic
Protect field fallback order, server-sanitized HTML rendering, benefit newline parsing, optional block conditions, external-link target/rel safety, branch selection from applyUrl, selectedJob hidden context and the full ApplicationForm contract. Visual changes cannot change status, applyUrl or role content; no fake detail section may replace this template.

## 8. Accessibility
Icons are labelled through adjacent text or decorative, chips wrap in reading order, rich content retains semantic headings/lists, external action has clear focus and optional external-context announcement, and form accessibility matches its dedicated plan. Heading levels must not jump after the page h1.

## 9. Builder state previews
Preview complete Job, missing each optional field, long salary/title/content, multiline benefits, external apply branch, embedded-form idle/validation/sending/error/success, and mobile layout. Fixtures never submit or modify Jobs.

## 10. Storage, publishing, and versioning
Store vacancy-detail-content template tokens/styles in a revisioned template-route document; exclude all Job/application payloads. Bindings are schema-checked against the Jobs contract and safely hide unsupported fields. Audit label/external-message/consent edits; publish atomically for every vacancy URL.

## 11. Planned implementation files (future only)
Instrument VacancyDetail.jsx and ApplicationForm.jsx with stable template/slot/state hooks; add binding/schema sanitizer in future class-am-vb-careers-design.php; build Record, Layers and States panels in assets/editor.js; add canvas rich-text/form parity CSS; test optional data, external/internal apply and submissions.

## 12. Acceptance checklist
- [ ] One template publishes consistently across all open Job URLs.
- [ ] Job fields remain authoritative and read-only in visual editing.
- [ ] Optional blocks, benefit parsing and application branching remain exact.
- [ ] Embedded form submits correct selected Job context.
- [ ] Canvas and public pages match in all previewed states/devices.
