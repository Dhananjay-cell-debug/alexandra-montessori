# Availability success state plan

## Intent and feel

Success should confirm receipt—not placement—and give the family a useful reference when available. The reset action should have explicit, tested behavior so parents are not surprised by which preferences remain.

## Exact current JSX/public evidence

- When `status === 'sent'`, the form is unmounted and replaced inside the same white card beneath the persistent form header.
- Panel is `mt-7`, sage-50, sage border, rounded-2xl, padded 8 and centred.
- It renders CheckCircle2, H3 `Availability request received`, exact explanatory copy, optional `Reference: {reference}`, and `Send another request` outline button.
- Current code does not move focus to the H3, add a live-status wrapper or announce success explicitly.
- Reset creates a new submission key, refreshes `loadedAt`, clears reference and returns status to idle.
- Because the form remounts, uncontrolled name/email/child-age and PhoneField reset; controlled branch/date/day/session/time state is **not explicitly cleared** and can reappear. The current plan must not claim a completely clean reset.

## Editable controls

- Icon, heading, receipt-not-placement copy, reference-label pattern and reset-button label.
- Panel surface/border/radius/padding/alignment and approved entrance/reduced-motion behavior.
- State previews with and without reference plus current reset result.
- Reset policy selector is developer/schema-controlled: either intentionally preserve scheduling preferences or clear every field; ordinary copy editing cannot change it.
- Optional follow-up actions require separately bound current pages/phones and are not part of current parity.

## Layers, reordering and dragging

Protected order is icon -> H3 -> explanatory copy -> optional reference -> reset button. These remain a centred semantic flow group; no free drag/overlap. The success body replaces only the form body, never the form header or branch-support section.

## Desktop, tablet and mobile

Panel uses the same full card width at all devices, with max-width on body copy. Test 320px, long reference, reference absent, high zoom and reduced motion. Reset button must remain a real reachable button.

## Data ownership and bindings

Success copy/presentation live in the Availability form schema. `reference` comes exclusively from the confirmed API response and is transient. Status/submission key/loadedAt and field values remain runtime state. No real reference is stored in page design or template preview.

## Protected behavior

- Success renders only after a resolved successful API response.
- Copy cannot imply a confirmed nursery place, fee, eligibility or visit.
- Reference token is read-only/system-provided and escaped.
- Reset always generates a new submission key and timing marker.
- Any future clear/preserve policy must reset controlled and uncontrolled fields consistently, including PhoneField.

## Accessibility

Planned implementation must focus or announce the success H3 without trapping focus, while preserving current visual parity. Check icon is decorative beside text. Reference is readable/copyable. Reset has visible focus and native button semantics; entrance animation respects reduced motion.

## State previews and failure handling

Preview with reference, without reference, long reference, reduced motion and post-reset state. Synthetic references are clearly labelled and never saved as real. If success response lacks a valid reference, omit its line exactly as current code. If reset fails internally, retain the success panel rather than reuse an old key unpredictably.

## Storage and versioning

Store only static copy/presentation and a versioned reset-policy enum. Runtime references/status/keys/values never persist in design revisions. Migration seeds current visual copy and records the current mixed reset behavior as a discrepancy requiring an explicit implementation decision.

## Planned implementation files (future only)

- `src/components/availability/AvailabilitySuccess.jsx`
- `src/pages/Availability.jsx`
- `src/lib/availabilityFormState.js`
- `wordpress/mu-plugins/alexandra-visual-builder/assets/panels/availability-success.js`
- `wordpress/mu-plugins/alexandra-visual-builder/tests/availability-success.php`

## Current public section -> exact WP canvas parity

| Current public state | Exact WP canvas requirement |
| --- | --- |
| Sage centred receipt panel below persistent header | Same surface, position, spacing and card ownership |
| CheckCircle + current H3/copy | Same icon, hierarchy and receipt-only wording |
| Optional `Reference: ...` | Same conditional rendering from synthetic/current response fixture |
| `Send another request` | Same outline button and current state transition |
| Mixed current reset (uncontrolled clear; controlled values may persist) | Canvas/test notes expose this accurately; do not claim full reset |

## Acceptance checklist

- [ ] Visual state matches current JSX with/without reference.
- [ ] Success can only follow confirmed API success.
- [ ] Real references and form values never enter design storage.
- [ ] Accessibility improvement announces/focuses success without changing appearance.
- [ ] Reset always rotates submission key/timing.
- [ ] Final clear/preserve policy is explicit and regression-tested for every field.

