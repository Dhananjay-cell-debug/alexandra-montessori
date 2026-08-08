# Additional requirements, consent and submit plan

## Intent and feel

The form ending should provide room for useful context, obtain explicit permission to respond, and present one unambiguous submission action. It must not hide legal meaning inside editable styling or imply the request is already confirmed.

## Exact current JSX/public evidence

- `Additional requirements` is an optional four-row, non-resizable textarea named `message` with the exact funding/dietary/settling/nursery-preferences placeholder.
- Required checkbox named `consent` labels the sentence `I am happy for {brand.short} to use these details to respond to my availability enquiry.`
- There is currently no Privacy Policy link inside this consent sentence.
- API error, when present, renders as a red `role=alert` immediately before submit.
- Submit is a real button: `Check availability` while idle, `Sending...` and disabled while sending; it is full width until `sm`, then auto width.

## Editable controls

- Textarea label/placeholder/help and bounded rows/visual style; functional optionality remains explicit.
- Consent sentence via protected `{brand}` and purpose tokens, with review metadata for legal-copy changes.
- Submit idle/sending labels, approved icon choice, primary-button presentation and responsive width.
- Error/consent/submit spacing and state preview in the real form context.
- A privacy link is not exposed as an existing editable element; adding one requires an approved content/markup change and exact destination binding.

## Layers, reordering and dragging

Protected flow is textarea -> consent -> error slot -> submit. These are semantic form controls and cannot be freely dragged or overlaid. Whole-group order changes require a schema-aware move that preserves error proximity and final submit position. Checkbox and sentence remain one label group.

## Desktop, tablet and mobile

Textarea and consent are full width. Submit is `w-full sm:w-auto`; canvas reproduces this exact change. Test 320px, long consent copy, error present, sending label and 400% zoom. Textarea stays usable without horizontal resize handles.

## Data ownership and bindings

`message` and consent state are runtime submission data; brand name is a global token. Copy/presentation live in form schema; purpose/required flag belong to protected privacy contract. API error/status comes from runtime and is previewed with fixtures only.

## Protected behavior

- Consent remains required, unchecked and tied to the stated response purpose.
- Submit remains a form submission button, never an arbitrary link/script.
- Message remains sanitized server-side and optional unless the full contract changes.
- Sending state disables duplicate clicks; error visibility cannot be styled away.
- Do not claim a guaranteed place, quote or booking in button/consent copy.

## Accessibility

Checkbox and full sentence are one accessible label; required state is native. Submit has visible focus and distinguishable disabled state. Error is announced once via `role=alert` and not encoded only in colour. Long legal copy wraps without separating the checkbox.

## State previews and failure handling

Preview empty/filled message, unchecked consent/native validation, idle, sending, API error and long-copy states. A design preview never actually submits. Missing brand token falls back to Alexandra, while invalid legal template blocks publication. Network/server behavior is owned by the next state plan.

## Storage and versioning

Store presentation/copy and legal-review metadata separately from runtime values. Consent-purpose/required changes need a versioned form/privacy contract and approval. Revisions never contain entered messages or checkbox state.

## Planned implementation files (future only)

- `src/components/availability/RequirementsConsentSubmit.jsx`
- `src/pages/Availability.jsx`
- `src/lib/availabilityFormModel.js`
- `wordpress/mu-plugins/alexandra-visual-builder/assets/panels/availability-submit.js`
- `wordpress/mu-plugins/alexandra-visual-builder/tests/availability-submit.php`

## Current public section -> exact WP canvas parity

| Current public element | Exact WP canvas requirement |
| --- | --- |
| Optional four-row Additional requirements textarea | Same label, placeholder, rows and non-resizable full-width frame |
| Required consent with `{brand.short}` | Same required checkbox and exact current sentence purpose |
| No inline Privacy link | Canvas must not pretend one currently renders |
| Conditional red API alert | Same location immediately before submit |
| `Check availability` / disabled `Sending...` | Same button semantics, labels and responsive width |

## Acceptance checklist

- [ ] Current textarea, consent, error slot and submit order match JSX.
- [ ] Consent remains required and its purpose cannot be removed cosmetically.
- [ ] No unsupported privacy-link control is shown as current parity.
- [ ] Sending/error states remain visible and keyboard accessible.
- [ ] Runtime message/consent values never enter design storage.
- [ ] Mobile/long-copy layout remains readable and operable.
