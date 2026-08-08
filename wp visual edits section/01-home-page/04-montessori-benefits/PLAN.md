# Home Montessori benefits plan

## Intent and feel

This white band should act as a breath between richer sections: three small line icons and three succinct promises, centred and calm. Editing should encourage short, readable statements and consistent icon language rather than turning the row into generic cards.

## Current evidence

- `Benefits` renders `data-am-vb-region="home-benefits"` on white with 64px desktop/tablet and 48px mobile padding.
- Current statements are “Rich experiences in a secure and nurturing environment”, “Montessori child-centred curriculum”, and “Highly skilled team who are devoted and passionate”.
- Default icons are HandHeart, Sprout and Users, each displayed in a 48px wrapper with a 36px glyph.
- Text is 18px, medium weight, max width 15rem, deep sage, centred.
- Grid is one column below 640px and three columns above; extra benefits can be appended through a sanitized collection.

## Editable elements and controls

- Section visibility, background token, vertical padding, content width, grid gap and statement text size per device.
- Per benefit: multi-line statement, approved icon-library selection, optional replacement icon upload/alt, icon scale/offset and text optical offset/scale.
- Readability meter flags overlong copy, more than a recommended number of lines and inconsistent punctuation without forcibly rewriting client words.
- Add, duplicate, reorder, hide/archive extra items and restore the three core defaults.
- Global icon-colour and icon-size controls within tested limits; per-item colour variation is not part of current parity.
- Preview 1–4+ items and choose only tested grid behaviour, preserving the current three-item default.

## Selection, layers and dragging

Each benefit is a two-layer group: icon then statement. Dragging the group reorders it; icon/text can receive small bounded optical offsets but cannot swap order or overlap. Custom icon media uses contain fit. Free resizing of text boxes is unnecessary because the responsive grid owns width; scale control must respect readable minimums.

## Desktop, tablet and mobile

Current parity is three equal columns from 640px and one column below. Text size defaults to 18px on all devices, with independent bounded overrides. Mobile maintains generous vertical gap and does not squeeze several items across. Test 320px, long statements, custom icons with unusual aspect ratios and up to the collection limit.

## Data ownership and bindings

The Home page owns this ordered collection and section design. Core defaults come from the local `benefits` array; visual overrides live in the Home model. Future normalization gives every core/extra item a stable ID rather than deriving icon key from text key. Uploaded icons reference WordPress attachments.

## Protected rules

- Keep at least one meaningful visible benefit when the section is published; hiding the whole section requires confirmation.
- Clamp type/icon sizes, gaps, content width and optical movement.
- Disallow raw HTML/scripts and empty visible statements.
- Keep icons visually secondary and use the approved brand colour unless a future design variant is reviewed.
- Preserve semantic list/group structure and source order in DOM.

## Accessibility and failure states

Statements carry meaning without the icon. Built-in icons are decorative; custom icon alt is empty when redundant or meaningful only when it adds information. Long/unbroken text wraps safely. Missing custom icon falls back to the selected approved icon. An invalid extra item remains repairable in editor but is omitted publicly. Zero items produces an explicit empty state rather than a blank padded band.

## Storage and versioning

Persist section design and stable item records in the Home design schema, retaining current v3 key compatibility. Revisions move whole item records on reorder. Sanitization caps 12 extra items and validated icon names; future schema changes must preserve text/icon pairings and default provenance.

## Planned implementation files (future only)

- `src/components/home/HomeBenefits.jsx`
- `src/pages/Home.jsx`
- `src/lib/homeVisual.js`
- `wordpress/mu-plugins/alexandra-visual-builder/assets/panels/home-benefits.js`
- `wordpress/mu-plugins/alexandra-visual-builder/tests/home-benefits.php`

## Current public section → exact WP canvas parity

| Current public item/section | WP canvas requirement |
| --- | --- |
| White band with 64/64/48px padding | Same background and exact device rhythm |
| HandHeart + secure/nurturing statement | Same initial icon, wording and centred layout |
| Sprout + child-centred curriculum | Same initial icon, wording and order |
| Users + skilled/devoted team | Same initial icon, wording and order |
| 18px copy under 48px icon wrapper | Same initial sizing, max width and spacing |

## Acceptance checklist

- [ ] Untouched row matches current public layout/copy/icons at all devices.
- [ ] Text and icon remain paired through reorder/duplicate/archive.
- [ ] Long copy guidance and wrapping prevent visual breakage without hidden truncation.
- [ ] Icon fallback and semantics remain accessible.
- [ ] Bounds prevent illegible size or destructive overlap.
- [ ] Missing/empty data never leaves unexplained blank space.
