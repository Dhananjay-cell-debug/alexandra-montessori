# Live LDRN account allocation and Two Factor ? 2026-10-15

Live site: https://ldrn.krildigital.com

Implemented the user-approved Alexandra account model, adapted to the current Site Editor. Existing sections and roles are reused. Add/Edit User now has an independent Site Editor page grid. Administrators retain all pages; custom Editors need explicit allocation; Viewers and empty/missing allocations have no Site Editor access. Site Editor-only accounts land in the editor when they have no dashboard section allocation.

Server enforcement covers the custom menu capability, direct admin URLs, canvas routes, revision-aware AJAX saves and publishing. Changed/deleted protected layers, shared chrome, moved protected blocks, duplicate inserted IDs and cross-page grouping/order are rejected. Full-site navigation, page creation/deletion, reusable section writes and restores stay Administrator-only. Editors cannot publish someone else's unallocated pending changes. The client hides global controls and unassigned pages and isolates local recovery by user.

Installed official WordPress Two Factor 0.17.0, matching Alexandra. Administrator email OTP is mandatory; an existing configured authenticator method is preserved. Application-password access is unavailable for protected administrators. Existing passwords and authenticated sessions were preserved. Enforcement is controlled by ldrn_force_2fa with an optional filesystem LDRN_FORCE_2FA override. A trusted hosting maintainer can use that private override if recovery is required; dashboard users cannot bypass the login requirement.

Validation: 51 checks passed, including role/allocation persistence; assigned page/canvas access; unassigned and Viewer HTTP 403; crafted save rejection; protected inserted child IDs; revision/force checks; actual assigned-page publication within a rolled-back transaction; OTP wrong, valid, replay and expiry; non-current user capability isolation; cleanup and unchanged public/editor state. PHP lint and JavaScript syntax checks passed. All 28 deployed source files verified. Browser confirmed the new allocation form, Two Factor settings/profile email primary state, and Administrator Site Editor canvas without JavaScript errors. Temporary test users remaining: zero.

Private SMTP authentication was verified without sending a message. Actual inbox delivery and an interactive real-user OTP login have not been exercised. Admin email OTP will be requested on the next sign-in; an existing session remains usable.

Deployment backups, consistent private SQLite backup, baseline hashes, authentication configuration recovery and receipts are outside public_html. Latest release: 20261015T091618Z-user-access. Live/draft hash and revision, active website build, content counts and search indexing setting were preserved.

Screenshots: Alexandra workspace proof/ldrn-site-editor-user-allocation.jpg and proof/ldrn-two-factor-settings.jpg. Detailed test results remain private in user-access-verification.json.
