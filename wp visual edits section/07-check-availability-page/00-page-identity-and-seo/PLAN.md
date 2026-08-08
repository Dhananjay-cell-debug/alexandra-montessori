# Check Availability page identity and SEO plan

## Intent and feel

The SEO panel should present this as the direct admissions-enquiry page: reassuring, local and action-oriented, without promising a place or exposing anything entered into the form. It edits metadata only; it does not create a visible page section.

## Exact current JSX/public evidence

- `Availability.jsx` passes `title="Check Availability"` to `Seo`, producing `Check Availability - Alexandra Montessori` through `Seo.jsx`.
- Description is exactly `Check nursery place availability at Alexandra Montessori in Hounslow, Heston and Hammersmith.`
- Canonical path is `/check-availability`; the site origin is resolved by `Seo.jsx` as `https://alexandramontessori.co.uk`.
- Open Graph image is `/assets/organisation/friends-two.webp`; Open Graph type is `website` and Twitter card is `summary_large_image`.
- No form value, selected nursery, reference number or submission status is currently written into `<head>`.

## Editable controls

- SEO title and meta description with search-result previews and length/duplication guidance.
- Social title/description may inherit the SEO values or use an explicit override.
- Social-image attachment picker, replacement, crop preview and reset to `friends-two.webp`.
- Search/social previews for default, long-title, missing-image and draft states.
- Canonical is displayed read-only from the stable page route; search visibility requires an advanced publishing permission.

## Layers, reordering and dragging

Metadata has no canvas layer and cannot be reordered with form sections. Only the social-image focal crop is draggable inside a bounded preview. Selecting SEO in the page outline opens metadata controls without scrolling to or highlighting a fabricated public section.

## Desktop, tablet and mobile

Metadata is shared across devices. The editor previews desktop/mobile search snippets and wide/compact social crops, but stores one canonical/title/description set. Crop guidance accounts for platform variation without creating device-specific metadata.

## Data ownership and bindings

These fields belong to the stable Check Availability page record. Brand/site name and public origin are global references; the share image is a WordPress media attachment. Form submissions remain in the submissions system and must never enter visual-document revisions or SEO previews.

## Protected behavior

- Route `/check-availability` and canonical origin cannot be free-typed in this section.
- Block scripts/markup, unsafe media URLs and an unpublished/private share attachment.
- Keep one title/canonical and do not derive metadata from query strings or form state.
- Wording must describe an enquiry/check, not guaranteed placement, funded-hours eligibility or an instant confirmation.

## Accessibility

Social-image alt metadata should be available when supported and describe the image rather than repeat the title. Preview controls have text labels and keyboard operation. Metadata length warnings do not rely only on colour.

## State previews and failure handling

Preview inherited/default, edited draft, missing attachment, overlong copy and duplicate-title states. Invalid fields remain unsaved with exact reasons. Missing/corrupt page metadata falls back to the current title, description and `friends-two.webp`; a Site URL error blocks canonical publication rather than emitting a malformed URL.

## Storage and versioning

Store a typed page-SEO record with override flags, attachment ID and schema version separate from the form/UI model. WordPress revisions capture metadata changes. A migration seeds the exact current values and preserves the current public `<head>` until a new valid revision is published.

## Planned implementation files (future only)

- `src/pages/Availability.jsx`
- `src/components/Seo.jsx`
- `src/lib/pageSeoModel.js`
- `wordpress/mu-plugins/alexandra-visual-builder/assets/panels/availability-seo.js`
- `wordpress/mu-plugins/alexandra-visual-builder/tests/availability-seo.php`

## Current public section -> exact WP canvas parity

| Current public output | Exact WP canvas requirement |
| --- | --- |
| Browser title `Check Availability - Alexandra Montessori` | Initial search/social title preview resolves identically |
| Current Hounslow/Heston/Hammersmith description | Same seeded description with no inferred claims |
| Canonical `https://alexandramontessori.co.uk/check-availability` | Read-only preview resolves this exact URL |
| `friends-two.webp` share image | Same initial attachment/crop in social preview |
| No form data in metadata | State simulation never inserts entered values or references into `<head>` |

## Acceptance checklist

- [ ] Untouched metadata equals current JSX output.
- [ ] Canonical remains bound to the registered route.
- [ ] Draft metadata stays private and revisioned.
- [ ] Share-image replacement is independent from the admissions visual card.
- [ ] Missing/invalid data restores safe current defaults.
- [ ] No form or submission data is stored in SEO/design revisions.

