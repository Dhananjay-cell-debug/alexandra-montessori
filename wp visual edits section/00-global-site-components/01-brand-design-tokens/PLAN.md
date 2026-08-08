# Global brand and design tokens plan

## Intent and feel

This control surface should feel like a small, calm brand studio: powerful enough to tune the whole site, but difficult to make visually inconsistent by accident. A client should edit named roles such as “Navigation green” or “Body text”, see every affected component highlighted, and understand that the change is global before saving.

## Current evidence

- `tailwind.config.js` defines `cream #f5f1ef`, `sand #e9f1e6`, `ink #36463d`, the sage scale, Poppins headings, Helvetica body copy, compact radii, and four named shadows.
- Current global components still contain direct `#a3bc9a` values in `Navbar.jsx` and `Footer.jsx`; Home also uses section CSS variables layered over Tailwind defaults.
- Responsive behaviour currently pivots mainly at 640px and 1024px, with additional `xl` refinements.
- The supplied brand lockup and the production screenshot establish the quiet pale-green, white and warm-neutral character. Controls must preserve that identity rather than expose unlabelled CSS knobs.

## Editable tokens and controls

- Colour roles: page cream, soft-section green, primary brand green, deep sage text, body ink, white-on-brand text, muted border and overlay colour. Use paired colour picker + validated hex field + swatch history.
- Typography roles: heading family, body family, base body size, H1–H4 scale, weight, line height and tracking. Font choices come from an approved, locally available allow-list; no arbitrary remote font URL.
- Shape roles: card radius, pill radius, input radius and image-frame radius.
- Elevation roles: navigation, card, soft and modal shadows, edited through restrained presets plus opacity/spread sliders.
- Rhythm roles: content width, wide content width, base spacing step and section-density preset. Per-page section controls may override within bounds.
- Motion roles: reveal duration, hover duration and reduced-motion fallback. Decorative animations can be globally disabled.
- “Where used” list and before/after preview for each token; reset one role or reset the full group without disturbing content.

## Selection, layers and dragging

Tokens are not draggable canvas layers. Selecting an on-canvas component may deep-link to the token role responsible for its colour/type/shadow. Direct drag remains owned by that component plan; token edits update all preview instances atomically. The token panel must never imply that moving one preview changes every component’s geometry.

## Desktop, tablet and mobile

Type size, content width and density may have Desktop/Tablet/Mobile values with explicit inheritance badges. Colour and font family remain global by default. A breakpoint preview must show exact viewport width and overflow warnings. Breakpoint definitions themselves are developer-protected because changing them would invalidate every section’s saved responsive values.

## Data ownership and bindings

Plan a dedicated WordPress global-design record, not Home’s `am_vb_home_design`. Semantic keys bind to CSS custom properties such as `--am-color-brand`, `--am-font-heading`, `--am-radius-card` and `--am-content-wide`. React components consume a defensive global resolver with the compiled Tailwind values as fallbacks. Each control shows the affected token key and components, while ordinary client language remains primary.

## Protected rules

- Enforce WCAG contrast warnings and block combinations that make essential text/actions unreadable.
- Keep logo artwork independent from colour tokens; never recolour an uploaded logo destructively.
- Clamp font sizes, content widths, radii and shadow values to tested ranges.
- Prevent empty font stacks, invalid colours and removal of visible focus indicators.
- Saving tokens cannot overwrite page copy, media, menu records or page-specific section overrides.

## Accessibility and failure states

Colour controls expose contrast ratios for normal and large text. Font previews include long headings, links, form labels and error copy. If a font fails to load, the preview and public site use the approved fallback stack. Invalid values stay unsaved with an inline reason; a partially missing record is merged with defaults. Reduced-motion preference always wins over decorative motion settings.

## Storage and versioning

Use a schema such as `am_vb_global_design` version 1 with immutable defaults, sanitisation, revision snapshots and a migration map from hard-coded current values. Store semantic values, not generated CSS. Save a full validated document with `updatedAt`, editor ID and revision ID; retain rollback points. A future schema version must migrate keys without resetting client choices.

## Planned implementation files (future only)

- `wordpress/mu-plugins/alexandra-visual-builder/includes/class-am-vb-global-design.php`
- `wordpress/mu-plugins/alexandra-visual-builder/assets/panels/global-tokens.js`
- `src/lib/globalVisual.js`
- `src/styles/global-tokens.css`
- `wordpress/mu-plugins/alexandra-visual-builder/tests/global-design-contract.php`

## Current public section → exact WP canvas parity

| Current public design evidence | WP canvas requirement |
| --- | --- |
| Cream `#f5f1ef`, sand `#e9f1e6`, ink `#36463d`, sage scale | Load these exact values as the untouched migration/default palette |
| Poppins headings and Helvetica body | Canvas and public iframe use the same real font stacks and fallbacks |
| Current card/soft/nav shadows and compact radii | Token previews render the actual component classes, not abstract samples only |
| 640px/1024px responsive pivots | Device previews evaluate the current breakpoints; editors cannot redefine them |
| Hard-coded `#a3bc9a` header/footer surfaces | Initial semantic brand token resolves to the identical visible colour |

## Acceptance checklist

- [ ] Every exposed token has a human name, current value, default and usage preview.
- [ ] A global colour/type change previews across header, content, footer, forms and overlays.
- [ ] Contrast, size and breakpoint guardrails prevent unusable output.
- [ ] Device inheritance is visible and independently resettable.
- [ ] Missing/corrupt saved data falls back to today’s approved design.
- [ ] Undo, revision history and one-token reset do not affect content records.
