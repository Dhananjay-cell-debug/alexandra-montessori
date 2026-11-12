/**
 * Events may be authored in WordPress without an explicit slug, so the
 * archive and the detail route derive the same one from the title. Keeping
 * this in one place is what guarantees a card's link resolves to a record.
 */
export function eventSlug(event) {
  if (event.slug) return event.slug;
  return event.title
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/(^-|-$)/g, "");
}
