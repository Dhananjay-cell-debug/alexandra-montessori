// The app is served from the site root as a static build and from
// /wp-content/themes/ldrn-theme/dist/ inside WordPress. Root-relative asset
// paths in content therefore need the active base prefixed at runtime.
const base = (window.ldrnData?.snapshotAssetBase || import.meta.env.BASE_URL).replace(/\/+$/, "");

/**
 * Resolves a content asset path against the current build base.
 * Absolute URLs and data/blob URIs are returned untouched.
 */
export function asset(path) {
  if (!path) return path;
  if (window.ldrnData?.snapshotAssetMap?.[path]) return window.ldrnData.snapshotAssetMap[path];
  if (/^(?:https?:|data:|blob:|\/\/)/i.test(path)) return path;
  if (base && path.startsWith(`${base}/`)) return path;
  return base + (path.startsWith("/") ? path : `/${path}`);
}
