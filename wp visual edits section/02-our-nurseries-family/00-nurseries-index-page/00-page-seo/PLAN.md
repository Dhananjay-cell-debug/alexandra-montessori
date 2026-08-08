# Nurseries index — SEO functional region

## Intent and feel

Give `/nurseries` a reliable search/share identity through a compact guided panel. This is a functional region, not an invented visible canvas section.

## Current public section → exact WP canvas parity

- Before the first public section, `Nurseries.jsx` renders `Seo` with title `Our Nurseries`, description `Welcoming Alexandra Montessori nurseries offering thoughtful, child-centred early years care.`, and path `/nurseries`.
- WP preview must reproduce those resolved defaults exactly; metadata appears in a separate `Search & sharing` panel and never as a draggable public block.

## Editable elements and controls

- Title, meta description, optional social title/description/image, and index/follow status.
- Character guidance, search-result preview, social preview, media picker, crop/focal control, inheritance indicator, reset, undo, and revision compare.

## Layers and dragging

- One locked functional layer: `Page → Search & sharing`.
- It can be focused from the page navigator but cannot be reordered among visible sections; only the social image crop is directly draggable.

## Responsive behaviour

- Mobile/desktop snippet previews use the same stored metadata and only demonstrate likely truncation.
- Editor panels collapse cleanly on narrow screens without hiding validation.

## Record/template binding and overrides

- Store on singleton `page:nurseries.seo`; optional fields inherit site SEO defaults until explicitly overridden.
- Canonical path derives from the page route and is read-only; no nursery record owns directory metadata.

## Protected rules

- Keep canonical `/nurseries`, escaped plain text, one canonical value, and validated share media.
- `noindex` requires elevated permission and explicit confirmation.

## Accessibility

- Preview controls are keyboard-labelled; metadata does not create duplicate visible headings.
- Media has an editor-facing description even though social platforms determine final presentation.

## Empty and error states

- Missing required description blocks publish and offers the exact current fallback.
- A failed media asset retains the last published image and reports the broken reference without losing the draft.

## Storage and versioning

- Version `pages/nurseries/seo` with draft/published revision IDs, editor, timestamp, inheritance snapshot, and asset ID.
- Restore affects this functional region only.

## Planned implementation files (future only)

- `am-visual-builder/schema/pages/nurseries/seo.php`
- `am-visual-builder/admin/pages/nurseries/SeoPanel.jsx`
- `am-visual-builder/runtime/pages/nurseries/seo-adapter.php`
- Future resolver integration in `src/pages/Nurseries.jsx`.

## Acceptance checklist

- [ ] Untouched WP output matches the current title, description, and `/nurseries` canonical.
- [ ] Visible canvas contains no fake SEO section.
- [ ] Inherited and overridden values are unmistakable.
- [ ] Keyboard, validation, preview, reset, and revision restore work.
- [ ] Accidental `noindex` publication is prevented.

