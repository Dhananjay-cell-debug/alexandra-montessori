# Desktop Our Nurseries dropdown plan

## Intent and feel

Hovering or focusing “Our Nurseries” should reveal a small, polished white panel containing the real settings—currently Hounslow, Heston and Hammersmith—without making the header feel busy. The editor should make the parent/child relationship obvious and keep nursery data connected to each nursery page.

## Current evidence

- `Navbar.jsx` conditionally turns `/nurseries` into a dropdown when `locations.length > 0`.
- The current panel is 14rem wide, centred under the parent, padded, rounded and shadowed; it appears on mouse enter and disappears on mouse leave.
- Items are generated from `locations`, link to `/nurseries/{id}`, and use active/hover styles.
- The production screenshot shows the same three nursery names in a larger rounded white flyout beneath “Our Nurseries”.

## Editable elements and controls

- Parent trigger label/route is referenced from primary navigation, not duplicated.
- Dropdown display mode: automatic from published nurseries (recommended) or curated subset.
- Per-child display label override, visibility, order and optional short location descriptor; route remains bound to the nursery record.
- Panel width, corner-radius preset, surface colour token, shadow preset, item padding and alignment within safe ranges.
- Open interaction preview for pointer, keyboard and touch-emulation; configurable intent delay in a tested band to prevent accidental closing.
- Add future nursery by selecting a published nursery record; an unpublished upcoming nursery can be staged but cannot be linked publicly without an explicit “coming soon” destination policy.

## Selection, layers and dragging

Child links reorder vertically using list drag handles and keyboard moves. The dropdown is one anchored overlay layer; it cannot be freely dragged away from its trigger. A constrained alignment control (left/centre/right) and x-offset with collision guides is sufficient. Panel and item layers stay grouped, and z-index is protected by the global layer plan.

## Desktop, tablet and mobile

Desktop owns hover plus focus/click behaviour. The panel must flip or clamp inside the viewport at narrower desktop widths. The same nursery order/labels feed the inline nested list in mobile navigation, but mobile geometry is configured there. At browser zoom or when the row wraps, fall back to click disclosure rather than rendering off-screen.

## Data ownership and bindings

Nursery identity, slug/status/name live in the nursery-directory data contract. This global component stores stable nursery IDs plus presentation overrides and order. It must not copy addresses, images or nursery-page content. The renderer resolves each target from current route data so renaming/slugs can migrate without dead links.

## Protected rules

- A child cannot point to a different nursery’s page while retaining another nursery ID.
- Prevent unsafe URLs, duplicate nursery IDs and empty visible labels.
- Preserve the parent `/nurseries` link as a usable overview destination.
- Panel remains keyboard operable and cannot be hidden behind the hero.
- Do not delete nursery records from this menu; “remove from menu” only changes presentation membership.

## Accessibility and failure states

Use a disclosure/navigation pattern with `aria-expanded`, `aria-controls` and predictable focus; hover alone is insufficient. Escape closes and returns focus to the trigger; focus can move through child links without premature close. If there are no published nurseries, render the parent as a normal link and show an editor empty state. Missing child records become flagged drafts and are omitted from the public list; current three-item fallback is used only when the CMS contract is unavailable.

## Storage and versioning

Persist panel presentation plus ordered stable nursery references in the global menu schema. Version child references separately from nursery content. The first migration imports Hounslow, Heston and Hammersmith in current `locations` order. Revision diffs should read “Moved Heston above Hounslow” rather than expose raw JSON.

## Planned implementation files (future only)

- `src/components/navigation/NurseryDropdown.jsx`
- `src/lib/navigationModel.js`
- `wordpress/mu-plugins/alexandra-visual-builder/assets/panels/nursery-dropdown.js`
- `wordpress/mu-plugins/alexandra-visual-builder/includes/class-am-vb-menus.php`
- `wordpress/mu-plugins/alexandra-visual-builder/tests/nursery-dropdown.php`

## Current public section → exact WP canvas parity

| Public dropdown evidence | WP canvas requirement |
| --- | --- |
| Trigger is the Our Nurseries primary link | Use the actual shared trigger/route, not a duplicated button |
| Hounslow, Heston, Hammersmith | Same three current nursery records, order and detail routes |
| Rounded white panel centred under trigger | Same initial width, padding, radius, border and shadow |
| Hover opens/leaves closes | Canvas interaction mode reproduces pointer behaviour and also tests focus/click |
| Active child green; other children sage with pale hover | Same state styling in actual preview markup |

## Acceptance checklist

- [ ] Current three nurseries render in the current order with valid overview/detail routes.
- [ ] Hover, click, keyboard focus, Escape and outside-click behaviour are stable.
- [ ] Panel stays within viewport and above page media at zoomed/narrow desktop sizes.
- [ ] Reordering propagates to mobile without copying nursery content.
- [ ] Empty, unpublished and deleted nursery states have deliberate editor/public behaviour.
- [ ] The `/nurseries` parent remains independently reachable.
