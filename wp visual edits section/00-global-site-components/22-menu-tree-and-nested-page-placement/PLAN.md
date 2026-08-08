# Menu tree and nested future-page placement plan

## Intent and feel

After a future page is ready, placement should feel like arranging a clear site map: drag the stable page record into Primary Navigation, Parent Info, an existing submenu, or a new explicitly named submenu, then preview desktop and mobile together. The client must see that page hierarchy, URL hierarchy and menu placement are related choices but not accidentally the same operation.

## Current evidence

- Primary navigation currently contains six items in fixed order.
- Our Nurseries currently owns a dynamic three-child dropdown from published `locations`: Hounslow, Heston and Hammersmith.
- Parent Info/menu drawer contains Fees, Fee Calculator, Funded Childcare, Blog and Food Hygiene Rating; mobile and footer reuse those records.
- React `Navbar.jsx` currently has bespoke rendering for only the nursery dropdown and Parent Info panel; it is not yet a generic nested menu renderer.
- Future visual pages have no current public menu placement and must not appear until both page publish and menu publish succeed.

## Editable elements and controls

- Site-map tree with named roots: Primary, Parent Info, Footer-only, Unplaced, plus supported submenu children beneath eligible parent items.
- Each row shows page/link label, target page/status, resolved path, placement consumers (desktop/mobile/footer), child count, source binding and errors.
- Drag/drop with clear before/inside/after zones; keyboard Move up/down, Nest under, Move out and destination picker.
- Add menu label override, visibility by approved consumer, accessible description and external-link metadata without changing page title/slug.
- Create a new submenu parent from an existing published overview page or non-link labelled disclosure only when renderer/accessibility rules support it.
- Nursery parent defaults to dynamic nursery-directory binding; manual curation is an explicit mode and does not duplicate nursery records.
- Preview real desktop primary/dropdowns, desktop Parent Info, mobile main/second view and footer quick links before global menu save.

## Selection, layers and dragging

Tree dragging changes semantic order/nesting, never x/y pixels. Maximum supported depth is explicitly bounded (initially parent + one child level, matching the nursery pattern) until deeper responsive/keyboard behaviour is implemented. Moving a shared Parent Info row shows all affected consumers. Menu panels themselves remain anchored layers governed by global navigation plans.

## Desktop, tablet and mobile

One menu tree feeds device-specific renderers. Every move runs desktop fit, dropdown viewport, mobile-height and footer-impact checks. A primary child becomes an anchored desktop submenu and an indented/disclosed mobile group. No device may have an entirely different target or misleading label without an explicit surface override visible in the tree.

## Data ownership and bindings

Menu nodes have immutable IDs and typed targets (page ID, nursery record ID, safe external URL or non-link disclosure). Target page content/slug/status remain page-owned. Menu tree stores order, parent ID, placement root and display overrides. Dynamic collections store query/binding configuration, not copied child arrays. Renderers resolve one versioned global menu snapshot.

## Protected rules

- Draft/private/unpublished pages cannot enter the published menu snapshot; they may be staged visibly in admin.
- Prevent cycles, orphan parents, duplicate node IDs, unsafe URLs and unsupported depth.
- Moving/renaming a node never moves/renames the underlying page or slug.
- Preserve at least one accessible Home, contact/availability and privacy path across the full site.
- Dynamic Nursery children cannot be reassigned to mismatched nursery targets.
- Menu save is atomic: all desktop/mobile/footer consumers receive one valid snapshot or keep the prior version.

## Accessibility and failure states

Tree uses semantic tree controls with announced level/position and full keyboard reorder/nest actions. Resulting navigation uses labelled disclosures, `aria-expanded/controls`, Escape/focus return and visible focus; hover alone is never required. Fit/duplicate-label warnings are explained textually. If a target unpublishes, stage node becomes invalid and public menu keeps the previous valid snapshot or omits it according to the route-safety policy. Empty submenu parents collapse to a normal link or are blocked if non-link.

## Storage and versioning

Store a versioned normalized menu tree and separately versioned published snapshot. Stable target IDs survive slug/label changes. Revision diffs describe moves in human terms (“Moved Fees under Parent Info”). Import the current six primary, three dynamic nursery children and five Parent Info records exactly. Preview/save uses optimistic locking to prevent concurrent menu overwrites.

## Planned implementation files (future only)

- `wordpress/mu-plugins/alexandra-visual-builder/assets/panels/menu-tree.js`
- `wordpress/mu-plugins/alexandra-visual-builder/includes/class-am-vb-menus.php`
- `src/components/navigation/MenuTreeRenderer.jsx`
- `src/components/navigation/NurseryDropdown.jsx`
- `src/components/navigation/MobileNavigation.jsx`
- `wordpress/mu-plugins/alexandra-visual-builder/tests/menu-tree.php`

## Current public section → exact WP canvas parity

| Current menu structure | WP canvas requirement |
| --- | --- |
| Six primary links | Imported in identical order/labels/routes with no extra future page |
| Our Nurseries → Hounslow/Heston/Hammersmith | Same dynamic parent/children, desktop flyout and mobile nesting |
| Five Parent Info links | Same shared order in desktop panel/mobile second view/footer quick links |
| Check availability has its own global CTA slot | Tree references but does not accidentally absorb/reorder that protected action |
| Future visual pages currently unplaced | Stay in Unplaced until explicit valid placement and menu publish |

## Acceptance checklist

- [ ] Current menu imports/renderers remain exact before any user change.
- [ ] Future page placement is explicit, previewed and atomic across consumers.
- [ ] Pointer and keyboard can reorder/nest with identical results.
- [ ] Stable IDs prevent slug changes from breaking menu nodes.
- [ ] Cycles, unsupported depth, drafts and broken targets cannot publish.
- [ ] Dynamic Nursery binding remains factual and non-duplicative.

