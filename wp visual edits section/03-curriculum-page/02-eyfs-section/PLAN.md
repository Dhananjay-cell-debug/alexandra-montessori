# Curriculum — The EYFS section

## Intent and feel

Explain a formal statutory framework in warm, readable language, using the sand band and hands-on water-pouring image to keep the section approachable.

## Current public section → exact WP canvas parity

- Second visible `CurriculumSection`: title `The EYFS`, background `bg-sand`, image left/text right.
- Image `/assets/organisation/water-pouring.webp`, alt `A child learning through a hands-on early years activity`, position `center 22%`, helper-default `aspect-[4/3] w-full`.
- Exactly two paragraphs: first defines EYFS and its purpose; second describes three prime/four specific areas and observation rather than testing.
- It remains one `<section>` with centred h2 and one responsive two-column grid; no separately draggable “seven areas” cards exist today.

## Editable elements and controls

- Title, two repeatable rich-text paragraphs, image/alt/focal/zoom, background/spacing, image side, 4:3 ratio, gap, text measure and reveal timing.
- Statutory-content review status/date, plain-language preview, paragraph reorder, direct canvas edit, approved link control for future official source, reset/diff.

## Layers and dragging

- Exact tree: `The EYFS section` → `Section title`; `Content grid` → `Image block`; `Text block` → `Paragraph 1`, `Paragraph 2`.
- Reorder paragraphs only within text; switch image/text side through existing imageLeft option with DOM-order parity. Do not convert learning areas into invented sub-sections/cards.

## Responsive behaviour

- Default `py-14/sm:py-20`, 4xl/5xl h2, one column to `lg`, image then text mobile, two columns at `lg`, 4:3 crop.
- `center 22%` is base crop; sparse device override allowed with subject-safe preview. Long statutory copy expands at 200% zoom.

## Record/template binding and overrides

- Content belongs only to `page:curriculum.sections.eyfs`; it does not inherit About-page or nursery copy.
- Presentation inherits curriculum-section template; sand background, image, crop and two paragraphs are EYFS-specific explicit values.
- Reset local style separately from reviewed copy; template changes cannot overwrite confirmed statutory text.

## Protected rules

- One current public section, h2 semantics, safe rich text/URLs, approved tokens, valid media/alt, at least one paragraph.
- Claims about government requirements/areas of learning require reviewer and date before publish when edited; no silent AI rewriting.

## Accessibility

- Scene-specific alt, logical title/content order, DOM/visual column parity, sand contrast, keyboard reordering, reduced motion and zoom.
- Current page-level missing h1 stays a diagnostic, not solved by converting this h2 alone.

## Empty and error states

- Empty/unfinished reviewed text cannot publish; draft remains intact and current publication stays live.
- Broken image uses last publication/placeholder; corrupt crop/layout falls back `center 22%`, sand, image-left, 4:3.
- Failed review metadata is an admin error, never public content.

## Storage and versioning

- `pages/curriculum/sections/eyfs` stores paragraph IDs/AST, review metadata, media/crop/alt, title and sparse layout/device overrides.
- Immutable review audit identifies reviewer/source/date; diff shows wording, order, crop and template-source changes.
- Restore is EYFS-only and does not roll back other curriculum sections.

## Planned implementation files (future only)

- `am-visual-builder/schema/pages/curriculum/eyfs.php`
- `am-visual-builder/admin/pages/curriculum/sections/EyfsEditor.jsx`
- `am-visual-builder/admin/components/RegulatedCopyReview.jsx`
- `am-visual-builder/runtime/pages/curriculum/CurriculumSection.php`
- `am-visual-builder/content/pages/curriculum/eyfs.json`

## Acceptance checklist

- [ ] Exact sand section, title, two paragraphs, image, `center 22%`, image-left and 4:3 output reproduce.
- [ ] No invented learning-area cards/sections appear.
- [ ] Statutory review, direct edit, pointer/keyboard order, crop/alt, device/zoom, error fallbacks and isolated restore pass.

