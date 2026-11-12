/**
 * The single place the app resolves editable content.
 *
 * Pages import from here rather than from `data/site` so that one component
 * renders both outputs: the standalone static preview reads the approved
 * baseline, and the WordPress build reads `window.ldrnData`. WordPress stores
 * each record in its own shape, so every collection is adapted back to the
 * shape the components already expect — that adaptation is what keeps the
 * page code free of `if (wordpress)` branches.
 *
 * Fixed editorial copy (page headings, form labels, the EYFS narrative) stays
 * in `data/site` and is imported directly; only records an editor can create,
 * edit or unpublish flow through here.
 */
import {
  allSettings as staticSettings,
  events as staticEvents,
  foodHygiene as staticFoodHygiene,
  testimonials as staticTestimonials,
  brand as staticBrand,
  footer as staticFooter,
} from "../data/site";
import { cmsCollection, cmsObject, hasWordPressDataContract } from "./cms";

/** Splits a stored "line one, line two, town" address into display lines. */
function toAddressLines(address, postcode) {
  const lines = String(address || "")
    .split(/\r?\n|,/)
    .map((line) => line.trim())
    .filter(Boolean);
  const code = String(postcode || "").trim();
  if (code && !lines.includes(code)) lines.push(code);
  return lines;
}

/**
 * WordPress Setting record to the shape the setting pages render.
 *
 * A record with no confirmed Ofsted grade is marked ineligible for structured
 * data, so an unverified setting can be published on the site without the
 * schema claiming a rating it does not hold.
 */
function adaptSetting(record) {
  const hasOfsted = !!String(record.ofstedRating || "").trim();

  return {
    slug: record.id,
    name: record.name,
    strapline: record.heroTagline || record.subheading || "",
    status: hasOfsted ? "Open" : "Availability unverified",
    active: true,
    address: toAddressLines(record.address, record.postcode),
    phone: record.phone || "",
    email: record.email || "",
    hours: record.hours || "",
    ages: record.ageRange || "",
    image: record.image || "",
    imageAlt: record.name ? `Little Rainbows ${record.name}` : "",
    published: true,
    structuredDataEligible: hasOfsted,
    ofsted: hasOfsted
      ? {
          grade: record.ofstedRating,
          date: record.ofstedLabel || "",
          urn: "",
          providerUrl: record.ofstedUrl || "",
        }
      : undefined,
    // Kept so the detail page can use editor-supplied media and copy.
    gallery: Array.isArray(record.gallery) ? record.gallery : [],
    short: record.short || "",
    welcome: record.welcome || "",
  };
}

function adaptEvent(record) {
  return {
    slug: record.id,
    title: record.title,
    date: record.date || "",
    time: record.time || "",
    location: record.location || "",
    image: record.image || "",
    excerpt: record.excerpt || "",
    description: record.description || "",
  };
}

/**
 * A testimonial is stored as one quote field; the cards render an array of
 * paragraphs, so blank lines become paragraph breaks.
 */
function adaptTestimonial(record) {
  const body = String(record.quote || "")
    .split(/\r?\n\s*\r?\n/)
    .map((paragraph) => paragraph.trim())
    .filter(Boolean);

  return {
    id: record.id,
    body: body.length ? body : [String(record.quote || "")],
    attribution: record.name || record.title || "",
    location: record.location || "",
  };
}

/** Food hygiene ratings live on the Setting record that holds them. */
function adaptFoodHygiene(record) {
  return {
    name: record.name,
    rating: record.hygieneRating,
    ratingDate: record.hygieneDate || "",
    authority: record.hygieneAuthority || "",
    address: toAddressLines(record.address, record.postcode).join(", "),
    href: record.hygieneUrl || "",
  };
}

const rawSettings = cmsCollection("settings");

export const settings = hasWordPressDataContract
  ? rawSettings.map(adaptSetting)
  : staticSettings;

export const allSettings = settings;

export const events = hasWordPressDataContract
  ? cmsCollection("events").map(adaptEvent)
  : staticEvents;

export const testimonials = hasWordPressDataContract
  ? cmsCollection("testimonials").map(adaptTestimonial)
  : staticTestimonials;

/** Only settings that actually carry a rating appear on the ratings page. */
export const foodHygiene = hasWordPressDataContract
  ? rawSettings
      .filter((record) => String(record.hygieneRating || "").trim())
      .map(adaptFoodHygiene)
  : staticFoodHygiene;

/** Open vacancies. Empty until an editor publishes one. */
export const jobs = cmsCollection("jobs");

/** Published articles. Empty until an editor publishes one. */
const archive = cmsObject("blogArchive", {});
export const blogPosts = hasWordPressDataContract
  ? [archive.featured, ...(archive.items ?? [])].filter(Boolean).filter((post, index, list) => list.findIndex((item) => (item.slug || item.id) === (post.slug || post.id)) === index)
  : [];

export function mediaIdentity(url) {
  let hash = 2166136261;
  for (const char of String(url)) hash = Math.imul(hash ^ char.charCodeAt(0), 16777619);
  return (hash >>> 0).toString(36);
}

// Global identity. WordPress values win where an administrator has set one.
const site = cmsObject("site", {});

export const brand = {
  ...staticBrand,
  email: site.email || staticBrand.email,
  phone: {
    ...staticBrand.phone,
    general: site.phone || staticBrand.phone.general,
  },
};

export const footer = {
  ...staticFooter,
  hoursLine: site.hours || staticFooter.hoursLine,
  address: String(site.address || "").trim(),
  socials: (Array.isArray(site.socials) ? site.socials : []).filter(
    (social) => social && String(social.href || "").trim(),
  ),
};

/**
 * Homepage "About us" section (Website Content -> About us). A blank field
 * comes back empty so the homepage keeps its built-in copy for that field.
 */
const cmsAbout = cmsObject("about", {});
export const about = {
  heading: String(cmsAbout.heading || "").trim(),
  paragraphs: Array.isArray(cmsAbout.paragraphs) ? cmsAbout.paragraphs : [],
  image: String(cmsAbout.image || "").trim(),
  imageSrcSet: String(cmsAbout.imageSrcSet || "").trim(),
  imageSizes: String(cmsAbout.imageSizes || "").trim(),
  imageAlt: String(cmsAbout.imageAlt || "").trim(),
  milestones: Array.isArray(cmsAbout.milestones) ? cmsAbout.milestones : [],
  upcoming: String(cmsAbout.upcoming || "").trim(),
};
