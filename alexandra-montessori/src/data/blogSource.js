import { blogs as generatedBlogs } from "./blogs.generated";

const cms = typeof window !== "undefined" ? window.amData : null;
const cmsBlogs = Array.isArray(cms?.blogs)
  ? cms.blogs.filter((post) => post?.slug && post?.title)
  : [];

export const blogs = cmsBlogs.length ? cmsBlogs : generatedBlogs;

export const blogBySlug = (slug) =>
  blogs.find((post) => post.slug === slug && post.content);

function buildFilters(posts) {
  const years = new Map();
  posts.forEach((post) => {
    const date = new Date(`${post.date}T12:00:00`);
    const year = date.getFullYear();
    const month = date.getMonth() + 1;
    if (!years.has(year)) years.set(year, new Set());
    years.get(year).add(month);
  });
  return Array.from(years.entries())
    .sort(([left], [right]) => right - left)
    .map(([year, months]) => ({
      year,
      months: Array.from(months).sort((left, right) => right - left),
    }));
}

function localArchive({ year = 0, month = 0, page = 1 } = {}) {
  const [featured = null, ...archivePosts] = blogs;
  const filtered = archivePosts.filter((post) => {
    const date = new Date(`${post.date}T12:00:00`);
    if (year && date.getFullYear() !== year) return false;
    if (month && date.getMonth() + 1 !== month) return false;
    return true;
  });
  const perPage = 9;
  const totalPages = Math.max(1, Math.ceil(filtered.length / perPage));
  const safePage = Math.min(Math.max(page, 1), totalPages);
  return {
    featured,
    items: filtered.slice((safePage - 1) * perPage, safePage * perPage),
    total: filtered.length,
    page: safePage,
    perPage,
    totalPages,
    filters: buildFilters(archivePosts),
  };
}

export function getInitialBlogArchive() {
  const initial = cms?.blogArchive;
  return initial?.items && Array.isArray(initial.filters)
    ? initial
    : localArchive();
}

async function readJson(response) {
  if (!response.ok) {
    const error = new Error(`Blog request failed with status ${response.status}`);
    error.status = response.status;
    throw error;
  }
  return response.json();
}

export async function fetchBlogArchive(
  { year = 0, month = 0, page = 1 } = {},
  signal,
) {
  if (!cms?.blogApiUrl) return localArchive({ year, month, page });

  const url = new URL(cms.blogApiUrl, window.location.origin);
  if (year) url.searchParams.set("year", String(year));
  if (month) url.searchParams.set("month", String(month));
  if (page > 1) url.searchParams.set("page", String(page));
  return readJson(await fetch(url, { signal, credentials: "same-origin" }));
}

export async function fetchBlogArticle(slug, signal) {
  if (!cms?.blogApiUrl) {
    const post = blogBySlug(slug);
    return post
      ? {
          post,
          related: blogs.filter((entry) => entry.slug !== slug).slice(0, 3),
        }
      : null;
  }

  const url = `${cms.blogApiUrl.replace(/\/$/, "")}/${encodeURIComponent(slug)}`;
  try {
    return await readJson(
      await fetch(url, { signal, credentials: "same-origin" }),
    );
  } catch (error) {
    if (error.status === 404) return null;
    throw error;
  }
}
