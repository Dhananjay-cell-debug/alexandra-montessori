import { Link } from "react-router-dom";
import { ArrowRight, Mail, Phone } from "lucide-react";
import Seo from "../components/Seo";
import Reveal from "../components/Reveal";
import Img from "../components/Img";
import PageHeader from "../components/PageHeader";
import { brand, settings } from "../lib/content";
import { isTbc } from "../lib/tbc";

/**
 * Fees are not published: the client has not supplied rates or session
 * patterns. Rather than showing an empty table, the page routes the visitor
 * straight to the setting team who can quote accurately.
 */
export default function Fees() {
  return (
    <>
      <Seo path="/fees" />
      <PageHeader
        title="Admissions & Fees"
        intro="Current fees, age ranges and session patterns are confirmed directly with each setting team."
      />

      <section data-ldrn-ws="fees.section-1" className="bg-canvas pb-16 pt-8 sm:pb-20">
        <div className="container-wide grid gap-10 lg:grid-cols-[0.92fr_1.08fr] lg:items-start">
          <Reveal className="mx-auto w-full max-w-[34rem] lg:max-w-none">
            <Img
              src="/assets/photography/child-hands-green-playdough.jpg"
              alt="A child exploring green play dough at Little Rainbows"
              position="center"
              className="aspect-square w-full shadow-card"
            />
          </Reveal>

          <div>
            <div className="grid gap-5">
              {settings.map((setting) => (
                <Reveal
                  key={setting.slug}
                  className="rounded-2xl border border-sky-100 bg-white p-5 shadow-soft sm:p-6"
                >
                  <div className="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                    <div className="min-w-0">
                      <h2 className="text-xl">{setting.name} fees</h2>
                      <p className="mt-1 text-sm text-ink/85">
                        {isTbc(setting.ages)
                          ? "Current age range and rates are available from the nursery team."
                          : `Ages ${setting.ages}; current rates are available from the nursery team.`}
                      </p>
                    </div>
                    <div className="flex shrink-0 flex-col gap-2 sm:items-end">
                      <a
                        href={`tel:${setting.phone.replace(/\s/g, "")}`}
                        className="btn-primary w-full sm:w-auto"
                      >
                        <Phone className="h-4 w-4" /> {setting.phone}
                      </a>
                      <a
                        href={`mailto:${setting.email || brand.email}`}
                        className="inline-flex items-center gap-2 text-sm font-semibold text-sky-800 hover:underline"
                      >
                        <Mail className="h-4 w-4" /> Email {setting.name}
                      </a>
                    </div>
                  </div>
                </Reveal>
              ))}
            </div>

            <Reveal delay={120} className="mt-8 rounded-2xl bg-sky-100 p-6 sm:p-7">
              <h2 className="text-2xl">Need a funding estimate?</h2>
              <p className="mt-2 text-sm leading-relaxed text-ink/85">
                Estimate weekly chargeable hours after funded childcare, then
                confirm eligibility and fees with Little Rainbows.
              </p>
              <Link to="/fee-calculator" className="btn-primary mt-5">
                Estimate funded hours <ArrowRight className="h-4 w-4" />
              </Link>
            </Reveal>
          </div>
        </div>
      </section>
    </>
  );
}
