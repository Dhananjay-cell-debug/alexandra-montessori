# Curriculum — page SEO functional region

## Intent and feel

Give the curriculum a clear, trustworthy search/share identity through a small guided publishing panel, without inventing a visible header section that the current page does not have.

## Current public section → exact WP canvas parity

- `Curriculum.jsx` first renders `Seo` with title `Our Curriculum`, description `Montessori-inspired practice woven with the EYFS, including our philosophy, the EYFS framework and the Montessori approach at Alexandra Montessori.`, and path `/curriculum`.
- No social image is passed. No visible page header/h1 exists before the first `Our Philosophy` h2; WP parity must not fabricate one inside the public canvas.
- The inspector should flag the current h1 absence as an accessibility/SEO review item without silently altering the public DOM.

## Editable elements and controls

- SEO title/description, optional social title/description/image, index/follow, search/social previews, media/focal controls, character guidance and inheritance/reset.
- A heading-structure diagnostic lists current first visible heading (`Our Philosophy`, h2) and links editors to the affected section; it is diagnostic, not a replacement block.

## Layers and dragging

- Locked functional layer `Page → Search & sharing`; it cannot enter the four-section canvas order.
- Only social-image focal position is draggable; metadata fields are not visual blocks.

## Responsive behaviour

- One stored metadata set previews likely desktop/mobile truncation; narrow inspector keeps warnings and field labels accessible.

## Record/template binding and overrides

- Bind to singleton `page:curriculum.seo`; optional fields inherit global site SEO until explicitly overridden.
- Canonical `/curriculum` derives from the route and is read-only. Section titles do not silently overwrite SEO title or vice versa.

## Protected rules

- Escaped plain text, one canonical, safe absolute share image, guarded noindex, no raw schema/scripts.
- Publish workflow requires acknowledgement of unresolved heading-structure warning, but exact parity remains available until a separately approved public change.

## Accessibility

- All preview/diagnostic controls are keyboard-labelled; metadata produces no duplicate public text.
- Heading diagnostic is announced with severity and exact section target, not colour alone.

## Empty and error states

- Missing title/description blocks publish and offers exact current fallbacks; failed social asset retains last publication and draft.
- Diagnostic failure never inserts hidden or visible public content.

## Storage and versioning

- Store `pages/curriculum/seo` with draft/published revision, inheritance snapshot, asset ID, editor/time and acknowledged diagnostics.
- Restore affects SEO only and records whether the h1 warning existed at that revision.

## Planned implementation files (future only)

- `am-visual-builder/schema/pages/curriculum/seo.php`
- `am-visual-builder/admin/pages/curriculum/SeoPanel.jsx`
- `am-visual-builder/admin/components/HeadingStructureDiagnostic.jsx`
- `am-visual-builder/runtime/pages/curriculum/seo-adapter.php`

## Acceptance checklist

- [ ] Defaults match current title, description and `/curriculum` canonical.
- [ ] No invented public header/h1 appears.
- [ ] Current missing-h1 issue is clearly diagnosed and acknowledged, not silently “fixed”.
- [ ] Inheritance, media, validation, noindex guard, keyboard use and isolated restore pass.

