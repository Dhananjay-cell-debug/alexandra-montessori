import { Link } from "react-router-dom";
import { FileDown, ExternalLink, ArrowRight } from "lucide-react";
import PageHeader from "../components/PageHeader";
import Reveal from "../components/Reveal";
import Icon from "../components/Icon";
import Img from "../components/Img";
import Seo from "../components/Seo";
import CTA from "../components/CTA";
import { SectionHeading } from "../components/Section";
import {
  funding,
  fundingSteps,
  fundingFeatures,
  faqs,
  feeSheets,
  fundingResources,
} from "../data/site";

export default function FundedChildcare() {
  return (
    <>
      <Seo
        title="Funded Childcare"
        description="Government-funded childcare at Alexandra Montessori - 15 and 30 hours, funding from 9 months and two-year-old funding. See what you're entitled to and how to claim."
        path="/funded-childcare"
      />
      <PageHeader
        region="funding-header"
        crumb="Funded Childcare"
        eyebrow="Parent information"
        title="Quality early years education, with government support"
        intro="We accept government-funded childcare across all three nurseries. Here's a simple guide to the funding on offer, who's eligible and how to claim your hours."
      />

      {/* Funding schemes */}
      <section className="container-wide pb-6 pt-2" data-am-vb-region="funding-offerings">
        <SectionHeading
          align="left"
          eyebrow="Our funded childcare offerings"
          title="The funding on offer"
        />
        <div className="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
          {funding.map((f, i) => (
            <Reveal
              key={f.title}
              delay={i * 70}
              className="card card-hover flex h-full flex-col overflow-hidden p-0"
            >
              <div className="relative">
                <Img
                  src={f.image}
                  alt={f.title}
                  rounded="rounded-none"
                  className="aspect-[4/3] w-full"
                />
                <span className="absolute left-4 top-4 flex h-12 w-12 items-center justify-center rounded-full bg-white/95 text-sage-600 shadow-soft backdrop-blur-sm">
                  <Icon name="PoundSterling" className="h-6 w-6" />
                </span>
              </div>
              <div className="flex flex-1 flex-col p-7">
                <h3 className="font-heading text-base font-medium text-ink">
                  {f.title}
                </h3>
                <p className="mt-2 flex-1 text-sm leading-relaxed text-ink/90">
                  {f.text}
                </p>
              </div>
            </Reveal>
          ))}
        </div>
      </section>

      {/* How to apply */}
      <section className="relative overflow-hidden bg-sand py-20" data-am-vb-region="funding-apply">
        <div className="grid-bg absolute inset-0 opacity-10" />
        <div className="container-wide relative">
          <SectionHeading
            eyebrow="How to apply"
            title="Three simple steps"
          />
          <div className="mt-12 grid gap-6 md:grid-cols-3">
            {fundingSteps.map((s, i) => (
              <Reveal
                key={s.title}
                delay={i * 90}
                className="rounded-4xl bg-cream/95 p-8 shadow-card"
              >
                <div className="flex items-center gap-3">
                  <span className="flex h-10 w-10 items-center justify-center rounded-full bg-sage-500 font-heading text-sm font-medium text-white">
                    {i + 1}
                  </span>
                  <Icon name={s.icon} className="h-7 w-7 text-sage-600" />
                </div>
                <h3 className="mt-5 font-heading text-lg font-medium text-ink">
                  {s.title}
                </h3>
                <p className="mt-2 text-sm leading-relaxed text-ink/90">
                  {s.text}
                </p>
              </Reveal>
            ))}
          </div>
        </div>
      </section>

      {/* Why choose us */}
      <section className="container-wide py-16 sm:py-20" data-am-vb-region="funding-benefits">
        <SectionHeading
          eyebrow="Why choose Alexandra Montessori?"
          title="Funded hours, full Montessori experience"
        />
        <div className="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
          {fundingFeatures.map((f, i) => (
            <Reveal
              key={f.title}
              delay={i * 70}
              className="card h-full p-7 text-center"
            >
              <span className="mx-auto icon-badge h-16 w-16">
                <Icon name={f.icon} className="h-8 w-8" />
              </span>
              <h3 className="mt-5 font-heading text-base font-medium text-ink">
                {f.title}
              </h3>
              <p className="mt-2 text-sm leading-relaxed text-ink/90">
                {f.text}
              </p>
            </Reveal>
          ))}
        </div>
      </section>

      {/* Fees & resources */}
      <section className="bg-sand py-16 sm:py-20" data-am-vb-region="funding-resources">
        <div className="container-wide grid gap-10 lg:grid-cols-2 lg:gap-14">
          {/* Fee sheets */}
          <Reveal className="card flex h-full flex-col p-8 sm:p-10">
            <span className="eyebrow">Our fees</span>
            <h2 className="mt-3 font-heading text-2xl font-medium text-sage-800 sm:text-3xl">
              Nursery fees
            </h2>
            <p className="mt-4 text-[1.02rem] leading-relaxed text-ink/90">
              Download the latest fee schedule for each of our nurseries. Funded
              hours are deducted from your invoice - our team will happily talk
              you through exactly what you'll pay.
            </p>
            <div className="mt-7 flex flex-col gap-3">
              {feeSheets.map((f) => (
                <a
                  key={f.name}
                  href={f.href}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="group flex items-center justify-between gap-3 rounded-xl border border-sage-200 bg-white px-5 py-4 transition-colors hover:border-sage-500 hover:bg-sage-50"
                >
                  <span className="font-body text-sm font-medium text-sage-800">
                    {f.name} - fee schedule (PDF)
                  </span>
                  <FileDown className="h-5 w-5 shrink-0 text-sage-600 transition-transform group-hover:translate-y-0.5" />
                </a>
              ))}
            </div>
            <Link to="/contact" className="btn-primary mt-7 self-start">
              Ask us about fees <ArrowRight className="h-4 w-4" />
            </Link>
          </Reveal>

          {/* External resources */}
          <Reveal delay={120} className="card flex h-full flex-col p-8 sm:p-10">
            <span className="eyebrow">Helpful links</span>
            <h2 className="mt-3 font-heading text-2xl font-medium text-sage-800 sm:text-3xl">
              Additional resources
            </h2>
            <p className="mt-4 text-[1.02rem] leading-relaxed text-ink/90">
              Trusted government and local-authority pages to help you
              understand and claim the childcare support your family is entitled
              to.
            </p>
            <ul className="mt-7 flex flex-col gap-4">
              {fundingResources.map((r) => (
                <li key={r.label}>
                  <a
                    href={r.href}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="group flex items-start gap-3"
                  >
                    <ExternalLink className="mt-0.5 h-5 w-5 shrink-0 text-sage-600" />
                    <span>
                      <span className="font-body text-sm font-medium text-sage-700 underline-offset-4 group-hover:underline">
                        {r.label}
                      </span>
                      <span className="mt-0.5 block text-sm leading-snug text-ink/85">
                        {r.note}
                      </span>
                    </span>
                  </a>
                </li>
              ))}
            </ul>
          </Reveal>
        </div>
      </section>

      {/* FAQ */}
      <section className="container-wide py-16" data-am-vb-region="funding-faq">
        <Reveal className="card mx-auto max-w-3xl p-8">
          <h2 className="font-heading text-xl font-medium text-ink">
            Funding FAQs
          </h2>
          <div className="mt-5 divide-y divide-sage-100">
            {faqs.map((f) => (
              <div key={f.q} className="py-4 first:pt-0 last:pb-0">
                <h3 className="font-heading text-sm font-medium text-ink">
                  {f.q}
                </h3>
                <p className="mt-1.5 text-sm leading-relaxed text-ink/90">
                  {f.a}
                </p>
              </div>
            ))}
          </div>
        </Reveal>
      </section>

      <CTA
        region="funding-closing"
        title="Not sure what you're entitled to?"
        text="Our team will happily walk you through the funding options and help you claim your hours."
      />
    </>
  );
}
