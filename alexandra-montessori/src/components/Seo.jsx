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

export default function Seo({
  title,
  description,
  path = "",
  image = "/assets/organisation/teacher-hug.webp",
}) {
  const fullTitle = title
    ? `${title} - Alexandra Montessori`
    : "Alexandra Montessori - Quality Montessori Childcare in London";
  const url = `${SITE}${path}`;
  const img = resolveImageUrl(image);
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
      <meta property="og:image" content={img} />
      <meta name="twitter:card" content="summary_large_image" />
    </>
  );
}
