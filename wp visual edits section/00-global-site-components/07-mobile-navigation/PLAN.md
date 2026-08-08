# Mobile navigation plan

## Intent and feel

On phones and compact tablets, navigation should feel spacious and reassuring: one clear menu button, a full-width green disclosure, direct nursery choices and a second Parent Info view that never feels like a maze. The editor should preview the genuine two-view interaction at real mobile sizes.

## Current evidence

- Below `lg`, `Navbar.jsx` replaces the desktop row with a large Menu/X button.
- Opening the menu locks body scrolling and renders a green full-width panel below the header.
- The first view lists every primary link, nests all nursery links under Our Nurseries, includes Check availability, then a “View more” button.
- “View more” switches to a Parent Info view with Back, a “Parent Info” eyebrow and the five `parentInfoLinks` records.
- Closing resets both `mobileOpen` and the nested Parent Info state.

## Editable elements and controls

- Mobile menu button accessible labels and approved Menu/X icon size.
- Main-view link data references the primary menu, nursery dropdown collection and availability CTA; no duplicate labels.
- Editable UI copy unique to this surface: “View more”, “Back” and “Parent Info”.
- Panel background token, separators, top/bottom padding, link density and indentation with bounded device values.
- Nested nursery display mode: always expanded (current parity) or accessible disclosure only after a separately approved behavioural change.
- Preview controls for closed, main view, Parent Info view, active route, long content and small-height landscape.

## Selection, layers and dragging

Link ordering is inherited from source collections and shown read-only here with shortcuts to edit each source. Unique UI-copy layers can be selected, but not freely positioned. The full menu panel is an anchored flow layer, not a draggable modal. Reordering visible main links occurs in Primary Navigation; nursery and Parent Info rows move in their own collection plans.

## Desktop, tablet and mobile

Test 320, 360, 390, 768 and narrow landscape heights. The menu begins below the actual header/logo geometry, uses safe-area padding and scrolls internally or with the page according to the agreed lock policy. It disappears cleanly at `lg` without leaving body overflow locked. Tablet portrait follows the current mobile treatment until 1024px.

## Data ownership and bindings

This component owns only mobile presentation and the three surface-copy strings. Primary items, nursery children, Parent Info resources and availability target are referenced from global source collections. React should resolve one snapshot so labels do not differ between desktop and mobile—the current direct `item.label` rendering must ultimately bind to the same sanitized edited label.

## Protected rules

- Menu toggle and Back remain real buttons; navigation rows remain links.
- Body overflow is always restored on close, route navigation, breakpoint change and unmount.
- One route activation closes all nested menu state.
- No free positioning, horizontal scrolling or hidden focus targets.
- The menu cannot cover the cookie preferences dialog, and the logo/home link remains reachable.

## Accessibility and failure states

Use `aria-expanded` and an owned menu-panel ID; announce view changes and move focus to the Parent Info heading/Back control as appropriate. Escape closes from either view and returns focus to the menu button. Screen readers receive meaningful group labels for nursery children. If one referenced collection is empty, omit only that group and retain other navigation. If data loading fails, render the current static navigation snapshot.

## Storage and versioning

Persist `mobileNavigation` presentation plus `viewMoreLabel`, `backLabel` and `parentInfoHeading` in the global schema. References point to menu collection IDs, never copied arrays. Revision history distinguishes copy/style changes from source-menu changes. Current breakpoint and expanded-nursery behaviour are version-1 defaults.

## Planned implementation files (future only)

- `src/components/navigation/MobileNavigation.jsx`
- `src/components/Navbar.jsx`
- `src/hooks/useBodyScrollLock.js`
- `wordpress/mu-plugins/alexandra-visual-builder/assets/panels/mobile-navigation.js`
- `wordpress/mu-plugins/alexandra-visual-builder/tests/mobile-navigation.php`

## Current public section → exact WP canvas parity

| Public behaviour | WP canvas requirement |
| --- | --- |
| Menu/X button below `lg` | Same header position, icon state and breakpoint |
| Green panel below header | Real header/logo clearance and full-width flow preview |
| Primary links with nursery children inline | Same order, labels, indentation and separators from shared data |
| Check availability row | Same shared label/target and position before View more |
| View more → Parent Info; Back reverses | Canvas must simulate both views and reset state on close |
| Open menu locks document scroll | Preview must lock only its iframe and always restore it |

## Acceptance checklist

- [ ] Public and canvas match in closed, main and Parent Info states.
- [ ] Edited shared labels appear identically on desktop and mobile.
- [ ] Scroll lock is restored across every close/navigation/breakpoint path.
- [ ] 320px and landscape layouts have no clipped or unreachable rows.
- [ ] Keyboard/screen-reader focus follows view changes predictably.
- [ ] Empty source groups and failed data loads degrade independently.

