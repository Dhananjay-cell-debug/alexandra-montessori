import { useState } from "react";
import { Link } from "react-router-dom";
import PageHeader from "../components/PageHeader";
import Reveal from "../components/Reveal";
import Seo from "../components/Seo";

const allJobs =
  typeof window !== "undefined" && Array.isArray(window.amData?.jobs)
    ? window.amData.jobs.filter((j) => j?.status === "open")
    : [];

// Preferred order for known values; any WP-created unknown appended after.
const KNOWN_LOCATIONS = ["Hammersmith", "Heston", "Hounslow", "All Nurseries"];
const KNOWN_TYPES = ["Full-time", "Part-time", "Sessional", "Volunteer"];

function orderedUnique(known, values) {
  const set = new Set(values);
  const seen = new Set();
  const out = [];
  known.forEach((k) => {
    if (set.has(k)) {
      out.push(k);
      seen.add(k);
    }
  });
  values.forEach((v) => {
    if (!seen.has(v)) {
      out.push(v);
      seen.add(v);
    }
  });
  return out;
}

const locationOptions = orderedUnique(
  KNOWN_LOCATIONS,
  allJobs.map((j) => j.location).filter(Boolean),
);
const typeOptions = orderedUnique(
  KNOWN_TYPES,
  allJobs.map((j) => j.jobType).filter(Boolean),
);

// ── sub-components ────────────────────────────────────────────────────────────

function ChipRow({ options, allLabel, active, onChange }) {
  const chip = (label) =>
    `rounded-full px-4 py-1.5 font-body text-xs font-medium transition-colors ${
      active === label
        ? "bg-sage-500 text-white"
        : "border border-sage-300 text-sage-800 hover:bg-sage-50"
    }`;

  return (
    <div className="flex flex-wrap gap-2">
      <button
        type="button"
        className={chip(allLabel)}
        onClick={() => onChange(allLabel)}
      >
        {allLabel}
      </button>
      {options.map((opt) => (
        <button
          key={opt}
          type="button"
          className={chip(opt)}
          onClick={() => onChange(opt)}
        >
          {opt}
        </button>
      ))}
    </div>
  );
}

function JobCard({ job }) {
  const preview = job.summary || job.shortDescription || "";

  return (
    <div className="flex h-full flex-col border border-sage-200 bg-white p-6">
      <h3 className="font-heading text-xl font-medium text-sage-800">
        {job.title}
      </h3>
      <div className="mt-3 flex flex-wrap gap-2">
        {job.location && (
          <span className="rounded-full bg-sage-100 px-3 py-1 font-body text-xs font-medium text-sage-800">
            {job.location}
          </span>
        )}
        {job.jobType && (
          <span className="rounded-full bg-sage-100 px-3 py-1 font-body text-xs font-medium text-sage-800">
            {job.jobType}
          </span>
        )}
        {job.hours && (
          <span className="rounded-full bg-sage-100 px-3 py-1 font-body text-xs font-medium text-sage-800">
            {job.hours}
          </span>
        )}
        {job.salary && (
          <span className="rounded-full bg-sage-100 px-3 py-1 font-body text-xs font-medium text-sage-800">
            {job.salary}
          </span>
        )}
      </div>
      {preview && (
        <p className="mt-4 flex-1 text-sm leading-relaxed text-ink/85 line-clamp-3">
          {preview}
        </p>
      )}
      <Link
        to={`/careers/vacancies/${job.id}`}
        className="btn-primary mt-6 text-center"
      >
        View role
      </Link>
    </div>
  );
}

// ── page ──────────────────────────────────────────────────────────────────────

export default function Vacancies() {
  const [locFilter, setLocFilter] = useState("All Locations");
  const [typeFilter, setTypeFilter] = useState("All Types");

  const filtered = allJobs.filter((job) => {
    const locMatch =
      locFilter === "All Locations" || job.location === locFilter;
    const typeMatch = typeFilter === "All Types" || job.jobType === typeFilter;
    return locMatch && typeMatch;
  });

  // Build location groups in stable order
  const groupOrder = orderedUnique(
    KNOWN_LOCATIONS,
    filtered.map((j) => j.location).filter(Boolean),
  );
  const groups = groupOrder
    .map((loc) => ({ loc, cards: filtered.filter((j) => j.location === loc) }))
    .filter(({ cards }) => cards.length > 0);
  const ungrouped = filtered.filter((j) => !j.location);

  const count = filtered.length;

  return (
    <>
      <Seo
        title="Current Vacancies"
        description="Browse open positions at Alexandra Montessori nurseries across London."
        path="/careers/vacancies"
      />
      <PageHeader title="Current Vacancies" region="vacancies-header" />

      <section className="bg-cream py-16 sm:py-20" data-am-vb-region="vacancies-archive">
        <div className="container-wide">
          {allJobs.length === 0 ? (
            /* ── no jobs at all ── */
            <Reveal>
              <p className="mx-auto max-w-xl text-center font-body text-sm text-ink/85">
                We don't have any open positions right now. Check back soon, or
                send us a speculative application.
              </p>
              <div className="mt-6 text-center">
                <a href="/careers#apply" className="btn-primary">
                  Send a speculative application
                </a>
              </div>
            </Reveal>
          ) : (
            <>
              {/* ── filter bar ── */}
              <Reveal className="space-y-3">
                {locationOptions.length > 0 && (
                  <ChipRow
                    options={locationOptions}
                    allLabel="All Locations"
                    active={locFilter}
                    onChange={setLocFilter}
                  />
                )}
                {typeOptions.length > 0 && (
                  <ChipRow
                    options={typeOptions}
                    allLabel="All Types"
                    active={typeFilter}
                    onChange={setTypeFilter}
                  />
                )}
              </Reveal>

              {/* ── count ── */}
              <p className="mt-6 font-body text-sm text-ink/85">
                Showing {count} open role{count !== 1 ? "s" : ""}
              </p>

              {/* ── no filter match ── */}
              {count === 0 ? (
                <Reveal className="mt-10">
                  <p className="text-center font-body text-sm text-ink/85">
                    No vacancies match this filter right now.
                  </p>
                </Reveal>
              ) : (
                <div className="mt-10 space-y-12">
                  {groups.map(({ loc, cards }) => (
                    <div key={loc}>
                      <Reveal>
                        <h2 className="mb-6 border-b border-sage-200 pb-3 font-heading text-lg font-medium text-sage-800">
                          {loc}
                        </h2>
                      </Reveal>
                      <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        {cards.map((job, i) => (
                          <Reveal key={job.id} delay={i * 80}>
                            <JobCard job={job} />
                          </Reveal>
                        ))}
                      </div>
                    </div>
                  ))}

                  {/* jobs with no location value (future-proof) */}
                  {ungrouped.length > 0 && (
                    <div>
                      <Reveal>
                        <h2 className="mb-6 border-b border-sage-200 pb-3 font-heading text-lg font-medium text-sage-800">
                          Other
                        </h2>
                      </Reveal>
                      <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        {ungrouped.map((job, i) => (
                          <Reveal key={job.id} delay={i * 80}>
                            <JobCard job={job} />
                          </Reveal>
                        ))}
                      </div>
                    </div>
                  )}
                </div>
              )}
            </>
          )}

          {/* ── back link ── */}
          <Reveal delay={200}>
            <div className="mt-14 text-center">
              <Link
                to="/careers"
                className="font-body text-sm font-medium text-sage-800 underline underline-offset-4 hover:text-sage-600"
              >
                ← Back to Careers
              </Link>
            </div>
          </Reveal>
        </div>
      </section>
    </>
  );
}
