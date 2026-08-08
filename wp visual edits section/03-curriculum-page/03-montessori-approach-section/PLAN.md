# Curriculum — The Montessori Approach section

## Intent and feel

Let the copy lead this section, followed by a calm material-focused image on desktop, reflecting child-led exploration and practical independence.

## Current public section → exact WP canvas parity

- Third visible `CurriculumSection`: title `The Montessori Approach`, white background, `imageLeft={false}` so text is first and image second in the grid/DOM.
- Image `/assets/organisation/shape-work.webp`, alt `A child using Montessori sensorial materials`, position `center 15%`, default 4:3.
- Exactly two paragraphs: one explains Dr Maria Montessori, prepared environments/materials/freedom of choice; the second connects independence to concentration, resilience, love of learning and practical life.
- No timeline, icon pillars, biography card, or separate practical-life subsection currently exists.

## Editable elements and controls

- Title, two ordered rich-text paragraphs, image upload/alt/focal/zoom, background/spacing, text-first/image-first switch, 4:3 ratio, grid gap/measure and reveal timing.
- Source/review note for educational claims, direct editing, safe link tool, paragraph add/remove/reorder, template inheritance/reset and exact device preview.

## Layers and dragging

- Exact tree: `The Montessori Approach section` → `Section title`; `Content grid` → `Text block` → two paragraphs; `Image block` → image.
- Paragraph reorder stays in text; current text-first layout is the explicit `imageLeft=false` value. Side switch updates visual and DOM order, not CSS-only order.
- Dragging cannot split the content into invented public sections.

## Responsive behaviour

- Preserve shared spacing/title scale and one-to-two-column transition at `lg`; current mobile and desktop DOM begins with text because `imageLeft=false`.
- Base crop `center 15%`; device crop override is sparse. Long copy and image remain unclipped at zoom.

## Record/template binding and overrides

- Content binds to singleton `page:curriculum.sections.montessoriApproach`.
- Shared curriculum-section template supplies layout tokens; white background, text-first direction, image/crop and paragraph set are local explicit values.
- No binding to the separate About-page `montessoriApproach` cards despite similar naming.

## Protected rules

- One current section/h2, safe rich text, valid media/alt, approved tokens, and at least one paragraph.
- Educational/historical claim edits require review metadata; no silent cross-import from About or nursery sections.

## Accessibility

- Text-first DOM and visual order match; image alt is activity-specific; title labels content, contrast and reduced motion pass.
- Keyboard paragraph reordering and side switch announce results; zoom has no clipping.

## Empty and error states

- Empty paragraph set blocks publish/intentional disable; incomplete paragraph remains draft.
- Broken image retains last published asset then placeholder; layout corruption returns exact white/text-first/4:3/`center 15%` defaults.
- Source/review service failure preserves existing publication.

## Storage and versioning

- Store `pages/curriculum/sections/montessori-approach` with paragraph IDs/AST, review metadata, media/crop/alt, explicit direction and sparse layout/device overrides.
- Diff highlights paragraph moves, side changes and crop thumbnails; section-only restore.

## Planned implementation files (future only)

- `am-visual-builder/schema/pages/curriculum/montessori-approach.php`
- `am-visual-builder/admin/pages/curriculum/sections/MontessoriApproachEditor.jsx`
- `am-visual-builder/admin/components/EducationalCopyReview.jsx`
- `am-visual-builder/runtime/pages/curriculum/CurriculumSection.php`
- `am-visual-builder/content/pages/curriculum/montessori-approach.json`

## Acceptance checklist

- [ ] Exact white section, title, two paragraphs, shape-work image, `center 15%`, text-first and 4:3 reproduce.
- [ ] No About-page card data or invented content leaks in.
- [ ] DOM-aware side switch, paragraph editing/reorder, review, crop/alt, responsive/zoom, errors, diff and restore pass.

