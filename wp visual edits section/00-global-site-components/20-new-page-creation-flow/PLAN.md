# Future visual page creation flow plan

## Intent and feel

Creating a future page should feel like opening a clean, guided workspace: name the page, confirm its draft URL, choose how to start, and arrive in an empty or deliberately templated canvas. It must never publish a blank route or quietly add itself to navigation. This workflow extends the current twelve page families without pretending every future page is one of them.

## Current evidence

- The existing visual-builder plugin already registers a private revision-enabled `am_visual_page` type, REST `/pages` creation, document schema version 2, workflow metadata and page locks/audit.
- `createPage()` currently uses two browser prompts for title and slug, creates an empty `sections: []` document, marks workflow `draft`, then switches into it.
- The private post row itself is inserted with WordPress `post_status=publish`, while public exposure is controlled separately by `_am_vb_workflow_status` and `_am_vb_published_document`.
- A public snapshot endpoint exists, but current React `routes.jsx` contains only explicit routes; a complete future-page registry/renderer is still required before a new visual page can become a real site route.
- Current public pages and navigation must remain unchanged when a draft is created.

## Editable elements and controls

- Replace prompts with a four-step wizard: Page identity → Starting structure → SEO essentials → Navigation placement (optional/later).
- Identity: internal page title, suggested slug, page purpose, owner/notes and page-family choice “Standalone future page” versus an explicitly supported existing child type.
- Starting structure: Blank page, selected section sequence, or duplicate a permitted visual page; no template is silently preselected.
- SEO essentials: title/description/social image source status, with valid fallbacks and draft warnings.
- Navigation: default “Do not add yet”; optional hand-off to the menu-tree plan after the first valid draft save.
- Creation summary names route, draft status, section count, global header/footer inheritance and every validation issue.
- Autosave/recovery, edit lock, revisions, preview and publish-readiness remain visible from first entry.

## Selection, layers and dragging

The creation wizard itself has no free canvas layers. Once created, the page outline owns section reordering via explicit handles/keyboard moves; each chosen section owns its internal layers. Dragging a page into a menu is postponed to the menu-tree workflow and never occurs implicitly because of page creation.

## Desktop, tablet and mobile

Wizard works at desktop and narrow admin widths with linear keyboard order and no modal overflow. Before “Create draft”, it shows page-frame previews for desktop/tablet/mobile only if a starting structure is selected. A new page must have all device layouts validated before publish; blank draft creation does not require invented mobile geometry.

## Data ownership and bindings

WordPress visual-page post ID is the stable identity. Title/slug/workflow/document/SEO/menu references are separate typed records connected by ID. Global header/footer/social/cookie are referenced at render time, not copied into the new document. A future public route registry maps a published stable page ID to its resolved slug and visual renderer. Menu placement stores that same ID.

## Protected rules

- New pages always begin workflow Draft and remain absent from public route registry, sitemap and menus until explicitly published/placed.
- Reserve all current React routes, WordPress admin/system slugs and child-route namespaces.
- Creation requires edit capability; publish requires the existing stronger publish capability.
- Do not duplicate form submissions, private data or immutable IDs when cloning a page.
- A page with zero meaningful sections, unresolved placeholders, broken links, missing SEO or incomplete device layouts cannot publish.
- Global component edits are not copied into or saved with a page draft.

## Accessibility and failure states

Wizard steps, errors and completion are keyboard/screen-reader usable with a progress label and focus moved to the first error. If slug availability check fails, retain entered work and retry; never create a guessed route. If document creation succeeds but navigation/preview setup fails, keep a recoverable unplaced draft and say exactly what remains. Conflicting create requests return the existing 409-style error and suggested safe alternatives.

## Storage and versioning

Build on `am_visual_page` document versioning, WordPress revisions, workflow snapshot and audit rather than a parallel page store. Add stable route/SEO metadata schemas and idempotent creation request IDs. Creation audit records origin (blank/template/duplicate) and template versions. A future document migration must keep drafts private and preserve published snapshots until a new version passes validation.

## Planned implementation files (future only)

- `wordpress/mu-plugins/alexandra-visual-builder/assets/flows/new-page-wizard.js`
- `wordpress/mu-plugins/alexandra-visual-builder/includes/class-am-vb-document.php`
- `wordpress/mu-plugins/alexandra-visual-builder/includes/class-am-vb-route-registry.php`
- `src/pages/VisualPage.jsx`
- `src/routes.jsx` (published visual-page resolver, without replacing current explicit routes)
- `wordpress/mu-plugins/alexandra-visual-builder/tests/new-page-flow.php`

## Current public section → exact WP canvas parity

| Current public/state evidence | WP canvas requirement |
| --- | --- |
| Twelve planned current page families and explicit React routes | New draft does not replace, rename or alter any existing page/route |
| Global header/footer wrap every current route | Future-page preview uses the exact same global components by reference |
| Empty new visual document currently has `sections: []` | Blank choice remains genuinely blank; no invented public section appears |
| Workflow starts Draft | New page stays absent from public menus/route registry/sitemap |
| Current Home has a separate live-parity path | New generic page uses its own renderer and cannot overwrite Home’s model |

## Acceptance checklist

- [ ] Wizard replaces prompts without changing existing page records or public routes.
- [ ] Every creation is a recoverable, unplaced Draft with stable ID.
- [ ] Blank/template/duplicate origins are explicit and audited.
- [ ] Global components are referenced, never copied.
- [ ] Public renderer/route registry is required and validated before publish.
- [ ] Failure at any later step leaves a coherent draft, not a partial public page.

