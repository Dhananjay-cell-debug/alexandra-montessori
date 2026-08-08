# Cookie consent banner plan

## Intent and feel

The first-visit notice should be compact, plain-spoken and balanced: privacy choices are equally reachable, while the banner never masquerades as a marketing CTA. In the builder it must always be inspectable without reading or overwriting the editor’s real browser consent.

## Current evidence

- `CookieBar.jsx` shows the banner when no valid `am-cookie-consent:v2` record exists; it also forces the banner visible inside the visual-builder iframe.
- It is fixed across the bottom at z-index 60 on a white surface with a top border/shadow.
- Current content is a message plus Privacy Policy link and four actions: Settings, Reject optional, Accept all and an X that also applies essential-only consent.
- Main frame, message/link and three text buttons already have visual keys. The banner writes structured consent to `localStorage`, applies data attributes/gtag consent and dispatches `am:cookie-consent-change`.
- Desktop uses one compact row; smaller screens wrap into a centred stack.

## Editable elements and controls

- Notice message, Privacy Policy label and stable Privacy page reference.
- Settings, Reject optional and Accept all labels; close-button accessible label.
- White/neutral banner style token, border/shadow preset, max message width, padding and action gap.
- Button style mapping is role-locked: Settings neutral, Reject equally available, Accept primary; editors may change approved token presets but not make Reject invisible.
- Frame scale/optical offset per device only inside safe bottom bounds; no free detachment from viewport edge.
- Builder-only state tools: show/hide for inspection, simulate no consent, and reset simulation. These must not touch real browser consent.

## Selection, layers and dragging

Message, Privacy link and each action are individually selectable; the action group and banner frame remain responsive flow containers. Reordering choices is restricted to an approved policy sequence and must keep all three primary decisions visible. The banner may not be dragged above page content or behind the social/header layers. Editing clicks select; a clearly marked interaction mode exercises consent callbacks in an isolated preview store.

## Desktop, tablet and mobile

Desktop keeps message and actions in one row when space permits; tablet/mobile wrap without shrinking targets below 44px. Include 320px, landscape and large-text previews plus mobile safe-area bottom padding. Banner height must feed a clearance variable so footer controls and floating actions are not covered.

## Data ownership and bindings

Banner copy/presentation belong to the global component model. Privacy destination binds to a stable published page. Consent decisions belong solely to the visitor’s local consent store and runtime state, never to WordPress content. Builder simulation uses iframe/session-only state separated by origin/key namespace.

## Protected rules

- Essential-only/reject must be as easy to reach as accept; no dark patterns.
- X behaviour is explicit: close means essential-only, reflected in its accessible label.
- Do not remove the Privacy link or all choice actions.
- Consent runtime code, category IDs and data attributes are developer-protected.
- No editing action may invoke a public consent save unintentionally.

## Accessibility and failure states

Logical reading order is message → privacy → settings/reject/accept/close. All buttons have visible focus and clear names; colour is not the only distinction. At 400% zoom the banner remains operable and scrollable. If `localStorage` fails, consent applies for the current page as it does now; public copy remains visible on the next visit. Missing model data uses the current compliant text/labels. If Privacy target is missing, block publication rather than emit a dead link.

## Storage and versioning

Keep visitor schema `am-cookie-consent:v2` independent from the global visual schema. Copy/style revisions cannot reset visitor consent. A material category/purpose change requires a consent-schema/version decision and re-consent plan, not a normal text save. Migrate existing `cookie-*` visual keys from Home v3 to `global.cookieBanner` with compatibility fallback.

## Planned implementation files (future only)

- `src/components/cookie/CookieConsentBanner.jsx`
- `src/components/CookieBar.jsx`
- `src/lib/cookieConsent.js`
- `wordpress/mu-plugins/alexandra-visual-builder/assets/panels/cookie-banner.js`
- `wordpress/mu-plugins/alexandra-visual-builder/tests/cookie-banner.php`

## Current public section → exact WP canvas parity

| Public element/behaviour | WP canvas requirement |
| --- | --- |
| Fixed white bottom banner | Same viewport anchoring, border, shadow and z-layer |
| Current message + Privacy Policy | Same default wording, inline link and route |
| Settings / Reject optional / Accept all / X | Same order, labels, button roles and close semantics |
| Responsive wrapped action row | Same actual breakpoints and target sizes in iframe |
| Banner forced visible in builder iframe | Inspectable without altering real stored consent |

## Acceptance checklist

- [ ] Default canvas and public banner match at desktop/tablet/mobile.
- [ ] Editing copy/style never resets or writes visitor consent.
- [ ] Accept, reject, settings and essential-only close remain equally operable.
- [ ] Banner clearance prevents footer/floating-control obstruction.
- [ ] LocalStorage/privacy-target failure states are safe and explicit.
- [ ] Consent semantics cannot be changed through cosmetic controls.

