import { ExternalLink } from "lucide-react";
import PageHeader from "../components/PageHeader";
import Reveal from "../components/Reveal";
import Seo from "../components/Seo";
import { foodHygieneRatings } from "../data/site";

function getRatingBadge(rating) {
  if (/^\d+$/.test(rating)) {
    return {
      label: rating,
      tone: rating === "5" ? "bg-sage-600 text-white" : "bg-sage-100 text-sage-800",
      detail: `FHRS rating ${rating}`,
    };
  }

  return {
    label: "Check latest record",
    tone: "bg-amber-100 text-amber-900",
    detail: "Awaiting public listing",
  };
}

export default function FoodHygieneRating() {
  return (
    <>
      <Seo
        title="Food & Hygiene"
        description="Food hygiene rating information for Alexandra Montessori nursery locations."
        path="/food-hygiene-rating"
      />
      <PageHeader
        region="hygiene-header"
        title="Food & Hygiene"
        intro="Branch-level food hygiene information is listed clearly so parents can check the relevant public record directly."
      />

      <section className="container-wide pb-16 pt-6" data-am-vb-region="hygiene-grid">
        <div className="grid gap-6 lg:grid-cols-3">
          {foodHygieneRatings.map((rating, index) => {
            const badge = getRatingBadge(rating.rating);

            return (
              <Reveal
                key={rating.name}
                delay={index * 80}
                className="card flex h-full flex-col overflow-hidden"
              >
                <div className="bg-gradient-to-br from-sage-50 via-white to-sage-100/70 p-6 sm:p-7">
                  <div className="flex items-start justify-between gap-4">
                    <div>
                      <p className="text-xs font-semibold uppercase tracking-[0.22em] text-sage-600">
                        {rating.name}
                      </p>
                      <h2 className="mt-3 font-heading text-2xl font-medium text-sage-800">
                        {badge.detail}
                      </h2>
                    </div>
                    <div
                      data-am-vb-region="hygiene-badges"
                      className={`flex min-h-16 min-w-16 items-center justify-center rounded-3xl px-4 text-center font-heading text-2xl font-medium shadow-soft ${badge.tone}`}
                    >
                      {badge.label}
                    </div>
                  </div>

                  <p className="mt-5 text-sm leading-7 text-ink/85">
                    {rating.address}
                  </p>

                  <dl className="mt-6 grid gap-4 border-t border-sage-200/80 pt-5 text-sm text-ink/85" data-am-vb-region="hygiene-metadata">
                    <div>
                      <dt className="font-semibold text-ink">Rating date</dt>
                      <dd className="mt-1">{rating.ratingDate}</dd>
                    </div>
                    <div>
                      <dt className="font-semibold text-ink">Authority</dt>
                      <dd className="mt-1">{rating.authority}</dd>
                    </div>
                  </dl>
                </div>

                <div className="mt-auto border-t border-sage-100 bg-white px-6 py-5 sm:px-7" data-am-vb-region="hygiene-record-action">
                  {rating.href ? (
                    <a
                      href={rating.href}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="inline-flex items-center gap-2 text-sm font-semibold text-sage-800 hover:underline"
                    >
                      View public record <ExternalLink className="h-4 w-4" />
                    </a>
                  ) : (
                    <span className="text-sm font-medium text-ink/70">
                      Public record details are being updated.
                    </span>
                  )}
                </div>
              </Reveal>
            );
          })}
        </div>
      </section>
    </>
  );
}
