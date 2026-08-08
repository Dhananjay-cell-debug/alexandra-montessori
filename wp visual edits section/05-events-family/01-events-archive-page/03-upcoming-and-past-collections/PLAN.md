# Events archive — Upcoming and past collection section plan

## 1. Intent and feel
Let families scan dates and places effortlessly while giving the client precise control of the archive shell and card presentation. Record management stays confidently separate behind a clear Manage Events doorway.

## 2. Current React/public evidence and live-canvas parity
When any Event exists, Events.jsx renders one white section with a spaced container. splitEvents compares date to todayISO, sorts upcoming ascending and past descending, and renders non-empty EventSection groups titled “Upcoming Events” and “Past Events”. Each group starts with six cards and optionally toggles View more/View less. EventCard displays bound image, date, optional time/location/excerpt and View details link using eventSlug. Canvas must execute this real section, components and toggle state.

## 3. Exact editable elements and controls
Expose section background/padding/container and group spacing; upcoming/past labels and heading typography; grid columns/gaps; card background/border/radius/shadow/padding/hover; image ratio/radius/focal treatment; calendar/clock/pin icon mapping/styles; date format preset; metadata spacing; title/excerpt typography and clamp; View details label/button states; View more/less labels/button states. Select any fixture Event to style the bound card shell; offer Manage Events, never inline record editing.

## 4. Layers and reorder rules
Section > upcoming EventSection and past EventSection > group heading > grid > repeated EventCard > image > date/time metadata > title > location > excerpt > detail action; each group also owns its toggle. Group order may be configured between the two existing groups, but date-derived record order cannot be dragged. Bound slots may be reordered within the card only when DOM order changes equally; action remains after descriptive content.

## 5. Responsive behavior
Preserve one-column mobile, two-column md and three-column lg grids with six-item initial slice. Allow bounded columns/gaps/image ratio/card padding per device. Long event titles, locations and times wrap; missing optional slots collapse cleanly; expanded lists do not shift controls over content.

## 6. Data ownership and binding
Events owns title, date, time/startTime/endTime, location, image, excerpt and slug inputs. cmsCollection makes WordPress authoritative when its versioned contract exists; data/site.js is preview fallback. The visual document owns only group labels, date-format choice, action labels and shell styles. All card field slots are locked bindings.

## 7. Protected functional logic
Protect todayISO classification, date sort directions, suppression of empty groups, PAGE_SIZE=6 initial visibility, independent expanded state per EventSection, aria-expanded, eventSlug keys/routes and optional field conditions. Visual edits cannot move an Event between upcoming/past, change chronology or duplicate records. Current archive has no loading/error branch and none is invented.

## 8. Accessibility
Group h2s follow the page h1; card title remains h3; image alt binds to event title unless Events supplies a better alt; icons are decorative beside text; links/buttons have visible focus and clear labels; toggle announces expanded state. Ensure chronological DOM and visual order match and colour contrast passes.

## 9. Builder state previews
Fixtures cover upcoming only, past only, both groups, one event, more than six with collapsed/expanded toggle, missing image/time/location/excerpt, long metadata, today's event and device grids. Toggle clicks run real React state and never edit records.

## 10. Storage, publishing, and versioning
Store shell under events-collections in revisioned /events design, with allow-listed field-slot order, date preset and responsive tokens. Exclude Events from design JSON. Published shell declares compatible Events schema; missing/new record fields degrade through existing optional rendering. Audit changes to date format and route-action labels.

## 11. Planned implementation files (future only)
Instrument Events.jsx/EventSection/EventCard with stable region/slot/item hooks and shell-token reads; add bindings/sanitizer in future class-am-vb-events-design.php; build Layers, Record preview and States panels in assets/editor.js; add exact canvas selection/expanded CSS; test splitting, sorting, paging, optional fields and links.

## 12. Acceptance checklist
- [ ] Canvas collections are rendered by the real Events components.
- [ ] Event edits remain in Events and flow into the same shell.
- [ ] Upcoming/past split, sort, six-card slice and toggle remain exact.
- [ ] Optional/long data works across three devices.
- [ ] Links, headings, focus and aria-expanded pass accessibility checks.
- [ ] Revisions contain no copied Event objects.
