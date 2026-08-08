# Alexandra Montessori frontend

React 19/Vite frontend for Alexandra Montessori. The same frontend is built in
two modes:

- `npm run build` — root-based static output for Vercel and standalone preview.
- `npm run build:wp` — WordPress-theme asset paths for the PHP/WordPress site.

The WordPress theme source and custom must-use plugins are under `wordpress/`.
The full WordPress installations and databases are stored in the encrypted
repository-level recovery vault, not in this deployable frontend directory.

## Commands

```powershell
npm ci
npm run lint
npm run build
npm run test:visual-regions
npm run preview
```

The standalone Vercel build has no PHP/MySQL backend. Its forms therefore do
not write to the official client WordPress database, which keeps the Vercel
copy isolated from `alexandramontessori.co.uk`.
