import { ExternalLink } from "lucide-react";
import Seo from "../components/Seo";
import Reveal from "../components/Reveal";
import PageHeader from "../components/PageHeader";
import { ofsted } from "../data/site";

/**
 * Publishes the Ofsted record exactly as Ofsted holds it, including any
 * closed setting. The grade badge and the availability note make the status
 * unmistakable rather than implying every listed setting is open.
 */
export default function Ofsted() {
  return (
    <>
      <Seo path="/about/ofsted" />
      <PageHeader title={ofsted.title} intro={ofsted.intro} />

      <section data-ldrn-ws="ofsted.section-1" className="container-wide pb-16 pt-6">
        <div className="grid gap-6 lg:grid-cols-3">
          {ofsted.reports.map((report, index) => {
            const closed = report.status === "Closed";
            return (
              <Reveal
                key={report.setting}
                delay={index * 80}
                className="card flex h-full flex-col overflow-hidden"
              >
                <div className="bg-gradient-to-br from-sky-50 via-white to-sky-100/70 p-6 sm:p-7">
                  <div className="flex items-start justify-between gap-4">
                    <div>
                      <p className="text-xs font-semibold uppercase tracking-[0.22em] text-sky-700">
                        {report.setting} ·{" "}
                        {closed ? "Published status" : "Current setting"}
                      </p>
                      <h2 className="mt-3 text-2xl">Ofsted inspection</h2>
                    </div>
                    <div
                      className={`flex min-h-16 min-w-16 items-center justify-center rounded-3xl px-4 text-center font-heading text-xl font-semibold shadow-soft ${
                        closed ? "bg-ink text-white" : "bg-sky-700 text-white"
                      }`}
                    >
                      {closed ? "Closed" : report.grade}
                    </div>
                  </div>

                  <dl className="mt-6 grid gap-4 border-t border-sky-200/80 pt-5 text-sm text-ink/85">
                    <div>
                      <dt className="font-semibold text-ink">
                        {report.dateLabel}
                      </dt>
                      <dd className="mt-1">{report.date}</dd>
                    </div>
                    <div>
                      <dt className="font-semibold text-ink">URN</dt>
                      <dd className="mt-1">{report.urn}</dd>
                    </div>
                    {closed && (
                      <div>
                        <dt className="font-semibold text-ink">
                          Current availability
                        </dt>
                        <dd className="mt-1 text-ink/65">
                          Unverified; Ofsted lists this setting as closed.
                        </dd>
                      </div>
                    )}
                  </dl>
                </div>

                <div className="mt-auto flex flex-wrap gap-x-5 gap-y-3 border-t border-sky-100 bg-white px-6 py-5 sm:px-7">
                  {report.reportUrl ? (
                    <a
                      href={report.reportUrl}
                      target="_blank"
                      rel="noreferrer noopener"
                      className="inline-flex items-center gap-2 text-sm font-semibold text-sky-800 hover:underline"
                    >
                      View inspection report{" "}
                      <ExternalLink className="h-4 w-4" />
                    </a>
                  ) : null}
                  <a
                    href={report.providerUrl}
                    target="_blank"
                    rel="noreferrer noopener"
                    className="inline-flex items-center gap-2 text-sm font-semibold text-sky-800 hover:underline"
                  >
                    View Ofsted record <ExternalLink className="h-4 w-4" />
                  </a>
                </div>
              </Reveal>
            );
          })}
        </div>
      </section>
    </>
  );
}
