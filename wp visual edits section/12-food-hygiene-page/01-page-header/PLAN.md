# Food & Hygiene — page header plan

## Intent and feel

Orient families with a plain, confident title and one transparent sentence before the factual branch cards. The current simplicity should be preserved rather than embellished.

## Exact current JSX and public evidence

- `FoodHygieneRating.jsx` passes only `title` and `intro` to `PageHeader`.
- Visible h1: `Food & Hygiene`.
- Visible intro: `Branch-level food hygiene information is listed clearly so parents can check the relevant public record directly.`
- `PageHeader.jsx` renders one white section with `pb-6 pt-12 sm:pt-16`, centred h1 at 4xl→5xl and conditional max-w-3xl intro.
- `PageHeader` renders no breadcrumb, eyebrow or flourish; none are passed on this page.

## Current public section -> exact WP canvas parity

| Current JSX/public layer | Exact seeded WordPress canvas layer |
| --- | --- |
| One white PageHeader section | One first visible `Page header` section |
| h1 `Food & Hygiene` | Required `Page title` layer with h1 semantics |
| Exact intro paragraph | `Introduction` layer beneath title |
| No crumb/eyebrow/media | No breadcrumb, eyebrow, image, overlay or decoration layers |
| Current centred spacing/type | Same seed tokens and responsive behaviour |

## Exact editing controls

- Direct plain-text title and constrained intro editor.
- White/approved background token, top/bottom spacing, width, alignment, title/intro size/colour and intro measure.
- Optional safe explanatory link inside `public record` copy is off by default and requires explicit edit; it is not a current visible link.
- Compliance wording review date/owner and live comparison against current seed.

## Layers, reorder and dragging

- Exact tree: `Page header` → `Page title`, `Introduction`.
- Title is protected first, cannot be removed/demoted or dragged below intro. Intro cannot leave this section.
- Header remains first visible section. No empty crumb/eyebrow layers are created.

## Responsive behaviour

- Preserve current `pt-12`→`sm:pt-16`, h1 4xl→5xl, centred max-w-3xl intro and natural wrapping.
- Mobile padding/line lengths avoid horizontal overflow; neither title nor compliance intro can be hidden per device.

## Data ownership and override semantics

- Header fields/style belong to singleton `page:food-hygiene.sections.header`.
- Copy is page-owned explanatory content; branch names/ratings are intentionally not embedded or auto-resolved here.
- Shared PageHeader style may be inherited; local overrides are sparse, source-labelled and revertible.

## Protected behaviour

- Exactly one visible h1; no raw HTML/classes/scripts or false certification language.
- Any new link uses a validated destination and safe attributes; default remains plain text.
- Adding breadcrumb/eyebrow/media is a public design change requiring review, not a seed control.

## Accessibility

- Maintain h1 then paragraph DOM order, sufficient contrast, readable measure and 200% zoom.
- Inspector controls and optional link editing are keyboard accessible; no empty nav landmark.
- Plain wording must remain understandable without reference to colour/cards below.

## Empty, loading and error states

- Empty h1 blocks publication and offers current title/WordPress page title recovery.
- Empty intro follows current component condition and omits the paragraph, with an editor warning about lost context.
- Corrupt local styles fall back to exact shared white/centred PageHeader seed.

## Storage and versioning

- Store `pages/food-hygiene/sections/header` with title, intro AST, review data, inherited/local style tokens and responsive overrides.
- Diff highlights compliance-copy and optional-link changes; restore does not affect ratings.

## Planned implementation files (future only)

- `am-visual-builder/schema/pages/food-hygiene/header.php`
- `am-visual-builder/admin/pages/food-hygiene/HeaderEditor.jsx`
- `am-visual-builder/admin/components/ComplianceCopyReview.jsx`
- `am-visual-builder/runtime/pages/food-hygiene/PageHeaderSection.php`

## Acceptance checklist

- [ ] Exact h1, intro, white section, spacing and centred responsive type reproduce.
- [ ] No breadcrumb, eyebrow, flourish, media or public-record link is invented by default.
- [ ] One-h1, compliance review, optional-link validation and inheritance/reset work.
- [ ] Missing title/intro, style fallback, keyboard, contrast, zoom, diff and restore pass.
