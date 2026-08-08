# Heston — SEO and structured data

## Intent and feel

Give Heston an accurate local search identity while making every structured fact traceable to the Heston record, especially its pending Ofsted status.

## Current public section → exact WP canvas parity

- `Seo` resolves `Heston Nursery`, description `A warm, welcoming home for early learners, with spacious studios and a secure garden.`, canonical `/nurseries/heston`, and `/assets/organisation/friends-two.webp`.
- JSON-LD is `ChildCare` using Heston welcome copy, `0203 627 6707`, `36 Springwell Road, Hounslow TW5 9EJ`, postcode `TW5 9EJ`, and hard-coded `Mo-Fr 08:00-18:00`.
- This remains a functional metadata panel outside visible-section ordering.

## Editable elements and controls

- SEO/social fields, local-result preview, media picker/crop, and record-derived schema preview with phone/address/postcode/hours validation.
- Explicit copy/image overrides show provenance and reset; schema source fields link to the Heston entity editor.

## Layers and dragging

- Locked functional layers: `SEO`, `Social preview`, `ChildCare schema`; only social-image crop is draggable.
- Metadata never becomes a public canvas block.

## Responsive behaviour

- Mobile/desktop previews demonstrate truncation using one metadata set; inspector retains validation on narrow screens.

## Record/template binding and overrides

- Canonical/JSON-LD bind only to stable Heston UUID/slug; SEO description may override Heston `short`, schema description inherits Heston `welcome`.
- Empty override resumes inheritance; it never falls back to Hounslow/Hammersmith record data.

## Protected rules

- Safe JSON serialization, valid absolute schema image, escaped text, immutable canonical slug, and guarded `noindex`.
- Pending Ofsted evidence must not be presented as a positive rating in metadata.

## Accessibility

- Keyboard-labelled preview/field links; no duplicate visible content or focus traps.

## Empty and error states

- Missing Heston record follows current `/nurseries` redirect and emits no partial schema.
- Invalid schema retains last valid published version and the draft with field-specific diagnostics.

## Storage and versioning

- Store sparse overrides at `nurseries/{hestonUuid}/seo`; record schema provenance and entity revision at publish.
- Independent restore and field audit distinguish derived facts from authored metadata.

## Planned implementation files (future only)

- `am-visual-builder/schema/entities/nursery/seo.php`
- `am-visual-builder/admin/pages/nursery/SeoSchemaPanel.jsx`
- `am-visual-builder/runtime/nursery/ChildCareSchema.php`
- `am-visual-builder/content/nurseries/heston/seo.json`

## Acceptance checklist

- [ ] Current Heston metadata/schema reproduce exactly.
- [ ] Heston pending/branch facts never cross-bind or become misleading claims.
- [ ] Canonical, validation, safe serialization, preview, error retention, and restore pass.

