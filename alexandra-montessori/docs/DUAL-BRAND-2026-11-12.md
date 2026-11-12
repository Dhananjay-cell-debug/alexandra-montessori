# Two nursery brands under Alexandra — 12 November 2026

Approved brief: Alexandra stays at the domain root; a public frontend copy of Little Rainbows lives at /ldrn/. Both views show Alexandra on the left and Little Rainbows on the right. Logo links switch between the two views using the same domain. Existing source LDRN site and WordPress remain untouched.

The new LDRN frontend is copied into ldrn-site/ and uses its own styles, assets and router basename. Public content and the 18 published design overrides are captured from the live LDRN site, with local copies of referenced media. No WordPress installation or private data is cloned.

The user chose to keep copied LDRN forms connected to the original LDRN submission system. A same-origin, fixed-upstream gateway forwards only the known public form/data endpoints. It forwards no authentication cookies, exposes no admin routes, does not choose recipients, and preserves original validation, CV storage and mail routing. Requests from other origins are rejected at the gateway.

Header badges are 72px on phones, 104px on tablets and 128px on desktop. Navigation switches to its mobile menu below 1280px to leave room for both badges. Existing header colours and each brand's page design are retained.

Verification covers deep-link refreshes, both brand links, navigation confinement to /ldrn/, mirrored assets, form gateway validation/file upload, and the original LDRN site's unchanged published assets/content. Backups precede activation on Alexandra's existing Hostinger account.
