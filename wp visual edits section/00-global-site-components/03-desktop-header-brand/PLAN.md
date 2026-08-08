# Desktop header shell and brand plan

## Intent and feel

The desktop header must keep the production character shown in the reference: a calm pale-green bar, a round Alexandra badge that hangs below it, crisp white navigation and generous air. Editing should feel like adjusting a precise brand lockup, not pushing an unconstrained image around the page.

## Current evidence

- `Navbar.jsx` owns `data-am-vb-region="global-header"`, a `#a3bc9a` background, white text and `shadow-nav`.
- At `lg`, the navigation height is 5rem; the logo is absolutely positioned near the top and sized to 10rem, extending beyond the bar.
- A 10.5rem spacer protects the navigation from the hanging logo.
- `Logo.jsx` already binds `header-logo` to replaceable media, defaults to `logo-badge.webp`, links to `/`, and uses an accessible Alexandra Montessori label.

## Editable elements and controls

- Header background token, subtle shadow preset, desktop height within a tested range, horizontal page gutter and content alignment.
- Header logo upload/replace, media-library selection, transparent-background guidance, alt text, rendered size and x/y optical offset.
- Logo crop mode is always “contain”; expose no destructive crop for the circular brand mark.
- Optional logo-hover motion, with preview and reduced-motion fallback.
- Header visibility is not a normal client toggle. A developer-only “minimal landing page” variant may be introduced later as a separate template.
- “Edit navigation”, “Edit nursery dropdown”, “Edit Parent Info” and “Edit availability action” are deep links to their own plans/panels, not mixed controls here.

## Selection, layers and dragging

Canvas selection highlights the full header or the logo. Logo dragging is constrained to an optical-offset lane with snap-back guides for the safe left gutter and nav exclusion zone. The header background layer is locked behind interactive links. Dragging cannot change logo z-index, its home link or the desktop navigation’s order.

## Desktop, tablet and mobile

This plan owns the desktop shell at 1024px and wider, with an `xl` refinement preview. Tablet/mobile brand treatment is owned by the mobile-navigation plan, but logo asset changes propagate globally. At every desktop width, show collision indicators between logo, first nav item, availability CTA and menu button. A very wide preview must keep the inner rhythm centred rather than stretching gaps without limit.

## Data ownership and bindings

Logo media and header geometry live in the global component model. Brand identity defaults resolve from `Logo.jsx` and the brand-token model. Navigation items remain a menu collection, not embedded in this record. The saved media record includes attachment ID/URL, alt, intrinsic dimensions and revision-safe fallback.

## Protected rules

- Logo target remains `/`; no external URL or “open new tab”.
- Preserve a minimum 44×44px effective link target and the full logo aspect ratio.
- Clamp header height, logo size and offsets; reject states that cover primary links.
- Keep header above normal content but below system dialogs according to the shared layer scale.
- Logo replacement must not mutate or delete the source media attachment.

## Accessibility and failure states

The logo link has a stable “Alexandra Montessori — home” accessible name even if an editor supplies decorative or duplicated alt text. If the upload is missing, corrupt or too slow, show the approved built-in badge without layout shift. High-contrast/focus previews cover the first and last header controls. At 200% zoom, the layout must transition safely rather than clipping links.

## Storage and versioning

Store `header.desktop` settings and a typed `header.logo` media reference in the versioned global document. Keep current values as schema defaults. Media replacements create a revision but do not copy binary files into the option. A later lockup style change requires an explicit variant migration, never reinterpretation of old offsets.

## Planned implementation files (future only)

- `src/components/Navbar.jsx`
- `src/components/Logo.jsx`
- `src/styles/global-header.css`
- `wordpress/mu-plugins/alexandra-visual-builder/assets/panels/header-brand.js`
- `wordpress/mu-plugins/alexandra-visual-builder/tests/header-brand.php`

## Current public section → exact WP canvas parity

| Public header element | WP canvas requirement |
| --- | --- |
| Pale-green bar with `shadow-nav` | Same full-width surface, height and shadow in the real iframe |
| Circular badge hangs below bar at left | Same default asset, contain fit, offsets and content overlap silhouette |
| 10.5rem desktop logo spacer | Same nav exclusion area/collision behaviour at `lg` |
| Logo points to Home | Interaction preview preserves `/`; edit mode selects without navigating |
| White content on 5rem desktop bar | Same responsive typography/vertical centring around the brand layer |

## Acceptance checklist

- [ ] Header matches the approved green, shadow, height and hanging-logo silhouette by default.
- [ ] Logo replacement, alt, size and optical offsets preview live and survive reload.
- [ ] Safe guides prevent logo/navigation/CTA collisions at 1024px, 1280px and wide desktop.
- [ ] Home target, aspect ratio, focus target and z-order cannot be broken by editing.
- [ ] Missing media falls back without a blank badge or layout jump.
- [ ] Header-only reset leaves menus and page content untouched.
