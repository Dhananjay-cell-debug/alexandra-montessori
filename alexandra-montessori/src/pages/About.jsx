import { Link } from "react-router-dom";
import { ArrowRight, Quote, BadgeCheck, Coffee } from "lucide-react";
import PageHeader from "../components/PageHeader";
import Reveal from "../components/Reveal";
import Icon from "../components/Icon";
import Img from "../components/Img";
import Seo from "../components/Seo";
import CTA from "../components/CTA";
import { SectionHeading } from "../components/Section";
import {
  brand,
  montessoriApproach,
  aboutStory,
  dayRhythm,
  snackTimes,
  featuredQuote,
  trainingList,
} from "../data/site";

export default function About() {
  return (
    <>
      <Seo
        title="About Us"
        description="Discover our Montessori approach, our dedicated early-years team and a typical day in the life of our nurseries."
        path="/about"
        image="/assets/organisation/classroom-calm.webp"
      />
      <PageHeader
        crumb="About us"
        eyebrow="About us"
        title="Montessori care for the early years"
        intro={`We offer high-quality care and education for ${brand.ageRange}, blending authentic Montessori practice with the EYFS to support confidence, independence and school readiness.`}
      />

      {/* Our story */}
      <section className="container-wide py-12 sm:py-16">
        <div className="grid items-center gap-12 lg:grid-cols-2">
          <Reveal>
            <Img
              src="/assets/organisation/teacher-hug.webp"
              alt="A toddler concentrating on a Montessori fine motor activity"
              position="center 22%"
              className="aspect-[4/3] w-full"
            />
          </Reveal>
          <div>
            <SectionHeading
              align="left"
              eyebrow="About our Montessori care"
              title="Where the unique child comes first"
              intro={aboutStory.intro}
            />
            <Reveal delay={140} className="mt-7">
              <Link to="/curriculum" className="btn-primary">
                Explore our curriculum <ArrowRight className="h-4 w-4" />
              </Link>
            </Reveal>
          </div>
        </div>
      </section>

      {/* Montessori-based approach - 4 pillars */}
      <section className="relative overflow-hidden bg-sand py-20">
        <div className="grid-bg absolute inset-0 opacity-10" />
        <div className="container-wide relative">
          <SectionHeading
            eyebrow="Our approach"
            title="A Montessori-based approach"
          />
          <div className="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            {montessoriApproach.map((m, i) => (
              <Reveal
                key={m.title}
                delay={i * 80}
                className="rounded-4xl bg-cream/95 p-7 shadow-card"
              >
                <span className="icon-badge h-16 w-16">
                  <Icon name={m.icon} className="h-8 w-8" />
                </span>
                <h3 className="mt-5 font-heading text-base font-medium text-ink">
                  {m.title}
                </h3>
                <p className="mt-2 text-sm leading-relaxed text-ink/90">
                  {m.text}
                </p>
              </Reveal>
            ))}
          </div>
        </div>
      </section>

      {/* Quote */}
      <section className="container-wide py-20">
        <Reveal className="rounded-5xl bg-sage-50 px-6 py-14 text-center sm:px-12">
          <Quote className="mx-auto h-10 w-10 text-sage-300" />
          <blockquote className="mx-auto mt-5 max-w-3xl text-balance font-heading text-2xl font-medium leading-snug text-ink sm:text-3xl">
            "{featuredQuote.text}"
          </blockquote>
          <p className="mt-5 text-sm font-medium uppercase tracking-[0.2em] text-sage-600">
            {featuredQuote.author}
          </p>
        </Reveal>
      </section>

      {/* Our staff */}
      <section className="container-wide pb-4">
        <div className="grid items-start gap-12 lg:grid-cols-2">
          <div>
            <SectionHeading
              align="left"
              eyebrow="Our staff"
              title="A dedicated, highly trained team"
              intro={aboutStory.staffIntro}
            />
            <p className="mt-5 max-w-md text-pretty text-sm leading-relaxed text-ink/90">
              All staff complete safeguarding and paediatric first-aid training,
              follow our child protection policy and adhere to strict
              confidentiality procedures. We encourage NVQ Level 2 &amp; 3
              study, with regular in-house training throughout the year.
            </p>
          </div>
          <Reveal
            delay={120}
            className="rounded-4xl border border-sage-100 bg-white p-6 shadow-soft"
          >
            <p className="text-xs font-medium uppercase tracking-wider text-ink/65">
              Ongoing in-house training
            </p>
            <div className="mt-4 flex flex-wrap gap-2">
              {trainingList.map((t) => (
                <span
                  key={t}
                  className="inline-flex items-center gap-1.5 rounded-full bg-sage-50 px-3 py-1.5 text-xs font-medium text-sage-700"
                >
                  <BadgeCheck className="h-3.5 w-3.5" />
                  {t}
                </span>
              ))}
            </div>
          </Reveal>
        </div>
      </section>

      {/* A typical day */}
      <section className="container-wide py-16 sm:py-20">
        <SectionHeading
          eyebrow="A typical day"
          title="The rhythm of a Montessori day"
        />
        <div className="mx-auto mt-12 max-w-3xl">
          <ol className="relative border-l border-sage-200 pl-8">
            {dayRhythm.map((d, i) => (
              <Reveal
                as="li"
                key={d.time}
                delay={i * 50}
                className="relative pb-9 last:pb-0"
              >
                <span className="absolute -left-[2.6rem] flex h-12 w-12 items-center justify-center rounded-full bg-sage-100 font-heading text-xs font-medium text-sage-700 ring-4 ring-cream">
                  {d.time}
                </span>
                <h3 className="font-heading text-base font-medium text-ink">
                  {d.title}
                </h3>
                <p className="mt-1 text-sm leading-relaxed text-ink/90">
                  {d.text}
                </p>
              </Reveal>
            ))}
          </ol>
          <Reveal className="mt-8 flex items-center gap-3 rounded-2xl bg-sage-50 px-5 py-4 text-sm text-ink/85">
            <Coffee className="h-5 w-5 shrink-0 text-sage-600" />
            <span>
              <span className="font-medium text-ink">Snack times</span> -
              healthy snacks served at {snackTimes.join(", ")}.
            </span>
          </Reveal>
        </div>
      </section>

      <CTA
        title="Come and see us for yourself"
        text="The best way to get to know us is to visit. Book a show-around and meet the team."
      />
    </>
  );
}
