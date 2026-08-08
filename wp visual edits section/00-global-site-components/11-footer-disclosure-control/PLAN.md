# Footer View more / View less disclosure plan

## Intent and feel

The footer should begin concise and invite exploration without hiding critical contact information. “View more” gently reveals practical links and nursery details; “View less” restores the compact state. The editor needs a trustworthy way to preview both states while preserving one simple, accessible disclosure control.

## Current evidence

- `Footer.jsx` keeps a local `showMore` boolean, initially false on every mount.
- Collapsed state shows a centred rounded “View more” frame below a border; expanded state renders three columns, legal strip and “View less”.
- Both frames already have visual-builder frame keys and responsive move/scale values.
- Current markup gives the outer frame `role="button"` while nesting a real icon button, creating duplicated interaction semantics that should be corrected without changing appearance.
- The ChevronDown rotates for collapse; the expanded content is identified as `footer-more`.

## Editable elements and controls

- “View more” and “View less” labels, optional accessible-label override, approved chevron icon and icon size.
- Button surface, border, radius, padding and hover/focus style presets derived from footer tokens.
- Default public state remains collapsed; an expanded-default option should be developer-gated because it materially changes every page’s footer length.
- Editor state switch: Collapsed / Expanded, plus “pin state while editing” so selecting inner sections does not collapse them.
- Animation duration and easing within a restrained range; zero-motion state always available.
- Frame scale/optical offset controls per device, bounded to the footer’s centre lane.

## Selection, layers and dragging

Each disclosure control is a single selectable component, not a nested button/frame pair. Canvas dragging is constrained horizontally/vertically inside the footer centre lane with a one-click Reset position. Expanded content stays grouped below its control and cannot be dragged above the brand summary. The chevron is locked inside the button.

## Desktop, tablet and mobile

The control remains centred at all widths. Expanded content layout is owned by its section plans, but this plan verifies reveal/collapse at desktop, tablet, mobile and small landscape heights. Motion must not cause page jumps that strand the focused button off-screen. Mobile safe-area and cookie-banner clearance are included.

## Data ownership and bindings

Labels and presentation live in the global footer component record. Open/closed state is transient UI state and is not stored as site content or shared between visitors. The visual builder may keep its preview state in editor session state only. Expanded region IDs are stable and code-owned.

## Protected rules

- Exactly one interactive button per state; no nested interactive element.
- `aria-expanded` and `aria-controls` must match the actual region.
- Collapsing must not discard content or editor changes.
- Button cannot be positioned outside the footer or below system overlays.
- Critical email/hours remain in the always-visible footer summary.

## Accessibility and failure states

The same button may safely toggle label/icon while retaining focus, or paired buttons must transfer focus explicitly. Space/Enter activate natively. Reduced-motion removes animated height/scroll transitions. If expanded content is empty, omit the disclosure control publicly and show a builder explanation. If JavaScript fails, a progressive-enhancement implementation should leave practical footer content reachable rather than permanently hidden.

## Storage and versioning

Store labels/frame presentation under `footer.disclosure`; do not persist visitor open state. Migrate `footer-view-more`, `footer-view-less` and the two frame keys from the existing Home v3 element store. A schema migration can consolidate them into one control while preserving both labels and per-device geometry.

## Planned implementation files (future only)

- `src/components/footer/FooterDisclosure.jsx`
- `src/components/Footer.jsx`
- `wordpress/mu-plugins/alexandra-visual-builder/assets/panels/footer-disclosure.js`
- `wordpress/mu-plugins/alexandra-visual-builder/tests/footer-disclosure.php`
- `src/styles/footer.css`

## Current public section → exact WP canvas parity

| Public behaviour | WP canvas requirement |
| --- | --- |
| Footer starts collapsed | Canvas opens in collapsed preview unless editor pins Expanded |
| Centred rounded View more control | Same label, border, fill, spacing and downward chevron |
| Reveal three columns/legal strip | Expanded preview mounts the exact real footer content |
| Centred View less with rotated chevron | Same position/appearance while using valid button semantics |
| State resets on remount | Public state remains transient; editor-only preview state is not published |

## Acceptance checklist

- [ ] Collapsed and expanded canvas states match public geometry exactly.
- [ ] One valid button exposes correct expanded state and controlled region.
- [ ] Focus survives toggle and keyboard activation is native.
- [ ] Reduced-motion and empty-content states are deliberate.
- [ ] Frame movement cannot detach the control from the footer.
- [ ] Migrated labels/positions survive without persisting visitor state.

