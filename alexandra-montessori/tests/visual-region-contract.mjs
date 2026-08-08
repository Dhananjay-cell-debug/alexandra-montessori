import { readFileSync, readdirSync, statSync } from "node:fs";
import { join, resolve } from "node:path";

const root = resolve(import.meta.dirname, "..");
const registryFile = join(
  root,
  "wordpress/mu-plugins/alexandra-visual-builder/includes/class-am-vb-site-design.php",
);

function filesBelow(directory) {
  return readdirSync(directory).flatMap((name) => {
    const path = join(directory, name);
    return statSync(path).isDirectory()
      ? filesBelow(path)
      : /\.(?:js|jsx)$/.test(name)
        ? [path]
        : [];
  });
}

const registry = readFileSync(registryFile, "utf8");
const regionIds = new Set(
  [...registry.matchAll(/self::region\('([^']+)'/g)].map((match) => match[1]),
);
const source = filesBelow(join(root, "src"))
  .map((file) => readFileSync(file, "utf8"))
  .join("\n");
const markers = new Set(
  [...source.matchAll(/(?:data-am-vb-region|region)="([a-z0-9-]+)"/g)].map(
    (match) => match[1],
  ),
);

// These are deliberately non-published simulator states. They remain present
// in the builder outline and design document, but only gain DOM when the
// protected runtime enters that state.
const conditionalOnly = new Set([
  "calculator-states",
  "contact-location-missing",
  "hygiene-states",
]);

const missing = [...regionIds].filter(
  (id) => !markers.has(id) && !conditionalOnly.has(id),
);
if (missing.length) {
  throw new Error(`Exact visual regions without React anchors: ${missing.join(", ")}`);
}

console.log(
  `Visual region contract passed: ${regionIds.size - conditionalOnly.size} anchored regions and ${conditionalOnly.size} protected conditional states.`,
);
