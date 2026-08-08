# Home section outline and ordering plan

## Intent and feel

The left outline should make the Home page understandable at a glance: six named core sections, any custom sections exactly where they render, and shortcuts to global components without mixing their ownership. Selecting an outline row should calmly focus the real canvas section and its tailored controls.

## Current evidence

- `Home.jsx` has six core regions in fixed semantic/render order: Video hero, Nursery feature links, Montessori benefits, About us journey, Parent testimonials and Trust/accreditations.
- Custom sections render only after one of those six anchors, in saved array order.
- Existing editor `LIVE_HOME_REGIONS` also lists Header, Social rail, Footer and Cookie control as global components around the six Home sections.
- The UI currently reports “6 core sections + N custom sections”, highlights a selected live region and can focus canvas nodes by `data-am-vb-region`.
- Core section visibility is editable, but core reordering is not part of the current public model.

## Editable elements and controls

- Outline rows show name, type (core/custom/global reference), visibility, unsaved/error badge, item/layer count and device issue count.
- Click/keyboard selection scrolls/focuses the exact iframe section; “Focus section” isolates its controls without changing public visibility.
- Core rows expose visibility/reset and their own plan link. Their order is protected to preserve exact Home composition.
- Custom rows expose rename, visibility, duplicate/delete, drag within/between legal after-anchors and insertion shortcut.
- Global Header/Social/Footer/Cookie rows are visually separated as references and open `00-global-site-components`; they are never stored in Home.
- Search/filter, collapse groups, expand all and “show hidden in editor” controls.

## Selection, layers and dragging

Core rows are not draggable in version 1. Custom rows drag between explicit drop zones after core anchors; their `after` value and sibling order update together. Keyboard Move before/after and Change insertion point provide parity. Dragging a section outline row never changes its internal layer z-order. Canvas click and outline selection stay synchronized by stable region ID.

## Desktop, tablet and mobile

The outline is device-independent but issue badges are device-specific. Changing device preserves selected region and scrolls to the same section. Hidden sections remain represented in the outline with an editor-only placeholder. Ordering is identical across devices; device-specific section order is prohibited because it would break reading/navigation consistency.

## Data ownership and bindings

Core order/IDs are code-owned by the Home template. Core visibility/design live in Home’s section map. Custom anchor/order/name/visibility live in `customSections`. Global rows resolve the global document only. The outline is derived UI state and is not separately persisted except harmless editor preferences such as collapsed groups.

## Protected rules

- Do not mix header/footer/social/cookie settings into the Home save payload.
- Core region IDs and current order cannot be renamed/deleted/reordered through client controls.
- Custom sections may use only the six sanctioned insertion anchors.
- Prevent all primary Home content from being hidden without a blocking page-quality warning.
- Selection/focus actions do not navigate links or trigger consent/menu behaviour unless Interact mode is explicit.

## Accessibility and failure states

Outline is a keyboard-operable tree/list with clear selected, hidden, locked and expanded states. Drag actions have Move up/down/insertion alternatives and live announcements. If an iframe region is absent because its public condition is false (for example no testimonials), selection focuses an editor placeholder and explains the upstream state. Unknown/orphan custom anchors appear in a repair group and fall back safely after Trust.

## Storage and versioning

No duplicate outline document. Derive from Home model version and global references. Revision diffs include custom moves/visibility and core visibility, while editor collapse/search preferences use per-user storage. A future intentional core-order feature would require a new schema and migration; it is not implied by this plan.

## Planned implementation files (future only)

- `wordpress/mu-plugins/alexandra-visual-builder/assets/panels/home-outline.js`
- `wordpress/mu-plugins/alexandra-visual-builder/assets/canvas/region-focus.js`
- `src/pages/Home.jsx`
- `src/lib/homeRegionRegistry.js`
- `wordpress/mu-plugins/alexandra-visual-builder/tests/home-outline.php`

## Current public section → exact WP canvas parity

| Current Home render order | WP canvas requirement |
| --- | --- |
| Video hero | First core Home row and first core canvas section |
| Nursery feature links | Second core row; custom-after-hero rows appear before it only when saved |
| Montessori benefits | Third core row with exact intervening custom-anchor sequence |
| About us journey | Fourth core row; story/milestone subplans stay one public region |
| Parent testimonials | Fifth core row, or conditional editor placeholder when no testimonials |
| Trust/accreditations | Sixth/final core row before after-trust custom sections |
| Header/social/footer/cookie around main | Separate global-reference group; never duplicated as Home sections |

## Acceptance checklist

- [ ] Default outline contains exactly six Home core rows in public render order.
- [ ] Global components are referenced separately and save to global ownership only.
- [ ] Custom drop/order maps exactly to runtime `after` anchors and array order.
- [ ] Hidden/conditional/orphan sections remain understandable and repairable.
- [ ] Keyboard selection/movement has full parity with pointer drag.
- [ ] No action invents or substitutes a new public core section.
