# Parent and child details field-group plan

## Intent and feel

The first form group should feel straightforward and respectful: only the contact facts needed to reply, plus an optional plain-language child age. Editing should improve clarity without letting a visual change break validation or the WordPress submission payload.

## Exact current JSX/public evidence

- A hidden `website` honeypot is the first form input; it is `tabIndex=-1`, autocomplete off, `aria-hidden` and `hidden`.
- The visible grid is one column and `sm:grid-cols-2` with 1.25rem gap.
- Full name is required, named `name`, autocomplete `name`, placeholder `Parent or carer name`.
- `PhoneField` is required, starts with GB then all supported countries, formats/validates with `libphonenumber-js`, and submits a hidden E.164 value named `phone`.
- Email is required/type email/autocomplete email, normalized by `normalizeEmail`, validated by `emailError`, and shows an inline `role=alert` with `aria-invalid`.
- Child age is optional free text named `childAge`, placeholder `Example: 2 years`.

## Editable controls

- Visible labels, required-marker presentation, placeholders and optional help copy.
- Approved field/border/focus/error styles, label spacing, group gap and one/two-column layout at the existing breakpoint.
- Flow order among the four visible fields only through a schema-aware reorder control that moves visual and DOM order together.
- State previews for empty, focus, filled, browser autofill, invalid email, invalid phone and disabled/read-only demonstration.
- Phone country/dial list is a data-driven functional control, not a design repeater.

## Layers, reordering and dragging

Fields are semantic flow blocks, not free-positioned layers. Drag handles may reorder whole visible field records only after payload/focus-order validation; keyboard Move up/down must match. Country selector and phone input stay grouped inside `PhoneField`. The hidden honeypot is invisible in the visual canvas and appears only as a locked Security item in the structure panel.

## Desktop, tablet and mobile

Default is one column below `sm`, two columns from `sm`; the phone subgrid also uses container-query behavior based on its actual width. Test 320px, long country names, error copy, autofill and 200–400% zoom. No field may overflow or lose its label.

## Data ownership and bindings

Presentation/copy live in the Availability form schema; stable names/types/required/autocomplete values bind to the `/wp-json/am/v1/availability` payload. The global validation and `PhoneField` modules own normalization rules. Entered personal data remains runtime/submission data and is never saved in page-design revisions or preview fixtures.

## Protected behavior

- Lock field names `name`, `phone`, `email`, `childAge` and hidden `website`.
- Keep name/phone/email required; keep email normalization and phone E.164 submission.
- Do not expose honeypot content, make it focusable or remove it through design controls.
- Block raw HTML/scripts and placeholder-only labelling.
- A data-schema change requires backend migration and submission-contract tests, not a style save.

## Accessibility

Every visible field has a programmatic label; placeholders are supplemental. Required state is both visible and native/programmatic. Email/phone errors are associated and announced, focus remains on the field, and country selector has its current `Country dialling code` accessible label.

## State previews and failure handling

Preview current empty state, valid filled values, invalid/empty phone, invalid/disposable/typo-suggested email, browser-native required errors and narrow phone layout. Validation failure retains all entered values. Missing validation assets block preview publication and use the last valid rules rather than accepting unvalidated data.

## Storage and versioning

Store copy/presentation separately from a versioned protected field contract. Each field has stable ID; reorder updates a list, not names. Design revisions contain sample placeholders only. Backend/form-schema versions are recorded so a future field migration cannot silently reinterpret stored submissions.

## Planned implementation files (future only)

- `src/components/availability/ParentChildDetailsFields.jsx`
- `src/components/PhoneField.jsx`
- `src/lib/availabilityFormModel.js`
- `src/lib/validation.js`
- `wordpress/mu-plugins/alexandra-visual-builder/assets/panels/availability-contact-fields.js`
- `wordpress/mu-plugins/alexandra-visual-builder/tests/availability-contact-fields.php`

## Current public section -> exact WP canvas parity

| Current public/control behavior | Exact WP canvas requirement |
| --- | --- |
| Full name required/autocomplete name | Same label, placeholder, type and required behavior |
| Required international PhoneField | Same country list, formatting/error and hidden E.164 payload |
| Required normalized email + inline alert | Same validation/error timing and copy source |
| Optional child-age free text | Same non-required field and placeholder |
| Hidden `website` honeypot | Locked structure record; never visible/focusable on canvas/public page |

## Acceptance checklist

- [ ] Default fields, labels, placeholders and grid match current JSX.
- [ ] Visual reorder also updates DOM order without changing payload names.
- [ ] Email/phone validation behaves identically in preview and public form.
- [ ] Personal preview values never persist in design storage.
- [ ] Honeypot/security behavior remains untouched.
- [ ] Mobile, zoom, autofill and error states remain readable and operable.

