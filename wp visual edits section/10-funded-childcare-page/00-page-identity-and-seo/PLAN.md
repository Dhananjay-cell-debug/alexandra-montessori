# Funded Childcare — page identity and SEO plan

## Intent and feel

Give the funding guide a precise, reassuring search result without making volatile eligibility wording feel casually editable. This is a publishing panel for metadata, not an invented visible page section.

## Exact current JSX and public evidence

- `FundedChildcare.jsx` renders `Seo` before `PageHeader`.
- Exact title: `Funded Childcare`.
- Exact description: `Government-funded childcare at Alexandra Montessori - 15 and 30 hours, funding from 9 months and two-year-old funding. See what you're entitled to and how to claim.`
- Exact route/canonical input: `/funded-childcare`.
- No image prop is supplied, so `Seo.jsx` currently resolves its exact default social image, `/assets/organisation/teacher-hug.webp`, rather than a page-owned image.

## Current public section -> exact WP canvas parity

| Current JSX/public output | Exact seeded WordPress parity |
| --- | --- |
| Non-visual `Seo` component | A separate `Search & sharing` inspector, never a public canvas section |
| Title `Funded Childcare` | Same initial SEO title |
| Exact 15/30-hours description above | Same initial description, punctuation and apostrophe |
| Path `/funded-childcare` | Read-only canonical route owned by the route registry |
| No page-specific SEO image; `Seo.jsx` default `/assets/organisation/teacher-hug.webp` | Visible `Inherited site image` state resolving that exact current asset; no fabricated page image |

## Exact editing controls

- Search title, meta description, optional social title, social description and social image.
- Index/follow permission, search-result preview, social-card preview, character guidance and funding-claim review status/date.
- Media-library picker, upload validation, focal-point control and `Clear override` for an optional social image.
- `Use search values` toggles for social title/description; they store inheritance, not duplicated strings.

## Layers, reorder and dragging

- Functional tree: `Page identity` → `Search metadata`, `Social metadata`, `Publishing status`.
- The tree is locked outside visible-section order; metadata cannot be dragged between Page Header and Funding Offerings.
- Only the social-image focal point is spatially draggable. Field order is fixed for predictable keyboard navigation.

## Responsive behaviour

- Desktop and mobile search/social previews use one stored value set and demonstrate likely truncation only.
- The narrow inspector stacks preview and fields without hiding validation or creating device-specific metadata forks.

## Data ownership and override semantics

- Store authored values on singleton `page:funded-childcare.seo`.
- Canonical path is route-owned and cannot be overridden here.
- Optional social fields inherit resolved SEO/site defaults until explicitly overridden; clearing an override resumes inheritance.
- Funding facts in metadata are page-owned reviewed copy, not automatically copied from offering cards, so a card edit cannot silently rewrite search text.

## Protected behaviour

- `noindex` requires elevated permission and explicit confirmation.
- Escape all text, validate media type/URL and prohibit raw meta tags, scripts or canonical text entry.
- Changes to ages, hours, entitlement or eligibility language require a named reviewer and review date before publish.

## Accessibility

- Inspector fields, counters and preview modes have programmatic labels and keyboard operation.
- Metadata does not create duplicate visible headings or off-screen public copy.
- Validation uses text and focus movement, not colour alone.

## Empty, loading and error states

- Empty required title/description blocks publish and offers the exact current seed as recovery.
- Failed preview generation never loses the draft; it shows a labelled preview-unavailable state.
- A missing/broken optional social image returns to inherited site imagery and identifies that provenance.

## Storage and versioning

- Store under `pages/funded-childcare/seo` with draft revision, published revision, editor, timestamp, review metadata, inheritance flags and media ID/crop.
- Field-level diff must distinguish authored wording changes from inherited site-image changes.
- Restore affects SEO only and cannot roll back visible page sections.

## Planned implementation files (future only)

- `am-visual-builder/schema/pages/funded-childcare/seo.php`
- `am-visual-builder/admin/pages/funded-childcare/SeoPanel.jsx`
- `am-visual-builder/admin/components/FundingClaimReview.jsx`
- `am-visual-builder/runtime/pages/funded-childcare/seo-adapter.php`

## Acceptance checklist

- [ ] Default metadata exactly matches current title, description and `/funded-childcare` path.
- [ ] No public SEO section or invented social image appears; the untouched preview resolves `/assets/organisation/teacher-hug.webp`.
- [ ] Inherited and overridden social values are visibly distinct and reversible.
- [ ] Funding-claim review and noindex permission gates work.
- [ ] Mobile/desktop previews, validation, draft retention, diff and isolated restore pass.
