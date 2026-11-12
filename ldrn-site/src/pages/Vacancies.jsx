import { Link } from "react-router-dom";
import { MapPin } from "lucide-react";
import Seo from "../components/Seo";
import Reveal from "../components/Reveal";
import PageHeader from "../components/PageHeader";
import { careers } from "../data/site";
import { jobs } from "../lib/content";

/**
 * Vacancies are WordPress records. Until an editor publishes one there is
 * nothing to list, so the page routes candidates to the general application
 * rather than showing an empty archive.
 */
export default function Vacancies() {
  return (
    <>
      <Seo path="/careers/vacancies" />
      <PageHeader title="Current Vacancies" tone="canvas" />

      {jobs.length > 0 ? (
        <section data-ldrn-ws="vacancies.section-1" className="bg-white py-16 sm:py-20">
          <div className="container-wide grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            {jobs.map((job, index) => (
              <Reveal
                key={job.id}
                delay={(index % 3) * 80}
                className="card flex h-full flex-col p-7"
              >
                <h2 className="text-xl">{job.title}</h2>
                {job.location && (
                  <p className="mt-2 inline-flex items-center gap-1.5 text-sm font-semibold text-sky-700">
                    <MapPin className="h-3.5 w-3.5" /> {job.location}
                  </p>
                )}
                <p className="mt-3 flex flex-wrap gap-x-3 gap-y-1 text-xs font-semibold uppercase tracking-wide text-ink/65">
                  {[job.jobType, job.hours, job.salary]
                    .filter(Boolean)
                    .map((fact) => (
                      <span key={fact}>{fact}</span>
                    ))}
                </p>
                {job.shortDescription && (
                  <p className="mt-4 flex-1 text-sm leading-relaxed text-ink/85">
                    {job.shortDescription}
                  </p>
                )}
                <Link to="/careers/apply" className="btn-primary mt-6 self-start">
                  Apply for this role
                </Link>
              </Reveal>
            ))}
          </div>
        </section>
      ) : (
        <section data-ldrn-ws="vacancies.section-2" className="bg-band py-16 sm:py-20">
          <div className="container-wide text-center">
            <h2>Vacancy listings are being updated</h2>
            <p className="mx-auto mt-4 max-w-xl text-ink/85">{careers.intro}</p>
            <div className="mt-8 flex flex-wrap justify-center gap-3">
              <Link to="/careers#apply" className="btn-primary">
                Send a general application
              </Link>
              <Link to="/careers/apply" className="btn-outline">
                Full application form
              </Link>
            </div>
          </div>
        </section>
      )}
    </>
  );
}
