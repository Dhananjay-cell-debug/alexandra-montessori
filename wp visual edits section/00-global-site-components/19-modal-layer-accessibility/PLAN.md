# Global overlay, layer and accessibility plan

## Intent and feel

This is the interaction safety contract behind existing menus and dialogs, not a new visible public section. The client should be able to open, select and edit layered UI without z-index battles, accidental navigation or trapped focus, while the published site remains natural for pointer, keyboard, touch and assistive technology users.

## Current evidence

- Existing layer values include social rail z-40, header z-50, cookie banner z-60 and cookie preferences z-80; dropdowns use z-50 within the header.
- Header menus use outside-click closing; mobile navigation locks body scroll; cookie settings responds to Escape.
- Current visual-builder markup uses `data-am-vb-allow-action="true"` to distinguish real UI actions from edit selection.
- Custom Home layers use movable media/text/frame CSS variables, while fixed system overlays need anchored behaviour.
- Cookie dialog currently lacks a full focus trap/inert/return-focus implementation, and overlapping menus/dialogs need one shared policy.

## Editable elements and controls

- No arbitrary z-index field. Editors choose approved layer roles: page content, floating page utility, sticky navigation, anchored menu, consent banner, modal backdrop/dialog and editor chrome.
- Overlay colour/opacity, dialog surface/radius/max-width and bounded anchor offset are exposed by the owning component.
- Builder mode switch: Select/Edit versus Interact/Preview, always visibly indicated.
- State navigator lists currently open header dropdown, mobile menu, footer disclosure, cookie banner/dialog and any page modal.
- Accessibility inspector: accessible name, expanded/controls relation, current focus, focus order, escape/outside-click behaviour, contrast and scroll-lock state.
- One-click close/reset of preview layers without modifying saved content.

## Selection, layers and dragging

Anchored menus snap to their trigger; modals remain centred/bottom-sheet; fixed consent/social layers stay in safe tracks. Custom page artwork can use its own canvas layer ordering but cannot cross into system roles. Dragging never changes semantic focus order automatically. A layer outline exposes locked parent/child structure and prevents a backdrop from being selected through its dialog.

## Desktop, tablet and mobile

The layer contract is identical semantically across devices while geometry changes: hover menus become touch disclosures, centred dialogs become bottom sheets, safe-area insets apply and mobile scroll lock is verified. Test small landscape height, 400% zoom, virtual keyboard, reduced motion and cookie banner plus open footer/menu combinations. System layers must never create horizontal overflow.

## Data ownership and bindings

Layer-role constants and accessibility behaviour live in code/shared utilities. Owning components store only approved presentation parameters and open state is transient. Builder preview state is session-local. Custom-section z-order is stored within its own bounded canvas and cannot request system-role values.

## Protected rules

- Fixed layer-role order cannot be edited numerically.
- Only one modal focus scope is active; opening a higher modal closes/suspends lower disclosures according to policy.
- Body scroll lock is reference-counted and always restored.
- Modal background becomes inert; focus returns to the invoker.
- Escape never discards unsaved builder changes; it closes the preview layer or selection according to explicit mode.
- Visual order changes do not silently rewrite DOM/focus order.

## Accessibility and failure states

Provide reusable focus trap, return-focus, live announcement and outside-click primitives. Every disclosure has correct expanded/control relationships; every dialog has label/description. If an invoker disappears before close, focus falls back to the nearest stable landmark. If inert is unsupported, use a tested fallback. If scroll-lock cleanup fails, route/breakpoint/unmount hooks force restoration. Reduced-motion eliminates nonessential transitions.

## Storage and versioning

Store a `layerRole` enum only for custom/owned components that require it; core components have code-defined roles. Accessibility mechanics are versioned in code/tests rather than editable data. Any layer-role migration maps old z-index values to enums and discards unsafe arbitrary values while preserving public defaults.

## Planned implementation files (future only)

- `src/lib/layerRoles.js`
- `src/hooks/useModalFocus.js`
- `src/hooks/useBodyScrollLock.js`
- `src/components/OverlayPortal.jsx`
- `wordpress/mu-plugins/alexandra-visual-builder/assets/accessibility-inspector.js`
- `wordpress/mu-plugins/alexandra-visual-builder/tests/layer-accessibility.php`

## Current public section → exact WP canvas parity

| Existing public layer | WP canvas requirement |
| --- | --- |
| Social rail z-40 / header z-50 | Same visual order while selection remains possible |
| Nursery and Parent Info anchored flyouts | Open from real triggers and stay within header layer |
| Cookie banner z-60 | Same fixed bottom dominance over page utilities |
| Cookie dialog z-80 | Same topmost overlay/geometry plus complete focus safety |
| Mobile menu scroll lock | Lock only preview document and restore on every exit path |

## Acceptance checklist

- [ ] No new public overlay is created by this contract.
- [ ] Current layer ordering matches public output exactly.
- [ ] Select/Edit and Interact/Preview modes cannot be confused.
- [ ] Focus trap/return, Escape, inertness and scroll cleanup pass automated/manual tests.
- [ ] Editors cannot create arbitrary z-index conflicts.
- [ ] Custom layers remain bounded below all system overlays.
