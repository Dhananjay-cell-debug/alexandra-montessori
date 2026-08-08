# Funded Childcare — closing CTA section plan

## Intent and feel

Close with reassurance for uncertain parents and one calm next step. Exact parity matters because the current next step is choosing a nursery, not contacting the team directly.

## Exact current JSX and public evidence

- `FundedChildcare.jsx` renders shared `CTA` after the FAQ section.
- Page-supplied h2: `Not sure what you're entitled to?`
- Page-supplied paragraph: `Our team will happily walk you through the funding options and help you claim your hours.`
- `CTA.jsx` accepts only `title` and `text`; its action is fixed in the component as `Choose a nursery` linking to `/nurseries`.
- Actual section is `bg-sand py-16 text-center`, with max-w-xl paragraph and one sage primary button. There is no contact action, icon, secondary action, media or overlay in current output.

## Current public section -> exact WP canvas parity

| Current JSX/public layer | Exact seeded WordPress canvas layer |
| --- | --- |
| One shared CTA section | One final `Closing CTA` section |
| Page-specific h2 | `CTA title` with exact entitlement question |
| Page-specific paragraph | `CTA text` with exact team-help sentence |
| Shared fixed link | One action `Choose a nursery` → `/nurseries` |
| Sand centred style | Current background, py-16, centred layout and max-w-xl copy |
| No other layers | No invented media, overlay, icon or secondary action |

## Exact editing controls

- Direct title/text editing; background token, spacing, alignment, text measure and approved type/colour controls.
- Action label and registered internal destination can be exposed as a deliberate instance override, seeded/inherited as `Choose a nursery` → `/nurseries`.
- Optional second action/media remain off and labelled as public-structure additions, not current editable layers.
- Preview shared CTA pattern changes versus page-local content/style overrides.

## Layers, reorder and dragging

- Exact tree: `Closing CTA section` → `CTA title`, `CTA text`, `Primary action`.
- Internal order is protected for comprehension; layers are directly selectable but not freely positioned.
- CTA stays final in this page’s current section order. Reordering it requires page-level confirmation and is not implied by button dragging.

## Responsive behaviour

- Preserve centred stack, `py-16`, 3xl→4xl h2 and naturally wrapping max-w-xl text.
- Button remains at least 44px, wraps safely and never clips on narrow screens; no device-only hidden action.

## Data ownership and override semantics

- Title/text belong to `page:funded-childcare.sections.closingCta`.
- Action/style currently inherit shared CTA component defaults; an explicit page override stores only changed label/destination/style fields.
- Clearing the action override returns exactly to `Choose a nursery` and `/nurseries`, not `/contact`.

## Protected behaviour

- Internal destination comes from registered route picker; no `javascript:`/raw HTML.
- Copy may offer guidance but cannot promise eligibility, funding approval or a guaranteed place.
- One primary action remains required while the section is visible; secondary/media additions require explicit design review.

## Accessibility

- h2, paragraph and descriptive link remain in logical order; visible keyboard focus and target size pass.
- Link purpose is clear without surrounding visual context; colour contrast and 200% zoom pass.
- No decorative image or icon is announced because none exists in the current seed.

## Empty, loading and error states

- Empty title/text warns and blocks accidental blank CTA publication; action remains visible only with valid label/destination.
- Missing registered `/nurseries` route blocks publish or uses last valid published action—never a dead link.
- Shared pattern load failure falls back to exact current sand/centred tokens with editor diagnostic.

## Storage and versioning

- Store page content and sparse action/style override at `pages/funded-childcare/sections/closing-cta`, including inherited shared-pattern revision.
- Diff distinguishes shared upstream changes from local overrides and flags destination changes prominently.
- Restore this CTA without altering FAQ or global CTA defaults.

## Planned implementation files (future only)

- `am-visual-builder/schema/pages/funded-childcare/closing-cta.php`
- `am-visual-builder/admin/pages/funded-childcare/ClosingCtaEditor.jsx`
- `am-visual-builder/admin/components/InheritedActionControl.jsx`
- `am-visual-builder/runtime/pages/funded-childcare/ClosingCtaSection.php`

## Acceptance checklist

- [ ] Exact h2, paragraph, sand style and final placement reproduce.
- [ ] Current action is correctly documented/rendered as `Choose a nursery` → `/nurseries`.
- [ ] No contact action, secondary button, icon, media or overlay is invented by default.
- [ ] Inherited/local action semantics, registered-route validation and claim guard work.
- [ ] Mobile wrap, focus, target size, empty/error states, diff and isolated restore pass.
