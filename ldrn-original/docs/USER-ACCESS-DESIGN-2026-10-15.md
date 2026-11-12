# LDRN user access and two-factor authentication

Approved reference: user supplied Alexandra account allocation screenshot and requested the same system, replacing Visual Builder with Site Editor, plus the same 2FA integration.

Reuse existing Administrator, Editor, Viewer and section allocations. Add an independent Site Editor page checkbox grid to Add/Edit User. Administrators retain every page; only the custom Editor role can use explicitly allocated pages; missing/empty allocation and Viewer receive no Site Editor access. Page IDs/routes derive from the current directory, including ready CMS record pages.

Enforce the allocation at menu, direct URL, canvas, AJAX save/publish and document diff boundaries. Preserve unallocated and shared layers; reject changed/deleted/moved protected nodes and inserted trees. Keep page creation, navigation controls, global restore/history and reusable section writes administrator-only. Prevent an Editor publishing another page's pending changes. Do not grant manage_options.

Reuse WordPress Two Factor, matching Alexandra. Mandatory email fallback for administrators after the live mail transport has been tested; existing configured authenticator methods remain. Keep existing browser sessions and passwords. Provide profile setup for authenticator/backup codes. Do not enforce on Editors/Viewers (Alexandra parity).

Deploy only targeted backed-up files. Verify role/allocation save, allowed/denied document changes, direct endpoints and live admin UI; verify 2FA provider and mail transport. Remove temporary test users and edits. Preserve public content, SEO/indexing, live/draft/revision and active build except any necessary runtime asset refresh.
