// Bridge between the WordPress content contract and the React app.
//
// The theme prints `window.ldrnData` before the bundle loads. When the site
// runs as a standalone static preview that object is absent, so every helper
// falls back to the approved baseline in data/site.js. That fallback is what
// lets the same source build both outputs.

export const hasWordPressDataContract =
  typeof window !== "undefined" &&
  !!window.ldrnData &&
  Number(window.ldrnData.schemaVersion) > 0;

/**
 * Returns a collection from WordPress, or the static fallback when the app is
 * running outside WordPress.
 *
 * When the contract *is* present, an empty collection is returned as empty
 * rather than falling back — an editor who unpublishes every event means the
 * archive should be empty, not repopulated with old hard-coded entries.
 */
export function cmsCollection(key, fallback = []) {
  if (!hasWordPressDataContract) return fallback;
  return Array.isArray(window.ldrnData?.[key]) ? window.ldrnData[key] : [];
}

/** Returns a single object from WordPress, merged over the static fallback. */
export function cmsObject(key, fallback = {}) {
  if (!hasWordPressDataContract) return fallback;
  const value = window.ldrnData?.[key];
  if (!value || typeof value !== "object" || Array.isArray(value)) {
    return fallback;
  }
  return { ...fallback, ...value };
}
