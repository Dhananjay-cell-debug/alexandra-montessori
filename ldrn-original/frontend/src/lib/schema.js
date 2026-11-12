import { brand, settings } from "../data/site";
import { isTbc } from "./tbc";

/** Canonical origin for LDRN. Replace once the client confirms the domain. */
export const SITE_URL = typeof window !== "undefined" && window.ldrnData
  ? window.location.origin
  : "https://www.littlerainbowsdaynursery.com";

/**
 * Drops empty and unconfirmed values so a `tbc()` placeholder can never be
 * published inside structured data.
 */
const compact = (object) =>
  Object.fromEntries(
    Object.entries(object).filter(
      ([, value]) => value != null && value !== "" && !isTbc(value),
    ),
  );

/**
 * Parses a human opening-hours line such as "Monday – Friday, 8am – 6pm"
 * into an OpeningHoursSpecification. Anything that does not match is left
 * out rather than guessed.
 */
function openingHours(hours) {
  if (isTbc(hours) || !hours) return undefined;
  const match = hours.match(/(\d{1,2})\s*am\s*[–-]\s*(\d{1,2})\s*pm/i);
  if (!match) return undefined;
  return [
    {
      "@type": "OpeningHoursSpecification",
      dayOfWeek: ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"],
      opens: `${String(match[1]).padStart(2, "0")}:00`,
      closes: `${String(Number(match[2]) + 12).padStart(2, "0")}:00`,
    },
  ];
}

/** Address lines are stored as "street…, locality, postcode". */
function postalAddress(lines) {
  if (isTbc(lines) || !Array.isArray(lines)) return undefined;
  const parts = [...lines];
  const postalCode = parts.pop();
  const addressLocality = parts.pop();
  return compact({
    "@type": "PostalAddress",
    streetAddress: parts.join(", ") || undefined,
    addressLocality,
    postalCode,
    addressRegion: "London",
    addressCountry: "GB",
  });
}

export function settingSchema(setting) {
  return compact({
    "@context": "https://schema.org",
    "@type": ["ChildCare", "LocalBusiness"],
    "@id": `${SITE_URL}/our-nursery/${setting.slug}`,
    name: `${brand.name} — ${setting.name}`,
    url: `${SITE_URL}/our-nursery/${setting.slug}`,
    address: postalAddress(setting.address),
    telephone: isTbc(setting.phone) ? undefined : setting.phone,
    email: setting.email || brand.email,
    openingHoursSpecification: openingHours(setting.hours),
    parentOrganization: {
      "@type": "Organization",
      name: brand.name,
      url: SITE_URL,
    },
    hasCredential: isTbc(setting.ofsted)
      ? undefined
      : {
          "@type": "EducationalOccupationalCredential",
          credentialCategory: "Ofsted inspection judgement",
          name: `Ofsted: ${setting.ofsted.grade}`,
          recognizedBy: {
            "@type": "GovernmentOrganization",
            name: "Ofsted",
          },
          validFrom: setting.ofsted.date,
          identifier: setting.ofsted.urn,
        },
  });
}

export function organizationSchema() {
  return compact({
    "@context": "https://schema.org",
    "@type": "Organization",
    "@id": SITE_URL,
    name: brand.name,
    alternateName: brand.shortName,
    url: SITE_URL,
    slogan: brand.tagline,
    email: brand.email,
    telephone: brand.phone.general,
    location: settings
      .filter(
        (setting) =>
          setting.structuredDataEligible !== false && !isTbc(setting.address),
      )
      .map((setting) => ({
        "@type": "Place",
        name: setting.name,
        address: postalAddress(setting.address),
      })),
  });
}
