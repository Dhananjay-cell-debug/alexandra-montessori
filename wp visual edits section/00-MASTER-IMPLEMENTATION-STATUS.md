# Alexandra Montessori Visual Builder — Master Implementation Status

Status date: 29 July 2026  
Scope: WordPress visual builder, exact public-site canvas, shared components, current pages/templates, and future-page authoring workflows.

This file is the single high-level status record for the plans in this folder. The individual `PLAN.md` files remain the detailed acceptance contracts.

## Status rule

The following terms are deliberately strict:

- **Registered** means a page/region exists in the builder's route registry.
- **Anchored** means the live DOM can identify the planned region or element.
- **Partially implemented** means a visible/editor interaction exists, but one or more plan acceptance requirements are missing or unverified.
- **Acceptance-complete** means exact public-site rendering, intended editing controls, protected functional behaviour, persistence, responsive behaviour, accessibility, and regression checks all pass.

Registration or DOM anchoring is **not** acceptance completion. A generic visibility, spacing, colour, or movement control does not by itself satisfy a section-specific plan.

## Current inventory

- 188 detailed section plans.
- 23 registered current page surfaces: 20 pages and 3 dynamic detail templates.
- 12 page-family workstreams plus shared/global components.
- The current organiser reports 16 visible entries and 7 removed entries; restoring an entry must not be confused with creating or completing a page. The registry itself contains 23 current surfaces.
- The latest structural audit found 100 exact-route region anchors plus 3 conditional anchors. This proves discoverability, not full editability or plan acceptance.

Registered surface count by workstream:

| Workstream | Surfaces |
| --- | ---: |
| Home | 1 |
| Our Nurseries | 4 |
| Curriculum | 1 |
| Careers | 4 |
| Events | 2 |
| Contact | 4 |
| Check Availability | 1 |
| Fees | 1 |
| Fee Calculator | 1 |
| Funded Childcare | 1 |
| Blog | 2 |
| Food & Hygiene | 1 |
| **Total** | **23** |

## What is genuinely implemented

- A route registry covers the current pages and the vacancy, event, and blog detail templates.
- Exact public React routes can load inside the WordPress canvas instead of using invented replacement layouts.
- Home retains its established design document; non-Home registered routes have isolated page-design documents.
- Shared header, social rail, footer, and cookie areas are represented as global regions around registered pages.
- The Pages organiser has `Navbar`, `Menu`, and `All` views, plus removed-page restoration UI.
- In plugin `0.8.7`, selecting/focusing a left-panel item and pressing the physical keyboard `ArrowUp` / `ArrowDown` keys performs one-step reordering. Pages persist through the organiser API; Regions and Templates use their scoped ordering store; document/custom layers update their real design state. Inline arrow buttons were removed after the interaction was clarified.
- Exact-page direct dragging now uses pointer capture, cancellation cleanup, a click-versus-drag threshold, rendered transform origins, and editor-owned unsaved state so the exact-route runtime cannot snap a drag back to an older saved value.
- View and Edit modes exist. View-mode link surfing and route hand-off back into Edit have been exercised, although the complete mode UX is still being corrected and re-tested.
- The missing production asset bundle that caused blank/empty exact-route canvases was identified and a build-and-sync path was added. Exact Apply-page header/footer metrics were compared against the public page successfully.
- Region and element annotation provides a broad structural foundation for selection and presentation controls.
- The registry distinguishes page-owned content from bound collections, protected forms/calculators/facts, conditional states, and global components.

These foundations are substantial, but they do not mean that every planned control or state is complete.

## Current acceptance status

| Workstream | Current state | Main acceptance gaps |
| --- | --- | --- |
| Shared/global components — 23 plans | **0 acceptance-complete; 21 partial; 2 not implemented** in the strict audit | One versioned global model, public menu-tree publishing, state previews, accessibility/focus checks, future-page route safety, and automated screenshot parity remain incomplete. |
| Home — 12 plans | Mature benchmark, **not declared fully accepted** | It remains the strongest editing experience, but every interaction still needs regression coverage across device modes and the new organiser/canvas movement contract. |
| Our Nurseries — 4 surfaces / 42 plans | Registered and anchored; partial | Bound nursery facts, branch overrides, repeated cards/items, galleries, modal behaviour, responsive editing, and per-branch acceptance remain incomplete. |
| Curriculum — 5 plans | Registered and anchored; partial | Section-specific typography/media/layout controls and responsive parity need full acceptance testing. |
| Careers — 4 surfaces / 14 plans | Registered and anchored; partial | Forms, bound vacancy templates, fallback states, galleries/modals, revision/publish safety, and complete element manipulation remain incomplete. |
| Events — 2 surfaces / 7 plans | Registered and anchored; partial | Bound archive/detail records, empty/not-found previews, record-safe editing, and state acceptance remain incomplete. |
| Contact — 4 surfaces / 29 plans | Registered and anchored; partial | Protected form identity/routing, branch facts, success/error states, galleries, redirects, and per-route QA remain incomplete. |
| Check Availability — 14 plans | Registered and anchored; no plan declared acceptance-complete | Protected field identity, full validation/sending/error/success previewing, schedule logic, Calendly modal accessibility, and responsive QA remain incomplete. |
| Fees — 5 plans | Registered and anchored; no plan declared acceptance-complete | Bound fee-sheet records, media controls, CTA behaviour, and exact responsive acceptance remain incomplete. |
| Fee Calculator — 10 plans | Registered and anchored; no plan declared acceptance-complete | Safe input editing, computed-value protection, working movement selection, actual layout targeting, calculation-state previews, incomplete-data states, and calculator regression tests remain incomplete. |
| Funded Childcare — 8 plans | Registered and anchored; no plan declared acceptance-complete | Repeated collections, FAQ interaction, bound fee resources, reviewed claims, and responsive/state QA remain incomplete. |
| Blog — 2 surfaces / 12 plans | Registered and anchored; no plan declared acceptance-complete | Filters, loading/error/not-found previews, rich-content safety, related records, pagination/state behaviour, and template QA remain incomplete. |
| Food & Hygiene — 7 plans | Registered and anchored; no plan declared acceptance-complete | Verified fact protection, badge variations, public-record links, empty/future-branch previews, and full state QA remain incomplete. |

## Immediate blocker backlog

These items take priority because they prevent the editor from feeling reliable even when the underlying page is present.

1. **Fix page actions everywhere the organiser is shown.** Three-dot actions and right-click actions must open reliably; rename, remove/restore, and relevant page actions must work consistently on mouse, touch, and keyboard.
2. **Make page reordering reliable.** Dragging from the full page card must show a clear handle/placeholder, allow scrolling while dragging, persist the new order, and recover safely after failed saves.
3. **Finish acceptance coverage for selected-item keyboard movement.** The implemented interaction is: select/focus a Page, Region, Template, Section, or Layer and press the physical keyboard `ArrowUp` / `ArrowDown` key. Remaining work is cross-list/device regression coverage, retained focus/selection, scroll-into-view after long-distance movement, accessible announcements, and failed-persistence recovery. No inline arrow-button UI is intended.
4. **Separate View from Edit unmistakably.** View should use the available canvas width and behave like the real site, without editing overlays or misleading disabled organiser actions. Edit should restore the builder rails and selection controls.
5. **Acceptance-test exact-route element movement across every route.** The stale-runtime and lost-pointer core failures are fixed, but text, buttons, labels, icons, images, cards, layout frames, and safe form presentation still need route-by-route and device-by-device verification before this can be called complete.
6. **Keep functional data protected.** Dragging or styling a form/calculator frame must never alter field names, option values, calculated results, consent semantics, upload behaviour, submission routing, bound nursery/job/event/article facts, or public-record destinations.
7. **Replace heuristic repeated-element identities with explicit semantic identities.** Current runtime allocation prevents duplicate keys in a loaded canvas and keeps the first occurrence stable, but long-term per-page semantic keys are still needed for conditional and reordered records.
8. **Target the real layout node.** Gap, content-width, padding, and alignment controls must affect the actual grid/flex/container visible on the page, not merely a wrapper where the CSS has no visible result.
9. **Protect required and conditional regions.** A generic “Show this section” control must not be offered where hiding the region would break a form, result, bound fact, global component, or required fallback.
10. **Close the save-safety gap.** Exact-route design saves currently need a proper revision, publish/draft, undo/redo, conflict, and recovery contract before client acceptance.

## Next implementation sequence

### P0 — restore trust in direct manipulation

- Complete and verify three-dot, context-menu, drag reorder, and Up/Down fallback controls.
- Verify the left sidebar remains scrollable during pointer drag and after menus open.
- Complete non-Home element selection and movement without damaging structured links, buttons, labels, forms, or calculators.
- Test changes in a separate QA browser tab and do not disturb the client's active tab.

### P1 — exact, need-aware editing

- Map every plan's intended content and design controls to the exact live element.
- Replace broad generic controls with section-specific controls where the plan requires them.
- Ensure images support appropriate upload, alt text, crop/focal position, and responsive treatment.
- Ensure repeated collections support item selection, order, add/remove rules, and bound-record safety appropriate to that collection.
- Provide explicit global-edit entry points and clearly state when one save affects every page.

### P2 — states, accessibility, and publishing safety

- Add non-publishing preview fixtures for loading, empty, error, success, not-found, closed, missing-data, and modal states.
- Validate keyboard order, focus trapping/restoration, announcements, reduced motion, colour contrast, labels, and touch targets.
- Add revisions, draft/publish, undo/redo, conflict protection, and safe failure recovery.
- Complete versioned menu-tree placement, slug changes, redirects, unpublish/trash/restore, and future-page route collision checks.

### P3 — acceptance automation

- Capture exact public-versus-builder screenshots at desktop, tablet, and mobile breakpoints.
- Add functional tests for forms, calculator rules, menus, modals, filters, templates, conditional states, and bound-data protection.
- Mark an individual `PLAN.md` acceptance-complete only when its visual, functional, responsive, persistence, accessibility, and safety checks all pass.

## Definition of “everything editable according to its need”

The acceptance target is not unrestricted DOM mutation. It is deliberate control at the correct ownership level:

- **Page-owned content:** edit copy, links, media, ordering, visibility when safe, and presentation.
- **Repeated page-owned collections:** edit items and their order with stable per-item identities.
- **Bound records:** edit presentation here; edit record-owned facts through their authoritative record workflow.
- **Forms and calculators:** edit labels, help text, surrounding layout, and visual treatment; protect names, values, validation, logic, computation, consent, uploads, and delivery.
- **Global components:** edit once through an explicit global context and preview the effect across pages.
- **Conditional states:** preview safely with fixtures; never publish fixture content or corrupt real state.
- **Responsive design:** allow intentional all-device defaults plus explicit device overrides, with a clear reset/inheritance model.

## Completion gate

No page or global plan should be reported as “done” merely because it appears in the Pages list, loads in the canvas, has region anchors, or exposes generic sliders. Completion requires the exact plan acceptance contract and evidence. Until that evidence exists, this master file must continue to report the workstream as partial.
