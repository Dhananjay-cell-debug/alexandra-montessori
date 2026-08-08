// Resolve a runtime asset path against Vite's configured base URL.
//
// Root-absolute asset paths in the source (e.g. '/assets/photos/x.webp') are
// plain runtime strings, so Vite's `base` rewriting never touches them. In dev
// `import.meta.env.BASE_URL` is '/', so these paths are returned unchanged. In
// the WordPress production build the base is the theme's /dist/ URL, so the
// helper rewrites them to load from the theme directory instead of the site root.
const BASE = import.meta.env.BASE_URL.replace(/\/+$/, "");

export function asset(path) {
  if (!path) return path;
  // Leave absolute URLs and inline data/blob URIs untouched.
  if (/^(?:https?:|data:|blob:|\/\/)/i.test(path)) return path;
  // Idempotent: don't prefix something that's already resolved.
  if (BASE && path.startsWith(BASE + "/")) return path;
  return BASE + (path.startsWith("/") ? path : "/" + path);
}
