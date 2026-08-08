import { startTransition, useEffect, useState } from "react";
import { Link, useParams } from "react-router-dom";
import { ArrowLeft, ArrowUpRight, CalendarDays } from "lucide-react";
import Img from "../components/Img";
import Reveal from "../components/Reveal";
import Seo from "../components/Seo";
import CTA from "../components/CTA";
import { blogBySlug, fetchBlogArticle } from "../data/blogSource";

function formatDate(iso) {
  return new Date(iso).toLocaleDateString("en-GB", {
    day: "numeric",
    month: "long",
    year: "numeric",
  });
}

function stripHtml(html = "") {
  return html
    .replace(/<[^>]+>/g, " ")
    .replace(/\s+([.,!?;:])/g, "$1")
    .replace(/\s+/g, " ")
    .trim();
}

function getArticleIntro(post) {
  const excerpt = post.excerpt?.replace(/\s+/g, " ").trim() ?? "";
  const excerptLooksComplete =
    excerpt.length > 0 &&
    !/(?:\.{3}|\u2026)$/.test(excerpt) &&
    /[.!?]["')\]]?$/.test(excerpt);

  if (excerptLooksComplete) {
    return excerpt;
  }

  const paragraphs = Array.from(
    post.content.matchAll(/<p\b[^>]*>([\s\S]*?)<\/p>/gi),
    ([, paragraph]) => stripHtml(paragraph),
  ).filter(Boolean);

  if (paragraphs.length === 0) {
    return excerpt;
  }

  const joined = paragraphs.slice(0, 2).join(" ").trim();
  const sentenceBoundary = joined.lastIndexOf(". ", 280);

  if (joined.length <= 280 && /[.!?]["')\]]?$/.test(joined)) {
    return joined;
  }

  if (sentenceBoundary > 0) {
    return joined.slice(0, sentenceBoundary + 1).trim();
  }

  return paragraphs[0];
}

export default function BlogArticle() {
  const { slug } = useParams();
  const safeSlug = slug ?? "";
  const cachedPost = blogBySlug(safeSlug);
  const [reloadToken, setReloadToken] = useState(0);
  const requestKey = `${safeSlug}:${reloadToken}`;
  const [request, setRequest] = useState(() => ({
    key: requestKey,
    data: cachedPost ? { post: cachedPost, related: [] } : null,
    status: cachedPost ? "ready" : "loading",
  }));

  useEffect(() => {
    const controller = new AbortController();
    fetchBlogArticle(safeSlug, controller.signal)
      .then((nextResult) => {
        startTransition(() => {
          setRequest({ key: requestKey, data: nextResult, status: "ready" });
        });
      })
      .catch((error) => {
        if (error.name !== "AbortError") {
          setRequest({ key: requestKey, data: null, status: "error" });
        }
      });
    return () => controller.abort();
  }, [reloadToken, requestKey, safeSlug]);

  const currentRequest = request.key === requestKey ? request : null;
  const result = currentRequest?.data ?? null;
  const loadState = currentRequest?.status ?? "loading";
  const post = result?.post;

  if (!post && loadState === "loading") {
    return (
      <section className="container-wide py-16 sm:py-20" aria-live="polite" data-am-vb-region="blog-loading">
        <div className="card mx-auto max-w-3xl p-8 text-center sm:p-12">
          <p className="eyebrow justify-center">Blogs & Resources</p>
          <h1 className="mt-4 font-heading text-3xl font-medium text-sage-800 sm:text-4xl">
            Loading article
          </h1>
          <p className="mt-4 text-base leading-relaxed text-ink/85">
            Retrieving the latest published version from our website archive.
          </p>
        </div>
      </section>
    );
  }

  if (!post && loadState === "error") {
    return (
      <section className="container-wide py-16 sm:py-20" data-am-vb-region="blog-error">
        <div className="card mx-auto max-w-3xl p-8 text-center sm:p-12">
          <p className="eyebrow justify-center">Blogs & Resources</p>
          <h1 className="mt-4 font-heading text-3xl font-medium text-sage-800 sm:text-4xl">
            The article could not be loaded
          </h1>
          <p className="mt-4 text-base leading-relaxed text-ink/85">
            The archive is temporarily unavailable. Please try again.
          </p>
          <button
            type="button"
            onClick={() => setReloadToken((token) => token + 1)}
            className="btn-primary mt-8"
          >
            Try again
          </button>
        </div>
      </section>
    );
  }

  if (!post) {
    return (
      <>
        <Seo
          title="Article not found"
          description="The requested Alexandra Montessori article could not be found."
          path="/blogs"
        />
        <section className="container-wide py-16 sm:py-20" data-am-vb-region="blog-not-found">
          <div className="card mx-auto max-w-3xl p-8 text-center sm:p-12">
            <p className="eyebrow justify-center">Blogs & Resources</p>
            <h1 className="mt-4 font-heading text-3xl font-medium text-sage-800 sm:text-4xl">
              This article is no longer available
            </h1>
            <p className="mt-4 text-base leading-relaxed text-ink/85">
              The page you tried to open does not match one of our current
              parent articles.
            </p>
            <Link to="/blogs" className="btn-primary mt-8">
              Back to all articles
            </Link>
          </div>
        </section>
      </>
    );
  }

  const relatedPosts = result?.related ?? [];
  const articleIntro = getArticleIntro(post);

  return (
    <>
      <Seo
        title={post.title}
        description={articleIntro}
        path={`/blogs/${post.slug}`}
        image={post.image}
      />

      <section className="container-wide pb-10 pt-8 sm:pb-12 sm:pt-12" data-am-vb-region="blog-hero">
        <Link
          to="/blogs"
          className="inline-flex items-center gap-2 text-sm font-semibold text-sage-700 transition-colors hover:text-sage-900"
        >
          <ArrowLeft className="h-4 w-4" />
          Back to blogs
        </Link>

        <div className="mt-6 grid gap-8 lg:grid-cols-[1.05fr_0.95fr] lg:items-start">
          <div className="order-2 lg:order-1">
            <div className="card overflow-hidden p-7 sm:p-9">
              <div className="flex flex-wrap items-center gap-3 text-xs font-medium uppercase tracking-[0.18em] text-sage-600">
                <span className="rounded-full bg-sage-100 px-3 py-1">
                  {post.category}
                </span>
                <span className="inline-flex items-center gap-2 text-ink/75">
                  <CalendarDays className="h-3.5 w-3.5" />
                  {formatDate(post.date)}
                </span>
              </div>

              <h1 className="mt-5 text-balance font-heading text-3xl font-medium leading-tight text-sage-800 sm:text-4xl lg:text-[2.8rem]">
                {post.title}
              </h1>

              <p className="mt-5 max-w-2xl text-base leading-8 text-ink/85 sm:text-lg">
                {articleIntro}
              </p>
            </div>
          </div>

          <div className="order-1 lg:order-2">
            <Img
              src={post.image}
              alt={post.title}
              priority
              className="aspect-[16/11] w-full shadow-card"
            />
          </div>
        </div>
      </section>

      <section className="container-wide pb-8" data-am-vb-region="blog-content">
        <Reveal>
          <article className="card overflow-hidden p-7 sm:p-10 lg:p-12">
            <div
              className="blog-richtext mx-auto max-w-4xl"
              dangerouslySetInnerHTML={{ __html: post.content }}
            />
          </article>
        </Reveal>
      </section>

      <section className="container-wide py-8 sm:py-10" data-am-vb-region="blog-related">
        <div className="flex items-end justify-between gap-4">
          <div>
            <p className="eyebrow">More to read</p>
            <h2 className="mt-3 font-heading text-2xl font-medium text-sage-800 sm:text-3xl">
              More parent articles
            </h2>
          </div>
          <Link
            to="/blogs"
            className="hidden text-sm font-semibold text-sage-700 underline underline-offset-4 hover:text-sage-900 sm:inline"
          >
            View all
          </Link>
        </div>

        <div className="mt-8 grid gap-6 md:grid-cols-3">
          {relatedPosts.map((entry, index) => (
            <Reveal key={entry.slug} delay={index * 80}>
              <Link
                to={`/blogs/${entry.slug}`}
                className="card card-hover group flex h-full flex-col overflow-hidden"
              >
                <Img
                  src={entry.image}
                  alt={entry.title}
                  rounded="rounded-none"
                  className="aspect-[16/10] w-full"
                />
                <div className="flex flex-1 flex-col p-6">
                  <span className="text-xs font-medium uppercase tracking-[0.18em] text-sage-600">
                    {formatDate(entry.date)}
                  </span>
                  <h3 className="mt-3 font-heading text-xl font-medium text-ink">
                    {entry.title}
                  </h3>
                  <p className="mt-3 flex-1 text-sm leading-7 text-ink/85">
                    {entry.excerpt}
                  </p>
                  <span className="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-sage-700">
                    Read article
                    <ArrowUpRight className="h-4 w-4 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5" />
                  </span>
                </div>
              </Link>
            </Reveal>
          ))}
        </div>
      </section>

      <CTA
        region="blog-closing"
        title="Need help with a nursery place or funded childcare?"
        text="Our team can talk you through availability, funding and the right nursery for your child."
      />
    </>
  );
}
