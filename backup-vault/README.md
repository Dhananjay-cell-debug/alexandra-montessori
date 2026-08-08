# Alexandra encrypted recovery vault

The vault contains six authenticated AES-256-GCM archives created on 8 August
2026. The encryption key is deliberately **not** stored in GitHub.

The expected key-file SHA-256 fingerprint prefix is:

```text
8B049476E07870D4
```

Keep the separate key file in a password manager and at least one additional
secure location. Losing it makes the vault unrecoverable; committing it beside
the vault defeats the protection.

## Vault contents

- `workspace-app.tar.gz.amvault` — exact original application folder,
  including dependencies, builds, logs, and temporary history.
- `workspace-original-assets.tar.gz.amvault` — original client photos/video,
  evidence, contact sheets, and visual-planning material.
- `workspace-historical-backups.tar.gz.amvault` — every historical production
  backup/release/delta directory that was present locally.
- `workspace-support-and-secrets.tar.gz.amvault` — all remaining root files,
  documentation, scripts, local settings, credentials, and private keys.
- `local-wordpress-complete-20260808.tar.amvault` — full Local WordPress files,
  Local database dump, local private uploads, unpublished drafts, and Visual
  Builder state.
- `production-wordpress-complete-20260808-125819.tar.amvault` — fresh official
  production database, complete WordPress docroot (`wp-admin`, core, plugins,
  themes, uploads, configuration), and private application uploads.

`2026-08-08/MANIFEST.json` records plaintext/encrypted sizes and SHA-256 hashes.
The verification files record archive readability and pre/post-transfer hash
checks. `WORKSPACE-ARCHIVE-LAYOUT.json` proves how every original top-level
workspace entry was assigned to one of the four workspace archives.

## Decrypt

From the repository root:

```powershell
node scripts/archive-vault.mjs decrypt `
  backup-vault/2026-08-08/production-wordpress-complete-20260808-125819.tar.amvault `
  production-wordpress-complete-20260808-125819.tar `
  --passphrase-file "D:\secure\ALEXANDRA-ARCHIVE-KEY-KEEP-SAFE.txt"
```

The command authenticates the archive before completing and prints the
decrypted SHA-256. Compare it with `plainSha256` in `MANIFEST.json`, then unpack:

```powershell
tar -xf production-wordpress-complete-20260808-125819.tar
```

Workspace bundles decrypt to `.tar.gz` and can be unpacked with `tar -xzf`.
Extract all four workspace bundles into the same empty destination to recreate
the original project folder.

## WordPress recovery

Restore production or Local WordPress only to a suitable PHP/MySQL host. The
bundle includes a database dump and file archive with internal checksums. For a
staging restore, update WordPress URLs safely after import and keep the staging
database, mail delivery, cron, analytics, and forms isolated from the client
site. Do not overwrite official production without a new verified backup and a
reviewed rollback plan.

