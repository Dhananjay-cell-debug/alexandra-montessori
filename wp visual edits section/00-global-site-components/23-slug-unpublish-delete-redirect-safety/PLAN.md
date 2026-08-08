# Slug, unpublish, delete and redirect safety plan

## Intent and feel

Route changes should feel deliberate and recoverable. Before renaming, unpublishing or deleting a page, the client sees every affected menu, CTA, internal link, search/social URL and child page, chooses a safe outcome, and can undo through revisions/trash. Destructive wording and buttons must match the real consequence.

## Current evidence

- Generic visual-page REST operations currently address pages by slug and include save, workflow transition, revisions/restore and delete.
- `AM_VB_Document::find()` resolves by sanitized path; create rejects duplicate visual-page slugs but does not by itself check all explicit React routes/namespaces.
- Public visual snapshots are workflow-controlled, while the underlying private post uses WordPress storage.
- Current React links include many hard-coded routes; global/menu work plans aim to normalize stable page references.
- Current explicit public routes such as `/`, `/nurseries/*`, `/careers/*`, `/events/*`, `/contact/*`, `/privacy` must never be shadowed by a future page.

## Editable elements and controls

- Slug editor shows current full URL, normalized proposed slug, reserved/conflict check, child-route impact and live link graph.
- Rename choices: create permanent redirect from old URL (recommended), keep alias temporarily, or cancel; no silent change.
- Unpublish dialog shows menu placements, inbound CTAs, sitemap/search impact, active campaigns and fallback choice (404, redirect, replacement page).
- Delete is two-stage: Move to trash (recoverable) then privileged permanent delete after retention/impact review.
- Redirect manager shows source, destination, status code, owner reason, created date, hits, chains/loops and expiry only for intentional temporary redirects.
- Bulk operation preview and downloadable audit for high-impact changes; single clear confirmation phrase for permanent actions.

## Selection, layers and dragging

This workflow has no visual canvas layers. Dependencies appear as a graph/list grouped by menu, page section, SEO and redirects. Dragging may reorder redirect-priority rows only where matching rules need it; ordinary route redirects are exact-match records and do not require free ordering. All actions have keyboard-accessible equivalents.

## Desktop, tablet and mobile

Impact review is usable at narrow admin widths with tables collapsing into labelled cards. Public redirect/unpublish behaviour is device-independent and tested on direct navigation plus in-app routing. Preview desktop/mobile menus confirms that no dead node remains; route caches/service workers receive invalidation regardless of device.

## Data ownership and bindings

Stable page ID is authoritative; slug is a versioned route attribute. Menus/CTAs/internal references bind to page ID and resolve the current path. Redirects live in a dedicated validated registry keyed by normalized source path. Published route registry, canonical/sitemap and caches update atomically after workflow success. Page document/content remains revisioned separately.

## Protected rules

- Reserve current explicit routes, their child namespaces, system/admin/API paths and existing redirect sources.
- Prevent redirect loops, chains where a direct target can be stored, unsafe external destinations and wildcard capture of broad site paths.
- A slug change cannot publish without an old-path policy and successful dependency validation.
- Unpublishing removes public menu/sitemap visibility atomically or blocks if a safe snapshot cannot be produced.
- Permanent delete requires elevated capability, empty trash-retention requirements, backup/audit and resolved inbound dependencies.
- Home and required legal/availability routes have additional deletion/unpublish protection.

## Accessibility and failure states

Impact counts, consequences and error states are announced and not encoded only by warning colour. Focus moves to the first unresolved dependency. If redirect creation or cache invalidation fails, roll back the route/menu publish and keep the old public state. If a target is unexpectedly missing, serve a deliberate 404 or last-known safe redirect—never an infinite loop/blank app. Trash restore recovers stable ID, document and intended route, resolving collisions explicitly.

## Storage and versioning

Add versioned route history per stable page ID, redirect registry revisions and audit events for proposed/applied/rolled-back operations. Do not use slug as the only REST identity after migration; routes should accept stable IDs with compatibility lookup. Store published route/menu/sitemap snapshots under one transaction/release ID. Retain redirect history after page deletion according to policy.

## Planned implementation files (future only)

- `wordpress/mu-plugins/alexandra-visual-builder/includes/class-am-vb-route-registry.php`
- `wordpress/mu-plugins/alexandra-visual-builder/includes/class-am-vb-redirects.php`
- `wordpress/mu-plugins/alexandra-visual-builder/assets/flows/page-route-safety.js`
- `src/lib/visualPageRoutes.js`
- `src/pages/NotFound.jsx` (resolved failure behaviour only)
- `wordpress/mu-plugins/alexandra-visual-builder/tests/route-safety.php`

## Current public section → exact WP canvas parity

| Current public route evidence | WP canvas/safety requirement |
| --- | --- |
| Existing explicit route table | Imported as reserved; no future page may shadow any route/namespace |
| Current menu/CTA URLs work as written | Before migration/change, every resolved public URL remains identical |
| Draft visual pages are not current public routes | Unpublish/create operations do not expose them accidentally |
| Current 404 handles unmatched paths | Missing/unpublished routes reach deliberate 404 unless a validated redirect exists |
| Current Home `/` and legal/availability paths are critical | Stronger protection prevents ordinary unpublish/delete/rename |

## Acceptance checklist

- [ ] Stable IDs replace slug-only dependency binding without changing current URLs.
- [ ] Rename shows complete impact and creates an atomic validated old-path policy.
- [ ] Unpublish removes/repairs public references with one consistent release.
- [ ] Trash is recoverable; permanent delete is privileged, audited and dependency-safe.
- [ ] Redirect loops/chains/reserved collisions are blocked.
- [ ] Any failure rolls public route/menu/canonical state back coherently.
