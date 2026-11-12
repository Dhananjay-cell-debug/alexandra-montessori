import { Link } from "react-router-dom";
import { ArrowRight, Mail, Phone } from "lucide-react";
import Seo from "../components/Seo";
import Reveal from "../components/Reveal";
import Icon from "../components/Icon";
import PageHeader from "../components/PageHeader";
import CTA from "../components/CTA";
import Pending from "../components/Pending";
import TbcBadge from "../components/TbcBadge";
import { SectionHeading } from "../components/Section";
import { approachPoints, feesAndFunding } from "../data/site";
import { brand, settings } from "../lib/content";

/**
 * Funding information the nursery has actually approved, with everything
 * still outstanding shown as a visible gap. Nothing on this page states a
 * scheme, rate or eligibility rule that has not been confirmed.
 */
export default function FundedChildcare() {
  return (
    <>
      <Seo path="/funded-childcare" />
      <PageHeader
        eyebrow="Parent information"
        title="Funded Childcare"
        intro={feesAndFunding.funded}
      />

      <section data-ldrn-ws="funded-childcare.section-1" className="container-wide pb-6 pt-2">
        <SectionHeading
          align="left"
          eyebrow="Funded childcare"
          title="The funding information we publish"
        />
        <div className="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
          <Reveal className="card card-hover flex h-full flex-col p-7">
            <span className="icon-badge h-14 w-14">
              <Icon name="CalendarCheck" className="h-7 w-7" />
            </span>
            <h3 className="mt-5">Funded spaces</h3>
            <p className="mt-2 text-sm leading-relaxed text-ink/90">
              {feesAndFunding.funded}
            </p>
          </Reveal>

          <Reveal
            delay={70}
            className="card flex h-full flex-col p-7 sm:col-span-1 lg:col-span-2"
          >
            <h3>Scheme and setting details</h3>
            <Pending className="mt-3">
              Full scheme eligibility and setting-by-setting details have not
              yet been published.
            </Pending>
            <div className="mt-3">
              <TbcBadge value={feesAndFunding.schemes} />
            </div>
          </Reveal>
        </div>
      </section>

      <section data-ldrn-ws="funded-childcare.section-2" className="relative overflow-hidden bg-band py-20">
        <div className="grid-bg absolute inset-0 opacity-10" />
        <div className="container-wide relative">
          <SectionHeading
            eyebrow="How to apply"
            title="Confirm funding with the nursery team"
          />
          <div className="mt-12 grid gap-6 md:grid-cols-3">
            {settings.map((setting, index) => (
              <Reveal
                key={setting.slug}
                delay={index * 90}
                className="rounded-4xl bg-white/95 p-8 shadow-card"
              >
                <div className="flex items-center gap-3">
                  <span className="flex h-10 w-10 items-center justify-center rounded-full bg-sky-700 font-heading text-sm font-semibold text-white">
                    {index + 1}
                  </span>
                  <Icon name="MapPin" className="h-7 w-7 text-sky-700" />
                </div>
                <h3 className="mt-5">{setting.name}</h3>
                <p className="mt-2 text-sm leading-relaxed text-ink/90">
                  Ask the {setting.name} team which funding and session patterns
                  apply to your child.
                </p>
              </Reveal>
            ))}
          </div>
        </div>
      </section>

      <section data-ldrn-ws="funded-childcare.section-3" className="container-wide py-16 sm:py-20">
        <SectionHeading
          eyebrow="Little Rainbows"
          title="Funded places, the same learning-through-play approach"
        />
        <div className="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
          {approachPoints.map((point, index) => (
            <Reveal
              key={point.title}
              delay={index * 70}
              className="card h-full p-7 text-center"
            >
              <span className="icon-badge mx-auto h-16 w-16">
                <Icon name={point.icon} className="h-8 w-8" />
              </span>
              <h3 className="mt-5">{point.title}</h3>
              <p className="mt-2 text-sm leading-relaxed text-ink/90">
                {point.text}
              </p>
            </Reveal>
          ))}
        </div>
      </section>

      <section data-ldrn-ws="funded-childcare.section-4" className="bg-band py-16 sm:py-20">
        <div className="container-wide grid gap-10 lg:grid-cols-2 lg:gap-14">
          <Reveal className="card flex h-full flex-col p-8 sm:p-10">
            <span className="eyebrow">Our fees</span>
            <h2 className="mt-3">Current fee information</h2>
            <Pending className="mt-4">
              A current fee schedule has not yet been published online.
            </Pending>
            <p className="mt-4 text-[1.02rem] leading-relaxed text-ink/90">
              Contact Little Rainbows for the current fee and session
              information for your preferred setting.
            </p>
            <div className="mt-7 flex flex-wrap gap-3">
              <a
                href={`tel:${brand.phone.admissions.replace(/\s/g, "")}`}
                className="btn-primary"
              >
                <Phone className="h-4 w-4" /> {brand.phone.admissions}
              </a>
              <Link to="/fees" className="btn-outline">
                View fees
              </Link>
              <TbcBadge value={feesAndFunding.fees} />
              <TbcBadge value={feesAndFunding.sessions} />
            </div>
          </Reveal>

          <Reveal delay={120} className="card flex h-full flex-col p-8 sm:p-10">
            <span className="eyebrow">Next step</span>
            <h2 className="mt-3">Ask about your family’s needs</h2>
            <p className="mt-4 text-[1.02rem] leading-relaxed text-ink/90">
              Tell us your preferred setting, start date, days and session
              pattern.
            </p>
            <div className="mt-7 flex flex-wrap gap-3">
              <Link to="/check-availability" className="btn-primary">
                Check availability <ArrowRight className="h-4 w-4" />
              </Link>
              <a href={`mailto:${brand.email}`} className="btn-outline">
                <Mail className="h-4 w-4" /> Email us
              </a>
            </div>
          </Reveal>
        </div>
      </section>

      <section data-ldrn-ws="funded-childcare.section-5" className="container-wide py-16">
        <Reveal className="card mx-auto max-w-3xl p-8">
          <h2 className="text-xl">Funding FAQs</h2>
          <div className="mt-5 border-t border-sky-100 pt-5">
            <h3 className="text-sm">
              Which funded childcare schemes do you accept?
            </h3>
            <Pending className="mt-2">
              This information is awaiting confirmation from Little Rainbows.
            </Pending>
            <TbcBadge value={feesAndFunding.schemes} className="mt-3" />
          </div>
        </Reveal>
      </section>

      <CTA
        title="Not sure what applies to your family?"
        text={feesAndFunding.funded}
        action={{ label: "Contact Little Rainbows", to: "/contact" }}
      />
    </>
  );
}
