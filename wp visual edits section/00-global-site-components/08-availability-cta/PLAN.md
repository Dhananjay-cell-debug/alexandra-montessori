# Global Check availability CTA plan

## Intent and feel

“Check availability” is the site’s persistent high-intent action. It should remain conspicuous without feeling loud: a white rounded pill in the desktop header and a clear navigation row on mobile. Editing must protect the conversion path and expose its global reach.

## Current evidence

- Desktop renders a white uppercase pill from `lg` upward, after primary navigation and before the Parent Info button.
- It points to `/check-availability`; active state adds a pale-green fill, ring and stronger shadow.
- Mobile renders the same destination as a full-width menu row.
- The label already binds to `header-availability` through `homeText`, but its route is still hard-coded in both placements.

## Editable elements and controls

- Label, internal destination picker, optional tracking name and accessible-label override.
- Desktop presentation: pill fill/text token, padding, radius, shadow, case and tracking within approved presets.
- Mobile presentation: row label and emphasis preset; it references the same label/target.
- Visibility by surface only with an explicit warning; at least one global navigation placement must remain visible.
- Preview active, hover, keyboard-focus, loading-route and long-label states.
- Optional icon may be selected from the approved library, default none; icon-only presentation is prohibited.

## Selection, layers and dragging

On canvas, select the whole CTA or its text. The desktop CTA remains anchored between primary nav and Parent Info; it can be reordered only through named slots, not arbitrary dragging. Mobile position remains after primary/nursery links and before View more for current parity. Text can be edited inline; free x/y movement is disabled.

## Desktop, tablet and mobile

Desktop fit validation covers 1024/1280/1440 widths and checks collision with nav/menu controls. Mobile inherits label/target but has independent row styling. At 200% zoom the CTA must not disappear merely to preserve a single row; the header may transition to compact navigation according to its breakpoint policy.

## Data ownership and bindings

One global `availabilityCta` record owns label, stable WordPress page reference, presentation variants and analytics token. Both `NavLink` renderers consume it. The availability page owns its content and SEO. A target rename/slug change resolves through page ID.

## Protected rules

- Disallow deletion of both desktop and mobile placements.
- Default destination is the published `/check-availability` page; publishing a different target requires a clear impact confirmation.
- Block unsafe protocols, blank labels and unreadable contrast.
- Preserve real link semantics, active-route state and minimum target size.
- Tracking metadata cannot contain executable markup or personally identifying values.

## Accessibility and failure states

The visible label should normally be the accessible name. Focus ring must remain visible against white and green states. Long translated labels wrap or trigger a fit warning rather than clipping. If the bound page is unavailable, block save and keep the previous published target. If global data is missing, use “Check availability” and `/check-availability`.

## Storage and versioning

Store the CTA as one typed global link record with `targetPageId`, resolved path, label, surfaces and style preset. Revision history logs destination changes prominently. Migrate the current label override from `am_vb_home_design.elements.header-availability` into the global record without losing it, then leave a compatibility reader during transition.

## Planned implementation files (future only)

- `src/components/navigation/AvailabilityCta.jsx`
- `src/components/Navbar.jsx`
- `src/lib/globalVisual.js`
- `wordpress/mu-plugins/alexandra-visual-builder/assets/panels/availability-cta.js`
- `wordpress/mu-plugins/alexandra-visual-builder/tests/availability-cta.php`

## Current public section → exact WP canvas parity

| Public behaviour | WP canvas requirement |
| --- | --- |
| White uppercase desktop pill | Same slot, rounded shape, typography and shadow |
| `/check-availability` NavLink | Initial target resolves to the same route and active state |
| Mobile menu row | Same shared label/target and current list position |
| Active pale fill/ring | Active-route preview reproduces public styling |

## Acceptance checklist

- [ ] Desktop and mobile use one label and one destination record.
- [ ] Current default visuals and active states match in canvas and public output.
- [ ] CTA remains reachable at all supported widths and zoom levels.
- [ ] Destination changes are validated, revisioned and clearly flagged as global.
- [ ] No editor state can remove every CTA placement.
- [ ] Missing data safely restores the current conversion path.

