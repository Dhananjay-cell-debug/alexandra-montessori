import { access, copyFile, mkdir, readFile, readdir } from "node:fs/promises";
import path from "node:path";
import { fileURLToPath } from "node:url";

const projectRoot = path.resolve(path.dirname(fileURLToPath(import.meta.url)), "..");
const sourceDist = path.join(projectRoot, "dist");
const wpRoot =
  process.env.AM_LOCAL_WP_ROOT ||
  "C:/Users/Dhananjay/Local Sites/alexandra-montessori/app/public";
const installedDist = path.join(
  wpRoot,
  "wp-content/themes/alexandra-theme/dist",
);
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
