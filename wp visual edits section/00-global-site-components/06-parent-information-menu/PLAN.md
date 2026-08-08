# Desktop Parent Information menu plan

## Intent and feel

The circular desktop menu button should act as a tidy second navigation tier for practical parent resources, keeping the main row uncluttered. Its white panel should feel like part of the same soft, rounded system as the nursery flyout, with editing focused on useful link management rather than decoration.

## Current evidence

- At `lg` and wider, `Navbar.jsx` shows a circular Menu/X button after the availability CTA.
- Clicking toggles a right-aligned 16rem white panel; clicking outside closes it.
- `parentInfoLinks` currently supplies Fees, Fee Calculator, Funded Childcare, Blog and Food Hygiene Rating.
- Active items use brand green/white; inactive items use deep-sage copy with a pale-green hover.
- These same five records also feed mobile Parent Info and footer quick links, so ownership must be explicit.

## Editable elements and controls

- Menu accessible name and optional visible tooltip; the icon itself stays from the approved icon set.
- Ordered resource collection: label, internal published-page reference or safe external URL, visibility, target behaviour and optional accessible-label override.
- Add an existing page/custom link, reorder, archive and restore; show “also used in mobile menu/footer” impact badges.
- Button size, border opacity, surface opacity and panel style from bounded token presets.
- Panel width, item density and right-edge alignment within collision-safe bounds.
- Interaction preview: closed, open, active child, long-label and broken-link states.

## Selection, layers and dragging

Items use vertical outline dragging with keyboard alternatives. The panel is anchored to its button and cannot be free-dragged; only a constrained x/y optical offset is allowed with viewport collision detection. The Menu/X control and its flyout form one logical component in the layers tree. Links must not be dragged into the primary-navigation collection without an explicit “Move to primary menu” command.

## Desktop, tablet and mobile

This panel renders from the desktop breakpoint upward. Its shared link data powers the separate mobile Parent Info view and, unless decoupled explicitly, footer quick links. Editors preview long labels at 1024/1280/1440 widths. On narrow or zoomed desktop, the panel clamps inside the viewport and remains scrollable if its content grows beyond available height.

## Data ownership and bindings

Use one `parentInformationMenu` collection with stable item IDs and route references. Desktop presentation settings live under this component; mobile/footer may reference the same collection with their own presentation overrides. A change summary must say every consumer affected. The five current static records become migration defaults.

## Protected rules

- The toggle remains a real button with stateful Menu/X icon, not a link-shaped div.
- Disallow unsafe protocols, blank visible labels and duplicate stable IDs.
- Menu must remain above page content and below cookie/preferences system layers.
- Closing the menu cannot navigate or lose unsaved editor state.
- Removing a menu item does not delete its WordPress page.

## Accessibility and failure states

Expose `aria-expanded`, a controlled panel ID and an accessible open/close label. Escape and outside click close; focus returns to the trigger when closed from within. Focus must not be lost when the icon changes. If the collection is empty, retain the button only in the editor with a clear empty-state prompt; public rendering may omit it. Broken targets show a blocking publish warning, while a missing global record falls back to the five current links.

## Storage and versioning

Persist the ordered shared resource collection in the global menu schema and desktop-only surface controls in `parentInfo.desktop`. Record references by page ID plus resolved route. Revisions report collection operations in readable terms. Schema migration must preserve link identity when a label or slug changes.

## Planned implementation files (future only)

- `src/components/navigation/ParentInfoMenu.jsx`
- `src/lib/navigationModel.js`
- `wordpress/mu-plugins/alexandra-visual-builder/assets/panels/parent-info-menu.js`
- `wordpress/mu-plugins/alexandra-visual-builder/includes/class-am-vb-menus.php`
- `wordpress/mu-plugins/alexandra-visual-builder/tests/parent-info-menu.php`

## Current public section → exact WP canvas parity

| Public behaviour | WP canvas requirement |
| --- | --- |
| Circular Menu/X toggle after availability CTA | Same size, order, icon state, border and green-bar context |
| Right-aligned rounded white panel | Preview the real anchored panel, not a generic menu mock-up |
| Five `parentInfoLinks` records | Show the same five labels/targets and active state in initial data |
| Outside pointer closes panel | Canvas interaction mode must exercise this without selecting the page behind it |
| Link set reused elsewhere | Impact badges must identify mobile Parent Info and footer quick links |

## Acceptance checklist

- [ ] Current five links and their routes match the public site exactly after migration.
- [ ] Toggle, outside-click, Escape, focus return and active state all work in preview.
- [ ] Shared-data impact is visible before save.
- [ ] Long menus remain on-screen and keyboard reachable.
- [ ] Unsafe/broken links cannot publish silently.
- [ ] Empty and missing-data states degrade safely.

