// ---------------------------------------------------------------------------
// TEMPORARY Calendly wiring - Dhananjay's own free Calendly account.
//
// The free plan allows only ONE ACTIVE event type. All three "Book a Visit"
// events exist on the account, but only `book-a-visit-hounslow` is switched on;
// the other two return "This Calendly URL is not valid" for visitors. So every
// nursery points at the active event for now, tagged with utm_campaign so the
// booking record still shows which nursery the parent came from.
//
// WHEN THE CLIENT'S PAID CALENDLY IS READY: change CALENDLY_USER to their
// handle and give each nursery its own slug below. Nothing else needs touching.
// ---------------------------------------------------------------------------
const CALENDLY_USER = "dhananjaychitmila";
const ACTIVE_EVENT = "book-a-visit-hounslow";

export const CALENDLY_EVENTS = {
  hounslow: ACTIVE_EVENT,
  heston: ACTIVE_EVENT, // -> "book-a-visit-heston" once activated
  hammersmith: ACTIVE_EVENT, // -> "book-a-visit-hammersmith" once activated
};

// Build the scheduling URL for a nursery. UTM values are recorded against the
// Calendly event and show up in Meetings and the CSV export.
export function calendlyUrl(nursery) {
  const id = nursery?.id || "";
  const slug = CALENDLY_EVENTS[id] || ACTIVE_EVENT;
  const params = new URLSearchParams({
    hide_gdpr_banner: "1",
    utm_source: "website",
    utm_medium: "book-a-visit",
    utm_campaign: id || "unknown",
    utm_content: nursery?.name || "",
  });
  return `https://calendly.com/${CALENDLY_USER}/${slug}?${params.toString()}`;
}
