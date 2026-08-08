# Funded Childcare — fees and resources section plan

## Intent and feel

Pair practical fee downloads with trusted external help in one balanced sand band. Editors should instantly understand that fee PDFs belong to nursery records, while helpful links belong to this page.

## Exact current JSX and public evidence

- Fourth content section is one `bg-sand py-16 sm:py-20` section containing a one-column grid that becomes two columns at `lg`.
- Left card layers: `Our fees`; h2 `Nursery fees`; exact invoice/funded-hours paragraph; one PDF row for every `feeSheets` item; `Ask us about fees` button linking `/contact`.
- `feeSheets` is derived from published `locations` with non-empty `feeSheetPdf`; current static data produces Hounslow, Heston and Hammersmith rows labelled `{name} - fee schedule (PDF)`.
- Right card layers: `Helpful links`; h2 `Additional resources`; exact trusted-government/local-authority paragraph; three external resources:
  1. Childcare Choices.
  2. GOV.UK - Help with childcare costs.
  3. Hammersmith & Fulham Council.
- PDF rows use `FileDown`; resources use `ExternalLink`; every external/PDF link opens a new safe tab.

## Current public section -> exact WP canvas parity

| Current JSX/public layer | Exact seeded WordPress canvas layer |
| --- | --- |
| One sand section | One `Fees & resources` section; not two page sections |
| Two `Reveal` cards | `Fee sheets card` then `Helpful links card` in current DOM/mobile order |
| `feeSheets.map` | Bound nursery fee-document collection, seeded Hounslow/Heston/Hammersmith |
| Contact Link | One action `Ask us about fees` → `/contact` |
| `fundingResources.map` | Page-owned resource collection seeded with exact three links |
| `lg:grid-cols-2` | One column below large, two equal columns from large |

## Exact editing controls

- Section background/spacing/width, grid gap and mobile/desktop card order; card surface, padding, border, radius and shadow.
- Per-card eyebrow, h2, paragraph and action label direct editing; action destination through internal route picker.
- Fee collection filter/order/display label pattern, row icon/style and missing-document preview; PDF field edits deep-link to the owning nursery record.
- Resource add/duplicate/archive/reorder; label, note, HTTPS URL, source organisation, last-checked date, status and link-test control.
- External/PDF target indicator is automatic and cannot be disguised by styling.

## Layers, reorder and dragging

- Exact tree: `Fees & resources section` → `Two-card grid` → `Fee sheets card` → eyebrow, h2, paragraph, `Fee document list` → bound rows, contact action; `Helpful links card` → eyebrow, h2, paragraph, `Resource list` → resource rows.
- Whole cards may swap through a labelled order control with pointer/keyboard parity; default/mobile DOM order remains fees first.
- Resource items can reorder within their list. Bound fee rows may set page display order but dragging never transfers a PDF between nursery records.

## Responsive behaviour

- Preserve one column below `lg` and two columns at `lg`, with `gap-10 lg:gap-14` and p-8→p-10 cards.
- Links wrap long labels/URLs without clipping; icons stay shrink-safe. Mobile order equals DOM order.
- Empty/short left list must not force unexpected collapse of the right card; equal-height behaviour applies only in two-column layout.

## Data ownership and override semantics

- Section/card copy and `fundingResources` belong to `page:funded-childcare.sections.feesResources`.
- Fee PDF URL/name derive from each nursery entity; the page stores only filter, display order and optional label pattern.
- Editing a PDF at source updates Fees and Fee Calculator consumers too, so the inspector must show downstream impact.
- Shared card styles are inheritable; page-local style changes are sparse and revertible.

## Protected behaviour

- Nursery fee URLs accept approved PDF/document types and safe HTTPS links; page editor cannot rewrite a bound source URL as static text.
- Resource URLs require valid HTTPS where available, safe target/rel, source and review date; stale/broken links warn or block according to status.
- The `/contact` action uses the registered route picker, not raw JavaScript or arbitrary href.

## Accessibility

- Both cards have h2 headings; resource items remain a semantic `ul/li`; links have visible focus and clear external/download meaning.
- Download label includes nursery and PDF context; icon is supplemental.
- Card and item reordering works by keyboard and updates DOM order; contrast and 200% zoom pass.

## Empty, loading and error states

- Current empty fee collection leaves heading/copy/contact action with an empty list; current empty resources leave heading/copy and empty `ul`. No public fallback copy exists today.
- Builder shows editor-only empty diagnostics; an authored inline message can be added within the same card only as an explicit future public change, off by default.
- Broken PDF/resource link is flagged before publish; last published valid list remains available during record/API failure.

## Storage and versioning

- Store section/card content/style and resource items at `pages/funded-childcare/sections/fees-resources`.
- Store fee display order/filter as stable nursery UUID references; source PDF remains versioned on each nursery entity.
- Revision diff separates page edits, resource-link verification and upstream nursery PDF changes; restore cannot roll back a nursery PDF unless authorised at source.

## Planned implementation files (future only)

- `am-visual-builder/schema/pages/funded-childcare/fees-resources.php`
- `am-visual-builder/admin/pages/funded-childcare/FeesResourcesEditor.jsx`
- `am-visual-builder/admin/components/BoundFeeSheetList.jsx`
- `am-visual-builder/admin/components/ReviewedResourceList.jsx`
- `am-visual-builder/runtime/pages/funded-childcare/FeesResourcesSection.php`

## Acceptance checklist

- [ ] One sand section, two cards, exact headings/copy and fees-first order reproduce.
- [ ] Hounslow, Heston and Hammersmith fee rows resolve from their own records with current label pattern.
- [ ] Exact three resources, notes, safe external behaviour and `/contact` action reproduce.
- [ ] Dragging cannot move a PDF between nursery owners; downstream impact is shown.
- [ ] One/two-column layout, keyboard order, empty/broken/load states, link review, diff and restore pass.
