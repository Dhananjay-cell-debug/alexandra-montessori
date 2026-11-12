# LDRN Website Content deployment

Deployed to https://ldrn.krildigital.com on 15 October 2026 IST (14 October UTC).

The latest existing theme, workspace, operations repository and Website Content
forms are deployed. About us and Site Settings use the approved baseline where
values were missing. Nursery photos and documents were imported into the native
Media Library and assigned to their collections. Nursery image references now
point to attachments. WordPress starter posts were moved to recoverable Trash.

Release: `20261014T184702Z-website-content`.
Published build: `a10f0bfbd25ea771b184463685dff378feac5f6ea3224cd109e6309ea53ebefe`.
Recovery session: `bMm3ZgaWkVwU` (Website Content ready).

## Verification

- Frontend lint and production build passed. The existing form timestamp was
  changed from a ref read during render to a lazy state initializer to satisfy
  React lint without changing the initial timestamp behavior.
- PHP syntax passed for all 59 deployed PHP source files.
- All 134 scoped source files match their deployed SHA-256 hashes.
- All 21 public routes return HTTP 200 and the WordPress data contract.
- All 35 published manifest JS/CSS assets return HTTP 200 with matching hashes.
- The Website Content dashboard and seven configured content/settings forms
  render successfully in the live WordPress runtime.
- Existing live/draft editor documents and revision match their pre-deploy
  hashes. Homepage rendering was inspected in Chrome.

The initial media import response failed after partially importing attachments.
The idempotent migration resumed successfully; all 26 approved source assets
were imported or reused, with no missing assets.

The saved live WordPress password was rejected. Browser/admin HTTP sign-in and
interactive save/publish workflows were not verified in this deployment. No
password or account was changed. Backend form rendering was verified through
the existing authenticated hosting maintenance mechanism instead.

## Recovery evidence

Private local directory:
`C:\Users\Dhananjay\Local Sites\lrdn\private-releases\20261014T184702Z-website-content`

Contains the pre-deploy SQLite backup (integrity check passed), before/after
file and editor-state hashes, deployment receipt and HTTP verification results.
Recovery copies of all replaced files are retained outside the live webroot at
`domains/krildigital.com/ldrn-releases/20261014T184702Z-website-content/before`.
The previous active build is recorded in the private `before.json`.

Temporary authenticated maintenance launchers were removed. Credentials and
database backups remain outside source and public webroots.
