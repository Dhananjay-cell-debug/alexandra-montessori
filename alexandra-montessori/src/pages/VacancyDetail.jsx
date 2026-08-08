import { useParams, Link } from "react-router-dom";
import { MapPin, Briefcase, Clock, BadgePoundSterling } from "lucide-react";
import PageHeader from "../components/PageHeader";
import Reveal from "../components/Reveal";
import Seo from "../components/Seo";
import ApplicationForm from "../components/ApplicationForm";

const allJobs =
  typeof window !== "undefined" && Array.isArray(window.amData?.jobs)
    ? window.amData.jobs.filter((j) => j?.status === "open")
    : [];

function MetaChip({ icon: Icon, children }) {
  if (!children) return null;
  return (
    <span className="inline-flex items-center gap-1.5 rounded-full bg-sage-100 px-3 py-1 font-body text-xs font-medium text-sage-800">
      <Icon className="h-3.5 w-3.5" />
      {children}
    </span>
  );
}

export default function VacancyDetail() {
  const { slug } = useParams();
  const job = allJobs.find((j) => j.id === slug) ?? null;

  // Vacancy not found / closed → gentle fallback.
  if (!job) {
    return (
      <>
        <Seo title="Vacancy not found" path={`/careers/vacancies/${slug}`} />
        <PageHeader title="Vacancy not found" region="vacancy-detail-header" />
        <section className="bg-white py-16 sm:py-20" data-am-vb-region="vacancy-detail-fallback">
          <div className="container-wide text-center">
            <p className="mx-auto max-w-xl font-body text-sm text-ink/85">
              This vacancy is no longer listed. You can browse our current roles
              or send a speculative application.
            </p>
            <div className="mt-6 flex flex-wrap justify-center gap-3">
              <Link to="/careers/vacancies" className="btn-primary">
                View current vacancies
              </Link>
              <Link to="/careers/apply" className="btn-outline">
                Send a general application
              </Link>
            </div>
          </div>
        </section>
      </>
    );
  }

  const benefitLines = String(job.benefits || "")
    .split(/\r?\n/)
    .map((line) => line.replace(/^[-•*]\s*/, "").trim())
    .filter(Boolean);

  const isExternal = !!job.applyUrl;

  return (
    <>
      <Seo
        title={`${job.title} — Careers`}
        description={
          job.summary ||
          job.shortDescription ||
          `Apply for the ${job.title} role at Alexandra Montessori.`
        }
        path={`/careers/vacancies/${job.id}`}
      />
      <PageHeader title={job.title} crumb="Vacancy" region="vacancy-detail-header" />

      <section className="bg-white py-14 sm:py-18" data-am-vb-region="vacancy-detail-content">
        <div className="container-wide max-w-4xl">
          {/* Meta chips */}
          <Reveal>
            <div className="flex flex-wrap gap-2">
              <MetaChip icon={MapPin}>{job.location}</MetaChip>
              <MetaChip icon={Briefcase}>{job.jobType}</MetaChip>
              <MetaChip icon={Clock}>{job.hours}</MetaChip>
              <MetaChip icon={BadgePoundSterling}>{job.salary}</MetaChip>
            </div>
          </Reveal>

          {/* Summary */}
          {(job.summary || job.shortDescription) && (
            <Reveal>
              <p className="mt-6 text-lg leading-relaxed text-ink/90">
                {job.summary || job.shortDescription}
              </p>
            </Reveal>
          )}

          {/* Full description (WYSIWYG HTML, sanitised server-side) */}
          {job.fullDescription && (
            <Reveal>
              <div
                className="blog-richtext mt-8"
                dangerouslySetInnerHTML={{ __html: job.fullDescription }}
              />
            </Reveal>
          )}

          {/* Benefits */}
          {benefitLines.length > 0 && (
            <Reveal>
              <div className="mt-10">
                <h2 className="font-heading text-xl font-medium text-sage-800">
                  Benefits
                </h2>
                <ul className="mt-4 grid gap-2 sm:grid-cols-2">
                  {benefitLines.map((benefit) => (
                    <li
                      key={benefit}
                      className="flex items-start gap-2 text-sm leading-relaxed text-ink/90"
                    >
                      <span className="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-sage-500" />
                      {benefit}
                    </li>
                  ))}
                </ul>
              </div>
            </Reveal>
          )}

          {/* Apply — job title auto-attaches via selectedJob */}
          <div className="mt-14 border-t border-sage-200 pt-12">
            <Reveal>
              <h2 className="text-center font-heading text-2xl font-medium text-sage-800">
                Apply for this role
              </h2>
            </Reveal>
            {isExternal ? (
              <Reveal>
                <p className="mx-auto mt-4 max-w-xl text-center text-sm text-ink/85">
                  Applications for this role are handled externally.
                </p>
                <div className="mt-6 text-center">
                  <a
                    href={job.applyUrl}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="btn-primary"
                  >
                    Apply now
                  </a>
                </div>
              </Reveal>
            ) : (
              <div className="mt-8">
                <ApplicationForm selectedJob={job} />
              </div>
            )}
          </div>

          <Reveal delay={100}>
            <div className="mt-12 text-center">
              <Link
                to="/careers/vacancies"
                className="font-body text-sm font-medium text-sage-800 underline underline-offset-4 hover:text-sage-600"
              >
                ← Back to vacancies
              </Link>
            </div>
          </Reveal>
        </div>
      </section>
    </>
  );
}
