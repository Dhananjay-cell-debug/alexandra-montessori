import { useEffect, useMemo, useState } from "react";
import { Link, useSearchParams } from "react-router-dom";
import { ArrowLeft, Quote } from "lucide-react";
import PageHeader from "../components/PageHeader";
import Reveal from "../components/Reveal";
import Seo from "../components/Seo";
import CTA from "../components/CTA";
import { testimonials as staticTestimonials } from "../data/site";
import {
  fetchTestimonials,
  hasWordPressDataContract,
} from "../lib/cms";

const PAGE_SIZE = 9;

function pageNumber(value) {
  const parsed = Number.parseInt(value || "1", 10);
  return Number.isFinite(parsed) && parsed > 0 ? parsed : 1;
}

function TestimonialCard({ testimonial, index }) {
  return (
    <Reveal
      delay={(index % 3) * 70}
      className="card flex h-full flex-col p-7 sm:p-8"
    >
      <Quote className="h-8 w-8 text-sage-400" strokeWidth={1.6} />
      <h2 className="mt-5 font-heading text-xl font-medium text-sage-800">
        {testimonial.title}
      </h2>
      <p className="mt-4 flex-1 text-pretty leading-relaxed text-ink/90">
        {testimonial.quote}
      </p>
      <p className="mt-6 border-t border-sage-100 pt-4 text-sm font-medium text-sage-800">
        {testimonial.name}
        <span className="font-normal text-ink/80">
          {testimonial.location ? `, ${testimonial.location}` : ""}
        </span>
      </p>
    </Reveal>
  );
}

export default function Testimonials() {
  const [searchParams] = useSearchParams();
  const page = pageNumber(searchParams.get("page"));
  const previewResult = useMemo(() => {
    const start = (page - 1) * PAGE_SIZE;
    return {
      items: staticTestimonials.slice(start, start + PAGE_SIZE),
      page,
      pages: Math.max(1, Math.ceil(staticTestimonials.length / PAGE_SIZE)),
      total: staticTestimonials.length,
    };
  }, [page]);
  const [cmsResult, setCmsResult] = useState({
    items: [],
    page: 1,
    pages: 1,
    total: 0,
    requestedPage: 0,
  });
  const [cmsErrorPage, setCmsErrorPage] = useState(0);
  const result = hasWordPressDataContract ? cmsResult : previewResult;
  const state = !hasWordPressDataContract
    ? "ready"
    : cmsErrorPage === page
      ? "error"
      : cmsResult.requestedPage === page
        ? "ready"
        : "loading";

  useEffect(() => {
    if (!hasWordPressDataContract) return undefined;

    const controller = new AbortController();
    fetchTestimonials({
      page,
      perPage: PAGE_SIZE,
      signal: controller.signal,
    })
      .then((payload) => {
        setCmsResult({ ...payload, requestedPage: page });
        setCmsErrorPage(0);
      })
      .catch((error) => {
        if (error.name !== "AbortError") setCmsErrorPage(page);
      });
    return () => controller.abort();
  }, [page]);

  const hasPrevious = result.page > 1;
  const hasNext = result.page < result.pages;

  return (
    <>
      <Seo
        title="Parent Testimonials"
        description="Read what parents and carers say about Alexandra Montessori nurseries."
        path="/testimonials"
      />
      <PageHeader title="Parent Testimonials" />

      <section className="bg-white py-16 sm:py-20">
        <div className="container-wide">
          <Link
            to="/"
            className="mb-8 inline-flex items-center gap-2 font-body text-sm font-semibold text-sage-700 transition-colors hover:text-sage-800"
          >
            <ArrowLeft className="h-4 w-4" strokeWidth={2} />
            Back to home
          </Link>
          <Reveal className="mx-auto max-w-3xl text-center">
            <p className="text-lg leading-relaxed text-ink/90">
              Real experiences from families across our nursery community.
            </p>
          </Reveal>

          {state === "loading" ? (
            <p className="py-16 text-center text-sm text-ink/70">
              Loading parent stories…
            </p>
          ) : null}

          {state === "error" ? (
            <div className="mx-auto mt-10 max-w-xl rounded-3xl bg-sage-50 p-8 text-center">
              <h2 className="font-heading text-2xl font-medium text-sage-800">
                Parent stories are temporarily unavailable
              </h2>
              <p className="mt-3 text-sm leading-relaxed text-ink/85">
                Please try again shortly or contact us if you would like to
                speak with a nursery family.
              </p>
            </div>
          ) : null}

          {state === "ready" && result.items.length === 0 ? (
            <div className="mx-auto mt-10 max-w-xl rounded-3xl bg-sage-50 p-8 text-center">
              <h2 className="font-heading text-2xl font-medium text-sage-800">
                More parent stories are coming soon
              </h2>
              <p className="mt-3 text-sm leading-relaxed text-ink/85">
                In the meantime, please contact us to learn more about life at
                Alexandra Montessori.
              </p>
              <Link to="/contact" className="btn-primary mt-6">
                Contact us
              </Link>
            </div>
          ) : null}

          {state === "ready" && result.items.length > 0 ? (
            <>
              <div className="mt-12 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                {result.items.map((testimonial, index) => (
                  <TestimonialCard
                    key={testimonial.id || `${testimonial.title}-${index}`}
                    testimonial={testimonial}
                    index={index}
                  />
                ))}
              </div>

              {result.pages > 1 ? (
                <nav
                  className="mt-12 flex items-center justify-center gap-4"
                  aria-label="Testimonials pages"
                >
                  {hasPrevious ? (
                    <Link
                      to={`?page=${result.page - 1}`}
                      className="btn-outline"
                    >
                      Previous
                    </Link>
                  ) : (
                    <span />
                  )}
                  <span className="text-sm font-medium text-ink/75">
                    Page {result.page} of {result.pages}
                  </span>
                  {hasNext ? (
                    <Link
                      to={`?page=${result.page + 1}`}
                      className="btn-outline"
                    >
                      Next
                    </Link>
                  ) : (
                    <span />
                  )}
                </nav>
              ) : null}
            </>
          ) : null}
        </div>
      </section>

      <CTA
        title="Would you like to see a nursery for yourself?"
        text="Book a visit and meet the people who make each setting feel like home."
      />
    </>
  );
}
