# Hammersmith — hero section

## Intent and feel

Preserve the calm classroom hero and Ravenscourt orientation, with especially careful portrait-aware crop control beneath the navigation.

## Current public section → exact WP canvas parity

- First public section uses `/assets/organisation/classroom-calm.webp`, explicit `heroPosition="center 28%"`, black overlay, bottom gradient, 70vh minimum and top offset.
- Exact order/copy: Home/Our Nurseries/Hammersmith; `Quality childcare in Hammersmith`; `Montessori Inspired care and education for Babies to 5 Years, in the heart of Ravenscourt.`; booking; `0204 618 3477`.
- Hammersmith record age is `12 months to 5 years`, but current hero descriptor uses global `Babies to 5 Years`; editor diagnostics must expose that source difference without silently changing public parity.

## Editable elements and controls

- Media/alt/focal/zoom, overlay/gradient, height/spacing, breadcrumb, tagline, descriptor tokens, actions/order, phone, and factual-source diagnostics.
- Crop preview includes fixed-nav safety and starts at the current `center 28%` override.

## Layers and dragging

- Exact tree: `Hero` → `Background image`, `Dark overlay`, `Bottom gradient`, `Content` → `Breadcrumb`, `H1`, `Descriptor`, `Actions` → `Book visit`, `Phone`.
- Stack locked; actions reorder within row only, with keyboard parity.

## Responsive behaviour

- Preserve 70vh/pt-24, mobile action stack and `sm` row; optional device crop overrides remain sparse.
- No hiding the age/area text by device; warnings for nav collision or subject clipping.

## Record/template binding and overrides

- Hammersmith owns image/crop/tagline/area/phone; current descriptor age inherits global brand while record age remains independently owned.
- A future switch to record-age token is an explicit content edit with diff, not an automatic migration; template owns geometry.

## Protected rules

- One h1, stable Hammersmith route/breadcrumb, valid phone, safe media, contrast, working modal trigger; no raw route/href entry.

## Accessibility

- Meaningful alt, breadcrumb nav semantics, visible focus, 44px targets, contrast, logical order, reduced motion.

## Empty and error states

- Broken asset retains last Hammersmith image/fallback; missing required copy/phone blocks or restores Hammersmith last value.

## Storage and versioning

- Store `nurseries/{hammersmithUuid}/sections/hero` with source tokens, asset/crop/alt, overrides, device settings and action order.
- Revision diff explicitly shows global-age token versus record-age token and crop thumbnails.

## Planned implementation files (future only)

- `am-visual-builder/schema/entities/nursery/sections/hero.php`
- `am-visual-builder/admin/pages/nursery/sections/HeroEditor.jsx`
- `am-visual-builder/runtime/nursery/HeroSection.php`
- `am-visual-builder/content/nurseries/hammersmith/hero.json`

## Acceptance checklist

- [ ] Current image, `center 28%`, Ravenscourt copy, global age phrase, and phone reproduce.
- [ ] Fact-source warning is clear without auto-changing output.
- [ ] Crop/upload, actions, responsive nav safety, contrast/focus, reset, diff, and restore pass.

