# Food & Hygiene — empty and future-branch states plan

## Intent and feel

Give editors a truthful simulator for incomplete and future records while keeping public behaviour inside the existing card-grid section. No state may tempt an editor to invent a score.

## Exact current JSX and public evidence

- This folder describes conditional states of the one existing rating-grid section; it is not a seventh public section.
- `foodHygieneRatings` maps every `locations` record. If there are zero locations, current JSX leaves `container-wide pb-16 pt-6` with an empty grid and no public message/action.
- A future generic nursery can supply empty hygiene strings: empty/non-numeric rating produces pending badge; empty address/date/authority render empty text slots; empty href produces `Public record details are being updated.`
- A numeric rating with missing date still shows the numeric score and an empty date dd. A truthy invalid URL currently attempts a link.
- Current component has no loading/error discriminator, archive policy, empty-state copy or contact action.

## Current public section -> exact WP canvas parity

| Current state fixture | Exact seeded WordPress/public behaviour |
| --- | --- |
| No nursery records | Existing padded rating section with empty grid; no invented message/CTA |
| Future branch, empty hygiene fields | Pending heading/badge, blank factual slots, update sentence footer |
| Missing/non-numeric rating | Pending `Awaiting public listing` / `Check latest record` |
| Numeric rating, missing date/authority | Numeric badge plus blank bound metadata value(s) |
| Missing href | Exact `Public record details are being updated.` footer |
| Truthy invalid href | Current JSX would render a link; planned safe validation blocks it |
| Record load failure | Not distinguished by current JSX; planned admin/runtime state must distinguish it |

## Exact editing controls

- Editor-only state selector for zero records, future pending, missing rating, missing date, missing authority, generic URL, invalid URL, stale record and loading failure.
- Fixture preview uses the real card template and never writes fixture values to nursery records.
- Pending labels/message styles are edited through the badge/action template controls; field completion deep-links to source record.
- Optional zero-collection message/action may be designed **inside the same section** as a deliberate future public change; it is disabled for exact current parity.

## Layers, reorder and dragging

- Editor state tree: `Rating grid section` → `State preview controls` (admin only) → resolved `Bound card` layers or `Zero-record preview`.
- State controls and fixtures never enter the public layer tree or section drag order.
- Future branch card participates in the same stable-UUID collection order; dragging changes display order only, not its status/facts.

## Responsive behaviour

- State previews cover mobile one-column and desktop three-column layouts, long pending copy, blank/long authority and min-16 badge.
- Zero-record current state must not impose arbitrary viewport height. Any future same-section message wraps and remains centred without horizontal overflow.
- No state hides factual meaning at a breakpoint; 200% zoom and keyboard-only inspection pass.

## Data ownership and override semantics

- Real state derives from nursery publication status plus `nursery:{uuid}.foodHygiene` fields and collection load state.
- Page/template owns pending/empty presentation labels only; fixture data is transient admin state.
- Last-known rating/current-vs-archived policy belongs to regulated record governance, not an arbitrary per-page override.
- New published branches inherit the existing card template automatically according to the grid collection rule.

## Protected behaviour

- No fixture can publish, no free-text score can replace incomplete data and no low/pending state can be visually promoted to rating 5.
- Numeric score with incomplete evidence is flagged; invalid URL cannot publish as action; current/stale/archive status requires explicit evidence policy.
- Data failure cannot be treated as zero records and must not erase last verified publication.

## Accessibility

- Pending/missing/stale meanings are textual, not colour-only; empty values never expose `undefined`.
- Admin state selector has labels/instructions and keyboard operation; public cards retain h2, dl/dt/dd and link/message semantics.
- Any future same-section empty message/action needs a proper heading/link name and visible focus without adding a new public section.

## Empty, loading and error states

- True zero records preserves exact current blank-grid seed until a deliberate inline fallback is published.
- Loading failure uses last verified collection snapshot with admin stale banner; if none exists, a safe same-section runtime state is policy-controlled and clearly distinct from true zero.
- Malformed individual records use pending/incomplete treatment, omit unsafe links and surface exact missing fields to editors.

## Storage and versioning

- Store template state labels/styles and optional inline zero-state configuration at `pages/food-hygiene/states`.
- Do not persist simulator fixtures. Store source facts/history on nursery entities and last verified collection snapshot with entity revision IDs.
- Revision history records empty-state activation, archive/current policy and template changes separately from regulated facts.

## Planned implementation files (future only)

- `am-visual-builder/schema/pages/food-hygiene/states.php`
- `am-visual-builder/admin/pages/food-hygiene/HygieneStateLab.jsx`
- `am-visual-builder/admin/fixtures/foodHygieneStates.js`
- `am-visual-builder/runtime/pages/food-hygiene/RatingGridStateResolver.php`
- `am-visual-builder/runtime/pages/food-hygiene/InlineEmptyState.php`

## Acceptance checklist

- [ ] State lab covers zero, future pending, incomplete, generic/invalid URL, stale and load-failure cases using the real card.
- [ ] Current zero-record seed remains an empty grid with no invented section/message/action.
- [ ] Fixtures cannot save to records; future branch needs no new page component.
- [ ] No state publishes made-up rating, `undefined`, unsafe link or misleading colour.
- [ ] Mobile/desktop/zoom, keyboard, last-verified fallback, governance diff and rollback pass.
