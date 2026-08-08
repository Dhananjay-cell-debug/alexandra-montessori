# Desktop primary navigation plan

## Intent and feel

The primary navigation should stay calm, short and immediately understandable: Home, Our Nurseries, Our Curriculum, Careers, Events and Contact Us. The client can rename, reorder or connect future pages through an intentional menu editor while seeing real width and active-state consequences.

## Current evidence

- `site.js` exports six `nav` records; `Navbar.jsx` renders them only from `lg` upward.
- Labels already have visual-builder keys such as `header-nav-home`, while routes still come from the static `nav` array.
- Active links are white with a two-pixel underline and six-pixel offset; inactive links use white at reduced opacity.
- Gaps change from 1.25rem to 2rem at `xl`; the row shares space with the logo, availability CTA and Parent Info button.

## Editable elements and controls

- Collection rows: label, internal-page picker, optional external URL, accessible label override, visibility and open-in-new-tab (external only).
- Drag handle for deliberate menu ordering; keyboard Move up/Move down equivalents.
- Add an existing published page, add a custom link, duplicate, archive and restore. New site pages can appear as “available but not in menu”.
- Active, hover and focus style presets drawn from global tokens; underline thickness/offset only within tested bounds.
- Density preview reports the remaining pixel budget and flags wrapping/collisions before save.
- Each submenu-owning item displays a relationship badge; nursery children stay governed by the nursery-dropdown plan.

## Selection, layers and dragging

Menu items reorder in the navigation outline, never by pixel dragging. On-canvas horizontal drag may act as a reordering gesture only after a clear handle grab and drop indicator. Links cannot be moved into logo/CTA layers. Multi-select and free scaling are disabled because navigation is a semantic list.

## Desktop, tablet and mobile

Desktop previews cover 1024, 1280 and 1440 widths. Tablet/mobile use the same menu data but present it through the mobile-navigation plan; editors see a propagation note when modifying a label or route. If the desktop row exceeds its safe width, the builder must suggest shorter copy, a hidden item or moving parent information—not silently reduce text below the approved minimum.

## Data ownership and bindings

The global navigation collection owns stable item IDs, labels, targets, order and visibility. It binds to WordPress published routes by page ID/route ID rather than raw slugs wherever possible so a slug change can resolve safely. React receives an ordered sanitized menu. Page documents own page titles and SEO; choosing “sync label to page title” is explicit, not automatic.

## Protected rules

- At least one visible route to Home and one contact/availability path must remain discoverable across the full navigation system.
- Disallow `javascript:`, unsafe protocols and malformed external URLs.
- Do not allow a parent menu link to point to an unpublished/deleted page without a blocking warning.
- Maximum top-level count is governed by measured fit, with a hard safety cap.
- Preserve list semantics, visible keyboard focus and 44px effective target height.

## Accessibility and failure states

Active state must not rely on colour alone. External links are announced when opening a new tab. Duplicate labels receive an editor warning because they are ambiguous to screen-reader and voice users. If a bound page is trashed, retain the row as a visible “Broken destination” draft in the editor but omit or reroute it safely on the public site according to policy. If the menu record is missing, use the six current defaults.

## Storage and versioning

Store menu schema version, stable IDs, target type, target reference, label overrides and order in the global model. Saving creates a global revision and records link-validation results. Migrations import the current six `site.js` records without changing their public order or routes.

## Planned implementation files (future only)

- `wordpress/mu-plugins/alexandra-visual-builder/includes/class-am-vb-menus.php`
- `wordpress/mu-plugins/alexandra-visual-builder/assets/panels/primary-navigation.js`
- `src/components/Navbar.jsx`
- `src/lib/navigationModel.js`
- `wordpress/mu-plugins/alexandra-visual-builder/tests/primary-navigation.php`

## Current public section → exact WP canvas parity

| Public navigation evidence | WP canvas requirement |
| --- | --- |
| Home, Our Nurseries, Our Curriculum, Careers, Events, Contact Us | Same six initial labels, routes and order after migration |
| Hidden below `lg` | Desktop/tablet/mobile canvas follows the actual breakpoint, not a generic always-visible row |
| Active white underline | Active-route preview reproduces thickness, offset and white colour |
| Inactive white/88 with hover to white | Same default/hover contrast and transition |
| `xl` gap refinement around shared header controls | Exact fit against real logo, availability and Parent Info components |

## Acceptance checklist

- [ ] All six current items import in the same order and route correctly.
- [ ] Add, rename, reorder, hide and restore work with keyboard and pointer controls.
- [ ] Desktop fit warnings are accurate at every supported width.
- [ ] Mobile navigation receives the same data without duplicate editing.
- [ ] Active, hover and focus states remain legible and distinguishable.
- [ ] Broken/unsafe destinations cannot be published silently.
