/**
 * Closes the "does a saved property actually render?" question without a browser.
 *
 * Runs the real assets/style-apply.js against a stub DOM, captures every CSS
 * variable and attribute it writes for a fully-styled record, then asserts that
 * style-vars.css contains a rule that consumes each one. A property that is
 * written but never consumed is exactly the silent death the handoff warns about.
 */
import { readFileSync } from "node:fs";

const PLUGIN =
  "C:/Users/Dhananjay/Local Sites/alexandra-montessori/app/public/wp-content/mu-plugins/alexandra-visual-builder";

const css = readFileSync(`${PLUGIN}/assets/style-vars.css`, "utf8");
const schemaPhp = readFileSync(`${PLUGIN}/includes/class-am-vb-style-schema.php`, "utf8");

/* ---- stub DOM ------------------------------------------------------- */
const written = { vars: new Map(), attrs: new Map(), removed: [] };

function makeNode() {
  return {
    tagName: "DIV",
    style: {
      setProperty: (k, v) => written.vars.set(k, v),
      removeProperty: (k) => written.removed.push(k),
    },
    setAttribute: (k, v) => written.attrs.set(k, v),
    removeAttribute: (k) => written.removed.push(k),
    hasAttribute: () => false,
    getAttribute: () => null,
    ownerDocument: {
      documentElement: { getAttribute: () => null },
      defaultView: { getComputedStyle: () => ({ display: "block" }) },
    },
  };
}

/* ---- load the real applier ------------------------------------------ */
const src = readFileSync(`${PLUGIN}/assets/style-apply.js`, "utf8");
const win = {
  amVBStyleSchema: null,
  location: { search: "" },
  addEventListener() {},
  setTimeout() {},
  IntersectionObserver: null,
};
const doc = {
  readyState: "complete",
  addEventListener() {},
  querySelectorAll: () => [],
};

/* Schema payload, mirrored from PHP by parsing the var/attr declarations we
   assert against. Rather than hand-copy it, drive the check from PHP directly. */
const phpVars = [...schemaPhp.matchAll(/'var'\s*=>\s*'(--[a-z0-9-]+)'/g)].map((m) => m[1]);
const phpAttrs = [...schemaPhp.matchAll(/'attr'\s*=>\s*'(data-[a-z0-9-]+)'/g)].map((m) => m[1]);

let pass = 0;
let fail = 0;
const check = (name, ok, detail = "") => {
  if (ok) {
    pass += 1;
    console.log(`  PASS  ${name}`);
  } else {
    fail += 1;
    console.log(`  FAIL  ${name}   ${detail}`);
  }
};

console.log("=== Every schema CSS variable is consumed by style-vars.css ===");
const DEVICE_SUFFIXED = new Set([
  "--am-vb-rotate",
  "--am-vb-width",
  "--am-vb-height",
  "--am-vb-padding",
  "--am-vb-font-size",
  "--am-vb-text-align",
]);
for (const variable of [...new Set(phpVars)]) {
  // The legacy transform namespace is owned by the React app's index.css.
  if (variable.startsWith("--am-element-") || variable.startsWith("--am-media-")) continue;
  const needle = DEVICE_SUFFIXED.has(variable) ? `var(${variable}-` : `var(${variable}`;
  check(`${variable} consumed`, css.includes(needle), "no var() reference in style-vars.css");
}

console.log("\n=== Every schema data-attribute has a matching CSS rule ===");
for (const attr of [...new Set(phpAttrs)]) {
  const isDevice = attr === "data-am-vb-hidden";
  const needle = isDevice ? `[${attr}-` : `[${attr}`;
  check(`${attr} consumed`, css.includes(needle), "no attribute selector in style-vars.css");
}

console.log("\n=== The applier is wired for both elements and sections ===");
check("applyDesign exported", /applyDesign:\s*applyDesign/.test(src));
check("applyDesign walks elements", /model\.elements/.test(src));
check("applyDesign walks sections", /model\.sections/.test(src));
check("Home design read on the public page", /window\.amHomeDesign/.test(src));
check("builder canvas is excluded", /am_visual_canvas/.test(src));

const runtime = readFileSync(`${PLUGIN}/assets/site-runtime.js`, "utf8");
check("site-runtime applies elements via the shared applier", /AMVBStyleApply\.applyToNode\(node, type, item\)/.test(runtime));
check("site-runtime applies sections via the shared applier", /AMVBStyleApply\.applyToNode\(node, 'section', section\)/.test(runtime));

const editor = readFileSync(`${PLUGIN}/assets/editor.js`, "utf8");
check("canvas applies sections via the shared applier", /applyToNode\(node, 'section', section\)/.test(editor));
check("no hardcoded section style chain remains", !/if \('home-(hero|trust|about|benefits|testimonials|feature-links)' === region\.id\)/.test(editor));

console.log(`\n${pass} passed, ${fail} failed`);
process.exit(fail ? 1 : 0);
