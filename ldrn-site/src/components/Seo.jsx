// Per-page SEO. React 19 hoists <title>/<meta>/<link> rendered anywhere in
// the tree into <head>, so no helmet dependency is needed.
//
// Titles and descriptions come from the route map in data/site.js, which the
// WordPress SEO plugin mirrors server-side. Keeping both in step is what lets
// a crawler that never runs JavaScript see the same metadata as a browser.
import { useEffect } from "react";
import { seoRoutes } from "../data/site";
import { brand } from "../lib/content";
import { asset } from "../lib/asset";
import { isTbc } from "../lib/tbc";
import { safeJsonLd } from "../lib/safeJsonLd";
import { SITE_URL } from "../lib/schema";

const DEFAULT_IMAGE = "/assets/logo/logo-primary-navy-wordmark.png";

function resolveImageUrl(image) {
  if (/^(?:https?:)?\/\//i.test(image)) return image;
  // Base-correct first (the WordPress build serves assets from the theme
  // directory, not the site root), then make it absolute for og:image.
  return `${SITE_URL}${asset(image)}`;
}

export default function Seo({
  path = "/",
  title,
  description,
  image = DEFAULT_IMAGE,
  schema,
  noindex = false,
}) {
  const route = seoRoutes[path] || {};
  const managed = typeof window !== "undefined" ? window.ldrnData?.seo?.[path] : null;
  const resolvedTitle = managed?.title || title || route.title || brand.name;
  const candidate = managed?.description ?? description ?? route.description;
  // An unconfirmed description must not be published as a meta tag.
  const resolvedDescription = isTbc(candidate) ? null : candidate;
  const url = managed?.canonical || `${SITE_URL}${path === "/" ? "" : path}`;
  const hideFromSearch = managed ? !managed.index : noindex;
  const resolvedImage = managed?.image || image;

  useEffect(() => {
    if (!managed) return;
    document.title = managed.title;
    const setTag = (selector, attribute, value, tag, attributes) => {
      const matches = [...document.head.querySelectorAll(selector)];
      const element = matches[0] || document.createElement(tag);
      for (const [key, val] of Object.entries(attributes)) element.setAttribute(key, val);
      element.setAttribute(attribute, value);
      if (!element.parentNode) document.head.appendChild(element);
      for (const duplicate of matches.slice(1)) duplicate.remove();
    };
    setTag('link[rel="canonical"]', "href", managed.canonical, "link", { rel: "canonical" });
    const tags = {
      description: managed.description,
      robots: managed.index ? "index,follow,max-image-preview:large" : "noindex,follow",
      "twitter:card": "summary_large_image",
      "twitter:title": managed.title,
      "twitter:description": managed.description,
      "twitter:image": managed.image,
    };
    for (const [name, value] of Object.entries(tags)) {
      setTag(`meta[name="${name}"]`, "content", value, "meta", { name });
    }
    const social = {
      "og:type": managed.type,
      "og:site_name": managed.siteName,
      "og:locale": "en_GB",
      "og:title": managed.title,
      "og:description": managed.description,
      "og:url": managed.canonical,
      "og:image": managed.image,
    };
    for (const [property, value] of Object.entries(social)) {
      setTag(`meta[property="${property}"]`, "content", value, "meta", { property });
    }
    const serverGraph = document.getElementById("ldrnseo-schema");
    if (serverGraph && managed.schema) serverGraph.textContent = safeJsonLd(managed.schema);
  }, [managed]);

  // WordPress owns these head nodes; update them on navigation without adding copies.
  if (managed) return null;

  return (
    <>
      <title>{resolvedTitle}</title>
      {resolvedDescription && (
        <meta name="description" content={resolvedDescription} />
      )}
      <link rel="canonical" href={url} />
      {hideFromSearch && <meta name="robots" content="noindex,follow" />}
      <meta property="og:type" content={managed?.type || "website"} />
      <meta property="og:site_name" content={managed?.siteName || brand.name} />
      <meta property="og:locale" content="en_GB" />
      <meta property="og:title" content={resolvedTitle} />
      {resolvedDescription && (
        <meta property="og:description" content={resolvedDescription} />
      )}
      <meta property="og:url" content={url} />
      <meta property="og:image" content={resolveImageUrl(resolvedImage)} />
      <meta name="twitter:card" content="summary_large_image" />
      {managed && <meta name="twitter:title" content={resolvedTitle} />}
      {managed && resolvedDescription && <meta name="twitter:description" content={resolvedDescription} />}
      {managed && <meta name="twitter:image" content={resolveImageUrl(resolvedImage)} />}
      {!managed && schema && (
        <script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: safeJsonLd(schema) }}
        />
      )}
    </>
  );
}
