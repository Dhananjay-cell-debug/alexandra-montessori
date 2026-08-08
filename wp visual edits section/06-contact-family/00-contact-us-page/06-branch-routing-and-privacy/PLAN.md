# Contact Branch Routing and Privacy Plan

## Implementation-grade parity contract

### Intent and feel

Routing feedback should make the chosen destination transparent to parents while keeping personal data handling boring, safe and impossible to miswire from a style inspector.

### Exact current React/public evidence

`ContactForm.jsx` populates Branch from `locations`, requires a stable Nursery ID, resolves `selectedLocation`, and displays `This enquiry will be routed to {gmail link}.` Consent is required and links to `/privacy`. The client posts only the branch ID; `alexandra-operations` owns REST ingestion/routing.

### Current public section -> exact WP canvas parity

| Current public truth | Required WordPress canvas result |
| --- | --- |
| Branch options | Resolve current published Nursery IDs/names/areas from the same records used site-wide. |
| Helper/privacy copy | Show the selected Nursery email via `gmailHref` and preserve the exact required consent purpose/link relationship. |
| Backend boundary | Canvas can simulate a routing table with synthetic data, but public submissions still use the existing protected `/am/v1/enquiry` operations path. |

### Editable elements and controls

Branch label/option display pattern, routing-helper sentence fragments, consent presentation fragments, Privacy link label, helper/icon styling and test-panel explanatory copy. Destination changes require editing the Nursery source with elevated permission.

### Layers, reorder, and dragging

Routing helper stays immediately after its select; consent stays immediately before status/submit. These functional groups move only through approved form-flow ordering and cannot become overlays or be dragged outside the form.

### Responsive behavior

Long addresses/emails wrap without horizontal scroll; helper and consent remain adjacent to their controls at 320px/200% zoom. No device-specific override may hide routing or consent meaning.

### Data ownership and bindings

Nursery email/ID comes from Nursery records; Privacy destination from registered page settings; style/copy fragments from form config; actual recipient resolution, storage, notifications and retention from operations.

### Protected behavior

No arbitrary recipient URL/email in the canvas, required unchecked consent, stable branch IDs, authorization, server-side routing/validation, anti-spam/idempotency and PII exclusion from analytics/revisions.

### Accessibility

Select has a persistent label; helper link has a meaningful destination name; consent is one labelled checkbox with visible focus; errors are announced. Do not rely on colour or icon alone to indicate routed branch.

### Relevant state previews

Preview no branch, each of Hounslow/Heston/Hammersmith, missing destination, unpublished branch, invalid Privacy route, synthetic test success and permission-denied source edit.

### Storage and versioning

Version only sentence templates, styles and internal binding IDs under `contact.routing_privacy`. Destination emails remain Nursery-source data; consent-review date/version is auditable; submission PII stays exclusively in the operations store.

### Planned implementation files (future only)

These paths describe later implementation ownership; they are **not created by this planning phase**.

- `includes/definitions/forms/enquiry-routing.php` — routing/privacy binding schema and permissions.
- `includes/renderers/forms/enquiry-routing.php` — selected-branch helper and consent renderer.
- `assets/editor/forms/enquiry-routing.js` — read-only routing matrix and synthetic tests.
- `tests/integration/contact/enquiry-routing.spec.js` — three-branch, missing-address and Privacy-link checks.

### Acceptance checklist

- [ ] Each selected Nursery resolves the correct current source email and stable ID.
- [ ] No designer-level control can redirect a real submission or remove required consent.
- [ ] Preview/revisions/analytics contain no entered parent or child information.


## Intent

Make it obvious to parents which nursery will receive their enquiry, while preventing an editor from accidentally routing personal data to the wrong address.

## Current evidence

Branch choices come from Nursery records. Selecting a branch shows the destination email. Consent links to `/privacy`. Server submission routing is handled outside page presentation.

## Editing experience

- A protected `Routing preview` panel lists Nursery -> destination mapping, record status, and last verification date.
- Editors can change the explanatory sentence and its visual treatment.
- Authorized administrators edit destination addresses at the Nursery source, with email validation and a confirmation test; page designers receive a read-only source link.
- The privacy link label is editable, while its relationship to the registered Privacy page is selected through an internal-page picker rather than raw typing.
- A test-mode submission sends only synthetic data and is visibly labelled.

## Guardrails

- No destination email is stored in the Contact page document.
- Unpublished/missing branch destinations block publication of a form that can select that branch.
- Consent remains explicit, unchecked, and required.
- Personal information never enters revisions, design JSON exports, previews, analytics events, or audit context.

## Acceptance

- Routing matches the chosen Nursery for all published branches.
- Designers cannot bypass protected routing with a visual link change.
- Privacy navigation works from normal, preview, and embedded form contexts.
