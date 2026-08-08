# Contact Social Links Row Plan

## Implementation-grade parity contract

### Intent and feel

The row should feel like a restrained secondary connection point, visually quieter than branch calls and the enquiry submit.

### Exact current React/public evidence

After the directory, `Contact.jsx` centres a `mt-6` flex row with `gap-3`. It maps `socialLinks`, allows home-visual icon/media and destination overrides, renders 20px icons, uses destination-dependent new-tab behavior, and derives the accessible label/title from link description, image alt, or platform label.

### Current public section -> exact WP canvas parity

| Current public truth | Required WordPress canvas result |
| --- | --- |
| Items | Render the current global Facebook, Instagram, X and LinkedIn collection in source order unless a published collection says otherwise. |
| Link resolution | Preserve `homeHref` destination override behavior, `#` same-tab fallback, safe new-tab attributes, and accessible-name fallback order. |
| Appearance | Seed centred inline flex, 12px gap, 20px icons, sage-800 colour and hover opacity transition. |

### Editable elements and controls

Local alignment, gap, wrap, icon size, normal/hover/focus colours, row visibility and surface. Global source editor controls platform, URL, accessible description and approved icon/media; local instance may explicitly override appearance or destination with a visible badge.

### Layers, reorder, and dragging

Row > ordered link items > icon. Links reorder with drag handle and keyboard controls while DOM order follows. Icons cannot be detached into free-floating clickable layers; optional decorative background is the only freely positioned layer.

### Responsive behavior

Allow per-device alignment, wrapping, gap and icon size with a protected minimum target wrapper. Seed stays centred and compact; mobile wrapping cannot reorder platforms or overlap the section edges.

### Data ownership and bindings

Platform records and master URLs belong to Global Social Links. Contact owns only local presentation plus any audited local override. Current home-visual overrides are an existing compatibility source that must be migrated without losing values.

### Protected behavior

URL protocol allow-list, `noopener noreferrer`, accessible-name fallback, stable item keys and sanitized media. Empty or `#` destinations never masquerade as working external links.

### Accessibility

Every icon-only link has a computed name, visible focus outline and adequate target. Decorative image alt is not substituted for an absent link name; reduced hover/motion is honored.

### Relevant state previews

Preview all four items, one item, wrapped mobile, missing icon, missing URL/`#`, long accessible name, local override, and keyboard focus.

### Storage and versioning

Keep global social records in their versioned global document; save Contact style/destination override references under `contact.social_links_row`. Revision UI must distinguish a global edit from a local override and support independent rollback.

### Planned implementation files (future only)

These paths describe later implementation ownership; they are **not created by this planning phase**.

- `includes/definitions/pages/contact.php` — local social-row instance schema.
- `includes/definitions/global/social-links.php` — shared item/URL/icon contract.
- `assets/editor/pages/contact/social-links-row.js` — global-source and local-override inspector.
- `tests/parity/contact/social-links-row.spec.js` — item, URL, label and wrapping parity.

### Acceptance checklist

- [ ] Seeded row matches current platform order, size, spacing and link behavior.
- [ ] A global change updates all bound consumers while local styles remain local.
- [ ] No item is keyboard-invisible or published without an understandable accessible name.


## Intent and feeling

Offer a quiet secondary way to connect without competing with branch contacts or the enquiry form.

## Current evidence

The page renders the global social collection with platform icon, destination, title/accessible description, and external-link behavior. Home visual controls currently provide icon/media overrides.

## Editing model

- This instance is a synced Global Social Links block. The page may change alignment, gap, icon size, surface, and local visibility; platform, URL, accessible name, and master icon normally come from the global record.
- `Edit globally` opens the social source. `Detach appearance` creates only local style overrides, never a second URL source.
- Repeaters support add, delete, duplicate, and reorder with approved platforms or a sanitized custom link/icon.
- Icons may use the approved icon library or sanitized Media SVG/raster asset. Raw SVG code is prohibited.

## Responsive and interaction

Allow wrap, centred/left alignment, mobile size, and reduced hover effects. External links preview destination and enforce `noopener noreferrer`.

## Accessibility

Every icon-only link requires an accessible label. Decorative alt text cannot replace the link name. Focus rings must meet contrast and must not be removed by style controls.

## Acceptance

- Global URL changes update this row and the fixed social rail.
- Local style edits do not fork factual destinations.
- Keyboard and screen-reader users can identify every platform.
