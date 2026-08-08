# Careers landing — General application form section plan

## 1. Intent and feel
Let the client tune wording and presentation while the application stays trustworthy, simple and operationally safe. Editing should feel like selecting the real field or message in place, never configuring a database form from scratch.

## 2. Current React/public evidence and live-canvas parity
Careers.jsx renders section id="apply" with ApplicationForm and no selectedJob. ApplicationForm.jsx supplies the real general-applicant form, conditional Other inputs, nursery routing hint, CV upload, consent, sending/error/success states and POST to /wp-json/am/v1/apply. The canvas must use this real component and section anchor.

## 3. Exact editable elements and controls
Allow editing field labels/placeholders/help copy for first name, last name, email, phone, qualification, position, branch, message, CV and consent; select prompt text; general branch prompt; upload idle text; accepted-format note; Submit/Sending labels; success title/body/reference prefix; failure title/body/retry label and support email display; field/background/border/focus/error colours; widths, two-column gap, textarea rows, section padding and form max width. Qualification, position and nursery option values remain data-bound, with separate Manage source links.

## 4. Layers and reorder rules
Layers: section/container > hidden security/context inputs (locked, hidden in normal canvas) > optional context banner slot (not rendered for this page) > identity grid > qualification/position/branch grid > message > CV upload > validation alert > consent > submit. Editor may reorder visible field groups only within protected logical boundaries; hidden inputs, consent-before-submit and error anchoring are locked.

## 5. Responsive behavior
Match the current two-column sm grid and single-column mobile stack. Per-device controls cover max width, gaps, padding and label/field typography; controls may not make tap targets smaller than 44px or clip native select/file focus.

## 6. Data ownership and binding
Presentation copy belongs to section key careers-general-application. Qualification levels and positions currently originate in data/site.js; nurseries come from locations and WordPress is authoritative when its contract exists. Submitted applications and attachments belong to the application operations system, never the visual design document.

## 7. Protected functional logic
Lock field names, required flags, autocomplete semantics, PhoneField/email normalization, Other validation, branch email routing, PDF/DOC/DOCX and 5 MB checks, honeypot/timing fields, consent requirement, idempotency key, 45-second abort and REST endpoint. Text editing must not change submission payloads or expose uploaded filenames in design storage.

## 8. Accessibility
Maintain label/control association, required announcement, aria-invalid, role=alert, keyboard-reachable native inputs, focus order, file rejection announcement and readable privacy link. Warn when labels are blank, duplicate or too long; preserve real form semantics in the iframe.

## 9. Builder state previews
Provide non-submitting fixtures for idle, selected qualification Other, selected position Other, selected nursery routing hint, chosen file, invalid email/phone, rejected file, missing CV, sending, server error and sent-with-reference. State preview never fires the REST request and is visibly marked Preview only.

## 10. Storage, publishing, and versioning
Store only sanitized labels, help text and visual tokens in the revisioned /careers document under careers-general-application; source lists and submissions remain external. Version the form-presentation schema separately so new operational fields receive safe defaults. Publish copy/styles atomically and audit changes to consent/error/support text.

## 11. Planned implementation files (future only)
Add design props/hooks without contract changes in Careers.jsx and ApplicationForm.jsx; define allowed copy/style keys in future class-am-vb-careers-design.php; extend assets/editor.js with a Form states tab and source-management links; add canvas form state fixtures and CSS; expand visual-builder and apply endpoint contract tests.

## 12. Acceptance checklist
- [ ] Builder idle form is pixel-equivalent to /careers#apply.
- [ ] Every real conditional/error/success state can be previewed safely.
- [ ] POST contract, validation, routing and upload limits are unchanged.
- [ ] Mobile order, labels, focus and alerts pass keyboard/screen-reader checks.
- [ ] No application data or attachments enter visual-page revisions.
