# Little Rainbows Day Nursery — WordPress

The independent LDRN WordPress product. It reuses the technical architecture
proven on Alexandra Montessori and nothing else: LDRN has its own theme slug,
content namespace, REST namespace, database, uploads, roles, recipients and
deployment path. No Alexandra content, data, recipient or credential is
present anywhere in this tree.

The two nurseries are publicly part of one group, and the site says so on the
hero and in the footer. That is a published corporate fact, not a technical
connection — the sites share no database, no mailbox and no admin account.

## Layout

```
frontend/                     React + Vite source; builds both outputs
  src/data/site.js            Approved content baseline and the SEO route map
  src/lib/content.js          Resolves records from WordPress or the baseline
  src/lib/api.js              Form submission transport (ldrn/v1)
wordpress/
  theme/ldrn-theme/           SPA shell, CMS getters, REST APIs, roles, media
  mu-plugins/ldrn-operations/ Submissions store, queue, files, admin inbox
  mu-plugins/ldrn-visual-builder/  Restricted visual editing per route
  mu-plugins/ldrn-seo.php     Server metadata, canonicals, redirects, sitemap
  mu-plugins/ldrn-admin-hardening.php, ldrn-cache-headers.php
```

## Build

```bash
cd frontend
npm install
npm run dev          # http://localhost:5174, proxies /wp-json to ldrn.local
npm run build        # standalone static preview -> frontend/dist
npm run build:theme  # theme bundle -> wordpress/theme/ldrn-theme/dist
npm run lint
```

`build:theme` re-bases assets onto `/wp-content/themes/ldrn-theme/dist/` and
copies them into the theme, verifying every manifest asset arrived. Set
`LDRN_LOCAL_WP_ROOT` to push straight into a Local WP install instead.

## How the frontend and WordPress fit together

The theme prints `window.ldrnData` before the bundle loads. `src/lib/cms.js`
detects it; `src/lib/content.js` adapts each WordPress record into the shape
the components already render and falls back to `src/data/site.js` when the
app runs as a standalone static preview. That fallback is why one source tree
builds both outputs, and why an editor unpublishing every event empties the
archive rather than resurrecting hard-coded entries.

Two contracts must be changed together:

- **SEO** — `seoRoutes` in `src/data/site.js` and `ldrn_seo_static_routes()`
  in `mu-plugins/ldrn-seo.php`. A crawler that never runs JavaScript sees the
  PHP table.
- **Validation** — `src/lib/validation.js` and the server-side checks. The
  browser copy is a convenience; WordPress re-validates everything.

## Forms are off until it is lawful to switch them on

Every public form currently checks the visitor's answers on the device and
sends and stores nothing. Two locks must both open before that changes:

1. `define('LDRN_SUBMISSIONS_ENABLED', true);` in `wp-config.php`. A
   deliberate human action, taken only once the privacy policy naming the
   data controller and retention periods is published.
2. An approved recipient set in the operations settings.

With either lock closed `window.ldrnData.submissions.enabled` is `false` and
the React forms keep their current local-only behaviour and notice. The
operations plugin also defaults to `dashboard_only` notifications and will
never invent a recipient — `ldrn_branch_fallback_email()` deliberately returns
an empty string, because guessing an address would route a parent's enquiry to
an inbox nobody reads.

## Staging install

1. Install a fresh WordPress for LDRN. Do not clone or import any other
   client's database.
2. Copy `wordpress/theme/ldrn-theme` into `wp-content/themes/` and activate
   **Little Rainbows Day Nursery**.
3. Copy everything in `wordpress/mu-plugins/` into `wp-content/mu-plugins/`,
   keeping the loader files (`ldrn-operations.php`,
   `ldrn-visual-builder.php`) next to their folders.
4. Visit **Settings → Permalinks** once and save, to refresh rewrite rules.
5. Create the Hounslow record under **Settings (Nurseries)** and fill in the
   fields the site publishes. Nothing appears publicly until a record passes
   its readiness check.
6. Confirm every route matches the approved reference before enabling any
   form or email integration.

## Checks

```bash
# PHP syntax, all 57 files
find wordpress -name '*.php' -exec php -l {} \;

# Visual Builder standalone contracts
cd wordpress/mu-plugins/ldrn-visual-builder/tests
for t in *-standalone.php; do php "$t"; done
```

`custom-layer-schema` (1) and `schema-parity` (2) report the same failures as
the upstream Alexandra suite; they are inherited, not caused by this port.

## Still required from the client

- Official domain and WordPress hosting.
- Registered data controller, privacy contact and retention periods.
- Approved central and per-setting recipient addresses.
- Confirmed fees, sessions, funding schemes, vacancies and team copy.
- Analytics / Search Console identifiers and cookie policy.

See [LDRN-WORDPRESS-PLAN.md](../LDRN-WORDPRESS-PLAN.md) for the full content
model and delivery sequence, and [docs/IMPLEMENTATION-STATUS.md](docs/IMPLEMENTATION-STATUS.md)
for what is built.
