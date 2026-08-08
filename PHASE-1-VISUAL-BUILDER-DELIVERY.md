# Phase 1 Visual Builder — Delivery Evidence

Date: 27 July 2026  
Environment: Local WordPress only  
Builder version: 0.2.0  
Production deployment: none  
Existing public routes switched: none

## Outcome

Phase 1 is complete as an engineering foundation on the Local Alexandra
Montessori WordPress site.

The Phase 0 single-page prototype is now a permissioned multi-page visual
workspace with:

- page creation, selection and recoverable deletion;
- reusable section templates;
- WordPress media browsing;
- multi-selection and grouping;
- layer and section visibility;
- layer, group and section edit locks;
- autosave and browser crash recovery;
- concurrent-session protection;
- optimistic save-conflict detection;
- draft, review and publish workflow;
- immutable published snapshots;
- publishing-readiness checks;
- revisions and restore;
- audit history;
- protected previews for any managed visual page;
- a draft-safe public AST endpoint;
- schema migrations, feature flags and dedicated capabilities.

Open the Local editor from:

1. WordPress Admin.
2. Website Content.
3. Visual Builder.

## Client editing experience delivered

### Pages

- The left Pages tab lists every managed visual page.
- A client can create a page with a stable title and slug.
- A client can switch between pages without leaving the builder.
- A page can be moved to WordPress Trash after confirmation.
- At least one visual page is retained.
- The active page, URL slug and workflow state are visible.

### Layers and sections

- Sections and layers can be selected and renamed.
- Shift/Ctrl/Cmd-click creates a multi-selection.
- Selected layers can be grouped or ungrouped.
- Sections, groups and layers have visibility controls.
- Sections, groups and layers have edit-lock controls.
- Locked content remains visible but cannot be changed or deleted.
- Unsupported future element types are preserved and shown as warnings rather
  than disappearing.
- Sections and elements can be added, duplicated, moved and deleted.

### Content, media and templates

- Inline rich-text editing remains available.
- Bold, italic, underline, bullet-list and numbered-list actions remain
  available.
- Text, button, image and shape elements remain available.
- The Media tab displays the Local WordPress image library.
- A media item can add a new image or replace a selected image.
- A selected section can be saved as a reusable template.
- A reusable template can be inserted into another page with regenerated IDs.
- Template deletion goes through WordPress Trash.

### Responsive styling

- Desktop, Tablet and Mobile previews remain available.
- Each breakpoint stores independent overrides.
- Section layout, background, padding, gap, alignment and overflow are
  editable.
- Element typography, colour, fill, sizing, spacing, corner, shadow, opacity,
  X/Y position, rotation and z-index are editable.
- Stack, grid and layered section modes remain available.

### Saving and recovery

- Changes are automatically saved after a short idle period.
- Ctrl/Cmd+S performs a manual save.
- Unsaved documents are also written to browser recovery storage.
- On reopening a page, a newer recovery copy can be restored or discarded.
- Undo and redo retain up to 100 in-browser document states.
- Every server save is revisioned by WordPress.
- Earlier revisions can be restored.
- A stale client hash cannot overwrite a newer server document.
- Failed or conflicting saves retain the browser recovery copy.

### Collaboration protection

- Opening a page acquires a short-lived editing lock.
- The lock is refreshed while the page remains open.
- A second tab or session receives a read-only workspace.
- The lock is released when switching pages or leaving the editor.
- An expired or non-owned lock cannot save, restore, publish or delete.

### Review and publishing

- Editors can submit a draft for review.
- Publishers can publish an eligible page.
- A page can return to draft editing.
- Missing image alternative text and unsupported elements block publication.
- A published snapshot is immutable.
- Later draft/autosave changes do not mutate the published snapshot.
- Returning to draft does not remove the earlier public snapshot.
- The editor reports whether the working document matches the published
  version or contains unpublished changes.

## Storage and API contract

### Private records

- `am_visual_page`: one revisioned record per managed visual page.
- `am_vb_template`: reusable section templates.
- `am_vb_audit`: immutable audit events.

All three post types are private and excluded from public WordPress queries.

### Schema

- Current document schema: version 2.
- Existing Phase 0 documents migrate automatically.
- Every section, group and element receives a page-wide unique DOM ID.
- Documents are bounded to 30 sections, 30 groups per section and 60 elements
  per section.
- Invalid group references are reconciled.
- Unknown elements keep their sanitized original data for future migration.
- Unsafe rich text, link protocols and CSS-like values are sanitized.

### Dedicated capabilities

- `edit_am_visual_pages`
- `publish_am_visual_pages`
- `manage_am_visual_templates`
- `view_am_visual_audit`

These are installed for Administrator and the existing Alexandra content
manager role when that role is present.

### REST routes

Authenticated:

- page list, create, read, save and Trash;
- lock acquire/release;
- workflow transitions;
- revisions and restore;
- audit events;
- template list/create/delete;
- media list;
- feature flags and schema version.

Public:

- immutable published page AST by slug.

The public endpoint never returns the working draft. It returns HTTP 404 until
a snapshot has been published and sends cache and ETag headers for a published
snapshot.

## Feature flags

Phase 1 provides independent Local feature flags for:

- editor;
- autosave;
- locking;
- templates;
- publishing;
- public AST;
- audit;
- crash recovery.

The MU plugin itself remains guarded to Local/development environments unless
an explicit constant enables it.

## Verification evidence

### Source checks

- Every plugin PHP file passes PHP syntax validation.
- The editor passes Node JavaScript syntax validation.
- The React source passes ESLint.
- The WordPress Vite production build completes successfully:
  1,943 modules transformed.
- Source and installed Local hashes match for:
  - `editor.js`;
  - the REST controller;
  - the active Vite manifest.

### Phase 1 integration suite

All 26 checks pass:

1. Local-only feature gate.
2. Schema version 2.
3. Feature flags enabled.
4. Dedicated Administrator capabilities.
5. Version-1 document migration.
6. Page-wide unique IDs.
7. Malicious markup and CSS-like values removed.
8. Unknown element preservation.
9. Group-reference reconciliation.
10. Large-tree section/element bounds.
11. Page creation.
12. First-session lock ownership.
13. Second-session conflict.
14. Lock-owner assertion.
15. Optimistic save.
16. Stale-hash conflict.
17. Readiness failure blocks publish.
18. Corrected document saves.
19. Publish creates immutable snapshot.
20. Draft change does not mutate snapshot.
21. Snapshot survives return-to-draft workflow.
22. Revision history creation.
23. Reusable section template.
24. Audit trail.
25. Unauthenticated admin API rejection.
26. Public API returns the published snapshot only.

The suite creates isolated temporary data and removes its test page, template,
audit records and lock before exiting.

### Authenticated Local HTTP checks

- Builder admin page: HTTP 200.
- Builder 0.2.0 asset is loaded.
- Initial page selector resolves `home-poc`.
- Managed page list loads.
- Page schema and document version both report 2.
- Lock can be acquired and released.
- Protected dynamic preview: HTTP 200.
- Preview payload is injected only on the protected preview request.
- Normal Local Home: HTTP 200.
- Normal Local Home contains no visual-builder preview payload.

### Regression and cleanliness

- Existing Home prototype remains:
  - 2 sections;
  - 8 elements;
  - text, button, image and shape types;
  - Desktop, Tablet and Mobile styles.
- No temporary test marker remains.
- No Phase 1 test pages remain.
- No test templates remain.
- Home has no stale edit lock.
- No new builder PHP fatal or parse error is present in Local logs.
- The existing Local PHP Imagick startup warning is unrelated to this work.

## Production safety

- Nothing was uploaded to `alexandramontessori.co.uk`.
- Nothing was uploaded to `alexandra.krildigital.com`.
- No production database record was created or changed.
- The existing public React route contract remains active.
- Only an explicit later production approval may start a staged rollout.

## Phase 1 exit gate

Engineering foundation: pass.  
Schema and migration gate: pass.  
Permissions and draft-isolation gate: pass.  
Concurrency and recovery gate: pass.  
Publishing-snapshot gate: pass.  
React build and Local HTTP gate: pass.  
Automated visual-browser screenshot gate: not available for the `.local` host;
the client-perspective walkthrough remains a manual Local Chrome check.

The next implementation phase is Phase 2: universal text, media, shape,
surface and button controls. See
`PHASE-2-VISUAL-CONTROLS-HANDOFF.md`.
