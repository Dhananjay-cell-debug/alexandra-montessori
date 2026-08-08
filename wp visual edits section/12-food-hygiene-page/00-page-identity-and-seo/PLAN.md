# Food & Hygiene — page identity and SEO plan

## Intent and feel

Create a sober, trustworthy search identity that tells families this page contains branch-level hygiene information without freezing volatile rating values into metadata.

## Exact current JSX and public evidence

- `FoodHygieneRating.jsx` renders `Seo` before `PageHeader`.
- Exact title: `Food & Hygiene`.
- Exact description: `Food hygiene rating information for Alexandra Montessori nursery locations.`
- Exact route/canonical input: `/food-hygiene-rating`.
- No page-specific SEO/social image is passed, so `Seo.jsx` currently resolves its exact default `/assets/organisation/teacher-hug.webp`.

## Current public section -> exact WP canvas parity

| Current JSX/public output | Exact seeded WordPress parity |
| --- | --- |
| Non-visible `Seo` component | One functional `Search & sharing` inspector, outside public section order |
| Title `Food & Hygiene` | Same initial SEO title |
| Exact one-sentence description | Same initial meta description |
| `/food-hygiene-rating` path | Read-only route-owned canonical |
| No image prop; `Seo.jsx` default `/assets/organisation/teacher-hug.webp` | Explicit inherited site-image state resolving that exact asset; no fabricated page image |
| No rating values in metadata | Metadata remains rating-agnostic by default |

## Exact editing controls

- Search title/description, optional social title/description/image and index/follow permission.
- Search/social previews, character guidance, media-library picker, upload validation, focal point and clear override.
- Optional `{nursery_count}` token with literal/token mode; no rating/date/authority tokens offered in the default metadata panel.
- Compliance wording review owner/date and preview of the public route name.

## Layers, reorder and dragging

- Functional tree: `Page identity` → `Search metadata`, `Social metadata`, `Publishing status`.
- It cannot be dragged among Page Header or the rating-card section; only social-image focal point is spatially draggable.
- Inspector field order remains fixed for keyboard predictability.

## Responsive behaviour

- Mobile/desktop previews use the same stored values and only illustrate truncation.
- Narrow inspector stacks fields and retains warnings; it never creates device-specific SEO content.

## Data ownership and override semantics

- Store on `page:food-hygiene.seo`; canonical remains route-registry owned.
- Optional social values inherit SEO/site defaults until explicitly overridden and resume inheritance when cleared.
- Nursery rating/date/authority updates do not rewrite SEO metadata; only optional nursery count resolves from published nursery records.

## Protected behaviour

- Guard noindex with elevated permission/confirmation; escape text and reject raw tags/scripts/canonical entry.
- Do not include unverifiable rating superlatives or a specific score in default metadata.
- Social media must be an approved attachment/absolute safe URL.

## Accessibility

- Inspector labels, counters, preview modes and errors are keyboard/screen-reader accessible.
- Metadata adds no hidden duplicate heading or visible off-canvas copy.
- Errors use text and focused associations, not colour alone.

## Empty, loading and error states

- Empty required title/description blocks publish and offers exact current seed for recovery.
- Missing/broken optional image returns to inherited site imagery with source label.
- Nursery-count failure retains last published literal/resolved metadata and shows an editor-only stale warning.

## Storage and versioning

- Store `pages/food-hygiene/seo` with drafts/publication, inheritance flags, media/crop, token mode, review metadata, editor and timestamps.
- Diff authored content separately from inherited image or nursery-count changes.
- Restore affects metadata only.

## Planned implementation files (future only)

- `am-visual-builder/schema/pages/food-hygiene/seo.php`
- `am-visual-builder/admin/pages/food-hygiene/SeoPanel.jsx`
- `am-visual-builder/admin/components/ComplianceMetadataReview.jsx`
- `am-visual-builder/runtime/pages/food-hygiene/seo-adapter.php`

## Acceptance checklist

- [ ] Exact current title, description and canonical reproduce.
- [ ] No visible SEO section, invented image or embedded rating appears; the untouched preview resolves `/assets/organisation/teacher-hug.webp`.
- [ ] Inheritance/token modes are clear and reversible.
- [ ] Noindex, media and compliance guards work.
- [ ] Responsive previews, errors, draft retention, diff and isolated restore pass.
