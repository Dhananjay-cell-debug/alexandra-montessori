# Contact Hammersmith Missing Record Behavior Plan

## Implementation-grade parity contract

### Intent and feel

A missing Hammersmith record must fail quietly and predictably, preserving the parent's route intent without exposing a half-bound page or editor error.

### Exact current React/public evidence

ContactLocation.jsx runs `locationBySlug(slug)` before rendering; when no record matches it returns `<Navigate to="/contact" replace />`. Therefore an unresolved `/contact/hammersmith` currently replaces browser history and lands on the general Contact page with no intermediate public missing-state markup.

### Current public section -> exact WP canvas parity

| Current public truth | Required WordPress canvas result |
| --- | --- |
| Trigger | Missing/unresolvable stable slug `hammersmith` produces no partial SEO, header, form or Hammersmith card output. |
| Navigation | Perform a replace redirect to registered `/contact`, matching current React behavior and avoiding a back-button redirect loop. |
| Editor simulation | State preview shows cause/dependencies in editor chrome but does not unpublish the real record or pretend a public fallback currently exists. |

### Editable elements and controls

Authorized route policy (current replace redirect vs future branded missing page), editor-only explanatory copy, dependency report display and optional migration redirect target selected from registered pages. There is no freehand redirect URL in the visual canvas.

### Layers, reorder, and dragging

This is route-resolution behavior, not a draggable section. A branded future fallback, if intentionally enabled, uses its own fixed semantic layout; redirect logic cannot be reordered among page visual sections.

### Responsive behavior

Redirect is viewport-independent. Editor dependency tables and any future fallback must remain usable at mobile/zoom, but no breakpoint may choose a different route outcome.

### Data ownership and bindings

Slug/status comes from the Hammersmith Nursery registry; `/contact` from page registry; route policy from template configuration. Nursery facts and redirects are never duplicated into page visual layers.

### Protected behavior

Resolve before rendering, use replace semantics, prevent loops/external open redirects, require capability and dependency acknowledgement for unpublish/slug change, and preserve audit/history mapping.

### Accessibility

Redirect target has a valid page title/H1 after navigation. Any editor/future fallback announces status, provides a keyboard link and avoids repeated focus changes; no inaccessible timed redirect.

### Relevant state previews

Preview valid Hammersmith, missing record, draft-only record, unpublished record, renamed slug with redirect, missing target page and redirect-loop protection without changing live data.

### Storage and versioning

Version route policy and historical slug mappings in the route registry, separate from `contact_location` visual design. Record actor/time/reason and dependent surfaces; rollback restores mappings atomically.

### Planned implementation files (future only)

These paths describe later implementation ownership; they are **not created by this planning phase**.

- `includes/routing/contact-location.php` -- branch resolution and replace-redirect policy.
- `includes/definitions/templates/contact-location.php` -- editor state/dependency descriptor.
- `assets/editor/templates/contact-location/hammersmith-missing-state.js` -- safe simulated fixtures.
- `tests/integration/contact-location/hammersmith-redirect.spec.js` -- no-partial-render, replace and loop regression.

### Acceptance checklist

- [ ] Unknown `/contact/hammersmith` context matches the current replace redirect to `/contact`.
- [ ] No bound fragment renders before resolution and no history/back loop is introduced.
- [ ] Unpublish/slug changes are permissioned, dependency-aware, versioned and recoverable.


## Exact current behavior

Unresolved Nursery context redirects to `/contact`.

## WordPress planning

State preview simulates missing/unpublished Hammersmith without modifying the record. Unpublish workflow inventories nursery directory, fees, hygiene, footer, navigation, availability, and contact dependencies, then requires redirect/missing-state selection.

## Acceptance

No partial bound output is public, redirect has no loop, historical links remain handled, and the decision is audited.
