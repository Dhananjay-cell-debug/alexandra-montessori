# Hounslow — SEO and structured data

## Intent and feel

Make Hounslow discoverable and trustworthy while deriving regulated facts from its own record. The panel is guided metadata, not a fake public section.

## Current public section → exact WP canvas parity

- `Seo` currently resolves title `Hounslow Nursery`, description `Montessori-inspired practice with a caring, family feel at the heart of Hounslow.`, path `/nurseries/hounslow`, and image `/assets/organisation/classroom-main.webp`.
- The adjacent JSON-LD is `ChildCare` with Hounslow name, welcome description, image, `0208 001 5165`, Ved Court address, postcode `TW3 1LS`, and hard-coded `Mo-Fr 08:00-18:00`.
- WP exposes this as a non-canvas `Search & structured data` region; public section order is unchanged.

## Editable elements and controls

- SEO title/description/social image plus local-business preview; schema preview is read-only-by-default and record-derived.
- Controls validate title length, absolute image URL, phone, postcode, canonical, and opening-hours conversion; explicit SEO copy overrides carry a badge.

## Layers and dragging

- Functional layers: `SEO`, `Social preview`, `ChildCare schema`; none can enter visible-section drag order.
- Social-image focal point is draggable; schema fields are selected through linked Hounslow record chips.

## Responsive behaviour

- Compact/mobile search and social previews share stored values; warnings show truncation without device-specific metadata forks.

## Record/template binding and overrides

- Bind canonical and schema facts to stable Hounslow UUID/slug; canonical slug is immutable here.
- SEO description may override `short`; schema description inherits `welcome`. Clearing an override resumes record inheritance.

## Protected rules

- Escape JSON-LD through the existing safe serializer; forbid raw JSON/scripts and accidental `noindex`.
- Address, phone, postcode, and hours require valid Hounslow values; do not inherit another branch’s facts.

## Accessibility

- Admin previews and linked-field controls are keyboard-labelled; metadata creates no duplicate visible content.

## Empty and error states

- Missing Hounslow record follows the current redirect to `/nurseries`, never emits partial schema.
- Invalid facts block schema publication while retaining last published SEO/schema and the draft.

## Storage and versioning

- Store sparse overrides at `nurseries/{hounslowUuid}/seo`; schema provenance points to the entity revision used at publish time.
- Restore metadata independently and log derived-fact changes separately from authored overrides.

## Planned implementation files (future only)

- `am-visual-builder/schema/entities/nursery/seo.php`
- `am-visual-builder/admin/pages/nursery/SeoSchemaPanel.jsx`
- `am-visual-builder/runtime/nursery/ChildCareSchema.php`
- `am-visual-builder/content/nurseries/hounslow/seo.json`

## Acceptance checklist

- [ ] Default metadata/schema reproduce the current Hounslow output.
- [ ] No Heston/Hammersmith fact can leak into schema.
- [ ] Canonical, validation, safe serialization, noindex protection, preview, and restore pass.
- [ ] Missing record emits neither page nor stale schema.

