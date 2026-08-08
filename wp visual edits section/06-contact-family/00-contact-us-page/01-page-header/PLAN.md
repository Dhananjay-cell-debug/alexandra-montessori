# Contact Page Header Plan

## Implementation-grade parity contract

### Intent and feel

The opening should feel quiet and unmistakable, leaving visual space before the practical branch directory and form.

### Exact current React/public evidence

`Contact.jsx` calls `<PageHeader title="Contact us" />`. The current `PageHeader.jsx` renders only a white section, centred Playfair-style H1, `pt-12 sm:pt-16`, and `pb-6`; because no `intro` is supplied there is no paragraph. It currently has no breadcrumb, eyebrow, media, or action.

### Current public section -> exact WP canvas parity

| Current public truth | Required WordPress canvas result |
| --- | --- |
| Visible content | Render the single text `Contact us` only; optional controls must start disabled so the seed is not embellished. |
| Geometry | White full-width section, contained centred text, current top/bottom spacing, and current responsive 4xl/5xl H1 scale. |
| Motion/DOM | Use the same reveal wrapper and one H1 in the same DOM position immediately after SEO output. |

### Editable elements and controls

H1 wording, surface colour, content width, text alignment, vertical padding, heading typography preset, and optional intro/eyebrow/media/decoration toggles. Every optional element is off in the exact-current preset.

### Layers, reorder, and dragging

Default layer tree is `header surface > content container > H1`. H1 stays in flow. Only an explicitly enabled decorative/media layer may be dragged within clipped safe bounds; drag order cannot move the header below page body sections.

### Responsive behavior

Desktop, tablet, and mobile preview H1 size, max width, alignment, and padding. Seed preserves `text-4xl` below `sm` and `text-5xl` from `sm`, with no fixed-height clipping beneath the global header.

### Data ownership and bindings

H1 and instance styling belong to the Contact page header block; fonts/colour tokens belong to global brand design tokens. Route and global site header offset are registry/shell owned.

### Protected behavior

Exactly one H1, semantic order, skip-navigation target, route, sanitization, and global nav clearance. Optional breadcrumb destinations must use registered routes.

### Accessibility

Maintain H1 semantics at every visual preset, sufficient sage-on-white contrast, readable line length, reduced-motion reveal, and no decorative media exposed to assistive technology.

### Relevant state previews

Preview exact current title-only seed, long H1, optional intro, optional background image with contrast overlay, reduced motion, and 320/390px widths.

### Storage and versioning

Store block content/style under versioned Contact section key `contact.page_header`; device overrides are sparse deltas from desktop. Draft, published, revision diff, and one-click rollback are required.

### Planned implementation files (future only)

These paths describe later implementation ownership; they are **not created by this planning phase**.

- `includes/definitions/pages/contact.php` — header block schema and current title-only defaults.
- `includes/renderers/sections/page-header.php` — semantic shared header renderer with Contact instance settings.
- `assets/editor/pages/contact/page-header.js` — inline text and responsive controls.
- `tests/parity/contact/page-header.spec.js` — title-only DOM and viewport screenshot fixtures.

### Acceptance checklist

- [ ] A new seed contains no eyebrow, breadcrumb, intro, media, or CTA.
- [ ] At matching viewports canvas and `/contact` reproduce the same H1, spacing, and alignment.
- [ ] Optional editing cannot create a second H1, inaccessible contrast, or mobile overlap.


## Intent and feeling

Give the page a calm, unmistakable entry point before presenting phone numbers and the form. It should feel welcoming rather than administrative.

## Current evidence

The page currently renders the shared `PageHeader` with title `Contact us`.

## Editable elements

- eyebrow, breadcrumb label, H1 text, optional introduction, optional background media, decorative overlay, and optional supporting action;
- section background/pattern, content width, vertical spacing, text alignment, heading style, and breadcrumb visibility;
- drag reorder within the header's approved layer tree; layered positioning is available only when the editor deliberately converts the header to the approved layered-header preset.

## Responsive behavior

Desktop/tablet/mobile inherit by default. Allow separate heading size, line width, alignment, padding, media focal point, and decorative visibility. H1 semantic level remains H1 even if its visual preset changes.

## Guardrails

Exactly one visible H1 is expected. Hiding it creates a publish warning. Breadcrumb links remain generated from the route registry; the label can change, but the destination cannot become an arbitrary broken path.

## Acceptance

- Text can be edited directly on the real canvas.
- Header styles can change without altering global headers on other pages.
- Mobile at 390px has no clipping or overlap.
- Breadcrumb and skip-link behavior remain keyboard accessible.
