export const hasWordPressDataContract =
  typeof window !== "undefined" &&
  window.amData &&
  Number(window.amData.schemaVersion) > 0;

export function cmsCollection(key, previewFallback = []) {
  if (!hasWordPressDataContract) return previewFallback;
  return Array.isArray(window.amData?.[key]) ? window.amData[key] : [];
}

export const testimonialApiUrl =
  hasWordPressDataContract &&
  typeof window.amData?.testimonialArchive?.apiUrl === "string"
    ? window.amData.testimonialArchive.apiUrl
    : "";

export async function fetchTestimonials({
  page = 1,
  perPage = 9,
  location = "",
  signal,
} = {}) {
  if (!testimonialApiUrl) {
    throw new Error("Testimonials API is not available.");
  }

  const url = new URL(testimonialApiUrl, window.location.origin);
  url.searchParams.set("page", String(page));
  url.searchParams.set("per_page", String(perPage));
  if (location) url.searchParams.set("location", location);

  const response = await fetch(url, {
    headers: { Accept: "application/json" },
    signal,
  });
  if (!response.ok) {
    throw new Error("Testimonials could not be loaded.");
  }

  const payload = await response.json();
  return {
    items: Array.isArray(payload?.items) ? payload.items : [],
    page: Number(payload?.page) || 1,
    pages: Math.max(1, Number(payload?.pages) || 1),
    total: Math.max(0, Number(payload?.total) || 0),
  };
}
