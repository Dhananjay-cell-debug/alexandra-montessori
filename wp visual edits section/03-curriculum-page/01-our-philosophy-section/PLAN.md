# Curriculum — Our Philosophy section

## Intent and feel

Open with a thoughtful, human explanation of Alexandra’s philosophy beside a tall painting image. Editing should make the five-paragraph narrative approachable without flattening it into one mixed text blob.

## Current public section → exact WP canvas parity

- This is the first visible `CurriculumSection`: title `Our Philosophy`, white background, image left, text right.
- Image is `/assets/organisation/philosophy-painting-children.jpeg`, alt `Two children painting in a Montessori classroom`, position `center`, and portrait class `mx-auto aspect-[3/4] w-full max-w-md` rather than the helper’s default 4:3.
- Text is exactly five paragraphs covering childhood’s intrinsic value, following the child/relationships, staff support, structure with freedom, and `learning for life`.
- Shared helper DOM parity: one `<section>` with centred h2, then one two-column grid; no page header, eyebrow, cards, CTA, or extra split section exists.

## Editable elements and controls

- Section title; five repeatable paragraph blocks with constrained rich text; image upload/replace, alt, focal drag, zoom; background/spacing; image side; portrait ratio/max width; grid gap and text measure.
- Direct canvas text editing, paragraph add/duplicate/remove, drag/keyboard order, device preview, inheritance/source badges, reset and compare.
- “Motto phrase” is plain text today; link creation is optional through safe rich-text controls and must not happen automatically.

## Layers and dragging

- Exact tree: `Our Philosophy section` → `Section title`; `Content grid` → `Image block` → `Image`; `Text block` → `Paragraph 1` … `Paragraph 5`.
- Paragraphs reorder only inside text block; image/text side swaps through the existing `imageLeft` control. Title remains first; no paragraph can become a separate public section by drag.
- Visual column swap also updates DOM order intentionally; keyboard movement has announced positions.

## Responsive behaviour

- Preserve `py-14`→`sm:py-20`, title 4xl→5xl, one-column below `lg`, two columns/gap-14 at `lg`, portrait 3:4 max-w-md.
- Default mobile order is image then text because `imageLeft=true`; device-specific order override is allowed only if DOM order follows.
- Long paragraph preview at 200% zoom must expand naturally without clipping or fixed height.

## Record/template binding and overrides

- Content belongs to singleton `page:curriculum.sections.philosophy`; it is not nursery-specific and never inherits nursery records.
- Layout defaults inherit a `curriculum-section` presentation template, while portrait ratio/max width, five paragraphs, image and title are explicit Philosophy values.
- Reset can target one field or all local presentation overrides without importing another curriculum section’s image/copy.

## Protected rules

- Keep this as one current public section and title as h2 under exact parity; no raw HTML/classes/scripts or unsafe links.
- At least one valid paragraph and image/alt are required while section is visible; destructive media-library deletion is separate from replacement.
- Background/type/spacing use approved tokens; changing heading semantics surfaces page-wide heading diagnostic.

## Accessibility

- Image alt remains meaningful and scene-specific; title precedes content in section semantics even when image is visually first.
- Paragraph/column DOM order matches configured visual order, contrast passes on white, focus controls remain visible, motion respects reduced-motion.
- Current absence of a page h1 remains a page diagnostic; this section plan does not invent one.

## Empty and error states

- Zero paragraphs blocks publish or intentionally disables the whole section; incomplete new paragraph stays draft-only.
- Broken image uses last published asset then approved placeholder with editor warning; missing alt blocks image publication.
- Corrupt layout override falls back to exact current white/image-left/portrait defaults.

## Storage and versioning

- Store normalized paragraph IDs/AST, media ID/alt/crop/transform, title, visibility and sparse layout/device overrides at `pages/curriculum/sections/philosophy`.
- Version paragraph moves separately from edits and include crop thumbnail, template revision, editor/time and public preview diff.
- Restore is section-local and never changes EYFS/Montessori/Where They Meet.

## Planned implementation files (future only)

- `am-visual-builder/schema/pages/curriculum/philosophy.php`
- `am-visual-builder/admin/pages/curriculum/sections/PhilosophyEditor.jsx`
- `am-visual-builder/admin/components/OrderedParagraphEditor.jsx`
- `am-visual-builder/runtime/pages/curriculum/CurriculumSection.php`
- `am-visual-builder/content/pages/curriculum/philosophy.json`

## Acceptance checklist

- [ ] Default public output is the exact first section with title, five paragraphs, white background and portrait painting image.
- [ ] Image/text remain one section and default image-left order matches JSX.
- [ ] Upload, focal crop, alt, paragraph direct edit/add/remove/pointer+keyboard order, mobile/desktop preview and reset pass.
- [ ] Heading diagnostic, contrast, zoom, broken/empty states, diff and isolated restore pass.

