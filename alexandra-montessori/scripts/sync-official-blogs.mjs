import { writeFile } from "node:fs/promises";
import { dirname, resolve } from "node:path";
import { fileURLToPath } from "node:url";

const WP_POSTS_URL =
  "https://alexandramontessori.co.uk/wp-json/wp/v2/posts?per_page=20&_embed&_fields=id,date,slug,link,title,excerpt,content,_embedded";

const HERE = dirname(fileURLToPath(import.meta.url));
const OUTPUT_FILE = resolve(HERE, "../src/data/blogs.generated.js");

const HTML_ENTITY_MAP = new Map([
  ["&amp;", "&"],
  ["&#038;", "&"],
  ["&quot;", '"'],
  ["&#8220;", '"'],
  ["&#8221;", '"'],
  ["&#8216;", "'"],
  ["&#8217;", "'"],
  ["&#039;", "'"],
  ["&apos;", "'"],
  ["&#8211;", "-"],
  ["&#8212;", "-"],
  ["&ndash;", "-"],
  ["&mdash;", "-"],
  ["&#8230;", "..."],
  ["&hellip;", "..."],
  ["&nbsp;", " "],
  ["&#160;", " "],
  ["&#8226;", "•"],
  ["&lt;", "<"],
  ["&gt;", ">"],
]);

function decodeHtmlEntities(input = "") {
  let output = input;

  for (const [entity, value] of HTML_ENTITY_MAP) {
    output = output.replaceAll(entity, value);
  }

  return output.replace(/&#(\d+);/g, (_, code) =>
    String.fromCodePoint(Number.parseInt(code, 10)),
  );
}

function normalizePunctuation(input = "") {
  return input
    .replaceAll("Â£", "GBP ")
    .replaceAll("£", "GBP ")
    .replaceAll("ðŸ‘‰", "")
    .replaceAll("â€™", "'")
    .replaceAll("â€œ", '"')
    .replaceAll("â€", '"')
    .replaceAll("â€“", "-")
    .replaceAll("â€”", "-")
    .replaceAll("â€¦", "...")
    .replaceAll("\u2018", "'")
    .replaceAll("\u2019", "'")
    .replaceAll("\u201c", '"')
    .replaceAll("\u201d", '"')
    .replaceAll("\u2013", "-")
    .replaceAll("\u2014", "-")
    .replaceAll("\u2026", "...")
    .replaceAll("\u00a0", " ");
}

function stripHtml(input = "") {
  return normalizeWhitespace(
    normalizePunctuation(
      decodeHtmlEntities(input).replace(/<br\s*\/?>/gi, " ").replace(/<[^>]+>/g, " "),
    ),
  );
}

function normalizeWhitespace(input = "") {
  return input.replace(/\s+/g, " ").trim();
}

function truncate(input, maxLength) {
  if (input.length <= maxLength) return input;

  const slice = input.slice(0, maxLength).trimEnd();
  const lastSpace = slice.lastIndexOf(" ");
  return `${(lastSpace > 80 ? slice.slice(0, lastSpace) : slice).trimEnd()}...`;
}

function sanitizeContentBlock(html = "") {
  let output = normalizePunctuation(decodeHtmlEntities(html));

  output = output
    .replace(/<\/?span[^>]*>/gi, "")
    .replace(/<\/?div[^>]*>/gi, "")
    .replace(/<b>/gi, "<strong>")
    .replace(/<\/b>/gi, "</strong>")
    .replace(/<i>/gi, "<em>")
    .replace(/<\/i>/gi, "</em>")
    .replace(/<a\b[^>]*href=(["'])(.*?)\1[^>]*>/gi, (_, __, href) => {
      return `<a href="${href}" target="_blank" rel="noreferrer">`;
    })
    .replace(/<(p|h2|h3|h4|ul|ol|li|strong|em)\b[^>]*>/gi, "<$1>")
    .replace(/<br\s*\/?>/gi, "<br />")
    .replace(/<(?!\/?(?:p|h2|h3|h4|ul|ol|li|strong|em|a|br)\b)[^>]+>/gi, "")
    .replace(/<p>\s*<\/p>/gi, "")
    .replace(/<h([234])>\s*<\/h\1>/gi, "")
    .trim();

  return output;
}

function extractContent(rendered = "") {
  const matches = [
    ...rendered.matchAll(
      /elementor-widget-text-editor[\s\S]*?<div class="elementor-widget-container">([\s\S]*?)<\/div>\s*<\/div>/gi,
    ),
  ];

  const blocks = matches
    .map((match) => sanitizeContentBlock(match[1]))
    .filter((block) => stripHtml(block) !== "");

  return blocks.join("\n\n");
}

function extractImage(post) {
  return (
    post?._embedded?.["wp:featuredmedia"]?.[0]?.source_url ??
    post.content?.rendered?.match(/<img[^>]+src=(["'])(.*?)\1/i)?.[2] ??
    ""
  );
}

function extractCategory(post) {
  return (
    post?._embedded?.["wp:term"]?.[0]?.[0]?.name ??
    "Resources"
  );
}

function extractExcerpt(post, content) {
  const excerpt = stripHtml(post.excerpt?.rendered ?? "");
  if (excerpt) return truncate(excerpt, 160);

  const firstParagraph =
    content.match(/<p>([\s\S]*?)<\/p>/i)?.[1] ?? stripHtml(content);
  return truncate(stripHtml(firstParagraph), 160);
}

function buildOutput(blogs) {
  return `// Generated from alexandramontessori.co.uk official WordPress posts.\n// Regenerate with: npm run sync:blogs\nexport const blogs = ${JSON.stringify(blogs, null, 2)};\n\nexport const blogBySlug = (slug) =>\n  blogs.find((post) => post.slug === slug);\n`;
}

async function main() {
  const res = await fetch(WP_POSTS_URL, {
    headers: { Accept: "application/json" },
  });

  if (!res.ok) {
    throw new Error(`Failed to fetch official blog posts: ${res.status}`);
  }

  const posts = await res.json();

  const blogs = posts
    .map((post) => {
      const content = extractContent(post.content?.rendered ?? "");
      return {
        slug: post.slug,
        title: stripHtml(post.title?.rendered ?? ""),
        date: post.date,
        category: extractCategory(post),
        image: extractImage(post),
        excerpt: extractExcerpt(post, content),
        sourceUrl: post.link,
        content,
      };
    })
    .filter((post) => post.title && post.content)
    .sort((a, b) => new Date(b.date) - new Date(a.date));

  await writeFile(OUTPUT_FILE, buildOutput(blogs), "utf8");
  console.log(`Wrote ${blogs.length} official blog posts to ${OUTPUT_FILE}`);
}

main().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});
