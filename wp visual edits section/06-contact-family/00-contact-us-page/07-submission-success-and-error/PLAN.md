# Contact Submission Success and Error States Plan

## Implementation-grade parity contract

### Intent and feel

Submission feedback should immediately remove uncertainty: success is warm and definite; failure is calm, actionable and preserves the parent's work.

### Exact current React/public evidence

On confirmed API success, `ContactForm.jsx` replaces the form with a bordered white panel containing `Message sent`, thank-you text, an optional selected-Nursery sentence, optional `Reference: ...`, and `Send another`. During send the button reads `Sending...`; caught API errors render a red `role=alert` and return to idle.

### Current public section -> exact WP canvas parity

| Current public truth | Required WordPress canvas result |
| --- | --- |
| Success | Use the exact heading/copy/conditional branch/reference/button structure and current `p-10 text-center` panel seed. |
| Sending/error | Disable submit and show `Sending...`; for caught errors keep the form visible and render the friendly API/server message without clearing fields. |
| Reset | `Send another` generates a fresh submission key, refreshes loaded time, clears reference, and returns to idle. |

### Editable elements and controls

Headings, static sentence fragments, token labels, icons, safe fallback action, button labels and state surfaces/spacing/type. Dynamic `{branch_name}`, `{reference}` and error class are read-only tokens.

### Layers, reorder, and dragging

Each state is an alternate root of the same form region, not a draggable page duplicate. Within a state, icon/heading/copy/reference/actions remain semantic flow; actions may reorder only among approved slots.

### Responsive behavior

Panels use natural height and readable padding at mobile; long references/errors wrap. Animations are optional per device and disabled under reduced motion. State swaps must not cause horizontal layout shift.

### Data ownership and bindings

State presentation belongs to enquiry-form configuration. Status, selected Nursery and reference are runtime values; error classification and message fallback come from API/operations. No runtime value is persisted in page design.

### Protected behavior

Only server-confirmed response may enter success; duplicate-send lock, idempotency, retry semantics, data preservation and new-key reset are locked. Editors cannot set the live initial state to fake success.

### Accessibility

Use `role=status`/appropriate live region for progress/success, `role=alert` for failure, focus the state heading after success, retain focus/field associations on failure, and give `Send another` a deterministic return target.

### Relevant state previews

Preview idle, sending, success with Hounslow, success without branch, success with long reference, validation failure, network failure, rate limit and service unavailable using synthetic fixtures.

### Storage and versioning

Version state copy/style under `contact.enquiry_states`; store fixture labels only, never actual references/errors or entered values. Operations submissions keep their own retention/audit lifecycle outside the visual builder.

### Planned implementation files (future only)

These paths describe later implementation ownership; they are **not created by this planning phase**.

- `includes/definitions/forms/enquiry-states.php` — state copy/token schema.
- `includes/renderers/forms/enquiry-states.php` — safe runtime state renderer.
- `assets/editor/forms/enquiry-states.js` — synthetic state switcher and animation controls.
- `tests/integration/contact/enquiry-states.spec.js` — transitions, live regions, reset/idempotency and field-retention tests.

### Acceptance checklist

- [ ] Current sending, success, optional branch/reference and error output reproduce exactly.
- [ ] Failure preserves fields; confirmed success alone replaces the form; reset creates a new key.
- [ ] Every state is understandable by keyboard/screen-reader users and contains no design-stored runtime PII.


## Intent and feeling

After submission, remove doubt. Success should feel human and definitive; an error should preserve effort and clearly explain the next action.

## Current evidence

Success displays `Message sent`, confirmation copy, optional branch name, reference number, and `Send another`. Sending changes the button label. API failure returns an alert and resets to the editable form.

## State editor

Provide explicit canvas state tabs: Idle, Validating, Sending, Success with branch, Success without branch, Recoverable error, Rate-limited, and Service unavailable.

Editable presentation includes state heading, supportive copy, icon, reference label, retry/send-another labels, fallback phone action, surface style, and animation from an approved reduced-motion-safe set.

Dynamic tokens such as `{branch_name}` and `{reference}` are inserted through a token picker and previewed with sample values. They cannot be deleted from the underlying response payload or replaced with unsafe HTML.

## Protected behavior

Status transitions, actual reference, retry rules, deduplication, error classification, and data preservation remain system-owned. The editor cannot write a fake success state into the normal page.

## Accessibility and acceptance

- Status announcements use appropriate live regions and focus moves to the state heading only after submission.
- Reduced-motion users receive no forced animation.
- Error state retains all non-sensitive entered values.
- Send another creates a new submission key and returns focus to the first field.
