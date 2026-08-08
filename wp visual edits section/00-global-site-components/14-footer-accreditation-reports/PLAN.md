# Footer accreditation and Ofsted reports plan

## Intent and feel

The third expanded-footer column should communicate regulatory trust accurately, without implying a rating or report that does not exist. Editors need structured report records and strong warnings around official wording, destinations and pending states.

## Current evidence

- `Footer.jsx` labels the column “Official Ofsted reports” and creates one card per `locations` record.
- A record with `ofstedUrl` renders as an external link with an ExternalLink glyph; otherwise it renders a non-interactive card.
- Each card contains nursery name and `ofstedLabel`, falling back to “Ofsted information pending”.
- Current examples include report URLs/labels for branches and a pending/no-published-report state.
- The separate top-footer `footer-ofsted` brand mark is owned by the brand/contact plan; this section owns branch report cards only.

## Editable elements and controls

- Column heading, card density, border/surface presets and vertical gap.
- Automatic branch membership from nursery records; branch name is source-bound with optional display override.
- Structured report status: Published report, No published report yet, Awaiting inspection, or custom factual status approved by an administrator.
- Official report URL validator restricted to HTTPS, with Ofsted-domain recommendation/warning; external-link treatment is automatic.
- Report label text, accessible link description and “Edit canonical nursery compliance details” deep link.
- Preview published, pending, malformed URL, missing branch and long-label states.

## Selection, layers and dragging

Report cards may reorder only with the nursery/reference order; free canvas movement is disabled. A card’s name, status and external-link icon remain a locked semantic group. The column stays in the third desktop grid slot. The official icon is generated from link state and is not an independently movable decorative layer.

## Desktop, tablet and mobile

Cards stack vertically at all devices. Desktop text aligns left; smaller views centre consistently with the rest of the expanded footer. Long report labels wrap without covering the icon. Focus rings remain visible within card borders. Future branch counts expand natural height; never use a clipped fixed-height carousel for regulatory links.

## Data ownership and bindings

Canonical `ofstedUrl`, `ofstedLabel` and rating/status belong to each nursery record. Footer presentation stores references and optional display overrides only. The public link is generated from the structured record. No rating value should be inferred from label wording or logo artwork.

## Protected rules

- A missing URL always produces a non-link pending card; never a `#` link.
- External links use `target="_blank"` with `noopener noreferrer` and an accessible new-window cue.
- Do not allow an uploaded logo to replace factual report status.
- Avoid claims such as “Outstanding” unless explicitly present in verified structured data.
- Hiding a card requires confirmation that official information remains reachable elsewhere.

## Accessibility and failure states

Link cards receive branch-specific accessible names. The ExternalLink glyph is decorative when the text already explains the destination. Status is conveyed in text, not colour. Invalid/missing URLs show a builder error and public pending card. If a branch record is deleted, omit it publicly and retain a repairable orphan reference in revision/editor diagnostics.

## Storage and versioning

Store nursery references plus footer display overrides under `footer.accreditationReports`; canonical compliance fields remain in nursery data. Migrate `footer-{slug}-ofsted-name/label` overrides by stable nursery ID. Revision logs highlight official URL/status edits and may require an elevated capability or review flag.

## Planned implementation files (future only)

- `src/components/footer/FooterAccreditationReports.jsx`
- `src/lib/nurseryModel.js`
- `wordpress/mu-plugins/alexandra-visual-builder/assets/panels/footer-accreditation.js`
- `wordpress/mu-plugins/alexandra-visual-builder/tests/footer-accreditation.php`
- `src/components/Footer.jsx`

## Current public section → exact WP canvas parity

| Public element/state | WP canvas requirement |
| --- | --- |
| “Official Ofsted reports” heading | Same copy/treatment and third-column slot |
| One card per current nursery | Same branch order, label and current link/pending state |
| Linked card shows ExternalLink glyph | Exact public card appearance and safe external behaviour |
| Missing URL yields plain pending card | Canvas previews a non-interactive card, never a fake link |

## Acceptance checklist

- [ ] Current branch report cards match the public source data exactly.
- [ ] Published/pending states produce correct link semantics automatically.
- [ ] Official wording and URLs receive stronger validation/review cues.
- [ ] No status depends on colour or an uploaded logo alone.
- [ ] Stable branch references survive reorder and slug changes.
- [ ] Invalid data cannot become a misleading public claim.

