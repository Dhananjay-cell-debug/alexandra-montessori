# Contact Enquiry Form Fields Plan

## Implementation-grade parity contract

### Intent and feel

The form should feel light and respectful while retaining every safeguard needed to deliver a complete, correctly routed enquiry.

### Exact current React/public evidence

`ContactForm.jsx` currently renders hidden honeypot; two-column First Name*/Last Name and Email*/optional Phone; required Branch select; required five-row Message; required consent with `/privacy`; API error; and Submit/Sending labels. It posts to `/wp-json/am/v1/enquiry`, normalizes email, validates phone/email, and accepts event-prefilled message.

### Current public section -> exact WP canvas parity

| Current public truth | Required WordPress canvas result |
| --- | --- |
| Fields/order | Canvas uses the actual current field component and exact public DOM order, required markers, autocomplete values, placeholders/options, textarea rows and consent sentence. |
| Layout/style | Seed `space-y-5`, `gap-5`, two columns from `sm`, bordered white fields with sage focus, non-card presentation and primary submit. |
| Interaction | Run real client validation in preview sandbox and preserve event prefill, selected branch helper and entered values on recoverable error. |

### Editable elements and controls

Public labels, approved help/placeholders, allowed field visibility, flow order/column span, gaps, field/label/focus/error/button visuals, and optional fields from a typed field library. Editors see contract and privacy impact before adding a field.

### Layers, reorder, and dragging

Form > honeypot(system-hidden) > identity grid > branch group > message > consent > error > submit. Public fields reorder only through flow handles with matching DOM order; free drag/absolute positioning is prohibited.

### Responsive behavior

Seed becomes two columns at `sm` and one below it. Device controls may alter column span/gap/button width, never field reading order. Test 320/390px, long email, browser zoom, autofill and mobile keyboard/input types.

### Data ownership and bindings

Presentation copy/style is Contact-form configuration. Branch options bind to Nursery records; brand and Privacy page bind globally. Stable payload keys and submissions belong to the operations API/repository, not the visual page document.

### Protected behavior

Required flags, field names/types/autocomplete, email normalization, PhoneField validation, branch ID, consent, honeypot, loaded timestamp, idempotency key, API endpoint, server sanitization/routing and retention are locked.

### Accessibility

Keep programmatic labels, required semantics, `aria-invalid`, live errors, logical focus, visible focus, correct autocomplete/input types and 44px targets. Error summary must identify fields without erasing inline messages.

### Relevant state previews

Preview blank, focused, filled, browser autofill, invalid email, invalid phone, branch selected, event-prefilled message, sending, API error and consent-missing fixtures with synthetic data only.

### Storage and versioning

Store labels/layout/style in versioned `contact.enquiry_form`; keep the field-contract version beside it for compatibility. Never copy entered values, test emails, submission payloads or operations records into designs/revisions.

### Planned implementation files (future only)

These paths describe later implementation ownership; they are **not created by this planning phase**.

- `includes/definitions/forms/enquiry.php` — stable field contract plus editable presentation schema.
- `includes/renderers/forms/enquiry.php` — accessible form renderer and bindings.
- `assets/editor/forms/enquiry.js` — flow reorder, state fixtures and contract locks.
- `tests/parity/contact/enquiry-form.spec.js` — fields, validation, prefill and keyboard order parity.

### Acceptance checklist

- [ ] The exact current field set, order and API payload keys survive a seed/publish cycle.
- [ ] Visual dragging always matches DOM, tab and error-summary order.
- [ ] Synthetic success/error tests prove routing, idempotency and data preservation without saving test PII in revisions.


## Intent and feeling

The form should feel short, respectful, and predictable. The client can tune its words and presentation without being given controls that can break validation or submissions.

## Current evidence

The form contains First Name (required), Last Name, Email (required with validation), optional Phone, required Branch, required Message, consent, hidden honeypot, and a submit button. It supports an event-prefilled message.

## Visual editor controls

- Form surface, width, padding, border, radius, shadow, field gap, one/two-column breakpoint, label style, field style, focus/error/success colours, and submit-button appearance.
- For each public field: label, help text, placeholder where appropriate, visible/hidden status where contract permits, column span, order within an approved set, and optional icon.
- Add optional approved fields only from a controlled library. New fields require a stable key, data type, validation, privacy classification, export label, and destination mapping.
- Direct dragging reorders fields in flow mode; absolute positioning is prohibited for inputs because DOM, reading, validation, and mobile order must agree.

## Protected contract

Field names, email normalization, phone validation, required branch routing, honeypot, form-loaded timestamp, submission key/idempotency, API action, server validation, storage, notification routing, and sanitization are locked. Required consent cannot be hidden or changed into pre-checked consent.

## Accessibility

Labels remain programmatically associated. Error messages use live alerts and point to fields. Touch targets, focus rings, autocomplete tokens, and correct input types are preserved.

## Acceptance

- Every editable label is reflected in success/error tests and submission exports where applicable.
- Event message prefill survives layout changes.
- Keyboard-only completion works in visual and DOM order.
- A failed network request preserves entered data and presents actionable feedback.
