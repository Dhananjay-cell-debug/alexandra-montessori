// Stable URL slug from a title (used to link events without a CMS id).
export function slugify(value) {
  return String(value ?? "")
    .toLowerCase()
    .trim()
    .replace(/[^\w\s-]/g, "")
    .replace(/[\s_]+/g, "-")
    .replace(/-+/g, "-")
    .replace(/^-|-$/g, "");
}

// An event's identifier: its CMS slug/id when present, else a title slug.
export function eventSlug(ev) {
  return ev?.id || ev?.slug || slugify(ev?.title);
}
