# Hounslow — accreditations section

## Intent and feel

Keep the current four trust badges restrained and accurate, with clear separation between shared label copy and Hounslow’s regulated evidence.

## Current public section → exact WP canvas parity

- Tenth public section is a white bordered rounded container holding four cream cards: Official Ofsted Reports, Montessori Approach, EYFS Curriculum, Food Hygiene Rated.
- Every card currently uses the same ShieldCheck icon and shared `accreditations` array; it does not display Hounslow’s `Good` rating, report URL, or hygiene score 4 in this section.
- WP default must not silently insert those undisplayed facts; evidence can appear in editor verification only until a deliberate design/content edit is published.

## Editable elements and controls

- Shared item label, order, icon, card tokens, and section spacing; Hounslow override/hide controls with source badge.
- Evidence drawer reads Hounslow Ofsted `Good`/official report and food hygiene `4` dated 6 October 2025, but public-value/link additions require explicit authored fields and verification.

## Layers and dragging

- Exact tree: `Accreditations section` → `Trust container` → four `Accreditation card` → `Icon`, `Label`.
- Cards reorder by pointer/keyboard inside Hounslow’s resolved collection; icon-before-label structure remains fixed.

## Responsive behaviour

- Preserve current one-column compact horizontal cards, then two at `sm`, four at `lg`, with the existing mobile/desktop internal alignment change.
- Long labels wrap without clipping at zoom.

## Record/template binding and overrides

- Current four labels inherit shared accreditation template; Hounslow’s regulatory facts belong to its record and are never shared globally.
- Sparse Hounslow display overrides reference evidence revision and can revert without altering the source rating.

## Protected rules

- No unverified rating/award claim; evidence URL/domain/date required when publishing a specific regulated fact.
- Shield/icon does not imply a certificate beyond the exact label; raw HTML and misleading logo upload are blocked.

## Accessibility

- Trust labels are real text; icon is decorative. Grid and card reading order equal visual order.
- Contrast, keyboard reorder, and 200% zoom pass.

## Empty and error states

- Missing evidence marks the admin item stale but preserves current generic public label; broken override falls back to shared label.
- Zero cards requires intentional section disable, not a blank framed container.

## Storage and versioning

- Store sparse display overrides at `nurseries/{hounslowUuid}/sections/accreditations`; record evidence maintains source URL, checked date, checker, and immutable history.
- Revision diff separates label/layout edits from regulatory evidence updates.

## Planned implementation files (future only)

- `am-visual-builder/schema/entities/nursery/sections/accreditations.php`
- `am-visual-builder/admin/pages/nursery/sections/AccreditationsEditor.jsx`
- `am-visual-builder/runtime/nursery/AccreditationsSection.php`
- `am-visual-builder/content/nurseries/hounslow/accreditations.json`

## Acceptance checklist

- [ ] Current four generic badges reproduce with no invented public rating.
- [ ] Hounslow evidence is visible to editors and cannot leak to other branches.
- [ ] Verification, responsive layout, keyboard order, stale/corrupt states, reset, diff, and restore pass.

