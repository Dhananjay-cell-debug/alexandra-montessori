import { startTransition, useEffect, useState } from "react";
import { Link, useSearchParams } from "react-router-dom";
import {
  ArrowLeft,
  ArrowRight,
  ArrowUpRight,
  CalendarDays,
} from "lucide-react";
import PageHeader from "../components/PageHeader";
import Reveal from "../components/Reveal";
import Img from "../components/Img";
import Seo from "../components/Seo";
import CTA from "../components/CTA";
import {
  fetchBlogArchive,
  getInitialBlogArchive,
} from "../data/blogSource";

const monthNames = [
  "January",
  "February",
  "March",
  "April",
  "May",
  "June",
  "July",
  "August",
  "September",
  "October",
  "November",
  "December",
];

function positiveInteger(value) {
  const parsed = Number.parseInt(value ?? "", 10);
  return Number.isInteger(parsed) && parsed > 0 ? parsed : 0;
}

function formatDate(iso) {
  return new Date(`${iso}T12:00:00`).toLocaleDateString("en-GB", {
    day: "numeric",
    month: "long",
    year: "numeric",
  });
}

function ArticleCard({ post, index }) {
  return (
    <Reveal delay={index * 70}>
      <Link
        to={`/blogs/${post.slug}`}
        className="card card-hover group flex h-full flex-col overflow-hidden"
      >
        <Img
          src={post.image}
          alt={post.title}
          rounded="rounded-none"
          className="aspect-[16/10] w-full"
        />
        <div className="flex flex-1 flex-col p-6">
          <div className="flex flex-wrap items-center gap-3 text-xs font-medium uppercase tracking-widest text-sage-600">
            <span className="rounded-full bg-sage-100 px-2.5 py-1">
              {post.category}
            </span>
            <span className="text-ink/65">{formatDate(post.date)}</span>
          </div>
          <h3 className="mt-3 font-heading text-lg font-medium text-ink">
            {post.title}
          </h3>
          <p className="mt-2 flex-1 text-sm leading-relaxed text-ink/90">
            {post.excerpt}
          </p>
          <span className="mt-4 inline-flex items-center gap-2 text-sm font-medium text-sage-600">
            Read more
            <ArrowUpRight className="h-4 w-4 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5" />
          </span>
        </div>
      </Link>
    </Reveal>
  );
}

export default function Blogs() {
  const [searchParams, setSearchParams] = useSearchParams();
  const year = positiveInteger(searchParams.get("year"));
  const month = year ? positiveInteger(searchParams.get("month")) : 0;
  const page = positiveInteger(searchParams.get("page")) || 1;
  const hasArchiveQuery = Boolean(year || month || page > 1);
  const [initialArchive] = useState(getInitialBlogArchive);
  const [reloadToken, setReloadToken] = useState(0);
  const requestKey = `${year}:${month}:${page}:${reloadToken}`;
  const [request, setRequest] = useState(() => ({
    key: "0:0:1:0",
    data: initialArchive,
    status: hasArchiveQuery ? "loading" : "ready",
  }));

  useEffect(() => {
    const controller = new AbortController();
    fetchBlogArchive({ year, month, page }, controller.signal)
      .then((result) => {
        startTransition(() => {
          setRequest({ key: requestKey, data: result, status: "ready" });
        });
      })
      .catch((error) => {
        if (error.name !== "AbortError") {
          setRequest({ key: requestKey, data: null, status: "error" });
        }
      });
    return () => controller.abort();
  }, [month, page, reloadToken, requestKey, year]);

  const currentRequest = request.key === requestKey ? request : null;
  const archive = currentRequest?.data ?? null;
  const loadState = currentRequest?.status ?? "loading";
  const filters = archive?.filters ?? initialArchive.filters;
  const selectedYear = filters.find((entry) => entry.year === year);
  const availableMonths = selectedYear?.months ?? [];
  const featured = archive?.featured ?? initialArchive.featured;
  const items = archive?.items ?? [];
  const total = archive?.total ?? 0;
  const firstResult = total ? (archive.page - 1) * archive.perPage + 1 : 0;
  const lastResult = total
    ? Math.min(archive.page * archive.perPage, total)
    : 0;
  function updateFilter(key, value) {
    const next = new URLSearchParams(searchParams);
    if (value) next.set(key, value);
    else next.delete(key);
    if (key === "year") next.delete("month");
    next.delete("page");
    setSearchParams(next);
  }

  function pageHref(nextPage) {
    const next = new URLSearchParams();
    if (year) next.set("year", String(year));
    if (month) next.set("month", String(month));
    if (nextPage > 1) next.set("page", String(nextPage));
    const query = next.toString();
    return query ? `/blogs?${query}` : "/blogs";
  }

  return (
    <>
      <Seo
        title="Blogs & Resources"
        description="Helpful reads for parents from Alexandra Montessori - settling in, the Montessori work cycle, nursery nutrition and a simple guide to funded childcare."
        path="/blogs"
      />
      <PageHeader
        region="blogs-header"
        crumb="Blogs & Resources"
        eyebrow="Parent information"
        title="Blogs & resources"
        intro="Friendly, practical reading to support you and your child - from settling in to understanding the Montessori approach and funded childcare."
      />

      <section
        className="border-b border-sage-100 bg-sage-50/60"
        aria-label="Blog archive filters"
        data-am-vb-region="blogs-filters"
      >
        <div className="container-wide flex flex-wrap gap-2 py-3">
          <select
            value={year || ""}
            onChange={(event) => updateFilter("year", event.target.value)}
            aria-label="Filter articles by year"
            className="min-w-32 rounded-xl border border-sage-200 bg-white px-3 py-2 text-sm text-ink shadow-sm outline-none focus:border-sage-500 focus:ring-2 focus:ring-sage-200"
          >
            <option value="">All years</option>
            {filters.map((entry) => (
              <option key={entry.year} value={entry.year}>
                {entry.year}
              </option>
            ))}
          </select>
          <select
            value={month || ""}
            disabled={!year}
            onChange={(event) => updateFilter("month", event.target.value)}
            aria-label="Filter articles by month"
            className="min-w-32 rounded-xl border border-sage-200 bg-white px-3 py-2 text-sm text-ink shadow-sm outline-none focus:border-sage-500 focus:ring-2 focus:ring-sage-200 disabled:cursor-not-allowed disabled:bg-sage-100 disabled:text-ink/45"
          >
            <option value="">All months</option>
            {availableMonths.map((monthNumber) => (
              <option key={monthNumber} value={monthNumber}>
                {monthNames[monthNumber - 1]}
              </option>
            ))}
          </select>
        </div>
      </section>

      {featured && (
        <section className="container-wide pb-8 pt-2" data-am-vb-region="blogs-featured">
          <p className="eyebrow mb-5">Featured article</p>
          <Reveal>
            <Link
              to={`/blogs/${featured.slug}`}
              className="card card-hover group grid items-center gap-6 overflow-hidden p-4 lg:grid-cols-2"
            >
              <Img
                src={featured.image}
                alt={featured.title}
                className="aspect-[16/10] w-full"
              />
              <div className="p-6 lg:p-8">
                <div className="flex flex-wrap items-center gap-3 text-xs font-medium uppercase tracking-widest text-sage-600">
                  <span className="rounded-full bg-sage-100 px-3 py-1">
                    {featured.category}
                  </span>
                  <span className="flex items-center gap-1.5 text-ink/85">
                    <CalendarDays className="h-3.5 w-3.5" />
                    {formatDate(featured.date)}
                  </span>
                </div>
                <h2 className="mt-4 text-balance font-heading text-2xl font-medium tracking-tight text-ink sm:text-3xl">
                  {featured.title}
                </h2>
                <p className="mt-3 text-pretty leading-relaxed text-ink/90">
                  {featured.excerpt}
                </p>
                <span className="mt-5 inline-flex items-center gap-2 text-sm font-medium text-sage-600">
                  Read more
                  <ArrowUpRight className="h-4 w-4 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5" />
                </span>
              </div>
            </Link>
          </Reveal>
        </section>
      )}

      <section
        className="container-wide py-10 sm:py-14"
        aria-busy={loadState === "loading"}
        data-am-vb-region="blogs-archive"
      >
        <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
          <h2 className="eyebrow">Article archive</h2>
          <p className="text-xs text-ink/60" aria-live="polite">
            {loadState === "loading" && !archive
              ? "Loading articles..."
              : total
                ? `Showing ${firstResult}-${lastResult} of ${total}`
                : "No matching articles"}
          </p>
        </div>
        {loadState === "error" && (
          <div className="card mx-auto max-w-2xl p-8 text-center">
            <h2 className="font-heading text-2xl font-medium text-sage-800">
              The archive could not be loaded
            </h2>
            <p className="mt-3 text-sm leading-relaxed text-ink/75">
              Your filters are still selected. Please try loading the articles
              again.
            </p>
            <button
              type="button"
              onClick={() => setReloadToken((token) => token + 1)}
              className="btn-secondary mt-6"
            >
              Try again
            </button>
          </div>
        )}

        {loadState !== "error" && items.length > 0 && (
          <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            {items.map((post, index) => (
              <ArticleCard key={post.slug} post={post} index={index} />
            ))}
          </div>
        )}

        {loadState === "ready" && items.length === 0 && (
          <div className="card mx-auto max-w-2xl p-8 text-center sm:p-10">
            <h2 className="font-heading text-2xl font-medium text-sage-800">
              No articles for this date
            </h2>
            <p className="mt-3 text-sm leading-relaxed text-ink/75">
              Choose another month or clear the date filters to see the full
              archive.
            </p>
            <Link to="/blogs" className="btn-secondary mt-6">
              View the full archive
            </Link>
          </div>
        )}

        {archive?.totalPages > 1 && loadState !== "error" && (
          <nav
            className="mt-10 flex items-center justify-center gap-3"
            aria-label="Blog archive pages"
          >
            {archive.page > 1 ? (
              <Link
                to={pageHref(archive.page - 1)}
                className="btn-secondary px-4 py-3"
                aria-label="Previous archive page"
              >
                <ArrowLeft className="h-4 w-4" />
                Previous
              </Link>
            ) : null}
            <span className="px-3 text-sm font-semibold text-sage-800">
              Page {archive.page} of {archive.totalPages}
            </span>
            {archive.page < archive.totalPages ? (
              <Link
                to={pageHref(archive.page + 1)}
                className="btn-secondary px-4 py-3"
                aria-label="Next archive page"
              >
                Next
                <ArrowRight className="h-4 w-4" />
              </Link>
            ) : null}
          </nav>
        )}
      </section>

      <CTA
        region="blogs-closing"
        title="Have a question we haven't answered?"
        text="We're always happy to help. Get in touch and a member of our team will get back to you."
      />
    </>
  );
}
