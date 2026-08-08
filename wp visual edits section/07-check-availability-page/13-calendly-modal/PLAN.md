# Availability Calendly modal plan

## Intent and feel

Visit booking should feel like a focused, secure extension of the selected nursery—not an unrelated embedded page. The plan must be honest about today’s temporary Calendly routing and distinguish current behavior from required accessibility hardening.

## Exact current JSX/public evidence

- `CalendlyModal` renders only when `open`; Availability can open it only through the selected-nursery enabled visit button.
- Effect locks `document.body.style.overflow`, closes on Escape and restores overflow/listener on cleanup.
- Backdrop is fixed inset, z-100, ink/60; clicking backdrop closes, clicking the white panel stops propagation.
- Backdrop itself has `role=dialog`, `aria-modal=true` and branch-specific `aria-label`.
- Panel is `h-[90vh] max-w-3xl`, rounded-3xl, with heading `Book a visit to {name}`, Close icon button, and lazy iframe titled `Calendly booking for {name}`.
- Current component has **no focus trap, initial-focus move, inert background or explicit return-to-trigger focus**.
- `calendly.js` currently uses Dhananjay’s temporary free account. All three branches map to the same active `book-a-visit-hounslow` event; `utm_campaign`/`utm_content` distinguish selected nursery. It is inaccurate to claim three active branch-specific events today.
- There is no custom loading/error/cookie-blocked view around the iframe and no consent gate in this component.

## Editable controls

- Existing title token pattern, Close accessible label/icon, backdrop opacity, panel radius/width/height, header padding and border.
- Synthetic preview states: closed and open for each nursery using the current derived URL; loading/error designs remain labelled planned until implemented.
- Optional reviewed loading/unavailable/contact fallback copy only alongside corresponding runtime detection.
- No arbitrary iframe HTML, script or per-page pasted URL.
- Configuration panel clearly shows temporary account/event mapping and readiness for client-owned events.

## Layers, reordering and dragging

Protected modal layers are backdrop -> panel -> header/close -> iframe. Modal is centred and cannot be freely dragged or placed behind global UI. Header stays above iframe. Canvas Select mode does not load/navigate the embed; Interact mode may load an isolated approved URL.

## Desktop, tablet and mobile

Current panel is 90vh with 1rem backdrop padding and max width 3xl at every device. Test 320px, landscape, safe-area, virtual keyboard and zoom. Planned bounds may reduce height safely but cannot clip Close or embed. Background scroll remains locked only while open.

## Data ownership and bindings

Selected nursery is runtime state from `locations`. Calendly account/event mapping lives in protected global booking configuration, not page design. Modal copy/presentation belongs to this component. URLs are generated and allow-listed; UTM contains stable nursery ID/name. No booking data enters the visual builder.

## Protected behavior

- Modal cannot open without a valid selected nursery.
- Block arbitrary origins/scripts and validate generated Calendly URL.
- Preserve Escape, backdrop close and scroll cleanup.
- Before production handoff, replace/approve the temporary account and verify each intended branch event; do not represent current shared-event routing as branch-specific.
- Focus trap/inert/return focus are mandatory future functional fixes, not falsely claimed current parity.

## Accessibility

Keep a named modal dialog and iframe title. Planned implementation moves initial focus to Close/heading, traps Tab, makes background inert and returns focus to the visit button. Close remains 44px-effective with visible focus. If iframe is inaccessible/unavailable, provide a real branch phone/contact fallback.

## State previews and failure handling

Preview closed, open Hounslow/Heston/Hammersmith, current shared-event URLs/UTMs, iframe loading, denied/unavailable and missing configuration. Only closed/open iframe states exist now; loading/error UI requires implementation. A missing/invalid config disables opening and retains the availability form plus contact path. Cleanup restores body overflow even on unmount.

## Storage and versioning

Store modal presentation and copy separately from a versioned global Calendly configuration with owner, event map, validation status and effective date. Revision history highlights account/event changes. Temporary credentials/handles are not editable by ordinary page designers and secrets must never enter client payloads.

## Planned implementation files (future only)

- `src/components/CalendlyModal.jsx`
- `src/lib/calendly.js`
- `src/hooks/useModalFocus.js`
- `src/hooks/useBodyScrollLock.js`
- `wordpress/mu-plugins/alexandra-visual-builder/assets/panels/availability-calendly-modal.js`
- `wordpress/mu-plugins/alexandra-visual-builder/tests/availability-calendly-modal.php`

## Current public section -> exact WP canvas parity

| Current public behavior | Exact WP canvas requirement |
| --- | --- |
| Selected-branch button opens z-100 modal | Same open condition, backdrop and centred 90vh panel |
| Branch name in dialog label/H2/iframe title | Same selected runtime nursery binding |
| Escape/backdrop/Close and body lock | Same current close/cleanup behavior |
| All branches currently use one active Hounslow event | Preview URLs show that exact shared-event mapping plus branch UTM |
| No current focus trap/return/loading/error UI | Canvas docs mark these as discrepancies/planned fixes, not current output |

## Acceptance checklist

- [ ] Current open/close/scroll/iframe visuals match component output.
- [ ] Temporary shared-event mapping is disclosed accurately.
- [ ] Planned client event map validates before replacing current config.
- [ ] Focus trap, inertness and return focus are implemented/tested before claiming parity.
- [ ] Arbitrary iframe origins/scripts remain impossible.
- [ ] Missing/loading/error states preserve a safe branch contact route.
