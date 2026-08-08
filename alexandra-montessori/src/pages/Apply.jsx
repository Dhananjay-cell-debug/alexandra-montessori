import { useSearchParams, Link } from "react-router-dom";
import PageHeader from "../components/PageHeader";
import Reveal from "../components/Reveal";
import Seo from "../components/Seo";
import ApplicationForm from "../components/ApplicationForm";

const allJobs =
  typeof window !== "undefined" && Array.isArray(window.amData?.jobs)
    ? window.amData.jobs
    : [];

export default function Apply() {
  const [params] = useSearchParams();
  const slug = params.get("job");

  // Look up job in the already-filtered (open) list from PHP.
  // If slug provided but not found (closed/removed), gracefully fall back to general application.
  const job = slug ? (allJobs.find((j) => j.id === slug) ?? null) : null;

  return (
    <>
      <Seo
        title={job ? `Apply - ${job.title}` : "Apply"}
        description="Apply to join the Alexandra Montessori team."
        path="/careers/apply"
      />
      <PageHeader title={job ? "Apply for this role" : "Apply"} region="apply-header" />

      <section className="bg-white py-16 sm:py-20" data-am-vb-region="apply-form">
        <div className="container-wide">
          {/* Polite notice when a slug was given but the vacancy is no longer listed */}
          {slug && !job && (
            <Reveal>
              <p className="mb-8 text-center font-body text-sm text-ink/85">
                This vacancy is no longer listed. You can still submit a general
                application below.
              </p>
            </Reveal>
          )}
          <Reveal>
            <ApplicationForm selectedJob={job} />
          </Reveal>
          <Reveal delay={100}>
            <div className="mt-10 text-center">
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
