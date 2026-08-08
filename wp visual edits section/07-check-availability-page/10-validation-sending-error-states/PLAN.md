# Availability validation, sending and error states plan

## Intent and feel

Validation and submission feedback should be calm, specific and recoverable: show the family exactly what needs attention, make progress unmistakable, and retain their work if the network or WordPress endpoint fails.

## Exact current JSX/public evidence

- Native required controls handle Full name, Email, Required start date and Consent before the submit handler runs.
- `onSubmit` separately runs required `emailError(...)` and `phoneRef.current.validate()`; email displays inline and PhoneField displays its own alert.
- If either email/phone check fails, no request is sent. Only `emailErr` is set in the parent; phone error is owned by `PhoneField`.
- Before API call, status becomes `sending`, previous API error/email error clear, and button becomes disabled with `Sending...`.
- `submitForm('availability', payload, submissionKey)` posts JSON with `X-AM-Idempotency-Key`.
- A failed response uses its JSON `message` or the generic `Sorry, something went wrong. Please try again, or call us.`; catch renders one `role=alert`, returns status to idle and retains mounted form state.
- Current UI has no spinner, no state-specific rate-limit/service-unavailable copy and no explicit sending live region.

## Editable controls

- Presentation/copy for the one safe API error summary and optional reviewed retry/contact guidance.
- Sending label, approved progress icon/spinner, disabled style and error spacing/colour tokens.
- Preview fixtures for native required, invalid email, invalid phone, sending, generic network failure and a server-supplied safe message.
- Field-specific validation copy remains owned by validation modules; editor can preview but not rewrite algorithms in the layer inspector.
- Technical response bodies, stack traces and endpoint details are never editable/public.

## Layers, reordering and dragging

Field errors remain adjacent to their fields; the API error slot remains immediately before submit. These state layers are conditional flow content, not draggable boxes. Reordering the error after an unrelated control or hiding it is prohibited. State preview toggles alter only iframe fixture state.

## Desktop, tablet and mobile

Errors wrap inside their field/card at all widths. Sending and restored-idle buttons retain current `w-full sm:w-auto` behavior. Test 320px, long safe server message, zoom and focus after validation. No conditional message may cause horizontal overflow or cover consent.

## Data ownership and bindings

Validation algorithms live in `validation.js` and `PhoneField`; network/idempotency behavior lives in `api.js`; status/error are transient React state. The visual form schema owns approved public copy/styles only. Preview fixtures contain synthetic values and never enter submissions/audit logs.

## Protected behavior

- Prevent a second send while `status === 'sending'`.
- Keep one stable submission key across retries until confirmed success.
- Preserve entered values on recoverable API failure.
- Do not allow cosmetic controls to bypass native/email/phone validation or hide an error.
- Sanitize server messages before public display and retain a friendly fallback.
- Error styling cannot make an idle button look disabled or a sending button look active.

## Accessibility

Field errors are associated with controls and announced once. API error retains `role=alert`; sending should gain a polite status announcement without moving focus. Disabled/progress state is conveyed programmatically and not only by colour/spinner. Focus moves to or summarizes the first invalid control under a tested policy.

## State previews and failure handling

Preview only states actually supported plus clearly labelled planned improvements. Current server failures collapse to one alert; rate-limit/offline/server-specific variants require future error classification before exposing separate editable copy. Preview switching never calls the endpoint. If validation configuration fails, use the last known valid rules and block publication.

## Storage and versioning

Store safe state copy/style separately from the versioned validation/API contract. Status, error text from a live response, submission key and entered data are never revisioned. Validation/routing changes require contract-version tests; visual revisions cannot alter retry/idempotency behavior.

## Planned implementation files (future only)

- `src/components/availability/AvailabilityFormStates.jsx`
- `src/pages/Availability.jsx`
- `src/lib/api.js`
- `src/lib/validation.js`
- `wordpress/mu-plugins/alexandra-visual-builder/assets/panels/availability-form-states.js`
- `wordpress/mu-plugins/alexandra-visual-builder/tests/availability-form-states.php`

## Current public section -> exact WP canvas parity

| Current runtime state | Exact WP canvas requirement |
| --- | --- |
| Native required-field blocking | Interaction preview uses the same real inputs/required attributes |
| Inline email and PhoneField alerts | Same location, validation source and `aria-invalid`/alert behavior |
| Disabled `Sending...` button | Same label/disabled styling while status is sending |
| One API `role=alert` then idle retry | Same safe response/generic message and status transition |
| Stable idempotency key across failed retries | Preview documents the rule without persisting/exposing the token |

## Acceptance checklist

- [ ] Section `10-validation-sending-error-states` matches the current validation, sending and error paths in `Availability.jsx` and `api.js`.
- [ ] Duplicate clicks cannot create concurrent sends.
- [ ] Recoverable errors preserve entered values and allow retry.
- [ ] Error/progress states are announced and never hidden cosmetically.
- [ ] Preview fixtures never submit or persist personal data.
- [ ] Planned rate-limit/offline variants are not misrepresented as current behavior.
