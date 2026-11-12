import { Link } from "react-router-dom";
import { useEffect, useState } from "react";
import { Quote } from "lucide-react";
import Seo from "../components/Seo";
import Reveal from "../components/Reveal";
import PageHeader from "../components/PageHeader";
import { testimonials } from "../lib/content";

export default function Testimonials() {
  const [archive, setArchive] = useState(null);
  const [page, setPage] = useState(1);
  const [retry, setRetry] = useState(0);
  useEffect(() => {
    const api = window.ldrnData?.testimonialArchive?.apiUrl;
    if (!api) return;
    const controller = new AbortController();
    fetch(`${api}?page=${page}&per_page=9`, { signal: controller.signal })
      .then((response) => response.ok ? response.json() : Promise.reject(new Error("Could not load parent stories.")))
      .then((result) => setArchive({ page, result }))
      .catch((error) => { if (error.name !== "AbortError") setArchive({ page, error: error.message }); });
    return () => controller.abort();
  }, [page, retry]);
  const current = archive?.page === page ? archive : null;
  const stories = current?.result ? current.result.items.map((record) => ({ id: record.id, attribution: record.name, body: String(record.quote).split(/\r?\n\s*\r?\n/).filter(Boolean) })) : page === 1 ? testimonials : [];
  return (
    <>
      <Seo path="/testimonials" />
      <PageHeader title="Parent Testimonials" tone="canvas" />

      <section data-ldrn-ws="testimonials.section-1" className="bg-white py-16 sm:py-20">
        <div className="container-wide grid gap-6 md:grid-cols-2 lg:grid-cols-3">
          {stories.map((testimonial, index) => (
            <Reveal
              key={testimonial.id || testimonial.attribution}
              data-ldrn-ws={`testimonials.card.${testimonial.id || index}`}
              // Stagger by column so each row animates as a row.
              delay={(index % 3) * 80}
              className="card flex h-full flex-col p-7"
            >
              <Quote className="h-8 w-8 text-sky-300" strokeWidth={1.6} />
              <div className="mt-4 flex-1 space-y-3 text-sm leading-relaxed text-ink/85">
                {testimonial.body.map((paragraph, paragraphIndex) => (
                  <p
                    key={`${testimonial.attribution}-${paragraphIndex}`}
                    className="whitespace-pre-line"
                  >
                    {paragraph}
                  </p>
                ))}
              </div>
              <p className="mt-6 border-t border-sky-100 pt-4 font-heading text-lg font-semibold text-ink">
                {testimonial.attribution}
              </p>
            </Reveal>
          ))}
        </div>
        {page > 1 && !current && <p role="status" className="container-wide mt-6">Loading parent stories…</p>}
        {current?.error && <p role="status" className="container-wide mt-6">{current.error} <button type="button" onClick={() => setRetry((value) => value + 1)}>Try again</button></p>}
        {current?.result?.pages > 1 && <nav aria-label="Parent stories pages" className="container-wide mt-8 flex items-center justify-center gap-6">
          <button type="button" className="btn-outline" disabled={page <= 1} onClick={() => setPage((value) => value - 1)}>Previous</button>
          <span>Page {current.result.page} of {current.result.pages}</span>
          <button type="button" className="btn-outline" disabled={page >= current.result.pages} onClick={() => setPage((value) => value + 1)}>Next</button>
        </nav>}
      </section>

      <section data-ldrn-ws="testimonials.section-2" className="bg-band py-16 text-center">
        <div className="container-wide">
          <h2>Would you like to see a setting for yourself?</h2>
          <Link to="/check-availability" className="btn-primary mt-7">
            Check availability
          </Link>
        </div>
      </section>
    </>
  );
}
