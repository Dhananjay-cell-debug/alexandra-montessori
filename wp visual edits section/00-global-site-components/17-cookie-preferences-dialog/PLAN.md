# Cookie preferences dialog plan

## Intent and feel

The preferences dialog should explain choices without legal fog: four plainly described categories, an unmistakably locked Essential switch and three reversible optional choices. The editor may refine factual wording and presentation but must not turn consent categories into arbitrary design layers.

## Current evidence

- Settings opens a z-index 80 full-screen dark overlay with a bottom-sheet posture on small screens and centred modal from `sm`.
- Dialog title/intro, category titles/descriptions and footer action labels are currently hard-coded.
- Categories are Essential (locked on), Functional, Analytics and Marketing; optional choices use `role="switch"`.
- Footer actions are Reject optional, Save choices and Accept all.
- Escape closes the dialog. The current code does not yet implement a complete focus trap, initial focus, background inertness or focus return.
- Saved consent includes version 2, essential true, three optional booleans and `savedAt`.

## Editable elements and controls

- Dialog title and introductory explanation.
- Category display titles and purpose descriptions, with category machine keys locked.
- Footer action labels and close accessible label.
- Overlay opacity, dialog width, radius, header/footer surface, spacing and switch colour from approved token controls.
- Category ordering is policy-locked with Essential first; a developer/legal capability is required to add/remove a category.
- Builder state tools: open with saved choices, all denied, all granted and storage unavailable; simulation remains isolated.

## Selection, layers and dragging

The modal is a protected compound layer: overlay, dialog, header, scrollable category body and action footer. It may not be freely dragged or resized beyond bounded max-width/max-height controls. Text is editable in place; switches remain interactable only in Preview interaction mode. Overlay clicks and Escape behaviour are previewed without closing the entire builder selection unexpectedly.

## Desktop, tablet and mobile

Desktop/tablet centre the dialog with an 88vh cap; small mobile presents it near the bottom with safe-area padding. Category body scrolls independently when needed, while title and actions remain discoverable. Test 320px, short landscape height, 200–400% zoom and long purpose copy. No footer action may be horizontally clipped.

## Data ownership and bindings

Purpose copy/presentation live in the global model. Category keys and consent application logic live in a versioned consent-policy module. Current choices are read from/written to visitor local storage only. Analytics/marketing integrations listen to the normalized runtime consent event; the editor cannot invent executable integrations in this panel.

## Protected rules

- Essential is permanently on/disabled and truthfully described.
- Reject optional, Save choices and Accept all remain present and semantically distinct.
- Category key/purpose changes that alter data processing require elevated review and a consent-version decision.
- Focus cannot escape to the page while modal is open.
- Background is inert/aria-hidden as appropriate; nested overlays must follow the shared layer contract.

## Accessibility and failure states

On open, focus moves to the title or first meaningful control; Tab/Shift+Tab stay within; Escape closes and focus returns to Settings. Dialog has `role="dialog"`, `aria-modal`, labelled title and associated description. Switches expose checked and disabled states plus visible labels. If saved JSON is corrupt/version-mismatched, start optional choices off. If storage writes fail, apply the selection for the current page and explain persistence only if necessary. Missing copy falls back to today’s factual defaults.

## Storage and versioning

Visitor consent remains `am-cookie-consent:v2`. Global dialog copy has its own revisioned schema. Store a `consentPolicyRevision` beside policy copy; only a reviewed material change increments the visitor consent version/re-consent requirement. Cosmetic revisions do not. Existing hard-coded copy becomes immutable fallback seed data.

## Planned implementation files (future only)

- `src/components/cookie/CookiePreferencesDialog.jsx`
- `src/components/cookie/CookieCategorySwitch.jsx`
- `src/lib/cookieConsent.js`
- `src/hooks/useModalFocus.js`
- `wordpress/mu-plugins/alexandra-visual-builder/assets/panels/cookie-preferences.js`
- `wordpress/mu-plugins/alexandra-visual-builder/tests/cookie-preferences.php`

## Current public section → exact WP canvas parity

| Public element/behaviour | WP canvas requirement |
| --- | --- |
| Dark full-screen overlay + white dialog | Same overlay, responsive bottom/centre posture and sizing |
| Cookie settings title/intro | Same current fallback copy and hierarchy |
| Essential, Functional, Analytics, Marketing | Same order, descriptions and locked/optional states |
| Reject / Save choices / Accept all | Same current action order and consent outcomes |
| Escape closes | Canvas preview reproduces close plus improved focus return |

## Acceptance checklist

- [ ] Canvas exactly mirrors all current category/action states by default.
- [ ] Focus trap, initial focus, Escape and return focus pass keyboard testing.
- [ ] Essential cannot be disabled or visually implied to be optional.
- [ ] Long text/zoom/mobile height keeps every choice and action reachable.
- [ ] Policy versus cosmetic revisions are separated.
- [ ] Corrupt/unavailable storage defaults to privacy-preserving choices.

