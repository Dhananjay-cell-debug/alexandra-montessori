# LDRN SEO deployed and connected

Live screen: https://ldrn.krildigital.com/wp-admin/admin.php?page=ldrn-seo

Deployed the existing local Little Rainbows SEO suite with its Business facts,
Pages, Audit, Speed, Traffic and Settings screens. Local audit/traffic history
was not copied into production reports. No Search Console service-account key
or Google verification code was configured locally; none was invented.

Pages settings feed the server title, description, canonical, share metadata,
robots directives and sitemap. Business settings feed the public Organization
and ChildCare structured data. React now uses the same WordPress SEO contract
and updates one authoritative set of head tags and JSON-LD on navigation. The
hard-coded frontend canonical domain no longer overrides the WordPress host.

The user's follow-up explicitly authorized indexing on `ldrn.krildigital.com`
now. `blog_public` is enabled. robots.txt permits public crawling and names the
live sitemap; sitemap.xml currently carries 19 indexable URLs. Intentional
per-page exclusions, including application/privacy routes, remain excluded.
Page-level noindex also removes dynamic records from the sitemap. The sitemap
revalidates instead of holding settings changes for an hour.

## Content behavior

The native create/edit forms and permissions are connected to the same post
fields and metadata the public CMS getters read. Complete published blog,
event, nursery and testimonial records feed their public pages/collections;
complete open jobs feed Vacancies. Drafts, incomplete records and closed jobs
are intentionally excluded. Events need title, date, time, location, excerpt,
description and featured image; blog articles need title and body. Media must
be selected in the relevant field to change an image on the site.

Changes become visible on a fresh public page load/refresh. The frontend does
not poll an already-open visitor tab for edits. Content creation was inspected
read-only as requested; no new article, event, job, draft or test content was
created. Counts remain 0 blogs, 0 events, 0 jobs, 1 nursery and 7 testimonials.

## Verification

- Frontend lint and production build passed; 18 PHP files passed syntax checks.
- All 136 packaged files verified against deployed SHA-256 hashes.
- Four SEO reporting tables installed successfully on production SQLite.
- Live browser: SEO Business screen, existing-value Save, single canonical and
  description, correct domain, one JSON-LD graph and route changes verified.
- 20 public pages return HTTP 200 with matching PHP/React metadata contracts.
- In-request, non-persistent overrides proved title, description, Business
  schema and noindex/sitemap wiring without saving test values.
- Blog/testimonial REST responses and all five content create permissions /
  configured forms were checked without creating records.
- Public crawling, sitemap, indexing and intentional private-route noindex
  were checked after enabling indexing.
- Existing Visual Builder live/draft documents, revision and published content
  counts were preserved.

Private deployment backup:
`C:\Users\Dhananjay\Local Sites\lrdn\private-releases\20261014T200151Z-seo-suite`.
Remote recovery files/options:
`domains/krildigital.com/ldrn-releases/20261014T200151Z-seo-suite`.
Private reports: `seo-live-verification.json` and `seo-indexing-enabled.json`
inside the local private-releases directory. Screenshot:
`proof/ldrn-seo-business-live.jpg` in the Alexandra workspace.

## Google behavior and final domain

Saving updates the site's SEO output, not Google's stored search index or its
Google Business Profile account. The Business panel is a preview of structured
data; it is not a direct Google Maps/profile editing API. Google controls when
it recrawls pages and which title/snippet/panel it displays. Its documentation
says crawling can take days to weeks and does not guarantee inclusion:
https://developers.google.com/search/docs/crawling-indexing/ask-google-to-recrawl

When the final nursery domain is configured, update the WordPress home/site
URLs, arrange appropriate old-to-new redirects and update/submit that domain's
sitemap and Search Console property. The frontend SEO now follows the actual
WordPress host instead of requiring another hard-coded canonical change.
