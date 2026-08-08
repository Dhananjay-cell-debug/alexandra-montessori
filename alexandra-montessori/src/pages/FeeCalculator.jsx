import { useMemo, useState } from "react";
import { FileDown } from "lucide-react";
import PageHeader from "../components/PageHeader";
import Reveal from "../components/Reveal";
import Seo from "../components/Seo";
import { feeSheets, locations } from "../data/site";

const dayHours = 10;

export default function FeeCalculator() {
  const [branchId, setBranchId] = useState(locations[0]?.id || "");
  const [days, setDays] = useState(3);
  const [fundedHours, setFundedHours] = useState(15);

  const branch = locations.find((location) => location.id === branchId);
  const sheet = feeSheets.find((item) => item.name === branch?.name);
  const weeklyHours = days * dayHours;
  const chargeableHours = Math.max(weeklyHours - fundedHours, 0);
  const fundedApplied = Math.min(weeklyHours, fundedHours);

  const summary = useMemo(
    () => [
      { label: "Attendance", value: `${days} days / ${weeklyHours} hours` },
      { label: "Funded hours applied", value: `${fundedApplied} hours` },
      { label: "Chargeable hours", value: `${chargeableHours} hours` },
    ],
    [chargeableHours, days, fundedApplied, weeklyHours],
  );

  return (
    <>
      <Seo
        title="Funding Estimate"
        description="Estimate weekly chargeable nursery hours after funded childcare and download the correct branch fee sheet."
        path="/fee-calculator"
      />
      <PageHeader
        region="calculator-header"
        title="Funding Estimate"
        intro="A simple planning tool for funded hours. It estimates chargeable weekly hours only; exact fees are confirmed by the branch using the latest fee sheet."
      />

      <section className="container-wide grid gap-8 pb-16 pt-6 lg:grid-cols-[0.85fr_1.15fr]">
        <Reveal className="rounded-4xl border border-sage-100 bg-white p-7 shadow-soft" data-am-vb-region="calculator-input">
          <div className="space-y-5">
            <label className="block" data-am-vb-region="calculator-nursery">
              <span className="mb-1.5 block text-sm font-medium text-sage-800">
                Nursery branch
              </span>
              <select
                value={branchId}
                onChange={(event) => setBranchId(event.target.value)}
                className="w-full rounded border border-sage-300 bg-white px-4 py-3 text-sm text-ink outline-none focus:border-sage-600"
              >
                {locations.map((location) => (
                  <option key={location.id} value={location.id}>
                    {location.name} - ages {location.ageRange}
                  </option>
                ))}
              </select>
            </label>

            <label className="block" data-am-vb-region="calculator-days">
              <span className="mb-1.5 block text-sm font-medium text-sage-800">
                Days per week
              </span>
              <input
                type="range"
                min="1"
                max="5"
                value={days}
                onChange={(event) => setDays(Number(event.target.value))}
                className="w-full accent-sage-600"
              />
              <span className="mt-2 block text-sm text-ink/85">
                {days} {days === 1 ? "day" : "days"} per week
              </span>
            </label>

            <label className="block" data-am-vb-region="calculator-hours">
              <span className="mb-1.5 block text-sm font-medium text-sage-800">
                Funded hours per week
              </span>
              <select
                value={fundedHours}
                onChange={(event) => setFundedHours(Number(event.target.value))}
                className="w-full rounded border border-sage-300 bg-white px-4 py-3 text-sm text-ink outline-none focus:border-sage-600"
              >
                <option value={0}>No funded hours</option>
                <option value={15}>15 funded hours</option>
                <option value={30}>30 funded hours</option>
              </select>
            </label>
          </div>
        </Reveal>

        <Reveal
          delay={100}
          className="rounded-4xl bg-sage-500 p-8 text-white shadow-card"
          data-am-vb-region="calculator-results"
        >
          <p
            className="text-sm font-semibold uppercase tracking-[0.18em] text-white/75"
            data-am-vb-bound-value="true"
          >
            Estimate for {branch?.name}
          </p>
          <div className="mt-7 grid gap-4 sm:grid-cols-3">
            {summary.map((item) => (
              <div key={item.label} className="rounded-3xl bg-white/10 p-5">
                <p className="text-xs uppercase tracking-[0.16em] text-white/60">
                  {item.label}
                </p>
                <p
                  className="mt-2 font-heading text-2xl font-medium text-white"
                  data-am-vb-bound-value="true"
                >
                  {item.value}
                </p>
              </div>
            ))}
          </div>
          <div data-am-vb-region="calculator-disclaimer">
            <p className="mt-6 text-sm leading-relaxed text-white/75">
              Exact invoices depend on the current branch fee sheet, funding
              eligibility, stretched-term arrangements and any agreed sessions.
            </p>
            {sheet && (
              <a
                href={sheet.href}
                target="_blank"
                rel="noopener noreferrer"
                className="btn-cream mt-7"
              >
                Download {sheet.name} fee sheet <FileDown className="h-4 w-4" />
              </a>
            )}
          </div>
        </Reveal>
      </section>
    </>
  );
}
