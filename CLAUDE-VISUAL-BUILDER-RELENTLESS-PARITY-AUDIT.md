# Claude execution brief — relentless Visual Builder parity audit

Status: evidence-backed implementation brief, audited on 1 August 2026 against the local WordPress Visual Builder and the rendered React site.

This is not a request for another high-level plan. Treat it as a defect register, implementation contract, and recurring QA loop. Keep this file open while working. Mark an item complete only after it works in the real builder, survives save/reload, appears on the public route, and passes the relevant acceptance test.

The full control catalogue and original architectural intent remain in [FULL-SITE-VISUAL-THEME-BUILDER-PLAN.md](FULL-SITE-VISUAL-THEME-BUILDER-PLAN.md). The current implementation history is in [wp visual edits section/HANDOFF-NEXT-SESSION.md](wp%20visual%20edits%20section/HANDOFF-NEXT-SESSION.md). Where those documents and the live implementation differ, this audit describes the current defect.

## 1. Non-negotiable outcome

Make every existing public page and dynamic template as directly, safely, and predictably editable as Home, then go beyond Home where Home is still incomplete.

The public route set must remain fixed. Do not turn the Visual Builder into a general page creator. Expose every route that already exists, including dynamic templates and system routes, but do not let ordinary users create, trash, rename, or accidentally hide public routes from the builder.

“Every element is editable” has a precise meaning:

- Every visible piece of local copy can be edited directly.
- Every image, logo, icon, video, background, attachment, link, button label, card, divider, badge, and decorative shape can be selected and given the controls appropriate to its type.
- Every section, container, grid, card shell, modal, menu, form surface, and repeated-item template can be selected and styled.
- Every dynamic value has a visible source, scope, and direct path to edit its authoritative record. Do not duplicate Nursery, Job, Event, Article, Testimonial, fee, contact, or food-hygiene facts into page-design JSON.
- Computed values, consent behavior, validation, submissions, verified ratings, routing, and integrations remain functionally protected, but their presentation and allowed microcopy are editable. Selecting them must never end in a dead end.
- Every responsive and interactive state can be previewed before publishing.
- Every save is recoverable, revisioned, conflict-safe, and verifiable on the real route.

Home parity is the minimum, not the finish line.

### 1.1 Zero-blockade rule

The current inspector labels “PRESENTATION ONLY,” “Appearance only,” “Protected,” and similar terminal states are product defects. Remove the concept of a dead-end inspector.

There must be no selectable object for which the client gets only a warning, only Size/Move X/Move Y, or a “Save presentation” button. Every selected object must expose all controls applicable to that object across Content, Style, Layout, Responsive, Interaction, Data, Accessibility, and Advanced.

In particular:

- A protected frame/container still needs complete background, gradient/media, border, per-corner radius, shadow, opacity, per-side spacing, size/min/max, flex/grid, alignment, order, overflow, z-index, transform, responsive, interaction, duplicate/style-copy, reset, and visibility controls.
- Its nested labels, text, images, icons, links, controls, help copy, and state copy must be independently selectable and editable.
- A CMS-bound value must show and edit its source or open that exact source record; it must not stop at “Appearance only.”
- A computed calculator value must expose editable label/template/state styling and a Data explanation of its formula/source. The business formula is changed through an authorized calculator-settings contract, not by silently replacing the displayed number.
- A form control must expose all safe label/help/placeholder/options/state presentation while its submission identity and security remain governed by the form contract.
- A global object must open Global editing from the current selection.
- A genuinely permission-restricted field must show the missing capability and an actionable request/open-source path; it must not downgrade the entire block.

“No blockades” therefore means no dead ends and no artificially shallow inspector. It does not mean corrupting calculations, verified data, consent, routing, or submission behavior by treating runtime output as arbitrary text.

## 2. What was inspected

The audit covered:

- The Home page builder, its Pages, Regions, and Templates panels.
- Home text, image, section, custom-layer, responsive, and media controls.
- Every one of the 23 currently registered exact-route builder entries.
- Bound records, forms, global components, dynamic templates, and conditional regions.
- The route registry, design persistence, generic document system, editor branching, runtime DOM annotation, runtime style application, and production feature gate.
- The existing full-site visual-builder and CMS plans.

Coverage below counts non-global page elements. “Editable” means content or media is editable in the selected-element panel. Protected elements may expose appearance controls, but appearance-only is not content parity. “Generated” means the runtime invented a positional key such as page-region-text-7 rather than receiving a stable semantic key from the source.

Home rendered 83 annotated objects in total: 56 page objects plus its shared/global objects. All 56 page objects had explicit stable keys and none was protected. Global objects are excluded from all route percentages so the repeated header/footer/social/cookie chrome does not inflate or depress a page’s score.

## 3. Measured parity: Home versus every registered route

| Builder entry | Existing public/template route | Content-editable | Protected | Generated IDs | Current result |
|---|---|---:|---:|---:|---|
| Home | / | 56/56 (100%) | 0 | 0 | The only page with explicit, stable page-element keys and full direct content treatment. |
| Our Nurseries | /nurseries | 3/21 (14%) | 18 | 21 | Intro works; the complete nursery directory is appearance-only. |
| Hounslow nursery | /nurseries/hounslow | 21/150 (14%) | 129 | 150 | Hero/welcome/philosophy/meals partly work; most collections and facts are dead ends. |
| Heston nursery | /nurseries/heston | 21/150 (14%) | 129 | 150 | Same family-wide failure as Hounslow. |
| Hammersmith nursery | /nurseries/hammersmith | 14/133 (11%) | 119 | 133 | Same failure, plus no usable preview/control for the intentionally disabled meals state. |
| Curriculum | /curriculum | 19/19 (100%) | 0 | 19 | Direct copy/media is available, but every key is positional and therefore unsafe. |
| Careers | /careers | 44/77 (57%) | 33 | 77 | Static cards work; form internals and some collection/gallery structures do not. |
| Current Vacancies | /careers/vacancies | 1/15 (7%) | 14 | 14 | Only the header is directly editable; the archive is a dead end. |
| Vacancy detail template | /careers/vacancies/:slug | 4/4 (100% shown) | 0 shown | 3 | Misleading result: only the fallback fixture rendered. Real populated/closed states were not available to inspect. |
| Apply | /careers/apply | 12/40 (30%) | 28 | 39 | Header and fragments of form copy work; the form contract and all important states are unavailable. |
| News & Events | /events | 4/46 (9%) | 42 | 46 | Header/closing CTA work; event collection and states are appearance-only. |
| Event detail template | /events/:slug | 1/13 (8%) | 12 | 12 | Only the header is directly editable. |
| Contact | /contact | 9/54 (17%) | 45 | 54 | Header/form intro work; directory, socials, and most form content are protected. |
| Contact Hounslow | /contact/hounslow | 2/53 (4%) | 51 | 51 | Only the page heading is direct; practically the whole page is a dead end. |
| Contact Heston | /contact/heston | 2/53 (4%) | 51 | 51 | Same family-wide failure. |
| Contact Hammersmith | /contact/hammersmith | 2/53 (4%) | 51 | 51 | Same family-wide failure. |
| Check Availability | /check-availability | 21/64 (33%) | 43 | 62 | Intro/media/highlights work; functional form regions and state screens do not. |
| Fees | /fees | 6/22 (27%) | 16 | 22 | Header/photo/CTA fragments work; fee-sheet collection is protected. |
| Fee Calculator | /fee-calculator | 5/16 (31%) | 11 | 14 | Header/result labels partly work; controls, disclaimer binding, and missing states do not. |
| Funded Childcare | /funded-childcare | 82/100 (82%) | 18 | 98 | Strongest non-Home page, but still positional; bound resources and layout shells remain incomplete. |
| Blog | /blogs | 5/38 (13%) | 33 | 38 | Header/closing CTA work; filters, featured card, archive, and states are protected. |
| Blog detail template | /blogs/:slug | 3/37 (8%) | 34 | 37 | Only closing CTA is direct; hero, rich article, and related content are protected. |
| Food & Hygiene | /food-hygiene-rating | 2/17 (12%) | 15 | 15 | Header works; all records/actions are protected. Badge and metadata regions expose no element annotations at all. |

This is the central quantitative failure: Home has zero generated page keys. Every non-Home route depends almost entirely on runtime-generated positional keys. A DOM insertion or reorder can silently attach a saved edit to the wrong element.

## 4. Existing routes missing from the builder

These are already real React routes; adding them to the builder does not mean adding new pages:

| Existing route | Current defect | Required builder treatment |
|---|---|---|
| /about | Not registered or listed | Full page regions, media, copy, layout, responsive, SEO, and global components. |
| /testimonials | Not registered or listed | Testimonial query/template, filters/states if present, record-source editing, layout, and accessibility. |
| /privacy | Not registered or listed | Revisioned rich legal content, semantic headings/lists/links, reviewed/published status, and protected legal warnings. |
| wildcard 404 | Not registered or listed | 404 content, search/navigation actions, illustration, responsive layout, and a safe preview URL. |

The builder currently says 16 pages are visible and 7 are “removed.” Those seven are not disposable pages; they are the vacancy template, Apply, event template, three branch-contact pages, and blog template. Restore them under explicit Template or System groupings. Do not use a user-removable organizer state to hide required audit scope.

## 5. The Home benchmark

Home currently demonstrates the following minimum behavior:

- Stable explicit element keys rather than runtime positions.
- Explicit editable regions for header/navigation, video hero, nursery feature links, benefits, About, testimonials, trust, social rail, footer, and cookie UI.
- Direct editing of local text and links.
- Text size, weight, colour, alignment, and capitalization controls.
- Image replacement, alternative text, focal X/Y, fit, aspect ratio, and masks.
- Element background, radius, border, opacity, shadow, hover, animation, brightness, saturation, tint, transform, dimensions, spacing, and visibility.
- Section style/layout, box, effects, and reset controls.
- Desktop, tablet, and mobile fixed previews.
- Global header, social rail, footer, and cookie regions accessible from Home.
- Thirteen starter section templates.
- Custom text, image, logo, button, shape, and tabs primitives.
- Free-layer ordering, duplication, deletion, transforms, and visibility for custom items.

All other pages need at least that appropriate control depth.

The audited starter library contains exactly:

1. Blank
2. Photo + story
3. Four visual cards
4. Logo/accreditation strip
5. Tabs + CTA
6. Heading + rich copy
7. Three feature cards
8. Photo gallery
9. Stats strip
10. Testimonial spotlight
11. Milestone timeline
12. Centered CTA
13. FAQ/information rows

Its filters are All, Basics, Story, Cards, Trust, Actions, and Gallery. The custom-section primitives are text, image, logo, button, shape, and tabs. Current shape choices are rectangle, circle, pill, and line. Treat these as the current inventory to migrate, not as a complete final library.

Home itself still has critical gaps, so do not copy its limitations:

- Real-route Home has no working undo/redo history, autosave, recovery, revisions, review, publish, conflict detection, or readiness check.
- The topbar offers only fixed device buttons, Save, and Open full page. It lacks zoom, orientation, custom width, preview, version history, draft/review/publish, export/import, and safe rollback.
- Its Regions panel is not a complete Layers panel.
- Core site elements cannot be grouped, duplicated, copied/pasted, aligned, locked, or reordered like real canvas objects.
- The region arrows only reorder the sidebar in browser local storage. They do not reorder the canvas or public page. This is deceptive and must be removed or made real.
- “Tabs” and FAQ-like custom templates are static visual text. They have no state, keyboard behavior, ARIA relationship, or runtime interaction.
- Custom text is plain text, not semantic rich text. A user cannot choose heading level, lists, emphasis, inline links, or meaningful document structure.
- Shapes are limited to rectangle, circle, pill, and line.
- There is no real Add, Layers, or Media panel for exact routes, no multi-select, snap/grid/guides, context menu, or style copy/paste.
- Unset colours display as #ffffff, which falsely suggests a white override instead of inheritance/no override.
- There is no accessibility, data, interaction, or advanced inspector tab.

## 6. P0 systemic defects Claude must solve before polishing pages

### 6.1 Two disconnected builders

The code contains a stronger generic document editor with autosave, recovery, locks, revisions, draft/review/publish, readiness, and undo/redo. Real Home and exact routes take a separate liveParity branch and save into v3/v4 sidecar design objects. The stronger document system therefore edits private prototypes, not the real public routes.

Unify the real routes onto one document/persistence contract and one renderer. Do not keep adding route-specific branches.

Required end state:

- The same structured document powers the canvas and public render.
- Home, exact pages, templates, globals, and conditional states share the same editor services.
- A page document may reference authoritative CMS records, but it must not copy them.
- Existing visual designs migrate deterministically and can roll back.
- Legacy DOM-mutation support is temporary, feature-flagged, observable, and removed after parity.

### 6.2 Unsafe real-route persistence

Home saves an option directly. Exact routes save post meta directly. Neither uses the generic document system’s base hash, lock session, revisions, draft/publish split, recovery, or conflict response.

Implement:

- Autosave with a visible timestamp.
- Local recovery after refresh/crash.
- Manual named revisions.
- Server revisions and restore.
- Draft versus published design.
- Preview of the current draft on the exact route.
- Optimistic concurrency with base hash/ETag.
- Real editor locks, takeover visibility, and read-only enforcement.
- Before-unload protection for unsaved real-route changes.
- Audit log with user, time, route, scope, changed fields, and revision.
- Atomic save across document plus affected source records, or an explicit recoverable multi-step transaction.
- No public change until Publish; Save must not silently equal Publish.

### 6.3 Fragile positional identity

The runtime discovers nodes and numbers them by order. This is unacceptable for persisted editing.

Every editable or selectable object must receive a stable semantic ID at source, for example:

- nursery-directory.card.{nurseryId}.title
- nursery-template.gallery.item.{mediaId}
- events.card.{eventId}.date
- contact.form.email.label
- globals.header.primaryNav.item.{menuItemId}

Rules:

- IDs survive copy edits, DOM wrappers, reordering, responsive changes, and data refresh.
- Repeaters use record IDs, not array indices.
- Template-scope IDs and record-scope IDs are distinct.
- No duplicate IDs in a rendered state.
- No generated positional fallback key may be persisted.
- CI fails if a visible supported element lacks a stable source annotation.

### 6.4 Protected content is a dead end

Bound text/image selection currently becomes Appearance-only. Global content on non-Home pages shows a protection message without a direct edit action.

Replace this with a source-aware inspector:

- Show Source, Scope, Inheritance, Record, Field, and Override status.
- Offer Edit content here when safe.
- Offer Open Nursery/Job/Event/Article/Testimonial/Global source for complex records.
- Save through the authoritative CMS endpoint.
- Return to the same canvas selection after source editing.
- Let the user switch between Edit this record, Edit template, and Edit global component where those scopes exist.
- Explain truly immutable computed/verified values and still expose their visual template/state controls.
- Never make users hunt for Home to edit a global. Provide Edit global header/footer/social/cookie from every page.
- Decide protection from the field/source contract, not from HTML tags. A semantic address, time, definition value, telephone link, or email link is not automatically uneditable; it is usually a bound field that needs the correct source action.

### 6.5 Runtime mutation is not a renderer

The current runtime heuristically scores sections, scans a limited selector list, writes textContent, and reapplies mostly when nodes are added. This can miss plain text wrappers, background images, pseudo-content, embeds, and React updates to existing nodes. Replacing textContent can destroy nested formatting.

Move to explicit component props/document rendering. During transition:

- Annotate components at source, never by text scoring.
- Observe relevant attribute/text changes as well as inserted nodes.
- Reapply idempotently after React commits.
- Preserve semantic/nested rich text.
- Handle CSS background media, SVG/icon content, video, iframe/embed placeholders, and pseudo-content deliberately.
- Apply alt changes even when the image source is unchanged.
- Detect and report an orphaned saved key instead of silently ignoring it.
- Replace time-based iframe guessing with an explicit route-ready/design-applied handshake. Contact Hammersmith, Check Availability, and Food Hygiene initially produced empty audit results until a retry, proving that load timing can make the editor believe a page has no objects.

### 6.6 The route inventory is not authoritative

Derive or validate the builder registry against the React route manifest. Every existing route must map to exactly one Page, Template, Global, or System entry. Dynamic routes need record fixtures. Wildcards need safe preview fixtures.

Link suggestions must use the registry’s real route, not derive a URL by prefixing the internal builder slug. Internal names such as home-poc, careers-vacancy-template, and contact-hounslow are not public URLs.

### 6.7 The builder is local-only

The plugin currently enables itself only in local/development-style environments. This means the client cannot use the feature in production. Do not simply remove the gate. Build a staged production enablement with capability checks, nonce/auth protection, draft preview security, rollback, performance monitoring, and feature flags.

## 7. Required editor contract

### 7.1 Fixed page navigator

Replace general page management with an immutable route navigator:

- Groups: Global, Main navigation, Parent information, Templates, System.
- Search and status filters.
- Page title, route, type, published/draft state, readiness, and last edited.
- Dynamic template record picker.
- No Create page, Trash page, or route rename in this product scope.
- Do not let local organizer settings hide a required page from QA.

### 7.2 Real Layers panel

List every selectable object, not just regions and custom layers:

- Searchable semantic names.
- Section/container/item nesting.
- Record identity for repeated items.
- Select, rename editor label, reorder where structurally safe, hide/show, lock/unlock, group/ungroup.
- Indicate global, template, record, computed, protected-functional, inherited, overridden, and missing-source states.
- Show conditional/state-only layers with a state icon.
- Reordering a layer must reorder the canvas and saved/public result. Otherwise no reorder control is shown.

### 7.3 Canva-quality canvas

- Exact responsive canvas width with custom width and orientation.
- Zoom to fit, 25–200% zoom, pan, rulers, optional grid, smart guides, snapping, safe areas, and overflow indicators.
- Click to select; double-click to edit; breadcrumbs for nested selection.
- Drag/drop sections and safe elements.
- Resize/rotate/crop handles appropriate to type.
- Multi-select, align, distribute, group, duplicate, copy, paste, copy style, paste style, and reset.
- Keyboard nudging, larger shift-nudging, delete with confirmation where needed, and complete keyboard access.
- Context menu and contextual toolbar.
- View mode and edit mode must render the same component tree as public preview.
- Clear selected, hovered, locked, inherited, and error outlines that never appear publicly.

### 7.4 Inspector tabs

Use consistent tabs:

- Content
- Style
- Layout
- Responsive
- Interaction
- Data
- Accessibility
- Advanced

Only show controls that make sense for the selected type. “Everything editable” does not mean giving text a meaningless crop control or a calculator value an unsafe content field.

### 7.5 Responsive inheritance

- Desktop/tablet/mobile plus custom widths.
- Explicit inherited versus overridden values.
- Reset one breakpoint to inherited.
- Consistent breakpoints with the actual frontend design system. The runtime currently uses 600/900 while the site styles use 640/1024; eliminate this mismatch.
- Controls for visibility, order, layout mode, columns, dimensions, type scale, spacing, alignment, background focal point, and interaction substitutions per breakpoint.
- Warnings for clipping, horizontal overflow, unreadable type, overlapping layers, hidden required actions, and undersized touch targets.

## 8. Every supported object and its required controls

### 8.1 Text and rich content

- Direct text editing for local copy.
- Source adapter for bound copy.
- Semantic type: H1–H6, paragraph, blockquote, caption, label, list, address, and inline span.
- Rich text: bold, italic, underline where appropriate, links, ordered/unordered lists, quotes, line breaks, superscript/subscript where needed, clear formatting.
- Font family/token, size, weight, style, line height, letter spacing, colour/token, alignment, capitalization, text decoration, max measure, wrapping, truncation, and responsive variants.
- Link destination, route picker, anchor, external/new-tab behavior, rel, download, and accessible label.
- Preserve nested markup; never flatten rich text via textContent.
- Spellcheck, missing-copy warnings, heading-order warning, and placeholder/fallback visibility.

### 8.2 Buttons and links

- Label, destination/action, route or record picker, icon, icon position, width, alignment, padding, radius, border, fill, type, and responsive behavior.
- Default, hover, focus-visible, active, disabled, loading, success, and error states.
- External-link and new-tab safety.
- Accessible name and minimum touch target.
- Functional submit/router/modal actions chosen from approved actions, not arbitrary JavaScript.

### 8.3 Images and logos

- Upload, media library, replace, remove/restore, source provenance, and record override.
- Alternative text, decorative toggle, caption, credit, and accessible long description where relevant.
- Focal X/Y, crop, fit, object position, aspect ratio, mask, width/height/min/max, border, radius, shadow, opacity, filters, overlay/tint, blend where supported, and link.
- Responsive source/variant, srcset/sizes, format, quality, lazy/eager/priority policy, and intrinsic dimensions.
- Separate logo treatment that preserves aspect ratio and brand constraints.
- An alt-only change must work without forcing a source replacement.
- Distinguish an intentionally decorative empty alt from “not supplied/inherit fallback”; an empty saved string must not accidentally erase a valid source fallback.
- Compose hover/brightness/saturation/tint effects from independent variables. One hover preset must not overwrite the selected image filters.

### 8.4 Video and embeds

- Upload/library or approved external provider.
- Poster, caption/transcript, alt equivalent, focal point, aspect, crop/fit, controls, autoplay, mute, loop, playsinline, preload, and responsive replacement.
- Autoplay accessibility/performance warnings.
- Safe editor placeholder for third-party embeds and consent-gated content.

### 8.5 Icons, badges, dividers, and shapes

- Approved icon picker, custom approved SVG, accessible label/decorative toggle, fill/stroke, size, weight, rotation, and background.
- Rectangle, rounded rectangle, circle, ellipse, line, arrow, polygon, star, arch, blob, speech shape, and brand-approved decorative shapes.
- Fill/gradient/image, border, corners, opacity, shadow, dimensions, rotation, and layer order.
- Rating badges and verified marks remain source-bound but expose complete template styling.

### 8.6 Sections, containers, grids, cards, and free layers

- Background colour/token, gradient, image, video, texture, overlay, tint, and focal point.
- Content width, min/max width, height/min-height, padding per side, margin per side, gap, alignment, justification, columns, rows, wrap, flex/grid/layered mode, order, and responsive variants.
- Border per side, radius per corner, shadow stack, opacity, overflow, z-index, sticky position, and safe visibility.
- Reorder, duplicate, save as template, copy/paste style, hide/restore, and reset.
- Core functional sections may be hidden only if the route remains valid and readiness rules permit it. Use reversible hiding instead of destructive deletion for legal, form, navigation, and system structures.
- Warn before radius/overflow/transform/animation clips dropdowns, modals, sticky elements, focus rings, or shadows.

### 8.7 Repeaters, cards, and collections

- Select the collection, item template, individual record instance, or nested field.
- Query/filter/sort/limit/pagination settings appropriate to the collection.
- Add/remove/reorder local repeater items.
- For CMS collections, create/edit/archive the authoritative record through a source adapter, not page JSON.
- Per-record image/copy/link editing, template styling, inherited defaults, and explicit overrides.
- Empty, one-item, many-item, loading, error, pagination, and future-record previews.
- Stable record IDs and deterministic empty-state behavior.

### 8.8 Tabs, accordions, galleries, carousels, menus, and modals

- These must be real interactive components, not styled text blocks.
- Item labels/content/media, item order, default/open item, allow-one/allow-many rules, animation, indicator/icon, and responsive substitution.
- Keyboard navigation, focus management, ARIA relationships, reduced-motion behavior, escape/close, click-outside policy, and scroll locking.
- Gallery crop/order/caption and accessible modal controls.
- Mobile menu open/closed and submenu states.
- Booking/Calendly modal shell editable while the integration remains protected.

### 8.9 Forms

- Select and style form, field group, individual field, label, help text, placeholder, error, consent, submit action, success panel, and retry action.
- Edit approved labels/help/placeholders/option labels and field order where the backend contract permits.
- Control types, names/IDs, routing, validation rules, required legal consent, upload security, anti-spam, submission, and computed values remain protected.
- Preview blank, filled, focus, disabled, browser autofill, validation error, server error, sending, upload progress/failure, success, and resubmit states with fixture data only.
- Accessible label association, described-by/error links, required indication, focus order, live regions, and colour-independent errors.
- The form renderer and backend schema must be one contract so visual changes cannot break delivery.

### 8.10 SEO, social, accessibility, and advanced

- Per route/template: SEO title, meta description, canonical, index policy, Open Graph title/description/image, social preview, and relevant structured-data preview.
- Accessibility panel: semantic role/tag, accessible name, alt/decorative state, heading outline, keyboard order, contrast, focus visibility, reduced motion, captions/transcript, touch target, and link-purpose warnings.
- Advanced: stable element ID, approved classes/tokens/data bindings, HTML tag where safe, source provenance, and diagnostics.
- Do not expose arbitrary JavaScript or unsanitized HTML/CSS to ordinary client roles.

## 9. Global theme and shared components

Add a first-class Global mode accessible from every page:

- Brand tokens: colours, typography, type scale, spacing scale, radii, shadows, borders, container widths, breakpoints, motion, and focus style.
- Header: logos, navigation labels/destinations, dropdown structure, active state, sticky behavior, CTA, desktop/mobile layouts, and open-menu preview.
- Social rail: platforms, icons, destinations, labels, order, display rules, and responsive placement.
- Footer: logos, copy, branch contacts, navigation, legal links, accreditation marks, disclosure, layout, and responsive stacking.
- Cookie UI: banner, preferences dialog, category labels/descriptions, actions, links, focus/keyboard behavior, compact/expanded states, and mobile layout. Consent mechanics and required-category behavior remain protected.

Current defects:

- Global nodes on non-Home routes are hard-protected dead ends.
- The protection routine marks descendants but not always the region root. The cookie frame can therefore receive an accidental page-specific presentation override.
- Global changes have no explicit scope preview or impact count.

Required behavior:

- Selecting a global anywhere offers Edit global component.
- The inspector states “affects all routes” and shows usages.
- Page-specific overrides are unavailable by default and explicit when supported.
- Publish can include or exclude global changes as a clear transaction.
- Visual regression runs across every consuming route before global publish.

## 10. Page-by-page defect and completion list

### 10.1 Home /

- Preserve all current explicit keys and direct controls.
- Move Home onto the common versioned document/publish system.
- Replace its special v3 option path with the common migration-compatible contract.
- Add true Layers/Add/Media panels, semantic rich text, working interactive blocks, undo/redo, autosave/recovery/revisions, workflow, accessibility, data, and responsive inheritance.
- Make global regions explicitly global rather than merely editable because this is Home.
- Verify every hero/video, nursery link, benefit, About, testimonial, trust, social, footer, and cookie state.

### 10.2 Our Nurseries /nurseries

Current: 3/21 direct; the 18-element directory is fully protected.

- Select the directory, card template, card instance, image, title, address, ages/hours, and CTA separately.
- Edit Nursery facts through the selected Nursery source; edit card layout once at template scope.
- Replace/crop/alt branch media through the Nursery record with visible override status.
- Reorder via Nursery sort order, preview 0/1/3/future branches, and edit empty state.
- Provide card hover/focus and responsive grid controls.

### 10.3 Hounslow /nurseries/hounslow

Current: 21/150 direct; 129 protected.

- Finish hero, welcome, philosophy, meals, and their button/media fields.
- Add source-aware gallery, features, team, parent-partnership, contact facts, accreditations, testimonials, and booking-modal editing.
- Support template defaults versus Hounslow overrides at every eligible field.
- Provide gallery/modal, carousel/testimonial, missing-contact, and booking states.

### 10.4 Heston /nurseries/heston

Current: 21/150 direct; 129 protected.

- Apply the same template/source contract as Hounslow.
- Verify Heston record identity and override isolation: a Heston record edit must not change Hounslow; a template edit must preview impact on all branches.
- Test all collections with Heston’s actual record counts and media ratios.

### 10.5 Hammersmith /nurseries/hammersmith

Current: 14/133 direct; 119 protected.

- Apply the shared nursery contract.
- Expose the meals section as an inherited conditional state with Off/On/Override provenance, not an unexplained missing section.
- Preview both states without publishing fixture data.
- Verify shorter gallery/feature counts do not break layout or stable IDs.

### 10.6 Curriculum /curriculum

Current: 19/19 direct, but all 19 keys are positional.

- Replace every generated key with source-level semantic keys.
- Give every philosophy, EYFS, Montessori, and comparison heading/copy/image full semantic, rich-text, media, layout, and responsive controls.
- Preserve alternating compositions and mobile reading order.
- Verify alt-only save and nested rich formatting.

### 10.7 Careers /careers

Current: 44/77 direct.

- Make benefits and qualities real repeaters with item controls.
- Finish vacancy intro/CTA source and state behavior.
- Make gallery media/order/crop/caption/modal fully editable.
- Give the general application form the form contract and all fixture states.
- Preserve submission routing and upload security.
- Complete closing CTA and page-wide responsive/accessibility controls.

### 10.8 Current Vacancies /careers/vacancies

Current: 1/15 direct; archive fully protected.

- Add template styling and record-source editing for Job cards.
- Expose archive heading, intro, count, filters, location grouping, metadata labels, card CTA, and pagination.
- Preview open jobs, no jobs, mixed locations, long titles, expired/closed jobs, loading, and error.
- Provide direct Open Job action and never copy Job fields into the page document.

### 10.9 Vacancy detail /careers/vacancies/:slug

Current preview rendered only the fallback, so the 4/4 number does not prove template parity.

- Add a Job fixture/record picker.
- Preview populated, closing-soon, closed, missing, and malformed-record states.
- Select and style title, location, employment facts, dates, description sections, application CTA/form handoff, related/back actions, and fallback.
- Edit record content at Job scope and layout at template scope.
- Add long/rich job-description and absent-optional-field tests.

### 10.10 Apply /careers/apply

Current: 12/40 direct.

- Support general and selected-role headings.
- Expose allowed label/help/placeholder/consent/success/error content and all field surfaces.
- Preview file upload idle/progress/type error/size error/server failure.
- Keep field identity, vacancy routing, delivery, anti-spam, validation, and consent semantics protected.
- Verify keyboard-only completion and mobile upload behavior.

### 10.11 News & Events /events

Current: 4/46 direct; 42-element events state fully protected.

- Add Event record/source adapter and card-template scope.
- Expose archive title/intro, card media/date/time/location/category/CTA presentation, section layout, and closing CTA.
- Preview upcoming only, past only, mixed, no events, loading, error, long title, missing media, and many events.
- Let records be edited through Event source without detaching from the collection.

### 10.12 Event detail /events/:slug

Current: 1/13 direct.

- Add Event fixture/record picker and populated/not-found states.
- Select title, date, time, location, description, hero/media, CTA, back action, and optional fields.
- Separate Event record edits from template presentation.
- Validate structured-data output and date/time accessibility.

### 10.13 Contact /contact

Current: 9/54 direct.

- Make each directory card source-aware and its template editable.
- Edit global social records from the selected row/icon.
- Apply the full form contract to enquiry form.
- Expose header, form intro, directory layout, social row, validation/sending/error/success states, and responsive reading order.
- Preserve branch routing, email delivery, consent, and anti-spam.

### 10.14 Contact Hounslow /contact/hounslow

Current: 2/53 direct.

- Add Hounslow source adapter for address, phone, email, hours, primary photo, and gallery.
- Make every form label/surface/state editable under the safe form contract.
- Edit the other-nurseries card template and bound destinations.
- Preview missing-record redirect and missing optional contact fields.

### 10.15 Contact Heston /contact/heston

Current: 2/53 direct.

- Apply the shared branch-contact template/source model.
- Verify all Heston source edits and overrides stay isolated.
- Test its actual images, contact values, and validation/success states.

### 10.16 Contact Hammersmith /contact/hammersmith

Current: 2/53 direct.

- Apply the shared branch-contact template/source model.
- Test its actual content lengths/media and missing-field fallbacks.
- Verify other-branch navigation remains bound and correct.

### 10.17 Check Availability /check-availability

Current: 21/64 direct.

- Finish page header, visual card, highlights, and form header.
- Apply the full form contract to parent/child, nursery, booking, date, schedule, requirements, consent, and submit areas.
- Make allowed labels/help text/options presentation editable while preserving IDs, values, date rules, routing, and validation.
- Add blank, partially filled, focus, invalid, sending, server-error, success/reference, send-another, and Calendly modal fixtures.
- Make branch support records source-aware.

### 10.18 Fees /fees

Current: 6/22 direct; fee-sheet collection fully protected.

- Edit header and photo with full media/accessibility controls.
- Select fee-sheet collection, branch card template, each branch instance, download label/icon, and empty/missing-PDF state.
- Replace the fee PDF through the Nursery source, show file name/date/size, and validate document link.
- Finish calculator callout/CTA states and responsive layout.

### 10.19 Fee Calculator /fee-calculator

Current: 5/16 direct.

- Screenshot-confirmed defect: selecting “Layout card 1 — Input card” produces a “PRESENTATION ONLY” inspector with only Size, Move left/right, Move up/down, and Save presentation. This exact restricted state must be deleted, not renamed.
- The input card itself must expose the full container contract: fill/gradient/media, borders, radius, shadow, opacity, width/min/max/height, per-side padding/margin, grid/flex/gap/alignment, responsive overrides, overflow, transform, interaction, visibility, ordering, duplicate/style-copy, and reset.
- Nursery selector, days range, funded-hours selector, every label/help line, track/thumb, option, focus/hover/disabled/error state, and their nested containers must be independently selectable.
- The result shell, each estimate card, computed value typography, explanatory copy, and fee-sheet action must be independently selectable. Computed numbers remain bound to the calculation result, but their template and state presentation are fully editable.
- Style each control, label, option state, results shell/card, disclaimer, and fee-sheet action.
- Keep allowed options, eligibility math, computed values, and Nursery bindings protected.
- Provide deterministic fixtures for every Nursery, day count, funded-hours choice, incomplete input, missing fee data, extreme value, and results state.
- Make result labels and disclaimer copy editable at template scope.
- Verify visual edits cannot change calculation results.

### 10.20 Funded Childcare /funded-childcare

Current: 82/100 direct, but 98 keys are generated.

- Replace all positional keys.
- Turn offerings, steps, benefits, resources, and FAQs into real repeaters/components.
- Make FAQ a functional accessible accordion, not a visual-only pattern.
- Keep fee PDFs bound to Nursery sources with direct edit actions.
- Add long-answer, all-open/one-open, missing resource, and mobile states.
- Add reviewed-claim/source metadata where legal accuracy matters.

### 10.21 Blog /blogs

Current: 5/38 direct.

- Make year/month filters selectable and state-styleable while options remain Article-derived.
- Add featured-card and archive-card template scope plus direct Article source actions.
- Expose image, category, date, title, excerpt, link label, pagination/load-more, and layout.
- Preview loading, error/retry, empty filter, no articles, one article, many articles, missing image, and long title/excerpt.
- Keep featured selection/query rules explicit.

### 10.22 Blog detail /blogs/:slug

Current: 3/37 direct.

- Add Article fixture/record picker.
- Style and source-edit hero category/date/title/excerpt/image, rich article content, back link, related cards, and closing CTA.
- Use a sanitised semantic rich-content renderer; never flatten it to text.
- Preview loading, error/retry, not found, short/long article, headings/lists/links/media, missing hero, and no related articles.
- Edit Article content at record scope and article shell at template scope.

### 10.23 Food & Hygiene /food-hygiene-rating

Current: 2/17 direct. Rating badge and metadata regions have no annotated elements.

- Select collection, record card template, branch instance, verified badge, score/pending state, authority, inspection date, address, and public-record action.
- Edit presentation at template scope; edit permitted Nursery metadata at source; keep verified score/source integrity protected.
- Add pending, missing, invalid/stale record, future branch, no branches, long authority name, and external-link failure states.
- Add explicit stable annotations for badge and metadata regions.

### 10.24 About /about

Currently absent from the builder.

- Register all existing sections without changing the route.
- Expose all headings, body copy, images, story/timeline/value cards, links, decorative elements, section layout, responsive behavior, accessibility, and SEO.
- Use real timeline/repeater structures if present, with stable item IDs.

### 10.25 Testimonials /testimonials

Currently absent from the builder.

- Register the existing route and bind Testimonial records.
- Expose archive intro, filters/grouping if present, card template, quote, attribution, branch relationship, media, trust metadata, empty/loading/error states, and responsive layout.
- Source-edit Testimonial records; template-edit card presentation.

### 10.26 Privacy /privacy

Currently absent from the builder.

- Register the existing route.
- Use revisioned semantic rich content with headings, lists, links, contact details, effective/updated date, and table-of-contents behavior where present.
- Add a legal-review warning and publish permission; do not treat legal copy like an untracked canvas text box.
- Preview long content, anchor navigation, print, mobile, and external links.

### 10.27 404 and system states

Currently absent from the builder.

- Register wildcard 404 as a System template with a safe preview URL.
- Edit title, explanation, image/shape, primary/secondary actions, search/help links, layout, and SEO noindex behavior.
- Include generic loading, offline/network, permission, empty, error, and maintenance patterns used elsewhere.
- Never publish fixture data or let a system-state preview change routing.

## 11. Conditional-state simulator

The editor currently shows whichever state happens to render. That cannot prove a dynamic template is editable.

Add a non-publishing simulator with named fixtures for:

- Header default/sticky/scrolled, dropdown open, mobile menu open, keyboard focus.
- Cookie banner, preferences dialog, each category, saved state, and reopened state.
- Forms blank/focus/filled/validation/sending/error/success/upload.
- Gallery/modal open, next/previous, no media, one media, many media.
- Tabs/accordion default/open/focus.
- Nursery conditional meals, missing fields, booking modal.
- Jobs open/closing/closed/missing and archive empty/loading/error.
- Events upcoming/past/mixed/empty/error and detail missing.
- Availability success/error/Calendly.
- Calculator incomplete/every configured input/missing data.
- Blog loading/error/not-found/long content/no related.
- Hygiene verified/pending/missing/stale/future branch.
- 404 and generic network/system states.
- Hover, focus-visible, active, disabled, reduced-motion, high-contrast, 200% text zoom, and RTL stress where useful.

The selected fixture must be visible in the toolbar and must never be saved as production data.

## 12. Safety, accessibility, and design governance

- Use role-based capabilities for content edit, design edit, source-record edit, legal edit, publish, global publish, and restore.
- Sanitize every field according to type.
- Use approved design tokens by default; allow custom values only with a clear escape hatch and warning.
- Prevent deletion/hiding of required navigation, consent, legal, submission, or recovery actions.
- Run contrast checks for normal/large text and component states.
- Require visible focus and complete keyboard operation.
- Respect reduced motion.
- Validate heading order, landmarks, labels, alt/decorative choices, link purpose, reading order, zoom/reflow, and touch targets.
- Warn on oversized/unoptimized media and broken links.
- Preserve router behavior, data integrity, form delivery, consent, calculations, and external integrations.

## 13. Implementation order

Do not “finish” one page by adding more positional annotations. Fix the common foundation first.

### P0 — foundation blockers

1. Freeze and reconcile the existing route inventory; restore hidden template/system entries and register About, Testimonials, Privacy, and 404.
2. Define the common real-route document schema, stable ID rules, source-binding descriptors, scope model, and migration.
3. Connect real routes to versioned draft/publish persistence, autosave/recovery, conflicts, locks, revisions, audit, and rollback.
4. Replace heuristic persisted identity with explicit source annotations; add a zero-generated-ID CI gate.
5. Implement source-aware Content/Data inspector and global editing from every route.
6. Build fixture/state preview and record picker for dynamic templates.
7. Make editor and public route use the same render contract.

### P1 — universal editor parity

1. Fixed page navigator, full Layers/Add/Media/Templates panels.
2. Canva-quality canvas selection, handles, guides, grouping, ordering, clipboard, and keyboard behavior.
3. Complete type-specific Content/Style/Layout/Responsive/Interaction/Data/Accessibility/Advanced inspectors.
4. Real rich text, media, video, buttons, sections, repeaters, tabs/accordion, gallery/modal, menus, and form presentation components.
5. Global tokens/header/social/footer/cookie system.
6. SEO/social and accessibility readiness.

### P2 — route migration

Migrate and prove route families in this order:

1. Home and globals onto the common foundation without visual regression.
2. Curriculum and Funded Childcare as mostly static/repeater proofs.
3. Nursery index and all nursery detail/contact variants.
4. Careers, vacancies, vacancy detail, and Apply.
5. Events archive/detail.
6. Contact and Check Availability.
7. Fees, calculator, Blog/archive/detail, and Food Hygiene.
8. About, Testimonials, Privacy, 404, and all system states.

### P3 — production readiness

1. Full route/state/responsive/accessibility regression.
2. Permission and security review.
3. Performance and payload budgets.
4. Client UAT using real client roles.
5. Staged production feature flag, backup, monitoring, rollback rehearsal, and handover.

## 14. Automated gates

Create tests that fail loudly:

- Route manifest gate: every existing public route matches exactly one builder entry.
- Visibility gate: no required page/template/system entry can be hidden by a personal organizer preference.
- Stable identity gate: zero duplicate IDs and zero persisted generated positional IDs in every fixture/state/breakpoint.
- Coverage gate: every supported visible text, media, action, icon, card, and container has an explicit selection contract.
- Selection gate: selecting an object yields direct content controls, an authoritative source action, or a clearly explained functional lock plus presentation controls. No unexplained dead end.
- Zero-blockade UI gate: the real-route editor contains no terminal “PRESENTATION ONLY,” “Appearance only,” or generic hard-protected inspector. A repository/UI test fails if those states return.
- Inspector-depth gate: no supported container can collapse to only size and X/Y controls; every object type must expose its complete applicable control schema and independently selectable descendants.
- Round-trip gate: edit → save draft → reload editor → preview → publish → reload public route preserves the value.
- Scope gate: record edits affect only that record; template edits affect all instances; globals affect all routes; overrides are explicit.
- Conflict gate: two sessions cannot silently overwrite one another.
- Revision gate: restore returns both content/design and bound override references to the selected revision.
- Responsive gate: desktop/tablet/mobile/custom widths use the same breakpoints and inheritance rules as the frontend.
- State gate: every declared conditional region has at least one selectable simulator fixture.
- Component gate: tabs, accordions, menus, modals, galleries, and forms remain functional after every allowed style/layout edit.
- Accessibility gate: keyboard, focus, semantics, contrast, labels, alt, reduced motion, reflow, and error announcements.
- Public parity gate: editor preview and public route share markup/rendering and meet visual-diff tolerance.
- Orphan gate: saved IDs missing from a render are reported and block publish until resolved or intentionally migrated.
- Media gate: alt-only updates work, assets have intrinsic size, and oversized/broken media warn.
- SEO gate: route/template metadata and social preview validate.
- Production gate: builder assets and draft data are protected and do not load for unauthorized public visitors.

## 15. Manual browser matrix

For every route and fixture:

- Desktop: 1440, 1280, 1024.
- Tablet: 834, 768.
- Mobile: 430, 390, 375, 320.
- Chrome, Edge, Firefox, Safari/iOS where available.
- Mouse, keyboard only, touch, 200% zoom, reduced motion, forced/high contrast where supported.
- Short, normal, and extreme content lengths.
- Missing optional media/data and broken external source.
- Logged-in editor, reviewer, publisher, and unauthorized user.

Record evidence for each route:

- Builder screenshot before edit.
- Selected-element inspector screenshot.
- Draft preview screenshot.
- Public result screenshot after publish.
- Stable key/source/scope.
- State and viewport.
- Test result and any accepted limitation.

## 16. Claude’s relentless audit loop

Repeat this loop after every shared-component or route-family change:

1. Reconcile routes, registry entries, regions, and state fixtures.
2. Open every affected real builder entry.
3. Enumerate visible and interactive objects from the rendered component tree.
4. Select every object and classify its result: Direct, Source-bound, Functional lock with presentation, or Defect.
5. Treat every generated key, missing layer, wrong scope, appearance-only dead end, inert control, and unpreviewable state as a defect.
6. Exercise every applicable control, including reset and breakpoint inheritance.
7. Save draft, reload, preview, publish, reload public route, and restore a revision.
8. Test collection counts, missing data, extreme content, form states, interaction states, and responsive widths.
9. Check console/network errors, orphaned IDs, overflow, focus, contrast, and performance.
10. Update this table and defect list with evidence. Do not lower the gate to make a page appear complete.

Use this defect format for anything newly found:

| Field | Required value |
|---|---|
| ID | Stable defect ID |
| Route/template | Exact route and record fixture |
| Region/object | Stable semantic ID |
| Viewport/state | Width, device, interaction/data state |
| Current behavior | Observable failure |
| Expected behavior | Exact editor/public contract |
| Scope/source | Local, record, template, or global |
| Risk | Data loss, wrong content, broken function, accessibility, responsive, or cosmetic |
| Proof | Automated test plus editor/public evidence |

## 17. Explicit “not done” rules

The Visual Builder is not complete if any of these is true:

- A page is missing, hidden, or represented only by a private prototype.
- A real route still uses the weaker save path without revisions/conflict protection.
- A persisted edit depends on element position.
- “Editable” means colour/spacing only while copy/media has no source action.
- Any selected block shows “PRESENTATION ONLY,” “Appearance only,” a generic protection wall, only three transform sliders, or “Save presentation.”
- A user must go to Home manually to find a global editor.
- A control changes only the sidebar or editor chrome, not the saved/public page.
- A visual Tabs/FAQ block has no real interaction and accessibility behavior.
- Only the currently lucky dynamic state was inspected.
- Editor preview differs from public rendering.
- A save silently publishes.
- Two editors can overwrite one another.
- Undo/redo appears but does not undo real-route changes.
- Alt, focus, form states, error states, empty states, or mobile states are skipped.
- About, Testimonials, Privacy, or 404 remains outside the builder.
- The plugin remains local-only with no safe production rollout.
- Claude marks a route complete without a full select/edit/save/reload/preview/publish/restore proof.

## 18. Source evidence pointers

- Route registry and direct exact-route save: alexandra-montessori/wordpress/mu-plugins/alexandra-visual-builder/includes/class-am-vb-site-design.php
- Home special design option: alexandra-montessori/wordpress/mu-plugins/alexandra-visual-builder/includes/class-am-vb-home-design.php
- Stronger but disconnected generic document workflow: alexandra-montessori/wordpress/mu-plugins/alexandra-visual-builder/includes/class-am-vb-document.php
- Editor liveParity branch, local-only sidebar ordering, and real-route toolbar: alexandra-montessori/wordpress/mu-plugins/alexandra-visual-builder/assets/editor.js
- Heuristic annotation, positional keys, textContent mutation, and mismatched breakpoints: alexandra-montessori/wordpress/mu-plugins/alexandra-visual-builder/assets/site-runtime.js
- Existing React routes missing from the builder: alexandra-montessori/src/routes.jsx
- Local/development feature gate: alexandra-montessori/wordpress/mu-plugins/alexandra-visual-builder/alexandra-visual-builder.php

The governing standard is simple: the client should be able to point at anything they can see, understand where it comes from, change what is safe to change, preview every meaningful state, and trust that save, publish, undo, and rollback do exactly what they say.
