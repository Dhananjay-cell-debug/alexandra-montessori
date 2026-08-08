# Home nursery feature links plan

## Intent and feel

This soft-green band should remain a cheerful three-way doorway into the site: Our Nurseries, Careers and News & Events, each with a concise heading, a distinctive hexagonal photograph, a direct text link and a small supporting line icon. The editor should treat each as a complete card while allowing precise media/copy control.

## Current evidence

- `FeatureLinks` renders `data-am-vb-region="home-feature-links"` on `sand #e9f1e6` with 56px desktop/tablet and 48px mobile vertical padding.
- Current items are Our Nurseries → `/nurseries`, Careers → `/careers`, News & Events → `/events`.
- Photos default to classroom-main, portrait-girl (top-biased) and painting-close; the frame is a 1.1547:1 hexagon, 224px from `sm` and 208px mobile.
- Default icons are Trees, GraduationCap and CalendarHeart; each can be replaced with media.
- Grid is one column below 640px and repeats the actual item count (capped visually at four columns) from 640px.
- Existing extras are appended through `collections.features`, sanitized to 12, with placeholder heading/link/photo/icon values.

## Editable elements and controls

- Section: visibility, background colour/token, vertical padding, grid gap, content width and hexagon media size per device.
- Per card: heading, CTA label, internal route picker/validated external target, link description, photo attachment, meaningful alt, focal x/y, scale/offset per device and icon choice/upload/alt.
- Card actions: add from the existing feature-card pattern, duplicate, reorder, hide, archive/delete extra, and restore core default.
- Approved icon library plus custom logo/image; show how fallback icon behaves when no upload exists.
- Link/card preview checks that clicking the image and text resolves to the same target.
- Live grid preview reports awkward counts (for example five on tablet) and offers tested layout presets without altering current default.

## Selection, layers and dragging

Each feature is a grouped flow card with sublayers heading → photo link → CTA → icon. Card dragging reorders cards in the collection; keyboard moves are equivalent. Media drag has two modes: Move frame (bounded scale/offset) and Adjust crop (object position inside hexagon). Heading/CTA/icon can use existing bounded per-device optical offsets, but the semantic card stack cannot be reordered or overlapped.

## Desktop, tablet and mobile

Initial parity is three equal columns at ≥640px and one column below. Section/device values inherit visibly. Long headings/links wrap within a card and maintain aligned rhythm where possible. Test 320px, 768px, 1024px and 1440px plus 1–4 and future safe item counts. Hover photo zoom must have a reduced-motion equivalent and never escape the hexagon clip.

## Data ownership and bindings

The Home page owns feature presentation and ordered card references. Core defaults come from `homeBlocks`; visual overrides live under stable keys. A future normalized model should store stable card IDs for all core/extras instead of relying on `feature-1` positions. Destinations bind to published page IDs/routes. Media library owns attachments.

## Protected rules

- Image and CTA for one card must share one resolved destination.
- Core public cards cannot be accidentally deleted; they can be hidden/archived with confirmation and restored.
- Clamp collection count, media size, offsets and content width; prevent hexagon/copy overflow.
- Block unsafe URLs and blank visible labels/headings for published cards.
- Preserve heading hierarchy and real link semantics; do not make the entire Reveal wrapper a nested link.

## Accessibility and failure states

Photo alt describes its visual content; heading/CTA supply link purpose. Custom icon may be decorative when redundant and should not repeat noisy alt. Focus styling must be visible on both image and text links. Missing photo renders the existing deliberate media fallback inside the hexagon; missing custom icon uses the approved default icon. Broken routes block publication or retain the last valid public target. Zero visible cards hides the section publicly and shows a builder empty state.

## Storage and versioning

Current values fit Home design v3; plan a stable-ID collection migration for a later schema version while reading old keys. Store section design, ordered IDs, element overrides and attachment references with revision metadata. Reordering must move the whole card record, not swap numeric element content. Default/override provenance is retained per field.

## Planned implementation files (future only)

- `src/components/home/HomeFeatureLinks.jsx`
- `src/pages/Home.jsx`
- `src/lib/homeVisual.js`
- `wordpress/mu-plugins/alexandra-visual-builder/assets/panels/home-feature-links.js`
- `wordpress/mu-plugins/alexandra-visual-builder/tests/home-feature-links.php`

## Current public section → exact WP canvas parity

| Current public card/section | WP canvas requirement |
| --- | --- |
| Soft-green band with three equal cards | Same background, 3-column/1-column grid and vertical rhythm |
| Our Nurseries / View nurseries / classroom hexagon / Trees | Exact initial copy, route, asset, crop and icon |
| Careers / View careers / portrait-girl top crop / GraduationCap | Exact initial copy, route, subject-safe crop and icon |
| News & Events / View events / painting-close / CalendarHeart | Exact initial copy, route, asset and icon |
| Heading → hexagon → text link → icon | Same DOM/visual layer order and hover behaviour |

## Acceptance checklist

- [ ] Three untouched cards match public desktop/tablet/mobile pixel structure.
- [ ] Each card’s heading/photo/link/icon edits remain grouped under a stable identity.
- [ ] Crop and frame movement are distinct, bounded and per-device.
- [ ] Add/reorder/hide/restore never cross-wire labels, media or routes.
- [ ] Missing media/icon and broken-link states remain composed and accessible.
- [ ] Core defaults survive missing WordPress design data.

