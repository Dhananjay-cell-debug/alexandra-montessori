import { useMemo, useState } from "react";
import { Link } from "react-router-dom";
import { ArrowRight } from "lucide-react";
import Seo from "../components/Seo";
import Reveal from "../components/Reveal";
import PageHeader from "../components/PageHeader";
import { feeCalculator } from "../data/site";
import { settings } from "../lib/content";
import { isTbc } from "../lib/tbc";

/**
 * Deliberately an *hours* estimator, not a price calculator.
 *
 * No fee schedule has been approved for publication, so quoting a figure here
 * would be inventing one. This tool answers the question a parent can act on
 * today — how many hours are left to pay for after funding — and hands off to
 * the nursery for the actual price.
 */
export default function FeeCalculator() {
  const [slug, setSlug] = useState(settings[0]?.slug || "");
  const [days, setDays] = useState(feeCalculator.defaultDays);
  const [fundedHours, setFundedHours] = useState(
    feeCalculator.defaultFundedHours,
  );

  const setting = settings.find((candidate) => candidate.slug === slug);
  const totalHours = days * feeCalculator.dayHours;
  const fundedApplied = Math.min(totalHours, fundedHours);
  const remaining = Math.max(totalHours - fundedHours, 0);

  const summary = useMemo(
    () => [
      {
        label: "Attendance",
        value: `${days} ${days === 1 ? "day" : "days"} / ${totalHours} hours`,
      },
      { label: "Funded hours applied", value: `${fundedApplied} hours` },
      { label: "Hours to confirm", value: `${remaining} hours` },
    ],
    [days, totalHours, fundedApplied, remaining],
  );

  return (
    <>
      <Seo path="/fee-calculator" />
      <PageHeader
        title="Funding Estimate"
        intro="A simple planning tool for weekly hours. It does not calculate a price or confirm funding eligibility."
      />

      <section data-ldrn-ws="fee-calculator.section-1" className="container-wide grid gap-8 pb-16 pt-6 lg:grid-cols-[0.85fr_1.15fr]">
        <Reveal className="rounded-4xl border border-sky-100 bg-white p-7 shadow-soft">
          <div className="space-y-6">
            <label className="block">
              <span className="mb-1.5 block text-sm font-semibold text-ink">
                Nursery setting
              </span>
              <select
                value={slug}
                onChange={(event) => setSlug(event.target.value)}
                className="w-full rounded border border-sky-300 bg-white px-4 py-3 text-sm text-ink outline-none focus:border-sky-700"
              >
                {settings.map((option) => (
                  <option key={option.slug} value={option.slug}>
                    {option.name}
                    {isTbc(option.ages)
                      ? " — age range to confirm"
                      : ` — ages ${option.ages}`}
                  </option>
                ))}
              </select>
            </label>

            <label className="block">
              <span className="mb-1.5 block text-sm font-semibold text-ink">
                Days per week
              </span>
              <input
                type="range"
                min="1"
                max="5"
                value={days}
                onChange={(event) => setDays(Number(event.target.value))}
                className="w-full accent-sky-800"
              />
              <span className="mt-2 block text-sm text-ink/85">
                {days} {days === 1 ? "day" : "days"} per week
              </span>
            </label>

            <label className="block">
              <span className="mb-1.5 block text-sm font-semibold text-ink">
                Funded hours to estimate
              </span>
              <select
                value={fundedHours}
                onChange={(event) => setFundedHours(Number(event.target.value))}
                className="w-full rounded border border-sky-300 bg-white px-4 py-3 text-sm text-ink outline-none focus:border-sky-700"
              >
                {feeCalculator.fundedHourOptions.map((option) => (
                  <option key={option} value={option}>
                    {option === 0 ? "No funded hours" : `${option} funded hours`}
                  </option>
                ))}
              </select>
            </label>
          </div>
        </Reveal>

        <Reveal
          delay={100}
          className="rounded-4xl bg-sky-700 p-8 text-white shadow-card"
        >
          <p className="text-sm font-semibold uppercase tracking-[0.18em] text-white/75">
            Estimate for {setting?.name}
          </p>
          <div className="mt-7 grid gap-4 sm:grid-cols-3">
            {summary.map((row) => (
              <div key={row.label} className="rounded-3xl bg-white/10 p-5">
                <p className="text-xs uppercase tracking-[0.16em] text-white/65">
                  {row.label}
                </p>
                <p className="mt-2 font-heading text-2xl font-medium text-white">
                  {row.value}
                </p>
              </div>
            ))}
          </div>
          <p className="mt-6 text-sm leading-relaxed text-white/80">
            This is an hours estimate only. Eligibility, stretched funding,
            session patterns and exact fees must be confirmed directly with
            Little Rainbows.
          </p>
          <Link to="/contact" className="btn-white mt-7">
            Confirm with the nursery <ArrowRight className="h-4 w-4" />
          </Link>
        </Reveal>
      </section>
    </>
  );
}
