# Future page section template picker plan

## Intent and feel

The section picker should feel like a curated shelf of real Alexandra layouts: preview what a section does, understand its required content, and insert an independent copy at a precise point. It is a construction tool for an explicitly created future page, not permission to mix the current twelve page plans into one generic document.

## Current evidence

- The existing builder already supports reusable section templates through REST `/templates`, author metadata, insert and capability-gated delete.
- A selected generic visual section can be saved as a reusable template.
- Home separately has 13 custom absolute-canvas templates; conversion from a saved generic template to Home currently maps a limited set of element types.
- Generic visual documents contain ordered sections with stable IDs, visibility/lock/settings/groups/elements and are sanitized to bounded counts.
- No template insertion changes a public route until its owning page is published.

## Editable elements and controls

- Library tabs: Approved page sections, My reusable sections and Blank; Home-only custom templates are labelled incompatible unless an explicit safe converter exists.
- Filters by purpose (Hero, Story, Navigation, Cards, Gallery, Trust, CTA, FAQ/Form), device readiness, data binding and accessibility status.
- Each card shows actual desktop/tablet/mobile rendered thumbnail, layer map, required assets/copy, interactive behaviours, template version/author and “used on” count.
- Insert before/after a selected page section, or add as first section; preview the real page context before confirmation.
- Choose Copy (default) versus Linked style preset only; content is never silently linked across pages.
- Blank section asks for layout mode and meaningful name, then opens its own tailored controls.
- Post-insert checklist flags placeholder copy/media, `#` links, contrast, reading order and device overflow.

## Selection, layers and dragging

Section cards are selected/inserted by buttons, not dragged from a palette across iframes. Once inserted, whole-section handles reorder in the page outline with keyboard alternatives. Internal layers retain template grouping, locks and reading order; their IDs are regenerated to prevent collision. The picker never flattens a semantic form/accordion into arbitrary visual layers.

## Desktop, tablet and mobile

Only templates with an explicit three-device contract can receive “Approved” status. Thumbnails use the actual renderer at representative widths, not static marketing images alone. Device-incomplete personal templates may be inserted into a Draft but create a publish blocker. Section insertion preserves device values exactly and never guesses mobile from desktop without an explicit previewed conversion.

## Data ownership and bindings

Template records live in the existing template store with schema/version, author, compatibility and source-section snapshot. Insertion creates new section/element/group IDs in the owning page document and stores `templateOrigin` for traceability. Canonical CMS collection bindings must be chosen/validated for the new page; template sample content remains placeholder until explicitly accepted.

## Protected rules

- Never expose templates from private/unpublished client pages without permission.
- No cross-page duplicate IDs, attachment deletion or shared mutable content by default.
- Approved templates must pass semantic/accessibility/responsive/security review.
- Current page-family sections remain owned by their specific plans; cloning one into a future generic page requires a declared compatible template, not raw copy/paste.
- Template deletion does not delete existing inserted sections.
- Unresolved sample claims, reviews, stats and compliance logos block publication.

## Accessibility and failure states

Library filters/cards are fully keyboard operable with textual structure descriptions in addition to thumbnails. Insertion announces section name/position and focuses it. If a template version is missing/corrupt, do not partially insert; retain selection and show validation details. Missing media uses editor placeholders. If conversion loses an unsupported element/interaction, cancel with a compatibility report rather than silently degrade it.

## Storage and versioning

Extend reusable template schema with `schemaVersion`, `rendererCompatibility`, `deviceStatus`, `origin`, `reviewStatus` and checksum. Inserted copies retain origin/version but do not auto-update when the template changes. A future “update from template” must show a field-level diff and preserve page overrides. Revisions capture insertion/removal/reorder as page-document operations.

## Planned implementation files (future only)

- `wordpress/mu-plugins/alexandra-visual-builder/assets/panels/page-section-library.js`
- `wordpress/mu-plugins/alexandra-visual-builder/includes/class-am-vb-template.php`
- `wordpress/mu-plugins/alexandra-visual-builder/includes/class-am-vb-document.php`
- `src/components/visual-page/VisualSectionRenderer.jsx`
- `wordpress/mu-plugins/alexandra-visual-builder/tests/page-section-picker.php`

## Current public section → exact WP canvas parity

| Current evidence | WP canvas requirement |
| --- | --- |
| Existing current page sections remain code/page-family owned | Picker does not replace them with lookalike generic templates |
| Generic new document can be empty | No section appears until the client explicitly inserts one |
| Existing reusable template stores a section snapshot | Preview/insert renders that exact sanitized snapshot/version |
| Home custom templates use a different model | They stay clearly separated unless conversion reports exact compatibility |
| Global header/footer are outside page sections | Template picker cannot insert duplicate global components into page main |

## Acceptance checklist

- [ ] Library distinguishes approved, personal, blank and incompatible Home templates.
- [ ] Preview uses the actual renderer on all three devices.
- [ ] Insert regenerates IDs and preserves the exact chosen section structure.
- [ ] Placeholder/factual/device/accessibility issues block publish, not draft work.
- [ ] Existing pages/inserted copies do not change when a template changes/deletes.
- [ ] Unsupported conversions fail explicitly instead of dropping layers or behaviour.

