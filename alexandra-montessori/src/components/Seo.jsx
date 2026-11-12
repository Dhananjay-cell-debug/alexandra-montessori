// Per-page SEO. React 19 hoists <title>/<meta>/<link> rendered anywhere in the
// tree up into <head>, so no helmet dependency is needed.
import { asset } from "../lib/asset";

const SITE = "https://alexandramontessori.co.uk";

function resolveImageUrl(image) {
  if (/^(?:https?:)?\/\//i.test(image)) return image;
  // Base-correct the path first (WordPress build serves assets from the theme
  // dir, not the site root), then make it an absolute URL for og:image.
  return `${SITE}${asset(image)}`;
}

// wp-admin's "Page SEO" screen (alexandra-seo.php) resolves each static
// route's title/description (its coded default, or the client's saved
// override) and injects the result as window.amSeoRoutes before this app
// mounts. Reading it here — instead of only trusting the hardcoded props
// below — is what makes that screen's edits actually reach the page: without
// this, React always wins the render race against the server's <title>/
// <meta description> tags (it renders after them), so a wp-admin edit would
// be visible in "view source" but never in the browser tab or to Google.
function resolvedRoute(path) {
  if (typeof window === "undefined" || !window.amSeoRoutes) return null;
  return window.amSeoRoutes[path] || null;
}

export default function Seo({
  title,
  description: descriptionProp,
  path = "",
  image = "/assets/organisation/teacher-hug.webp",
}) {
  const resolved = resolvedRoute(path);
  const fullTitle =
    resolved?.title ||
    (title
      ? `${title} - Alexandra Montessori`
      : "Alexandra Montessori - Quality Montessori Childcare in London");
  const description = resolved?.description || descriptionProp;
  const url = `${SITE}${path}`;
  const images = (
    Array.isArray(resolved?.images) && resolved.images.length
      ? resolved.images
      : [resolved?.image || image]
  ).map(resolveImageUrl);
  return (
    <>
      <title>{fullTitle}</title>
      {description && <meta name="description" content={description} />}
      <link rel="canonical" href={url} />
      <meta property="og:type" content="website" />
      <meta property="og:site_name" content="Alexandra Montessori" />
      <meta property="og:title" content={fullTitle} />
      {description && <meta property="og:description" content={description} />}
      <meta property="og:url" content={url} />
      {images.map((img) => (
        <meta key={img} property="og:image" content={img} />
      ))}
      <meta name="twitter:card" content="summary_large_image" />
      <meta name="twitter:image" content={images[0]} />
    </>
  );
}
