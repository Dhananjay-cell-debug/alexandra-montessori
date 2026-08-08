# Funded Childcare — funding FAQ section plan

## Intent and feel

End the information body with an always-open, low-friction answer card. The editor must reveal that the current collection mixes general nursery questions with one funding-specific answer rather than pretending it is already a specialised funding FAQ set.

## Exact current JSX and public evidence

- Fifth content section is `<section className="container-wide py-16">`.
- One `Reveal` card is centred at max-w-3xl with p-8 and h2 `Funding FAQs`.
- `faqs` maps to a static, always-open divided list; current JSX has no accordion buttons or collapse state.
- Exact current order:
  1. `What ages do you care for?`
  2. `Do you offer funded childcare?`
  3. `What are your opening hours?`
  4. `What makes Alexandra Montessori different?`
- The first answer currently says `All three nurseries welcome children from Babies to 5 Years.`, while the Hammersmith nursery record states `12 months to 5 years`; this is a current source-content discrepancy to flag, not silently rewrite during planning.
- Each item is a div containing h3 question and paragraph answer. The array is imported from `site.js` but is only consumed by this page in the current source.

## Current public section -> exact WP canvas parity

| Current JSX/public layer | Exact seeded WordPress canvas layer |
| --- | --- |
| One contained section | One `Funding FAQs` section with current py-16 |
| One max-w-3xl card | `FAQ card` with current width/padding/surface |
| h2 `Funding FAQs` | Required section heading layer |
| Four open FAQ divs | Ordered `FAQ collection`, all expanded and visible |
| Divide-y separators | Card-list divider token between items |
| No accordion interaction | Default presentation `Open list`; no disclosure buttons invented |

## Exact editing controls

- Section/card width, spacing, surface, border/radius/shadow and padding; h2 typography.
- FAQ add, duplicate, archive, reorder; question plain text, answer safe rich text, optional source URL, topic tag, review owner/date and status.
- Divider colour/weight, item spacing and question/answer type controls.
- Optional `Accordion` presentation can exist only as an explicitly selected future design change; default seed remains open list and preview shows semantic/print consequences.

## Layers, reorder and dragging

- Exact tree: `Funding FAQ section` → `FAQ card` → `Section heading`, `FAQ list` → four `FAQ item` → `Question`, `Answer`.
- Pointer/keyboard item reorder updates DOM order. Question stays before its own answer and cannot be detached into another item.
- Card and heading remain within the current section; no FAQ becomes a separate page section through dragging.

## Responsive behaviour

- Preserve max-w-3xl centred card, p-8 and full open answers at every breakpoint.
- Long questions/answers wrap and expand vertically; no clipping at 200% zoom.
- If accordion is deliberately enabled later, its mobile/desktop state must not hide content in print or no-script fallback.

## Data ownership and override semantics

- FAQ items become page-owned `page:funded-childcare.sections.faqs`; they are not nursery-record facts.
- Seed the exact current four general/funding items and mark topic/review metadata explicitly.
- Shared card tokens may be inherited; content/order stay local. No answer is auto-copied from the funding offering cards.

## Protected behaviour

- Visible items require non-empty question and answer, stable ID, sanitized rich text and validated source links.
- Funding/age/hours answers require freshness review and must not contradict active nursery records or government guidance.
- Publishing the current age answer unchanged requires resolving or explicitly acknowledging the Hammersmith 12-month-minimum discrepancy.
- Changing to accordion requires explicit review; no hidden-by-default content is introduced silently.

## Accessibility

- Current open list retains h2→h3 structure and needs no interactive ARIA.
- If accordion is chosen, question becomes a real button with `aria-expanded`/`aria-controls`, keyboard support and open print fallback.
- DOM order equals visual order; contrast, focus and zoom pass.

## Empty, loading and error states

- Current zero-item JSX would show the card heading and empty list container; builder gives an editor-only warning and blocks accidental empty publish unless section is intentionally disabled.
- Empty question/answer remains draft-only. Load failure uses the last reviewed published FAQ snapshot.
- A public contact fallback is not current output and must not appear unless explicitly authored within this same section.

## Storage and versioning

- Store `pages/funded-childcare/sections/faqs` with stable ordered items, answer AST, sources/topics/review metadata and presentation/style settings.
- Diff question/answer edits, order, review status and open-list↔accordion changes separately.
- Restore section without changing CTA or funding cards.

## Planned implementation files (future only)

- `am-visual-builder/schema/pages/funded-childcare/faqs.php`
- `am-visual-builder/admin/pages/funded-childcare/FaqEditor.jsx`
- `am-visual-builder/admin/components/ReviewedFaqSorter.jsx`
- `am-visual-builder/runtime/pages/funded-childcare/FaqSection.php`

## Acceptance checklist

- [ ] Exact h2, four questions/answers/order, open-list presentation and dividers reproduce.
- [ ] Hammersmith age-range conflict is visible in editor diagnostics and cannot be silently propagated.
- [ ] Documentation does not claim a current accordion or funding-only dataset.
- [ ] Pointer/keyboard reorder, direct edit, source/review and long-copy zoom pass.
- [ ] Empty/incomplete/load states remain safe without invented fallback output.
- [ ] Any accordion opt-in meets semantics, focus, print, diff and rollback requirements.
