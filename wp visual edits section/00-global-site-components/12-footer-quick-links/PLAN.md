# Footer quick-links column plan

## Intent and feel

The first expanded-footer column should be a compact practical index, not another independently maintained menu. Clients should immediately see that these links mirror Parent Information, with the freedom to override footer display only when there is a real reason.

## Current evidence

- Expanded `Footer.jsx` renders a “Quick links” heading and maps all five `parentInfoLinks` records.
- Existing editable keys allow five labels and href overrides: Fees, Fee Calculator, Funded Childcare, Blog and Food Hygiene Rating.
- The column is centred on small screens and left-aligned at `lg`; links use small white copy over green.
- Because numeric keys follow array index, reordering the shared source can currently misassociate saved overrides.

## Editable elements and controls

- Column heading, optional description (off by default), text alignment preset and vertical spacing.
- Source mode: mirror Parent Information (default) or curated footer subset. Show source badges per row.
- Ordered row controls: display label override, destination reference, visibility and accessible-label override.
- “Restore source label/target” on each row; footer-specific override never writes back silently to the desktop/mobile menu.
- Preview long label, active/visited treatment, broken route and 320px width.
- Link typography/colour uses approved footer token presets; no arbitrary free styling per row.

## Selection, layers and dragging

Rows reorder only when curated mode is selected; mirror mode shows inherited order with a shortcut to the Parent Info plan. Canvas selection can target heading or row, but free position/scale are disabled. The whole column may move only through footer grid ordering controlled at component level; current position remains first.

## Desktop, tablet and mobile

At `lg`, this is column one of three and left-aligned. Below `lg`, it stacks and centres according to the actual footer. Link targets stay at least 44px effective height through spacing even when text is visually small. Tablet/mobile previews include expanded footer plus cookie-banner overlap.

## Data ownership and bindings

Stable Parent Info item IDs are authoritative. Footer stores heading, source mode, ordered references and optional display overrides. It never copies underlying page content. Target resolution uses page IDs/current paths. The renderer must not use position-based keys after migration.

## Protected rules

- Unsafe protocols, empty visible labels and unpublished targets cannot publish silently.
- Mirror mode cannot reorder or delete the source menu from this panel.
- A footer override is clearly marked and reversible.
- Preserve semantic heading and navigation/list structure.
- The column cannot be moved outside the expanded footer region.

## Accessibility and failure states

Use a labelled footer navigation landmark only if it does not create duplicate ambiguous landmark names; otherwise use a semantic heading plus list. Focus and underline/hover states must pass contrast. If a source item disappears, flag the reference and omit it publicly until repaired. If all rows are hidden, hide the column and let the remaining grid reflow; the builder shows an empty-state card.

## Storage and versioning

Store stable `sourceItemId`, override fields and order inside `footer.quickLinks`. Migrate five index-based `footer-quick-link-N` overrides by matching the current static order, then record stable IDs. Revision diffs identify the affected named link, not its former number.

## Planned implementation files (future only)

- `src/components/footer/FooterQuickLinks.jsx`
- `src/lib/navigationModel.js`
- `wordpress/mu-plugins/alexandra-visual-builder/assets/panels/footer-quick-links.js`
- `wordpress/mu-plugins/alexandra-visual-builder/includes/class-am-vb-menus.php`
- `wordpress/mu-plugins/alexandra-visual-builder/tests/footer-quick-links.php`

## Current public section → exact WP canvas parity

| Public element | WP canvas requirement |
| --- | --- |
| “Quick links” heading | Same text, uppercase tracking, colour and spacing |
| Five Parent Info links | Same initial labels, routes and order from shared source |
| First expanded-footer column | Same grid slot on desktop and stack position on smaller screens |
| Small white links on green | Same public typography/interaction states in preview |

## Acceptance checklist

- [ ] Initial canvas exactly matches the five public quick links.
- [ ] Mirror mode stays synchronized by stable ID after reorder/rename.
- [ ] Footer-only overrides are visible, reversible and do not mutate source silently.
- [ ] Mobile stack and desktop column align with real expanded footer.
- [ ] Empty/broken rows reflow safely and remain diagnosable.
- [ ] Migration removes positional override ambiguity.

