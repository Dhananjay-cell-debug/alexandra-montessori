# Global social links rail plan

## Intent and feel

The social rail should remain a slim, confident edge detail: four recognisable platform blocks, fixed at mid-screen, never competing with nursery content. Editing should make icon, destination and accessible meaning inseparable so a visual replacement cannot silently create a misleading link.

## Current evidence

- `SocialSidebar.jsx` owns `data-am-vb-region="social-sidebar"` and renders from `md` upward only.
- It is fixed at the right edge, vertically centred, z-index 40, with 40×40px blocks.
- Current order is Facebook, Instagram, X and LinkedIn from `socialLinks.jsx`.
- Each item already supports replacement icon media, href and `linkDescription`; platform brand colours are mapped in component code.
- External links open in a new tab with `noopener noreferrer`; `#` placeholders do not.

## Editable elements and controls

- Ordered rows: platform type, destination URL, visible label, accessible description, icon source, visibility and open-new-tab policy.
- Add from an approved platform list, reorder, archive/restore and validate account URL; custom platform requires icon, colour and accessible name.
- Per-icon replace/upload with transparent SVG/PNG/WebP guidance, alt treatment and contain sizing.
- Rail side (right is current/default), vertical anchor, block size, gap and brand-colour lock. Custom colour is allowed only for a custom platform or explicit brand update.
- Preview normal, hover, keyboard focus, missing icon, invalid URL and page overlap states.

## Selection, layers and dragging

Rows reorder vertically via handles and keyboard moves. The whole rail may move only along a constrained vertical track and between approved left/right edge slots; individual blocks cannot be pixel scattered. Its layer order is protected relative to header, page content, cookie banner and dialogs. Selecting an icon opens its linked URL fields in the same panel.

## Desktop, tablet and mobile

Exact current parity is hidden below `md`, visible from tablet upward. Desktop/tablet offsets have bounded independent values. A mobile alternative is not introduced in this workstream; social access on mobile remains via footer or other existing surfaces until separately approved. Preview must detect overlap with cookie banners, floating form actions and narrow landscape safe areas.

## Data ownership and bindings

Social-account identity should come from one WordPress settings collection (`brand.socials` currently has CMS capability), while rail-specific icon/presentation overrides live in the global visual model. Stable platform IDs bind account URL, icon and accessible label. Footer consumers may reference accounts without inheriting the rail’s geometry.

## Protected rules

- Disallow unsafe protocols and empty accessible names for visible links.
- Platform colour remains authoritative unless the platform is custom.
- External links keep security rel attributes.
- Clamp rail position/size so it stays on-screen and does not create horizontal overflow.
- Removing a rail item does not delete the underlying organisation social account.

## Accessibility and failure states

Every icon-only link has an explicit accessible name. Focus indicator must be visible against each platform colour and not clipped by the viewport. New-tab behaviour is stated in accessible help where appropriate. A missing custom icon falls back to an approved generic link glyph plus platform name in the editor; the public rail may omit invalid rows. If all rows are invalid/hidden, omit the rail without an empty fixed container.

## Storage and versioning

Persist ordered platform references and rail presentation in the global document; keep WordPress attachment IDs/URLs for custom icons. Migrate existing `social-1-icon` … `social-4-icon` overrides by matching current platform order, then establish stable IDs so later reordering does not swap icon data. Retain current four social URLs as CMS/default evidence.

## Planned implementation files (future only)

- `src/components/SocialSidebar.jsx`
- `src/data/socialLinks.jsx` (fallback only)
- `src/lib/socialModel.js`
- `wordpress/mu-plugins/alexandra-visual-builder/assets/panels/social-rail.js`
- `wordpress/mu-plugins/alexandra-visual-builder/tests/social-rail.php`

## Current public section → exact WP canvas parity

| Public behaviour | WP canvas requirement |
| --- | --- |
| Four 40px blocks, right edge, vertically centred | Same fixed position and dimensions in iframe preview |
| Facebook → Instagram → X → LinkedIn | Same initial order, platform identities and URLs |
| Platform-coloured squares with white glyphs | Canvas uses actual brand colours and current/default icons |
| Hidden below `md` | Mobile preview hides the rail, rather than inventing a replacement |
| Icon click opens external account | Interaction mode validates real target while edit mode selects safely |

## Acceptance checklist

- [ ] Current four accounts, order, colours, sizing and breakpoint match public output.
- [ ] Reordering keeps each icon, URL and accessible label together.
- [ ] Rail never collides with system overlays or produces horizontal scroll.
- [ ] Keyboard focus is visible on every platform colour.
- [ ] Invalid/empty collections leave no orphan fixed container.
- [ ] Migration no longer depends on numeric icon position.

