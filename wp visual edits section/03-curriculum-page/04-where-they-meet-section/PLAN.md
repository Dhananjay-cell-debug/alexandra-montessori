# Curriculum — Where They Meet section

## Intent and feel

Close the page by making the relationship between EYFS and Montessori feel coherent and reassuring, with the soft sage band and child-led creative image.

## Current public section → exact WP canvas parity

- Fourth and final visible `CurriculumSection`: title `Where They Meet`, background `bg-sage-50`, image left/text right.
- Image `/assets/organisation/collage-activity.webp`, alt `A child engaged in creative, child-led learning`, position `center top`, default 4:3.
- Exactly two paragraphs: first explains shared child-led hands-on foundations and what each system contributes; second explains Alexandra’s combined delivery.
- There is no closing CTA, cards, footer-like band, or fifth curriculum section in this component.

## Editable elements and controls

- Title, two ordered rich-text paragraphs, image/alt/focal/zoom, sage background/spacing, image side, ratio, gap/measure and reveal timing.
- Copy review ties assertions back to the EYFS and Montessori section revisions, showing divergence warnings but not auto-copying text.
- Direct edit, paragraph add/remove/reorder, safe links, template source/reset, and responsive preview.

## Layers and dragging

- Exact tree: `Where They Meet section` → `Section title`; `Content grid` → `Image block`; `Text block` → `Paragraph 1`, `Paragraph 2`.
- Paragraph drag/keyboard reorder stays inside text; side switch follows existing imageLeft behaviour and updates DOM order.
- This remains final within `Curriculum.jsx`; no invented CTA is attached. Page-level reorder, if permitted later, is a separate explicit action with heading/content warnings.

## Responsive behaviour

- Preserve shared `py-14/sm:py-20`, 4xl/5xl h2, one column then two at `lg`, current image-first order and 4:3 crop.
- Base `center top` focal position; sparse device override. Text and image expand without clipping at 200% zoom.

## Record/template binding and overrides

- Content belongs to `page:curriculum.sections.whereTheyMeet`; references to the concepts do not bind/edit the other two sections.
- Presentation inherits curriculum-section template; sage background, image-left direction, asset/crop and two paragraphs are explicit local values.
- Reset never duplicates or replaces EYFS/Montessori content.

## Protected rules

- One exact current public section, h2, valid media/alt, safe rich text/URLs, approved tokens, at least one paragraph.
- Relationship claims require review when upstream EYFS/Montessori copy changes materially; no silent synthesis or inserted CTA.

## Accessibility

- Informative alt, title/content semantics, DOM/visual order parity, sage contrast, keyboard reorder, reduced motion and zoom.
- Current page-level missing h1 is diagnosed globally rather than changing this h2 in isolation.

## Empty and error states

- Empty section copy blocks publish or requires intentional section disable; incomplete paragraph stays draft-only.
- Broken image uses last published/placeholder; corrupt local layout falls back to sage/image-left/4:3/center-top.
- Upstream comparison failure shows `review unavailable` in admin but never removes public content.

## Storage and versioning

- Store `pages/curriculum/sections/where-they-meet` with paragraph IDs/AST, review links to source revisions, media/crop/alt, title and sparse layout/device overrides.
- Diff shows upstream review status separately from local edits and crop/order changes; isolated restore.

## Planned implementation files (future only)

- `am-visual-builder/schema/pages/curriculum/where-they-meet.php`
- `am-visual-builder/admin/pages/curriculum/sections/WhereTheyMeetEditor.jsx`
- `am-visual-builder/admin/components/CurriculumCoherenceReview.jsx`
- `am-visual-builder/runtime/pages/curriculum/CurriculumSection.php`
- `am-visual-builder/content/pages/curriculum/where-they-meet.json`

## Acceptance checklist

- [ ] Exact final sage section, title, two paragraphs, collage image, `center top`, image-left and 4:3 reproduce.
- [ ] No closing CTA or invented fifth section appears.
- [ ] Coherence review, direct edit/order, side/DOM switch, crop/alt, responsive/zoom, errors, diff and isolated restore pass.

