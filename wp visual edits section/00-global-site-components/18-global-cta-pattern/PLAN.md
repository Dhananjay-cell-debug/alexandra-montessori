# Existing global CTA and link pattern plan

## Intent and feel

This is a control contract for actions already present across the site, not a new public section. It keeps primary pills, quiet text links, card links and external actions consistent while letting clients edit meaningful labels/destinations directly in their owning section.

## Current evidence

- Existing examples include the header availability pill, Home feature links, Home testimonial archive CTA, footer links, form submit buttons and external report/contact links.
- Current code often stores display copy and href in one visual element (`homeText`/`homeHref`) but several routes remain hard-coded.
- Styles already use recurring rounded pills, underlined text links, green/white surfaces, hover transitions and visible active states.
- `editor.js` has a `CORE_LINK_SUGGESTIONS` list covering all current internal routes, nursery/contact children and Privacy.

## Editable elements and controls

- Typed link fields: visible label, internal page/record picker or validated external URL, accessible description, target behaviour and optional tracking key.
- Style roles: Primary pill, Secondary outline, Text link, Card link and External official link. Each owning section chooses only roles proven in its current public design.
- Icon selection/position from approved library when the existing section supports it; icon-only calls to action are not permitted for primary conversion paths.
- Interaction-state preview: default, hover, focus, active, disabled/loading and broken destination.
- “Where this action appears” and source/override status; editing a global action such as availability warns all consumers.
- Link suggestions dynamically derive from published routes instead of becoming a stale manual list.

## Selection, layers and dragging

CTA selection opens the typed link panel while retaining the owning section’s layout rules. The pattern does not grant global free movement: a Home feature text link stays under its image, the header CTA stays in its slot, and footer links stay in lists. Whole-button frame movement/scale is available only where the owning section explicitly plans it.

## Desktop, tablet and mobile

Every style role defines minimum 44px target geometry, visible focus and safe wrapping. Device controls may change size/density only inside the owning section. A label edit runs fit tests across all consumers before global save. Hover-only feedback is supplemented by focus/active states for touch and keyboard users.

## Data ownership and bindings

The CTA pattern owns typed schemas and style tokens, not actual business copy. Actual records live with their section or global component. Internal destinations store stable entity/page IDs plus resolved routes; external URLs store a normalized safe URL. Analytics keys remain non-personal, allow-listed metadata.

## Protected rules

- Block unsafe protocols, executable markup, empty labels and unpublished destinations.
- External new-tab links automatically receive `noopener noreferrer` and a meaningful accessible cue.
- Button-versus-link semantics follow outcome: navigation is a link; in-place action is a button.
- Focus styling and contrast cannot be disabled.
- Style changes cannot silently alter target or tracking behaviour.

## Accessibility and failure states

Visible wording should usually be the accessible name; optional descriptions add context rather than contradict it. Icons are decorative when text names the action. Long labels wrap or produce fit warnings. Broken internal references stay repairable in the editor and retain the last valid public target until saved. If pattern tokens fail, render current component classes as approved fallbacks.

## Storage and versioning

Define a reusable typed link schema and style-role version in the global contract. Section records reference `styleRole` and store their own content/target. Route migrations update resolved paths by stable ID. Revision diffs separate label, destination and visual-role changes so conversion-impacting edits are reviewable.

## Planned implementation files (future only)

- `src/components/CtaLink.jsx`
- `src/lib/linkModel.js`
- `src/styles/cta-patterns.css`
- `wordpress/mu-plugins/alexandra-visual-builder/assets/controls/link-picker.js`
- `wordpress/mu-plugins/alexandra-visual-builder/tests/link-contract.php`

## Current public section → exact WP canvas parity

| Existing public action | WP canvas requirement |
| --- | --- |
| Header Check availability pill | Preserve its current white-pill role and owning header slot |
| Home feature text links | Preserve under-image placement and current understated link styling |
| Home testimonial archive CTA | Preserve translucent/outlined light-on-image treatment |
| Footer navigation/contact links | Preserve list/contact semantics, not turn them into pills |
| Official external report cards | Preserve external-link security and official-card role |

## Acceptance checklist

- [ ] No new public CTA section is introduced by this system plan.
- [ ] Every current action retains its owning section’s exact default style/placement.
- [ ] Typed page/link picker replaces unsafe free-form routing where appropriate.
- [ ] Link/button semantics, focus and external security are automatic.
- [ ] Global edits run fit/impact checks across every consumer.
- [ ] Broken references keep the last safe public state and clear repair guidance.

