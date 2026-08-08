# Vacancy detail — Not-found or closed fallback section plan

## 1. Intent and feel
Turn a stale or closed vacancy URL into a considerate next step: explain briefly, then offer current roles or a general application without implying an error by the visitor.

## 2. Current React/public evidence and live-canvas parity
When no open Job id matches the route, VacancyDetail.jsx renders a white section with one explanatory paragraph and two links: View current vacancies and Send a general application. It is mutually exclusive with the live-role section and follows the Vacancy not found PageHeader.

## 3. Exact editable elements and controls
Expose fallback paragraph; two button labels; validated internal destinations defaulting to /careers/vacancies and /careers/apply; primary/outline styles; section/background/padding/container/spacing; alignment and responsive wrapping. SEO fallback title remains in page settings.

## 4. Layers and reorder rules
Section > centred container > message > action group > current-vacancies action/general-application action. Actions may swap order as whole links, but cannot leave the fallback group. This section cannot coexist with the live-role detail in public output.

## 5. Responsive behavior
Keep actions wrapping with a three-unit gap; allow stacked full-width mobile actions, bounded text width and device padding/type overrides. DOM order follows displayed order.

## 6. Data ownership and binding
Fallback copy and link presentation belong to the vacancy template shell. The state is triggered solely by Jobs lookup/status; the visual builder does not maintain a closed-job list.

## 7. Protected functional logic
Protect the !job condition and safe internal routes. A design cannot force this state on a valid open Job, reveal closed record data, or make both live/fallback sections render. Canonical path remains the requested vacancy URL.

## 8. Accessibility
Use plain explanatory text, distinct descriptive action names, visible focus, 44px targets and adequate contrast. Do not rely on colour alone to distinguish primary/secondary choices.

## 9. Builder state previews
One explicit Vacancy not found/closed fixture selects this actual branch; also test long copy, focus/hover and mobile stacked actions. No fabricated server-error state is published.

## 10. Storage, publishing, and versioning
Store under vacancy-detail-not-found in the shared vacancy-template revisions, with typed internal links and sanitized text. Publish readiness checks both routes and keeps this copy versioned separately from Job status/data.

## 11. Planned implementation files (future only)
Add fallback region/slot hooks to VacancyDetail.jsx; register conditional section schema/link validation in future class-am-vb-careers-design.php; add the real-state switcher to assets/editor.js and fallback parity CSS/tests.

## 12. Acceptance checklist
- [ ] Missing and closed slugs show this exact public branch.
- [ ] Valid open slugs never show it.
- [ ] Both links are valid and keyboard accessible.
- [ ] Mobile action wrapping matches the canvas.
- [ ] Revisions do not contain closed Job content.
