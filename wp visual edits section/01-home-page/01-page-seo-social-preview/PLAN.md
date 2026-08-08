# Home page SEO and social preview plan

## Intent and feel

SEO editing should feel like finishing the Home page, not entering code: one accurate search result preview, one social card preview and clear guidance about what is inherited from the Alexandra brand. This is metadata for the existing page, not a visible replacement Home section.

## Current evidence

- `Home.jsx` renders `<Seo>` with no explicit title, description “Montessori childcare for Babies to 5 Years, with welcoming settings in Hounslow, Heston and Hammersmith.”, path `/`, and `classroom-main.webp`.
- `Seo.jsx` therefore produces the current title “Alexandra Montessori - Quality Montessori Childcare in London”.
- It outputs canonical URL, Open Graph website/site/title/description/url/image and `twitter:card=summary_large_image`.
- Site origin is currently fixed to `https://alexandramontessori.co.uk`; relative images are converted to absolute asset URLs.
- The visible Home hero contains only a screen-reader H1 using `brand.name`, so SEO copy must stay coordinated with the actual page identity.

## Editable elements and controls

- SEO title with live pixel/character guidance and “use brand default” toggle.
- Meta description with live search-snippet preview, length guidance and branch-name spelling check.
- Social title/description default to SEO values, with explicit override switches.
- Social image media picker, focal preview at common 1.91:1 crop, replacement/reset and file-size/dimension guidance.
- Canonical path displayed read-only as `/`; domain resolves from approved Site URL settings.
- Optional social image alt field when the output implementation supports `og:image:alt`/Twitter alt.
- Validation summary before save: duplicate title, empty description, missing image, non-indexable page or incorrect canonical.

## Selection, layers and dragging

SEO metadata has no public canvas layer and cannot be pixel-dragged. Selecting “SEO & sharing” opens side-by-side Google-style and social-card previews. Only the social-image focal point is draggable within its crop frame; this stores crop metadata without modifying the original media.

## Desktop, tablet and mobile

Search preview offers desktop and mobile snippet widths; social preview offers wide card and compact-share contexts. The actual metadata is device-independent. Image crop previews must show safe text-free composition because platforms crop differently; no device-specific canonical/title values are stored.

## Data ownership and bindings

SEO fields belong to the Home page’s stable WordPress page/meta record, not the visual section option and not global Site Settings. Brand name/default title and Site URL are referenced globally. Social media stores a WordPress attachment reference plus derived absolute URL. The public React `Seo` component receives a sanitized Home SEO payload with today’s constants as fallback.

## Protected rules

- Canonical domain/path cannot be typed freely in this page panel.
- Do not permit markup/scripts in title or description.
- Block unsupported/unsafe image URLs and warn when an attachment is private/missing.
- One canonical and one document title only.
- Hiding a visual Home section cannot silently change SEO metadata.
- Search-engine index/follow controls, if ever added, require an advanced confirmation and are outside ordinary styling.

## Accessibility and failure states

Preview labels are accessible and do not rely on pixel-colour alone. Social image alt should describe the image, not repeat the title. Empty optional overrides inherit clearly; empty required description/image falls back to current values until a valid save. If the media attachment disappears, use the current classroom image and flag repair in admin. If Site URL is misconfigured, block canonical publication and show the exact source setting.

## Storage and versioning

Plan a typed Home SEO schema (`homeSeo` version 1) stored with page metadata/revisions. Store override intent separately from rendered fallback values. Revision diffs show title/description/image/canonical-source changes. Migration seeds the exact existing runtime values without changing the public `<head>`.

## Planned implementation files (future only)

- `src/components/Seo.jsx`
- `src/pages/Home.jsx`
- `src/lib/pageSeoModel.js`
- `wordpress/mu-plugins/alexandra-visual-builder/assets/panels/home-seo.js`
- `wordpress/mu-plugins/alexandra-visual-builder/tests/home-seo.php`

## Current public section → exact WP canvas parity

| Current public head output | WP canvas requirement |
| --- | --- |
| “Alexandra Montessori - Quality Montessori Childcare in London” | Initial search/social title preview renders this exact default |
| Current Babies-to-5 / three-settings description | Same text in meta and preview before any edit |
| Canonical `https://alexandramontessori.co.uk/` | Read-only canonical preview resolves to the exact URL |
| `classroom-main.webp` social image | Same resolved image and crop in initial social preview |
| Open Graph website + summary-large Twitter card | Preview labels reflect the actual emitted metadata types |

## Acceptance checklist

- [ ] Initial generated `<head>` is byte-for-value equivalent to current public metadata.
- [ ] Search/social overrides are explicit and independently resettable.
- [ ] Canonical cannot drift from the approved Home route.
- [ ] Image crop/picker retains attachment identity and safe fallback.
- [ ] Missing/invalid data leaves a valid public title, description and image.
- [ ] Metadata revisions do not alter visual sections or global settings.

