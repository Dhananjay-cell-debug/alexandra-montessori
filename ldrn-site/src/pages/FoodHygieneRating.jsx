import { ExternalLink } from "lucide-react";
import Seo from "../components/Seo";
import Reveal from "../components/Reveal";
import PageHeader from "../components/PageHeader";
import { foodHygiene } from "../lib/content";

export default function FoodHygieneRating() {
  return (
    <>
      <Seo path="/food-hygiene-rating" />
      <PageHeader
        title="Food Hygiene Rating"
        intro="Current Food Standards Agency information for Little Rainbows Hounslow."
      />

      <section data-ldrn-ws="food-hygiene-rating.section-1" className="container-wide pb-16 pt-6">
        <div className="mx-auto grid max-w-3xl gap-6">
          {foodHygiene.map((record) => (
            <Reveal
              key={record.name}
              className="card flex h-full flex-col overflow-hidden"
            >
              <div className="bg-gradient-to-br from-sky-50 via-white to-sky-100/70 p-6 sm:p-7">
                <div className="flex items-start justify-between gap-4">
                  <div>
                    <p className="text-xs font-semibold uppercase tracking-[0.22em] text-sky-700">
                      {record.name}
                    </p>
                    <h2 className="mt-3 text-2xl">Food hygiene rating</h2>
                  </div>
                  <div className="flex min-h-16 min-w-16 items-center justify-center rounded-3xl bg-sky-700 px-4 text-center font-heading text-3xl font-semibold text-white shadow-soft">
                    {record.rating}
                  </div>
                </div>

                <p className="mt-5 text-sm leading-7 text-ink/85">
                  {record.address}
                </p>

                <dl className="mt-6 grid gap-4 border-t border-sky-200/80 pt-5 text-sm text-ink/85 sm:grid-cols-2">
                  <div>
                    <dt className="font-semibold text-ink">Inspection date</dt>
                    <dd className="mt-1">{record.ratingDate}</dd>
                  </div>
                  <div>
                    <dt className="font-semibold text-ink">Local authority</dt>
                    <dd className="mt-1">{record.authority}</dd>
                  </div>
                </dl>
              </div>

              <div className="border-t border-sky-100 bg-white px-6 py-5 sm:px-7">
                <a
                  href={record.href}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="inline-flex items-center gap-2 text-sm font-semibold text-sky-800 hover:underline"
                >
                  View the Food Standards Agency record
                  <ExternalLink className="h-4 w-4" />
                </a>
              </div>
            </Reveal>
          ))}
        </div>
      </section>
    </>
  );
}
