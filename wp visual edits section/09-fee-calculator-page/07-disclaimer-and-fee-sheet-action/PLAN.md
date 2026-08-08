# Calculator Disclaimer and Fee-Sheet Action Plan

## Implementation-grade parity contract

### Intent and feel

The close of the result panel should calmly set limits and offer the correct evidence document, preventing a planning estimate from feeling like an invoice.

### Exact current React/public evidence

After the three summary cards, current JSX renders exact paragraph `Exact invoices depend on the current branch fee sheet, funding eligibility, stretched-term arrangements and any agreed sessions.` with `mt-6`, small relaxed white/75 text. If `sheet` exists, a `btn-cream mt-7` anchor opens `sheet.href` in a new tab with `noopener noreferrer` and reads `Download {sheet.name} fee sheet` plus FileDown. If no match exists, current JSX renders no button and no fallback message/action.

### Current public section -> exact WP canvas parity

| Current public truth | Required WordPress canvas result |
| --- | --- |
| Disclaimer | Preserve exact copy, punctuation, placement, colour and spacing after the result cards. |
| Valid document | For current Hounslow/Heston/Hammersmith selections, resolve the matching source PDF and exact branch-specific cream action with icon/safe attributes. |
| Missing document | Exact-current fixture omits the button entirely; a future Contact fallback may be added only as an explicit designed state, not claimed as current output. |

### Editable elements and controls

Disclaimer rich text, review/effective date, action label token pattern, approved icon, type/button styles, alignment and spacing, optional file metadata. PDF selection stays source-bound. Financial/legal changes require reviewer status.

### Layers, reorder, and dragging

Results panel > disclaimer paragraph > conditional document action. Both remain after summaries in flow. The action may not be dragged above inputs or detached from its branch/document binding; icon stays inside link.

### Responsive behavior

Copy wraps naturally and the button remains a reachable, non-overflowing target at 320px/200% zoom. Long Nursery/file labels may wrap; new-tab behavior is consistent across devices.

### Data ownership and bindings

Disclaimer/action presentation belongs to the page. Selected sheet URL/name comes from the selected Nursery/fee-sheet projection; internal fallback destination, if enabled, comes from route registry. Current input values do not alter disclaimer copy.

### Protected behavior

Valid allowed document/HTTPS URL, selected-Nursery association, safe target/rel, escaped label, no blank/dead link and no wording that guarantees an invoice. A page designer cannot type a substitute PDF URL.

### Accessibility

Link text identifies the Nursery and fee sheet; icon is decorative; focus is visible; PDF/external-new-tab meaning is communicated consistently. Disclaimer remains text, not tooltip-only.

### Relevant state previews

Preview each valid current PDF, missing PDF (current no-button output), invalid/blocked file, long label, branch switching, reviewed future Contact fallback, keyboard focus and 320px/zoom.

### Storage and versioning

Version copy/style/label pattern under `fee_calculator.disclaimer_action`; retain only a binding to selected Nursery sheet. Document metadata/revisions stay on Nursery/Media records; review status and optional fallback route are auditable.

### Planned implementation files (future only)

These paths describe later implementation ownership; they are **not created by this planning phase**.

- `includes/definitions/pages/fee-calculator.php` -- disclaimer/action schema and tokens.
- `includes/data/fee-sheet-resolver.php` -- validated selected-Nursery document binding.
- `assets/editor/pages/fee-calculator/disclaimer-action.js` -- copy/review/document states.
- `tests/parity/fee-calculator/disclaimer-action.spec.js` -- exact copy, three PDFs and missing-button parity.

### Acceptance checklist

- [ ] Exact disclaimer and all three current branch actions match the React route.
- [ ] Missing/invalid sheet never creates an empty or wrong-branch link; current seed simply omits action.
- [ ] Review, accessibility, safe external attributes and mobile wrapping pass before publication.


## Exact current section

Below results is copy explaining invoice dependencies: fee sheet, eligibility, stretched term, and agreed sessions. If the selected Nursery has a sheet, a cream button downloads `{branch_name} fee sheet` with FileDown icon.

## Editing model

Disclaimer rich text, link/button label token pattern, icon, styles, alignment, spacing, and optional last-updated/file metadata. Legal/financial copy requires review status. PDF remains bound to selected Nursery.

## States

Preview valid PDF, missing PDF, invalid file, and branch changed during use. Missing PDF replaces the button with an editable safe Contact action; never an empty link.

## Acceptance

Each Nursery selection downloads its own valid PDF; safe external/document attributes apply; disclaimer remains visible and readable at all breakpoints.
