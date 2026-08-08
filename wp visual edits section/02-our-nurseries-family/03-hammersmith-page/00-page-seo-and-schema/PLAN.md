# Hammersmith — SEO and structured data

## Intent and feel

Give the Ravenscourt-area Hammersmith nursery accurate local identity with transparent, branch-owned structured facts.

## Current public section → exact WP canvas parity

- `Seo` resolves title `Hammersmith Nursery`, description `Montessori-inspired care moments from Ravenscourt Park, with bright, natural-light studios.`, canonical `/nurseries/hammersmith`, and `/assets/organisation/classroom-calm.webp`.
- JSON-LD is `ChildCare` using Hammersmith welcome text, `0204 618 3477`, `Dalling Road, London W6 0EU`, postcode `W6 0EU`, and hard-coded `Mo-Fr 08:00-18:00`.
- Metadata stays in a non-visible functional panel; no extra canvas section is invented.

## Editable elements and controls

- SEO/social title, description, image/crop, local-result preview; linked schema preview and validators for image, phone, address, postcode, hours.
- Source badges distinguish `Hammersmith` branch name from `Ravenscourt` area and allow sparse SEO overrides/reset.

## Layers and dragging

- Locked `SEO`, `Social preview`, `ChildCare schema` layers; only image focal point is draggable and nothing enters public section order.

## Responsive behaviour

- One metadata set previews desktop/mobile truncation; narrow inspector keeps field errors visible.

## Record/template binding and overrides

- Bind canonical/schema only to stable Hammersmith UUID/slug; description inherits Hammersmith `short`/`welcome` according to output type.
- Clearing overrides resumes Hammersmith data, never Hounslow/Heston.

## Protected rules

- Safe JSON serializer, immutable canonical, validated facts/image, guarded noindex, and no false hygiene-rating claim while listing is unavailable.

## Accessibility

- Keyboard-labelled preview/field links; no duplicate visible output.

## Empty and error states

- Missing Hammersmith record follows current redirect and emits no schema; invalid schema keeps last valid publication plus draft diagnostic.

## Storage and versioning

- Sparse overrides at `nurseries/{hammersmithUuid}/seo`; publish records source entity revision and derived/authored provenance.
- Independent restore and audit.

## Planned implementation files (future only)

- `am-visual-builder/schema/entities/nursery/seo.php`
- `am-visual-builder/admin/pages/nursery/SeoSchemaPanel.jsx`
- `am-visual-builder/runtime/nursery/ChildCareSchema.php`
- `am-visual-builder/content/nurseries/hammersmith/seo.json`

## Acceptance checklist

- [ ] Current metadata/schema reproduce with correct branch/area facts.
- [ ] Invalid/missing data never emits mixed-branch schema.
- [ ] Canonical, noindex, safe serialization, preview, errors, audit, and restore pass.

