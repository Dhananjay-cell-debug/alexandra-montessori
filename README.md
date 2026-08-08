# Alexandra Montessori — complete project and recovery archive

This repository preserves the Alexandra Montessori website as of 8 August
2026. It contains the normal development source, the WordPress theme/MU-plugin
source, deployment and verification helpers, project documentation, and an
encrypted recovery vault containing every part of the original workspace plus
fresh Local and production WordPress snapshots.

## Safe environment boundary

- Official client website and WordPress: `https://alexandramontessori.co.uk`
- Isolated static Vercel copy: `https://alexandramontessori-dhananjay.vercel.app`
- Deployable React/Vite app: `alexandra-montessori/`

The Vercel copy is intentionally static. Vercel does not run this project's
PHP/WordPress/MySQL backend, so it cannot modify the official client database,
submissions, media, or published pages. WordPress editing must happen in a
separate WordPress environment and reach the official site only through an
explicit, reviewed publish/deployment step.

## Repository layout

- `alexandra-montessori/` — React/Vite frontend, public assets, tests, scripts,
  WordPress theme source, and custom must-use plugins.
- `scripts/` — production backup/deploy helpers and the encrypted-vault tool.
- `backup-vault/2026-08-08/` — AES-256-GCM encrypted full-workspace, Local
  WordPress, and official production WordPress recovery bundles.
- Root Markdown files — project history, architecture, handoff, CMS, DNS, and
  operational documentation.
- `wp visual edits section/` — page/section planning records for the custom
  Visual Builder.

Large original assets, historical releases, dependency folders, generated
builds, logs, plaintext secrets, databases, and private applicant uploads are
not duplicated in the normal Git tree. They are preserved byte-for-byte in the
encrypted vault. See `backup-vault/README.md` and the vault manifest.

## Frontend development

```powershell
cd alexandra-montessori
npm ci
npm run lint
npm run build
npm run preview
```

`npm run build:wp` creates theme-prefixed assets for WordPress. After syncing
that output to a WordPress theme, run `npm run build` again so `dist/` returns
to root-based paths for Vercel/static hosting.

## Important restore rule

Never import a Local or archived database directly over the official client
site. Restore into a separate PHP/MySQL staging environment first, verify it,
and take a fresh target backup before any controlled migration.

