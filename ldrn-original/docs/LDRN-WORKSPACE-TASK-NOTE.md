# LDRN workspace: scope, reference and verification ledger

Last updated: 4 October 2026. This is the working acceptance ledger, not a completion claim.

## Current deployment and latest instructions

- WordPress and the website are LIVE at `https://ldrn.krildigital.com`, deployed using FTPS and a one-use authenticated PHP launcher. SSH/hPanel was not needed for activation.
- The live database uses the official SQLite Database Integration plugin, outside `public_html`, with consistent private backups. The existing hosting MySQL database is not connected; its credentials were not located. Do not describe the `/ldrn` website folder as a database.
- Actual live editor verification passed: publish `Check Availability ✓`, inspect the visitor website, publish the original label again and verify the mark disappears without code redeployment. Both versions remain in All saved sessions.
- Live All saved sessions opens from the editor in the INU shell. Search, Open and navigation while retaining the saved-session parameter were verified. F2 rename persists. The old verification version retains its label after the visitor site is restored.
- 1,849 workspace backend checks pass. All 21 local/live routes match after asynchronous content settles: rendered text, headings, links, image paths and form fields. Both testimonials archives contain seven published stories. Evidence: `verification/local-live-comparison.json`.
- Curriculum image isolation is FIXED AND DEPLOYED. All three StoryBand instances shared `curriculum.section-1`; they now use section-1/2/3. The runtime rejects ambiguous named anchors. Independent replacement, second-image alt edit and Undo were exercised locally; live draft and published design documents were preserved exactly. All 21 rendered local routes have no duplicate editor anchors. Evidence: `verification/editor-address-audit.json` and live Curriculum screenshots.
- Thirty actual template insertions and Undo passed in Chrome. The split photo template was inspected at Desktop 1440, Tablet 834 and Phone 390 with no horizontal overflow. New button containers and their label children have different stable IDs; label duplication/deletion and independent edits were exercised. Evidence: `verification/template-browser-insertion-checks.json`, `verification/template-responsive-checks.json`.
- Additional reusable-section correction: preserve all saved child overrides and explicit new-tab behavior on insertion; copied button target settings remain independent. Local library/backend checks pass; latest reusable correction awaits live deployment and complete save/reinsert browser exercise.
- A private live SQLite backup was downloaded over FTPS and checked with SQLite integrity_check: OK, 28 tables. Backup receipt stays private; no database or credentials are published.
- Local Alexandra administrator access is recovered and the existing content/plugins are preserved. Submissions inbox/detail, private reply queue, workflow ownership/status/follow-up and audit timeline were inspected and captured. Its Visual Builder contains five named sessions. Appearance is open in Chrome; local config disables the built-in Theme File Editor, and a separate Code Sessions feature has not been located. The user is identifying the actual reference; do not invent it.
- Existing private configuration contains the user's Gmail SMTP settings. The LDRN forms-to-operations mapping needs adaptation before activation: contact uses `setting`, availability uses `parentName` and weekday arrays, and the full careers form uses `/application`, `forename`/`surname`/`mobilePhone`, a `cv` upload and detailed employment/referee fields. The unadapted Alexandra ingestion expects different names and must not be enabled as-is.

## Ordered work requested on 4 October

1. Finish independent-element editor verification first: images, repeated component instances, text, button containers, labels, icons, destinations, duplicate/delete, undo/redo, reusable sections, templates and native CMS page controls. Audit all rendered pages for duplicate addresses; desktop/tablet/phone checks; preserve existing user edits.
2. Finish live/local route comparison, saved-session restore/recovery browser checks and backup integrity verification. Record evidence and precise remaining gaps; do not call untested flows complete.
3. Recover access to the existing local `alexandra-montessori.local` WordPress without rebuilding or replacing its content. The password-reset email screen is not evidence the website was deleted. Retain credentials privately and log in through the browser.
4. Inspect Alexandra's ACTUAL Submissions, File/Code Editor and Code Sessions pages and their dialogs visually. Capture screenshots and document purpose, permissions, save/publish/restore, reply/email, filters, attachments and status/assignment mechanisms. Inspect the actual live reference when accessible; keep Alexandra unchanged apart from the explicitly authorized access recovery.
5. Reproduce those established flows for LDRN. Keep visual website sessions and code sessions distinct where the reference does; connect code publication to deployed website assets and preserve recovery. Do not invent a replacement flow based on assumptions.
6. Connect all LDRN forms to its submissions queue and the user's Gmail using the reference's real configuration and mail mechanism. Confirm sender/account, recipients and credentials from existing configuration where possible. If a required credential or recipient is missing, ask one focused question while continuing independent implementation; do not silently substitute Alexandra's recipients. Verify receipt, attachment access, idempotency, reply, delivery failures/retry and website feedback with authorized test data.
7. AFTER the editor work, consolidate verified Hostinger FTP/SSH/key/API connection values into ONE reusable private local credentials file outside source/webroots. Preserve source credential files until consumers migrate; separate per-account credentials and per-site paths; do not publish secrets or invent missing keys. Document how other projects reference it.
8. Deploy the resulting verified LDRN changes through FTPS, check local/live parity again and write the client handover instructions and remaining dependency list.

## User requirements that take priority

- Use INU's actual editor and saved-session behaviour as the reference. Inspect controls in Chrome, capture screenshots, and compare the visible result. Do not substitute a generic WordPress admin table or invent a different saved-session journey.
- The editor's **All saved sessions** button opens the matching full-screen workspace: same shell/header, active session pill, save/refresh/back controls, search, nearest-date filter, change descriptions, author/date, Open and Delete.
- Open a save as its real saved website. Navigate its pages while staying in that save. Restore uses the reference confirmation/recovery mechanism. Every Publish becomes a saved session automatically.
- Separate a button/link's container, text and icon into individually selectable and editable layers. A combined button/text field does not meet the requirement. Apply this to existing pages and newly inserted assets/templates.
- Show destinations when selecting text, images or buttons inside existing links. Allow adding/removing destinations and choosing pages for newly created text/images/buttons. Preserve working submit/menu/calculator actions.
- Preserve every original LDRN element, including parent stories, forms, galleries, navigation, footer, hidden/expanded content, responsive layouts and existing links.
- Provide future page/article/event/gallery creation, nursery templates, media, reusable sections, responsive editing, draft/publish, undo and recovery.
- Prepare an LDRN deployment with backups, verification and rollback, then deploy WordPress and the workspace to the dedicated LDRN live site when the required hosting/database connection exists.

## Reference inspection ledger

| Flow | Evidence / current finding | Remaining verification |
|---|---|---|
| INU Pages, Assets, Media | Actual panels inspected; screenshots/DOM inventory in `verification` | Inspect remaining menus and interactions; capture visual comparisons |
| INU All saved sessions | Full-screen list/preview, search, nearest-date ordering, inline Save/Cancel, date-picker control and back/editor button inspected | Rename/delete and restore confirmation dialogs; cancel reference mutations |
| LDRN original visitor site | 21 routes inventoried in `verification/live-audit.json`; desktop/phone reference screenshots | Compare every editable element and expanded/interactive state against the original; tablet/phone workflows |

## Implementation and acceptance ledger

| Item | Status | Acceptance evidence required |
|---|---|---|
| Original testimonials | Local query fixed; three approved home quotes visible | Verify complete archive, future records, all responsive widths and editable child layers |
| Existing linked images | Parent destination appears in inspector | URL/new-tab/remove changes, undo, reload and public output |
| New text destinations | Native anchor created; Undo verified | Recheck after separate-text-layer implementation; media and button insertion |
| Button text vs container | React-owned independent label layers implemented. Header label selection and size change verified; selecting parent retained original container styles/link; Undo restored both test edits | Inserted label duplicate/delete/reusable-section handling; broad page, icon, responsive and published-output checks |
| Session data/build recovery | Complete public-content/build snapshots implemented; transaction checks pass | Match INU UI and actual restore journey; browser preview/restore; missing media/deleted-record recovery; snapshot REST paging/auth |
| Session screen | INU full-screen presentation ported. Save, F2 rename, server persistence, search, Open and preview navigation verified; comparison screenshots captured | Actual restore/delete confirmation and recovery verification; remaining dialogs |
| Thirty templates + elements | All 30 actual insertions and Undo pass; split template desktop/tablet/phone inspected; 222 independently identified layers | Drag/drop, every template's remaining responsive states and reusable save/reinsert; no fabricated nursery claims |
| Reusable sections | Save/template insertion implemented | Browser save, reinsert, independent edits, persistence |
| Native CMS / future pages | Custom page and article backend flows implemented; archive paging added | Browser create, menu inclusion, SEO/status, publish/unpublish and live collections without rebuild |
| Forms/select labels/icons | Inspector support added; real submit behaviour preserved | Verify placeholders/options/icons independently and existing validation; do not send test enquiries to real recipients |
| Concurrency and recovery | Revision guards, local recovery and publication transactions implemented | Browser conflict, discarded draft, expired nonce and failure recovery; no loss of concurrent user edits |
| Deployment | LIVE WordPress and visitor website, FTP-only activation of `20261004T054547Z-fb1c80c0`, private SQLite database/backups. Live reversible Publish and all 21 route comparisons pass. Editor code update preserves user live/draft/revision documents; rollback files retained privately | Deploy subsequent verified fixes and forms configuration; preserve the user's unpublished Curriculum photo edit |
| Handover | Pending | Instructions for all actual client workflows, with screenshots and known dependencies |

## Working rules

1. Keep INU unchanged. Inspection may open dialogs and cancel them; do not publish, save, delete or restore its content.
2. Preserve the user's local LDRN edits. Save recovery before changing shared state; isolate test elements and remove only our own test content.
3. Mark a row verified only after exercising that user workflow in the browser and checking the resulting visitor state. A successful build or backend assertion alone is insufficient.
4. Capture reference, LDRN result and responsive evidence under `docs/verification`; inspect the actual images, not only DOM text.
5. Recheck affected existing flows after changing shared runtime, source addressing, navigation or publish/session logic.
6. Do not report deployment or full completion while required live connections or verification remain outstanding. Private credentials/SQL stay outside source and public directories.

## Immediate remaining work

1. Deploy and inspect the latest reusable-link correction, finish actual save/reinsert and session restore/delete browser exercises, and complete native CMS page-row controls. Keep existing user drafts intact.
2. Identify the actual Alexandra code-editor/session reference from the Appearance page now shown to the user. The local built-in Theme File Editor is disabled; matching a missing feature requires its real reference rather than a guessed substitute.
3. Adapt every LDRN form to the established operations workflow, including full careers field preservation and private CV storage. Test on isolated databases first, then configure the existing private Gmail account and verify live notifications/replies using only the user's authorized inbox.
4. Consolidate private reusable hosting credentials after editor work, migrate deployment consumers, then complete the handover. Public privacy text currently says forms are not connected and must accurately reflect any activation.
