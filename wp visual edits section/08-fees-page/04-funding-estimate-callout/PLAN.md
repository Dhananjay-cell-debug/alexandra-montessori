# Funding estimate callout plan

## Intent and feel

The sand callout should offer one clear next step after fee sheets: estimate chargeable hours, then verify against the relevant branch document. It must remain visibly advisory rather than an online quote.

## Exact current JSX/public evidence

- The callout is the next `Reveal` in the right column after the fee-sheet card grid, with delay 120 and `mt-8`.
- Frame is `rounded-2xl bg-sand p-6 sm:p-7`.
- H2 is exactly `Need a funding estimate?`.
- Paragraph is exactly `Estimate weekly chargeable hours after funded childcare, then confirm the exact amount with the relevant branch fee sheet.`
- React Router `Link` is `btn-primary mt-5`, points to `/fee-calculator`, and reads `Estimate funded hours` plus ArrowRight.
- It is not currently draggable/full-width and has no image/secondary action.

## Editable controls

- H2, explanatory copy and link label using a registered internal-page picker seeded to Fee Calculator.
- ArrowRight from approved icons, button role/style and visible focus.
- Sand surface, radius, padding, spacing and Reveal timing within tested bounds.
- Advisory-language review and preview for long copy, missing calculator route and reduced motion.
- No image, second CTA or layout-preset control as current elements.

## Layers, reordering and dragging

Protected order is H2 -> paragraph -> CTA inside one flow card. The callout remains after the fee-sheet grid in the right column. It can be selected but is not freely dragged, layered over cards or promoted full width through this parity plan. Any future page-section reorder needs an explicit outline/schema change.

## Desktop, tablet and mobile

It follows the right-column stack at every width; below `lg` that entire column follows the feature photo. Padding increases from 6 to 7 at `sm`. CTA wraps safely at 320px and zoom without detaching its icon.

## Data ownership and bindings

Copy/presentation belong to Fees page. Destination binds by stable page ID to the published Fee Calculator; route resolution is global. It does not own calculator formulas, funded-hours choices or branch fee facts.

## Protected behavior

- CTA remains an internal navigation link, not a form submission or unknown external calculator.
- Wording cannot promise an exact invoice, eligibility or funding entitlement.
- Block missing/unpublished destination and unsafe URL overrides.
- Preserve one H2 and minimum focus/tap/contrast behavior.
- Changing this card cannot alter calculator rules.

## Accessibility

H2/copy make purpose understandable without icon. ArrowRight is decorative. Link has visible focus and meaningful label. Reveal honors reduced motion. Text contrast must pass on the selected sand/background token.

## State previews and failure handling

Preview exact current, long copy/label, reduced motion, destination unpublished/missing and high zoom. If Fee Calculator is unavailable, retain the last valid public link and block a broken save; the editor displays a repair action. Empty copy does not leave unexplained padding.

## Storage and versioning

Store copy, stable destination reference and bounded presentation under a versioned Fees callout record. Revisions highlight destination/advisory-copy changes. Migration seeds exact current text, `/fee-calculator` binding and style.

## Planned implementation files (future only)

- `src/components/fees/FundingEstimateCallout.jsx`
- `src/pages/Fees.jsx`
- `src/lib/linkModel.js`
- `wordpress/mu-plugins/alexandra-visual-builder/assets/panels/fees-funding-callout.js`
- `wordpress/mu-plugins/alexandra-visual-builder/tests/fees-funding-callout.php`

## Current public section -> exact WP canvas parity

| Current public element | Exact WP canvas requirement |
| --- | --- |
| Sand rounded card after fee-sheet grid | Same right-column order, margin, radius and padding |
| `Need a funding estimate?` | Same H2 text/hierarchy |
| Current advisory paragraph | Same exact receipt-versus-estimate wording |
| Primary `Estimate funded hours` + ArrowRight | Same label/icon/style and `/fee-calculator` target |
| No image/secondary action/full-width mode | Canvas must not fabricate these layers or layout |

## Acceptance checklist

- [ ] Untouched callout matches current JSX at all widths.
- [ ] Destination remains stable-page-bound to Fee Calculator.
- [ ] Advisory copy cannot become a guaranteed quote unnoticed.
- [ ] CTA focus/target/icon remain accessible and secure.
- [ ] Missing route blocks broken publication with last valid state retained.
- [ ] Callout edits do not alter fee sheets or calculator rules.
