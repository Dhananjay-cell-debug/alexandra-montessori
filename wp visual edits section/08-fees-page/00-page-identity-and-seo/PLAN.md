# Fees page identity and SEO plan

## Intent and feel

Metadata should identify this as the official branch fee-sheet hub with a helpful funding-estimate path, while avoiding language that implies one universal price or a binding online quote. This is metadata only, not a visible page section.

## Exact current JSX/public evidence

- `Fees.jsx` passes `title="Admissions & Fees"`, producing `Admissions & Fees - Alexandra Montessori` through `Seo.jsx`.
- Description is exactly `Download nursery fee sheets for Hounslow, Heston and Hammersmith, and calculate funded childcare hours before contacting the right branch.`
- Canonical path is `/fees` under the fixed public site origin.
- Open Graph image is `/assets/organisation/materials-shelf.webp`; Open Graph type/site/title/description/url and Twitter summary-large card come from `Seo.jsx`.
- The social image is not the same as the visible feature photo (`portrait-girl.webp`) and should remain independently editable.

## Editable controls

- Search title/description with length, duplication and financial-claim guidance.
- Social title/description inherit or explicitly override SEO values.
- Social-image attachment/crop/reset, seeded to `materials-shelf.webp`.
- Read-only canonical preview and capability-gated search visibility.
- Preview default, long copy, missing image, draft and branch-list wording that no longer matches published nurseries.

## Layers, reordering and dragging

SEO has no public canvas layer and cannot be dragged among Fees sections. Only social-image focal crop moves within a bounded preview. Selecting it opens metadata previews rather than highlighting a fabricated page region.

## Desktop, tablet and mobile

One metadata set serves every device. Provide desktop/mobile search snippet and wide/compact share-card previews; these do not create device-specific canonicals or titles.

## Data ownership and bindings

Fields belong to the stable Fees page record. Site name/origin are global. Social image references WordPress media. Published nursery names can be validated against `locations`, but metadata remains editorial rather than copied fee/PDF data.

## Protected behavior

- Canonical `/fees` cannot be free-typed here because menus/resources link to it.
- Do not claim an exact fee, eligibility or guaranteed funding result in metadata without verified source/review.
- Block scripts/markup, unsafe image URLs and private/missing attachments.
- Metadata cannot derive from a selected PDF, visitor input or Fee Calculator state.

## Accessibility

Social-image alt metadata is available where supported; it describes imagery rather than repeating title text. Preview/error controls are labelled and keyboard accessible. Warnings are not colour-only.

## State previews and failure handling

Preview current values, edited draft, overlong/duplicate copy, missing social attachment and stale branch naming. Missing/corrupt values fall back to current JSX values. Site URL errors block canonical publication; they do not emit a malformed link.

## Storage and versioning

Use a typed page-SEO schema with override flags, attachment ID and page revisions, separate from visible Fees content. Migration seeds exact current values without changing `<head>`. Copy/image revisions never alter PDF records or visible feature media.

## Planned implementation files (future only)

- `src/pages/Fees.jsx`
- `src/components/Seo.jsx`
- `src/lib/pageSeoModel.js`
- `wordpress/mu-plugins/alexandra-visual-builder/assets/panels/fees-seo.js`
- `wordpress/mu-plugins/alexandra-visual-builder/tests/fees-seo.php`

## Current public section -> exact WP canvas parity

| Current public head output | Exact WP canvas requirement |
| --- | --- |
| `Admissions & Fees - Alexandra Montessori` | Same initial search/social title preview |
| Current fee-sheet/funding description | Same exact seeded meta/OG description |
| Canonical `https://alexandramontessori.co.uk/fees` | Same read-only resolved URL |
| `materials-shelf.webp` share image | Same initial attachment and crop preview |
| Visible feature uses a different image | Editing share media does not silently alter the page photo |

## Acceptance checklist

- [ ] Untouched metadata equals current `Fees.jsx`/`Seo.jsx` output.
- [ ] Canonical remains registry-bound to `/fees`.
- [ ] Financial wording receives source/review warnings.
- [ ] Social and feature images remain independent references.
- [ ] Invalid metadata restores safe exact defaults.
- [ ] Draft previews never change public PDFs, routes or content.

