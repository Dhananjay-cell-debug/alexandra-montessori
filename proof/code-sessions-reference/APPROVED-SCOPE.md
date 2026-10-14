# Approved theme editor and code sessions scope

Approved by the user on 15 October 2026.

Restore the original Theme File Editor and Code Sessions under Appearance on
Alexandra production. Port the same session list, search, date filter, detail,
restore-with-backup and recoverable delete behavior to LDRN, using LDRN's own
theme, database, post type and metadata namespace.

Remove only the marked Yoast score/link columns from Alexandra's Blog list and
the Website result columns from its Events and Testimonials lists. Preserve
the current add/edit screens and other content columns.

Use the original live Code Sessions plugin as the reference. Add the modern
WordPress AJAX save hook, nonce validation, strict theme-path validation and
backup failure handling. Theme editor access is limited to existing
administrators that already hold the edit_themes capability. Keep global file
modification restrictions and login protection in place.

Implementation order: back up affected live files; install the reviewed access
adapter, original/ported session plugin and Alexandra column filter; run PHP
syntax and permission checks; verify actual CSS save/automatic backup/restore;
verify the visible menus, legacy session and cleaned list screens. Preserve
existing website content, visual builder state and saved sessions.
