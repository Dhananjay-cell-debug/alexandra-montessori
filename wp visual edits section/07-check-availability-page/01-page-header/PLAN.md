# Check Availability page header plan

## Intent and feel

The header should remain plain, centred and immediately clear: one calm H1 followed by a concise admissions introduction. Its restraint is important because the detailed form starts directly below it.

## Exact current JSX/public evidence

- `Availability.jsx` calls `PageHeader` with title `Check Availability` and the exact introduction beginning `Tell us what you need...` and ending `...best next step for your family.`
- `PageHeader.jsx` accepts only `title` and `intro` and renders a white section, centred container, Reveal H1 and optional Reveal paragraph.
- It does **not** render a breadcrumb, eyebrow, flourish, background image, icon or CTA. Passing such props would currently do nothing.
- Section spacing is `pt-12 sm:pt-16 pb-6`; H1 is `text-4xl sm:text-5xl`; intro is max-width `3xl`, `text-base sm:text-lg`, with `mt-5`.

## Editable controls

- Page-specific H1 and introduction text with direct canvas editing and reset.
- White/background token, top/bottom padding, container width, intro maximum width, alignment and approved typography scale.
- Reveal on/off and bounded delay controls, with reduced-motion preview.
- No eyebrow/breadcrumb/media/CTA control in this plan because none exists in the current public section; adding one would require a separately approved component/schema change.

## Layers, reordering and dragging

The protected layer order is section surface -> H1 -> intro. H1 and intro remain in document flow; they are not freely draggable or resizable into overlap. The whole header is the first page-owned section and can be selected/focused, but it cannot be moved inside the form card.

## Desktop, tablet and mobile

Desktop/tablet/mobile controls may tune padding, text scale, intro width and alignment within tested bounds. Current default remains centred at every width. Test 320px, long copy, 200–400% zoom and the real global hanging-logo/header clearance.

## Data ownership and bindings

Header copy/presentation belong to the Check Availability page. `PageHeader` supplies shared markup and fallback classes; global navigation/header remains separately owned. The H1 text may default from page identity but an explicit override is stored and displayed as such.

## Protected behavior

- Exactly one visible page H1; it cannot be demoted to a span for styling.
- No raw HTML/scripts or form controls inside the intro.
- Keep the header above the admissions body in DOM order.
- Do not imply guaranteed availability, pricing or funding eligibility through unreviewed copy.

## Accessibility

H1 and paragraph preserve semantic reading order. Text contrast passes against the selected surface, line length remains readable, and Reveal respects `prefers-reduced-motion`. Zoom/wrapping cannot clip the H1.

## State previews and failure handling

Preview current, long-title, long-intro, intro-empty, reduced-motion and narrow-mobile states. An empty intro legitimately omits the paragraph and its margin, matching `PageHeader`. An empty H1 blocks publication and falls back to `Check Availability` in preview.

## Storage and versioning

Store a page-section record keyed to `availability-page-header`, with shared-component schema version and per-device style values. Revisions preserve current strings as defaults. Future `PageHeader` schema additions must not cause unused breadcrumb/eyebrow fields to appear automatically.

## Planned implementation files (future only)

- `src/components/PageHeader.jsx`
- `src/pages/Availability.jsx`
- `src/lib/pageSectionModel.js`
- `wordpress/mu-plugins/alexandra-visual-builder/assets/panels/availability-page-header.js`
- `wordpress/mu-plugins/alexandra-visual-builder/tests/availability-page-header.php`

## Current public section -> exact WP canvas parity

| Current public element | Exact WP canvas requirement |
| --- | --- |
| Plain white header section | Same surface and `pt-12/sm:pt-16 pb-6` default spacing |
| Centred H1 `Check Availability` | Same heading level, text, type scale and Reveal behaviour |
| One centred admissions intro | Same exact copy, max width and responsive font size |
| No eyebrow/breadcrumb/flourish/photo/CTA | Canvas must not invent or show any of these as if public |

## Acceptance checklist

- [ ] Untouched canvas matches `PageHeader.jsx` at each viewport.
- [ ] One visible semantic H1 is preserved.
- [ ] Empty intro omits only the paragraph and margin.
- [ ] No unsupported crumb/eyebrow/media controls are presented.
- [ ] Mobile/zoom states have no clipping or global-header overlap.
- [ ] Header revisions do not alter SEO, form fields or global navigation.

