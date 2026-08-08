# Admissions visual card plan

## Intent and feel

This card should make the form feel human before asking for details: a real nursery image with a quiet dark gradient and a short promise that a real branch team will respond. Media controls should feel like careful crop art direction, not an unrestricted poster designer.

## Exact current JSX/public evidence

- It is the first item in the left `Reveal` column of the cream body section.
- Outer frame is white, `rounded-4xl`, overflow-hidden and `shadow-card`.
- Image stage is `relative min-h-[26rem] bg-sage-700`; `friends-two.webp` fills it with `object-cover`.
- Image alt is exactly `Children at Alexandra Montessori`.
- Gradient is `from-sage-950/80 via-sage-900/25 to-transparent`, running upward from the bottom.
- Bottom copy is `Admissions support` plus H2 `Find the right place with a real nursery team`, padded 7 / 8 at `sm`.

## Editable controls

- Image attachment, replacement, alt, focal x/y, crop, source-size/weight guidance and reset.
- Stage minimum height, frame radius/shadow and bounded width within the left column.
- Gradient stops/opacity from an approved dark-sage preset, with live contrast sampling.
- Eyebrow and H2 copy, inset, width, alignment and bounded typography controls.
- Section preview at current, light-photo, portrait-photo, missing-media and long-copy states.

## Layers, reordering and dragging

Layer order is locked: sage fallback -> image -> gradient -> bottom text group. Crop dragging moves the image focal point inside its frame; frame movement remains governed by the page grid. Text may receive small bounded inset adjustments but cannot be dragged behind the gradient, outside the stage or above the form column.

## Desktop, tablet and mobile

Below `lg`, visual card and highlights precede the form as one stacked column; at `lg`, it occupies the 0.9fr left rail. Stage remains at least 26rem and text padding increases at `sm`. Provide independent focal previews for 320/390/768/1024+ widths without inventing a different mobile section.

## Data ownership and bindings

Card copy/media/presentation belong to the Check Availability page. WordPress media library owns the source attachment. The same `friends-two.webp` is also the current social image, but these are independent references: changing one must not silently change the other.

## Protected behavior

- Preserve meaningful alt and `object-cover` fallback behaviour.
- Clamp height/crop/inset so copy remains inside the visual safe area.
- Overlay cannot be reduced below readable contrast for white copy.
- Do not add a link or form action to the current non-interactive card without a separately approved behavior.
- Replacing media never deletes the previous attachment.

## Accessibility

H2 follows the page H1 and remains real text, not baked into media. The photo has meaningful alt; gradient is decorative. Contrast is checked at the actual text pixels. Reduced motion disables only Reveal, not content visibility.

## State previews and failure handling

Preview default, media loading, broken media, bright/low-contrast image, long H2 and reduced-motion states. On load failure, retain the sage-700 stage and readable copy rather than show a broken image icon. Missing alt blocks a meaningful-image replacement from publication.

## Storage and versioning

Store a typed media reference, focal point, copy and bounded stage/overlay values under a stable section key. Revisions distinguish media replacement from crop/overlay/copy edits. Current asset, alt, gradient and geometry are immutable fallbacks.

## Planned implementation files (future only)

- `src/components/availability/AdmissionsVisualCard.jsx`
- `src/pages/Availability.jsx`
- `src/lib/pageMediaModel.js`
- `wordpress/mu-plugins/alexandra-visual-builder/assets/panels/admissions-visual-card.js`
- `wordpress/mu-plugins/alexandra-visual-builder/tests/admissions-visual-card.php`

## Current public section -> exact WP canvas parity

| Current public layer | Exact WP canvas requirement |
| --- | --- |
| White rounded/shadowed outer card | Same frame, overflow and left-column position |
| 26rem-minimum `friends-two.webp` stage | Same asset, cover fit, alt and current centre crop |
| Bottom sage gradient | Same direction and 80%/25% current stops |
| `Admissions support` + current H2 | Same copy, hierarchy, inset and white styling |

## Acceptance checklist

- [ ] Untouched card visually matches current JSX.
- [ ] Crop and frame controls are distinct and bounded.
- [ ] Copy remains readable over every allowed image/overlay state.
- [ ] Broken media preserves a composed sage fallback.
- [ ] Mobile/desktop order stays consistent with the real page grid.
- [ ] Media editing is independent from SEO and other image references.

