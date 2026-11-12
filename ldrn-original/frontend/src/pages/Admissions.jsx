import { Link } from "react-router-dom";
import { ArrowRight, FileDown, Phone } from "lucide-react";
import Seo from "../components/Seo";
import Reveal from "../components/Reveal";
import Img from "../components/Img";
import PageHeader from "../components/PageHeader";
import Pending from "../components/Pending";
import TbcBadge from "../components/TbcBadge";
import { admissions } from "../data/site";
import { brand, settings } from "../lib/content";
import { isTbc } from "../lib/tbc";

export default function Admissions() {
  return (
    <>
      <Seo path="/admissions" />
      <PageHeader
        title="Admissions & Fees"
        intro="Choose your preferred setting to view its published admissions information. Fees, funding and session patterns are confirmed directly with Little Rainbows."
      />

      <section data-ldrn-ws="admissions.section-1" className="bg-band pb-16 pt-8 sm:pb-20">
        <div className="container-wide grid gap-10 lg:grid-cols-[0.92fr_1.08fr] lg:items-start">
          <Reveal className="mx-auto w-full max-w-[34rem] lg:max-w-none">
            <Img
              src="/assets/photography/child-pointing-in-classroom.jpg"
              alt="A child learning in a Little Rainbows classroom"
              position="top"
              className="aspect-square w-full shadow-card"
            />
          </Reveal>

          <div>
            <div className="grid gap-5">
              {settings.map((setting) => {
                const ages = isTbc(setting.ages) ? null : setting.ages;
                return (
                  <Reveal
                    key={setting.slug}
                    className="rounded-2xl border border-sky-100 bg-white p-5 shadow-soft sm:p-6"
                  >
                    <div className="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                      <div className="min-w-0">
                        <h2 className="text-xl">{setting.name} admissions</h2>
                        {ages ? (
                          <p className="mt-1 text-sm text-ink/85">Ages {ages}</p>
                        ) : (
                          <Pending className="mt-1" />
                        )}
                        <TbcBadge value={setting.ages} className="mt-2" />
                      </div>

                      {/* Only Hounslow has an approved application form; any
                          other setting routes to contact instead. */}
                      {setting.slug === "hounslow" ? (
                        <a
                          href={admissions.form}
                          download
                          className="btn-primary w-full shrink-0 sm:w-auto"
                        >
                          Application form <FileDown className="h-4 w-4" />
                        </a>
                      ) : (
                        <Link
                          to="/contact"
                          className="btn-primary w-full shrink-0 sm:w-auto"
                        >
                          Contact setting <ArrowRight className="h-4 w-4" />
                        </Link>
                      )}
                    </div>
                  </Reveal>
                );
              })}
            </div>

            <Reveal delay={120} className="mt-8 rounded-2xl bg-sky-100 p-6 sm:p-7">
              <h2 className="text-2xl">Need funding information?</h2>
              <p className="mt-2 text-sm leading-relaxed text-ink/85">
                {admissions.funded}
              </p>
              <div className="mt-5 flex flex-wrap gap-3">
                <Link to="/funded-childcare" className="btn-primary">
                  Fees &amp; funding <ArrowRight className="h-4 w-4" />
                </Link>
                <a
                  href={`tel:${brand.phone.admissions.replace(/\s/g, "")}`}
                  className="btn-outline"
                >
                  <Phone className="h-4 w-4" /> {brand.phone.admissions}
                </a>
              </div>
            </Reveal>
          </div>
        </div>
      </section>
    </>
  );
}
