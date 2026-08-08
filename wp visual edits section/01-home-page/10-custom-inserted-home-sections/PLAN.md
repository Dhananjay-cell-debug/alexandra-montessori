# Home custom inserted sections plan

## Intent and feel

Custom sections should give the client room to add future Home content without dismantling the six carefully composed core sections. The experience should feel like choosing a well-made layout card, placing it at a clear insertion point, then editing it in the real responsive canvas—not like starting with an empty developer tool.

## Current evidence

- `homeCustomSections()` reads `amHomeDesign.customSections`; defaults are empty, so the untouched public Home has no custom inserted section.
- `Home.jsx` renders `CustomHomeSections` after each of six anchors: hero, feature links, benefits, about, testimonials and trust.
- The editor currently offers 13 templates: Blank design canvas, Photo + story, Four visual cards, Logo/accreditation strip, Tabs + CTA, Heading + rich copy, Three feature cards, Photo gallery, Numbers/stats, Testimonial spotlight, Milestone timeline, Centred CTA and FAQ/information rows.
- A new section receives stable ID/name, visible true, insertion anchor, background, responsive heights and template layers.
- Sanitization caps custom sections at 20, layers at 30, heights at 180–1000px desktop/tablet and 180–1200px mobile.

## Editable elements and controls

- Template gallery grouped by Basics, Story, Cards, Trust, Actions and Gallery; each card shows desktop/tablet/mobile thumbnails, layer count, purpose and accessibility cautions.
- Exact insertion selector: After Video hero / Feature links / Benefits / About / Testimonials / Trust; when adding from a selected custom section, place after that sibling within the same anchor group.
- New section name, visibility/draft state, background colour/token and responsive stage heights.
- Duplicate, move within an anchor, change anchor, hide/show, save as personal template and delete with impact summary.
- Blank canvas remains available but visually secondary to proven templates.
- Preflight after insertion: placeholder media, `#` links, overflow, empty text, contrast and mobile completion.
- The inserted-section summary must list every owned layer (text, image, logo, button, shape or visual tabs), its z-order and its Desktop/Tablet/Mobile completion state; detailed manipulation remains in `11-custom-section-layer-canvas`.

## Selection, layers and dragging

At section level, drag handles reorder custom sections only; dropping between core sections updates the named `after` anchor and sequence. Core sections themselves stay protected under `12-home-section-outline-order`. Section dragging does not alter internal layer coordinates. A selected custom section highlights its full stage and exposes its layer canvas plan.

## Desktop, tablet and mobile

Template preview must show the actual saved coordinates and heights at all three devices before insertion. A template is inserted with all three layouts populated, never desktop-only. Section height is independent per device with overflow warnings based on real layers. At 320px, content must stay inside the 390/342px design assumptions or be corrected before publish.

## Data ownership and bindings

Custom sections are Home-page-owned records inside the versioned Home design document. A template is an immutable seed; insertion deep-copies it into a new stable section/layer identity. Saved personal templates live in a separate template library and do not remain linked unless an explicit linked-template feature is designed later.

## Protected rules

- Custom sections cannot be inserted before the hero or after global footer/cookie components through this Home tool.
- Enforce section/layer caps and sanitized colours/numbers/types.
- Do not publish unresolved `#` CTA links, missing required media or off-canvas essential text.
- Deletion removes only the selected section and its owned element records after confirmation; revisions allow recovery.
- Template names/descriptions never become hidden public content by accident.
- Stats/accreditation/testimonial templates carry factual-content warnings; placeholders are not publishable claims.

## Accessibility and failure states

Template cards are keyboard-selectable and describe structure, not only thumbnails. Insertion returns focus to the new section and announces its position. A blank/zero-layer section stays visible in editor with “Add a layer” guidance but is omitted or blocked publicly if it has no meaningful output. Missing template data falls back to Blank canvas, while malformed imported templates are rejected with a field-level report.

## Storage and versioning

Continue `customSections` with stable IDs, named anchors and ordered array position. Store a `templateOrigin`/template version for diagnostics, while all inserted content remains independent. Revisions include create/duplicate/move/hide/delete operations and owned layers. Schema migrations preserve anchor/order; unknown anchors fall back to after Trust with a repair warning, matching current sanitizer safety.

## Planned implementation files (future only)

- `wordpress/mu-plugins/alexandra-visual-builder/assets/panels/home-section-library.js`
- `wordpress/mu-plugins/alexandra-visual-builder/includes/class-am-vb-home-templates.php`
- `src/components/home/CustomHomeSections.jsx`
- `src/lib/homeCustomSections.js`
- `wordpress/mu-plugins/alexandra-visual-builder/tests/home-custom-insertion.php`

## Current public section → exact WP canvas parity

| Current runtime/editor behaviour | WP canvas requirement |
| --- | --- |
| Default `customSections: []` | Untouched public/canvas Home contains no invented extra section |
| Six legal insertion anchors after core sections | Picker/drop indicators map to these exact `after` values |
| Custom sections render in saved array order per anchor | Canvas and public runtime produce identical sibling order |
| Current 13 template seeds | Template previews/inserted output use the actual current layer records, not substitute designs |
| 420/420/520px blank defaults and bounded heights | Initial blank stage and sanitizer ranges match runtime |

## Acceptance checklist

- [ ] Default Home remains six core sections with zero custom additions.
- [ ] Every existing template previews and inserts its exact current structure on all devices.
- [ ] Named insertion point/order is obvious and matches public output after reload.
- [ ] Placeholder/factual/link/mobile preflight prevents unfinished publication.
- [ ] Duplicate/move/delete affects only the intended custom section and owned layers.
- [ ] Revision recovery restores deleted/moved sections with stable IDs.
