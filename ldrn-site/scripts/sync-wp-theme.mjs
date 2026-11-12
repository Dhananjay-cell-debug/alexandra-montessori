import { access, copyFile, mkdir, readFile, readdir, rm } from "node:fs/promises";
import path from "node:path";
import { fileURLToPath } from "node:url";

// Copies the built LDRN bundle into the LDRN WordPress theme.
//
// By default it syncs the in-repo theme at
// `ldrn-wordpress/wordpress/theme/ldrn-theme/dist`, which is what gets
// packaged for staging. Set LDRN_LOCAL_WP_ROOT to push straight into a Local
// WP install instead. This never touches any other client's theme.
const projectRoot = path.resolve(
  path.dirname(fileURLToPath(import.meta.url)),
  "..",
);
const sourceDist = path.join(projectRoot, "dist");
const repoThemeDist = path.resolve(
  projectRoot,
  "../wordpress/theme/ldrn-theme/dist",
);
const localWpRoot = process.env.LDRN_LOCAL_WP_ROOT || "";
const installedDist = localWpRoot
  ? path.join(localWpRoot, "wp-content/themes/ldrn-theme/dist")
  : repoThemeDist;
const manifestRelativePath = ".vite/manifest.json";

async function copyTree(source, destination, relative = "") {
  const entries = await readdir(source, { withFileTypes: true });
  await mkdir(destination, { recursive: true });

  for (const entry of entries) {
    const nextRelative = path.posix.join(relative, entry.name);
    if (nextRelative === manifestRelativePath) continue;

    const sourcePath = path.join(source, entry.name);
    const destinationPath = path.join(destination, entry.name);
    if (entry.isDirectory()) {
      await copyTree(sourcePath, destinationPath, nextRelative);
    } else if (entry.isFile()) {
      await mkdir(path.dirname(destinationPath), { recursive: true });
      await copyFile(sourcePath, destinationPath);
    }
  }
}

async function main() {
  const sourceManifestPath = path.join(sourceDist, manifestRelativePath);
  const manifest = JSON.parse(await readFile(sourceManifestPath, "utf8"));

  // Drop the previous bundle so stale hashed chunks from an earlier build do
  // not accumulate inside the theme.
  await rm(installedDist, { recursive: true, force: true });

  // Assets arrive before the manifest, so WordPress can never advertise a
  // freshly hashed bundle that has not been copied yet.
  await copyTree(sourceDist, installedDist);

  const referencedFiles = new Set();
  for (const record of Object.values(manifest)) {
    if (record?.file) referencedFiles.add(record.file);
    for (const cssFile of record?.css || []) referencedFiles.add(cssFile);
  }
  for (const relativeFile of referencedFiles) {
    await access(path.join(installedDist, relativeFile));
  }

  const installedManifestPath = path.join(installedDist, manifestRelativePath);
  await mkdir(path.dirname(installedManifestPath), { recursive: true });
  await copyFile(sourceManifestPath, installedManifestPath);

  console.log(
    `Synced ${referencedFiles.size} manifest assets to ${installedDist}`,
  );
}

main().catch((error) => {
  console.error(error instanceof Error ? error.message : error);
  process.exitCode = 1;
});
