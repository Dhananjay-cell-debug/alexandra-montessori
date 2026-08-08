# Fees feature photo plan

## Intent and feel

The square Montessori activity photo should bring warmth and context to a functional fee-sheet page without competing with download actions. Editing should prioritize subject-safe crop, accessible description and performance.

## Exact current JSX/public evidence

- Body section is cream, `pt-8`, `pb-16 sm:pb-20`; its grid becomes `0.92fr 1.08fr` at `lg`, gap 10, items-start.
- The left `Reveal` is centred/full width with max width 34rem below `lg`; `lg:max-w-none` removes that cap in its grid column.
- `Img` source is `/assets/organisation/portrait-girl.webp`.
- Alt is exactly `A child using Montessori number materials`.
- `position="top"`, `aspect-square w-full shadow-card`; `Img` supplies its default rounded-4xl frame, object-cover, lazy loading and sage fallback.
- It is not a link and currently has no caption/overlay.

## Editable controls

- Attachment replace/library selection, meaningful alt, focal x/y and per-device crop preview.
- Approved square aspect/current radius/shadow, max-width/alignment and performance/file-size guidance.
- Reset to exact current source/alt/top crop.
- Preview loading, broken media, portrait/landscape replacement and narrow body grid.
- No link/caption/overlay fields as current elements; each would require an explicit approved markup change.

## Layers, reordering and dragging

The photo is one media frame. Crop drag moves image inside its square; frame alignment remains bounded in the left grid column. Free absolute movement is prohibited. Current body order is image column then fee/card column; this plan does not expose an arbitrary swap/full-width redesign as if it exists.

## Desktop, tablet and mobile

Below `lg`, photo appears first and centres up to 34rem; at `lg`, it occupies the 0.92fr left column. Device-specific focal points may keep the child/materials visible while preserving square aspect. Test 320/390/768/1024+, 200% zoom and slow-image fallback.

## Data ownership and bindings

Media/presentation belong to Fees page; WordPress owns attachment and responsive derivatives. The visible image is independent from SEO’s `materials-shelf.webp`. Body-grid definition is a shared Fees layout setting referenced by photo/cards, not duplicated with conflicting values.

## Protected behavior

- Meaningful alt remains required for a meaningful replacement.
- Preserve aspect/containment and clamp focal/width values.
- Block unsafe/private/unsupported media and warn on oversized uploads.
- Replacing media never deletes the old attachment.
- Do not turn the photo into a download/navigation target unintentionally.

## Accessibility

Alt describes content, not filename/marketing copy. The image has no tab stop while non-interactive. Fallback is decorative only when the real image fails and does not emit a broken accessible control. Reveal respects reduced motion.

## State previews and failure handling

Preview current loaded photo, loading skeleton, broken source/fallback, missing alt, light/dark/portrait replacement and mobile/desktop crop. Invalid media retains the last published attachment. If source is absent, `Img`-style sage fallback preserves layout without a broken icon.

## Storage and versioning

Store attachment ID, alt/focal points and bounded frame settings in a stable Fees media record. Revisions distinguish replacement from crop/style. Keep current asset/alt/top position as immutable fallback values.

## Planned implementation files (future only)

- `src/components/fees/FeesFeaturePhoto.jsx`
- `src/components/Img.jsx`
- `src/pages/Fees.jsx`
- `wordpress/mu-plugins/alexandra-visual-builder/assets/panels/fees-feature-photo.js`
- `wordpress/mu-plugins/alexandra-visual-builder/tests/fees-feature-photo.php`

## Current public section -> exact WP canvas parity

| Current public media behavior | Exact WP canvas requirement |
| --- | --- |
| `portrait-girl.webp` | Same initial asset and WordPress-resolved URL |
| Current Montessori-materials alt | Same exact meaningful alt default |
| Square, rounded-4xl, shadowed, top crop | Same frame/crop/style in real iframe |
| Max 34rem before `lg`, left 0.92fr at `lg` | Same width/alignment/body-grid context |
| Non-link with no caption/overlay | Canvas must not fabricate those layers |

## Acceptance checklist

- [ ] Untouched photo matches current source/crop/frame at all widths.
- [ ] SEO and visible image remain independently editable.
- [ ] Crop is distinct from frame alignment and stays bounded.
- [ ] Missing/broken media preserves a composed square fallback.
- [ ] Meaningful alt and performance warnings are enforced.
- [ ] Photo order remains exact relative to fee cards.

