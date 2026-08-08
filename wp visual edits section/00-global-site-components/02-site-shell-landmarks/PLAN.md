# Global site shell and landmarks plan

## Intent and feel

The shell should be invisible when it works: pages begin under a dependable header, short pages still end cleanly at the footer, keyboard users can bypass navigation, and overlays never destabilise the document. The editor should present this as “Site frame”, not as a generic free-layout canvas.

## Current evidence

- `App.jsx` renders a flex-column `min-h-screen` wrapper with `bg-cream`.
- The order is skip link → `Navbar` → `SocialSidebar` → `<main id="main">`/`Outlet` → `Footer` → `CookieBar`.
- `Footer` relies on `mt-auto`; `<main>` uses the cream background and the footer owns its green fill.
- Global components are mounted once around every route, so shell edits necessarily affect all pages.

## Editable elements and controls

- Skip-link label; destination remains the protected `#main` landmark.
- Page background colour role, main minimum-height strategy and approved content-width preset.
- Header behaviour preset: normal flow or sticky-with-offset, shown with a warning because it changes every page’s top spacing.
- Footer pinning toggle for short pages, default on.
- Global smooth-scroll toggle, default off when reduced motion is requested.
- Preview switches for “long page”, “short page”, “404”, “cookie banner open” and “modal open”.
- A structure inspector that names the landmarks and shows which component owns each region; it does not allow arbitrary HTML editing.

## Selection, layers and dragging

The shell outline is fixed and not freely draggable. Global header, main, footer, social rail and consent layer can be selected and focused, but their z-order is governed by the layer contract. Reordering these landmarks is protected. Page sections may be rearranged only inside `<main>` under their own page plan.

## Desktop, tablet and mobile

Preview the same shell at exact desktop/tablet/mobile widths and with both short and overflowing page content. Sticky-header offsets and cookie-banner clearance must be calculated per device. The social rail may disappear below its defined threshold without leaving lateral overflow. Mobile browser safe-area insets must be applied to bottom-fixed controls.

## Data ownership and bindings

Shell settings belong to the versioned global model. Route content remains owned by each page. The public binding should expose only sanitized presentation settings to `App.jsx`; landmarks, IDs and component order remain in code. Global background uses the brand-token reference rather than storing a duplicate hex value when possible.

## Protected rules

- Exactly one `<main id="main">` per route frame.
- Header, footer and main cannot be deleted, nested or visually reordered.
- Skip target cannot be changed to an arbitrary selector or URL.
- Overlay portals must remain outside transformed ancestors to preserve fixed positioning.
- No setting may hide all page content, introduce horizontal page scroll, or put the footer over form controls.

## Accessibility and failure states

The skip link becomes clearly visible on focus and lands on a focusable main landmark without trapping focus. Route changes move focus/announce the new page according to the router accessibility policy. If saved shell values are absent or invalid, current `App.jsx` behaviour is the fallback. If sticky measurement fails, use normal document flow. With JavaScript errors, semantic landmark order still remains useful.

## Storage and versioning

Persist only the small allow-listed shell settings inside the global document, with defaults matching the current flex shell. Record revision metadata with other global changes. A schema migration must never change landmark IDs, because external skip links, tests and assistive technology depend on them.

## Planned implementation files (future only)

- `src/App.jsx` (bind sanitized shell settings)
- `src/components/SkipLink.jsx`
- `src/lib/globalVisual.js`
- `wordpress/mu-plugins/alexandra-visual-builder/assets/panels/site-shell.js`
- `wordpress/mu-plugins/alexandra-visual-builder/tests/site-shell.php`

## Current public section → exact WP canvas parity

| Current public shell | WP canvas requirement |
| --- | --- |
| Skip link precedes all global UI | Same first-focus target and `#main` destination inside preview |
| Header → social rail → main route → footer → cookie control | Same protected component/render order in canvas outline |
| Flex-column `min-h-screen` with `mt-auto` footer | Short-page canvas has identical bottom-fill behaviour and no cream gap |
| Cream main background | Preview uses the exact current token/default before any edit |
| One `<main id="main">` around route outlet | Canvas markup retains the same landmark, not an extra editor main |

## Acceptance checklist

- [ ] Header/main/footer order and one-main rule hold on every route.
- [ ] Skip link is keyboard-visible and lands correctly.
- [ ] Footer reaches the viewport bottom on short pages without overlaying content.
- [ ] Sticky and normal-flow previews have correct per-device clearance.
- [ ] Cookie/modal states do not create horizontal scroll or inaccessible content.
- [ ] Invalid settings return to the current safe shell.
