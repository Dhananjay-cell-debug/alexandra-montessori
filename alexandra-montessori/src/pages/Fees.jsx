import { FileDown } from "lucide-react";
import FeeCalculator from "./FeeCalculator";
import PageHeader from "../components/PageHeader";
import Reveal from "../components/Reveal";
import Seo from "../components/Seo";
import Img from "../components/Img";
import { feeSheets, locations } from "../data/site";

export default function Fees() {
  return (
    <>
      <Seo
        title="Admissions & Fees"
        description="Download nursery fee sheets for Hounslow, Heston and Hammersmith, and calculate funded childcare hours before contacting the right branch."
        path="/fees"
        image="/assets/organisation/materials-shelf.webp"
      />
      <PageHeader
        region="fees-header"
        title="Admissions & Fees"
        intro="Choose your preferred nursery to view the latest fee sheet. Funding, session patterns and final invoices are always confirmed directly with the branch."
      />

      <section data-am-ws="fees.section-1" className="bg-cream pb-16 pt-8 sm:pb-20">
        <div className="container-wide grid gap-10 lg:grid-cols-[0.92fr_1.08fr] lg:items-start">
        <Reveal className="mx-auto w-full max-w-[34rem] lg:max-w-none" data-am-vb-region="fees-photo">
          <Img
            src="/assets/organisation/portrait-girl.webp"
            alt="A child using Montessori number materials"
            position="top"
            className="aspect-square w-full shadow-card"
          />
        </Reveal>
        <div>
          <div className="grid gap-5" data-am-vb-region="fees-sheets">
            {feeSheets.map((sheet) => (
              <Reveal
                key={sheet.name}
                className="rounded-2xl border border-sage-100 bg-white p-5 shadow-soft sm:p-6"
              >
                <div className="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                  <div className="min-w-0">
                    <h2 className="font-heading text-xl font-medium text-sage-800">
                      {sheet.name} fees
                    </h2>
                    <p className="mt-1 text-sm text-ink/85">
                      Ages{" "}
                      {locations.find((location) => location.name === sheet.name)
                        ?.ageRange || "Babies to 5 Years"}
                    </p>
                  </div>
                  <a
                    href={sheet.href}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="btn-primary w-full shrink-0 sm:w-auto"
                  >
                    Download PDF <FileDown className="h-4 w-4" />
                  </a>
                </div>
              </Reveal>
            ))}
          </div>
        </div>
        </div>
      </section>
      <FeeCalculator />
    </>
  );
}
