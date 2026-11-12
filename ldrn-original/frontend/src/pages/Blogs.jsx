import { Link } from "react-router-dom";
import { useEffect, useState } from "react";
import Seo from "../components/Seo";
import Reveal from "../components/Reveal";
import Img from "../components/Img";
import PageHeader from "../components/PageHeader";
import { blog } from "../data/site";
import { blogPosts } from "../lib/content";
import { workspaceRequest } from "../lib/workspace-request";

function formatDate(date) {
  if (!date) return "";
  return new Date(`${date}T12:00:00`).toLocaleDateString("en-GB", {
    day: "numeric",
    month: "long",
    year: "numeric",
  });
}

/**
 * Articles are WordPress posts. Nothing is published yet, so the page shows
 * its empty state; publishing an article makes it appear here without a code
 * change.
 */
export default function Blogs() {
  const [page, setPage] = useState(1);
  const [archive, setArchive] = useState(null);
  const [retry, setRetry] = useState(0);
  useEffect(() => {
    const api = window.ldrnData?.blogApiUrl;
    if (!api) return;
    const controller = new AbortController();
    workspaceRequest(`${api}?page=${page}`, { signal: controller.signal })
      .then((response) => response.ok ? response.json() : Promise.reject(new Error("Could not load articles.")))
      .then((result) => setArchive({ page, result }))
      .catch((error) => { if (error.name !== "AbortError") setArchive({ page, error: error.message }); });
    return () => controller.abort();
  }, [page, retry]);
  const current = archive?.page === page ? archive : null;
  const posts = current?.result ? [current.result.featured, ...current.result.items].filter(Boolean).filter((post, index, list) => list.findIndex((other) => other.slug === post.slug) === index) : page === 1 ? blogPosts : [];
  return (
    <>
      <Seo path="/blogs" />
      <PageHeader title={blog.title} />

      <section data-ldrn-ws="blogs.section-1" className="bg-white pb-20 pt-4">
        <div className="container-wide">
          {posts.length > 0 ? (
            <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
              {posts.map((post, index) => (
                <Reveal
                  key={post.slug}
                  delay={(index % 3) * 80}
                  className="card flex h-full flex-col overflow-hidden"
                  data-ldrn-ws={`article-card.${post.slug}`}
                >
                  <Img
                    src={post.image}
                    alt={post.title}
                    shape="square"
                    className="aspect-[16/10] w-full"
                  />
                  <div className="flex flex-1 flex-col p-6">
                    <p className="text-xs font-semibold uppercase tracking-wide text-sky-700">
                      {formatDate(post.date)}
                    </p>
                    <h2 className="mt-2 text-lg"><Link to={`/blogs/${post.slug}`}>{post.title}</Link></h2>
                    {post.excerpt && (
                      <p className="mt-3 flex-1 text-sm leading-relaxed text-ink/85">
                        {post.excerpt}
                      </p>
                    )}
                    <Link to={`/blogs/${post.slug}`} className="mt-4 font-bold">Read article →</Link>
                  </div>
                </Reveal>
              ))}
            </div>
          ) : page > 1 && !current ? <p role="status">Loading articles…</p> : (
            <Reveal className="mx-auto max-w-3xl rounded-4xl border border-sky-100 bg-sky-50 px-6 py-12 text-center shadow-soft sm:px-10">
              <p className="text-base font-semibold text-ink/80">
                {blog.emptyMessage}
              </p>
            </Reveal>
          )}
          {current?.error && <p role="status" className="mt-6">{current.error} <button type="button" onClick={() => setRetry((value) => value + 1)}>Try again</button></p>}
          {current?.result?.totalPages > 1 && <nav aria-label="Article pages" className="mt-8 flex items-center justify-center gap-6">
            <button type="button" className="btn-outline" disabled={page <= 1} onClick={() => setPage((value) => value - 1)}>Previous</button>
            <span>Page {current.result.page} of {current.result.totalPages}</span>
            <button type="button" className="btn-outline" disabled={page >= current.result.totalPages} onClick={() => setPage((value) => value + 1)}>Next</button>
          </nav>}
        </div>
      </section>
    </>
  );
}
