# Hounslow — features section

## Intent and feel

Keep Hounslow’s complete offer easy to scan as a balanced icon grid while making item editing safe, fast, and fact-aware.

## Current public section → exact WP canvas parity

- Fifth public section has eyebrow `What we offer` and Hounslow override heading `Everything your child needs to achieve, thrive and belong in Hounslow`.
- It renders 12 record features in this order: School readiness, Montessori Inspired, Practical life, Sensory spaces, Home-cooked meals, Qualified practitioners, Outdoor garden play, Buggy store, Secure entry, Extended hours, Onsite chef, Creative atelier.
- Grid defaults to 2 columns, 3 at `sm`, 4 at `lg`; each card contains icon badge then label.

## Editable elements and controls

- Heading/eyebrow, item label, approved Lucide icon, add/duplicate/archive, manual order, columns/gaps, card/icon tokens, reveal timing, and section visibility.
- Fact-sensitive amenities show a verification prompt and “last confirmed” note; changing a feature does not alter Heston/Hammersmith.

## Layers and dragging

- Exact tree: `Features section` → `Heading group`; `Feature grid` → 12 `Feature card` → `Icon`, `Label`.
- Drag/keyboard reorder operates within Hounslow only; internal icon-before-label order is locked. Adding creates a new stable item ID, not a reused array index.

## Responsive behaviour

- Preserve 2/3/4-column parity, readable labels, equal card rhythm, and safe wrapping at 200% zoom.
- Allowed per-breakpoint columns cannot produce undersized touch/select targets in editor or overflow in public output.

## Record/template binding and overrides

- Hounslow owns its feature collection and explicit `offerHeading`; presentation inherits the shared template.
- Shared starter features may be copied by reference at creation, but published Hounslow edits are entity-local and store provenance/revert options.

## Protected rules

- At least one valid item if visible; unique IDs; approved icons; plain labels; no unsupported claims without acknowledgement.
- “Home-cooked meals,” garden, chef, and other facilities must not be silently inherited from another branch.

## Accessibility

- Icons are decorative; labels carry meaning. DOM order matches the visible order; contrast and zoom checks apply.
- Keyboard add/reorder/remove flows announce item and position.

## Empty and error states

- Empty visible grid blocks publish or requires intentionally disabling the section.
- Missing icon falls back to a neutral approved icon with admin warning; missing label keeps the card draft-only.

## Storage and versioning

- Store ordered feature IDs/fields at `nurseries/{hounslowUuid}/sections/features`; layout overrides are sparse.
- Revision diff shows additions, removals, moves, label/icon edits, and verification notes.

## Planned implementation files (future only)

- `am-visual-builder/schema/entities/nursery/sections/features.php`
- `am-visual-builder/admin/pages/nursery/sections/FeaturesEditor.jsx`
- `am-visual-builder/runtime/nursery/FeaturesSection.php`
- `am-visual-builder/content/nurseries/hounslow/features.json`

## Acceptance checklist

- [ ] All 12 current Hounslow items, heading, icons, order, and 2/3/4 grid reproduce.
- [ ] Edits remain Hounslow-only; facility claims require confirmation.
- [ ] Pointer/keyboard reorder, add/remove, missing icon/label, zoom, reset, and revision diff pass.

