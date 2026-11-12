import { Link } from "react-router-dom";
import { ArrowRight, BadgeCheck, Quote } from "lucide-react";
import Seo from "../components/Seo";
import Reveal from "../components/Reveal";
import Img from "../components/Img";
import Icon from "../components/Icon";
import PageHeader from "../components/PageHeader";
import CTA from "../components/CTA";
import { SectionHeading } from "../components/Section";
import { approach, curriculum, healthSafety, philosophy } from "../data/site";

// One icon per health-and-safety commitment, in the order they are published.
const SAFETY_ICONS = ["ShieldCheck", "BookOpen", "HandHeart", "BadgeCheck"];

export default function About() {
  return (
    <>
      <Seo path="/about" />
      <PageHeader
        eyebrow="About us"
        title="Learning through play for the early years"
        intro={approach.body}
      />

      <section data-ldrn-ws="about.section-1" className="container-wide py-12 sm:py-16">
        <div className="grid items-center gap-12 lg:grid-cols-2">
          <Reveal>
            <Img
              src="/assets/photography/child-exploring-sensory-bag.jpg"
              alt="A child exploring a sensory activity"
              position="center 22%"
              className="aspect-[4/3] w-full"
            />
          </Reveal>
          <div>
            <SectionHeading
              align="left"
              eyebrow="About our care"
              title="Where every child comes first"
              intro={philosophy.paragraphs[2]}
            />
            <Reveal delay={140} className="mt-7">
              <Link to="/curriculum" className="btn-primary">
                Explore our curriculum <ArrowRight className="h-4 w-4" />
              </Link>
            </Reveal>
          </div>
        </div>
      </section>

      <section data-ldrn-ws="about.section-2" className="relative overflow-hidden bg-band py-20">
        <div className="grid-bg absolute inset-0 opacity-10" />
        <div className="container-wide relative">
          <SectionHeading
            eyebrow={healthSafety.eyebrow}
            title={healthSafety.title}
          />
          <div className="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            {healthSafety.points.map((point, index) => (
              <Reveal
                key={point.title}
                delay={index * 80}
                className="rounded-4xl bg-white/95 p-7 shadow-card"
              >
                <span className="icon-badge h-16 w-16">
                  <Icon name={SAFETY_ICONS[index]} className="h-8 w-8" />
                </span>
                <h3 className="mt-5 text-base">{point.title}</h3>
                <p className="mt-2 text-sm leading-relaxed text-ink/90">
                  {point.text}
                </p>
              </Reveal>
            ))}
          </div>
        </div>
      </section>

      <section data-ldrn-ws="about.section-3" className="container-wide py-20">
        <Reveal className="rounded-5xl bg-sky-50 px-6 py-14 text-center sm:px-12">
          <Quote className="mx-auto h-10 w-10 text-sky-300" />
          <blockquote className="mx-auto mt-5 max-w-3xl text-balance font-heading text-2xl font-medium leading-snug text-ink sm:text-3xl">
            “{philosophy.paragraphs[0]}”
          </blockquote>
          <p className="mt-5 text-sm font-semibold uppercase tracking-[0.2em] text-sky-700">
            Little Rainbows philosophy
          </p>
        </Reveal>
      </section>

      <section data-ldrn-ws="about.section-4" className="container-wide pb-4">
        <div className="grid items-start gap-12 lg:grid-cols-2">
          <div>
            <SectionHeading
              align="left"
              eyebrow="Our staff"
              title="A caring, supported team"
              intro={healthSafety.points[1].text}
            />
            <p className="mt-5 max-w-md text-pretty text-sm leading-relaxed text-ink/90">
              {healthSafety.intro}
            </p>
          </div>
          <Reveal
            delay={120}
            className="rounded-4xl border border-sky-100 bg-white p-6 shadow-soft"
          >
            <p className="text-xs font-semibold uppercase tracking-wider text-ink/65">
              Our published commitments
            </p>
            <div className="mt-4 flex flex-wrap gap-2">
              {healthSafety.points.map((point) => (
                <span
                  key={point.title}
                  className="inline-flex items-center gap-1.5 rounded-full bg-sky-50 px-3 py-1.5 text-xs font-semibold text-sky-800"
                >
                  <BadgeCheck className="h-3.5 w-3.5" />
                  {point.title}
                </span>
              ))}
            </div>
          </Reveal>
        </div>
      </section>

      <section data-ldrn-ws="about.section-5" className="container-wide py-16 sm:py-20">
        <SectionHeading
          eyebrow="A typical day"
          title="Learning through play throughout the day"
        />
        <div className="mx-auto mt-12 max-w-3xl">
          <ol className="relative border-l border-sky-200 pl-8">
            {curriculum.blocks.map((block, index) => (
              <Reveal
                as="li"
                key={block.name}
                delay={index * 50}
                className="relative pb-9 last:pb-0"
              >
                <span className="absolute -left-[2.6rem] flex h-12 w-12 items-center justify-center rounded-full bg-sky-100 font-heading text-xs font-semibold text-sky-800 ring-4 ring-white">
                  {String(index + 1).padStart(2, "0")}
                </span>
                <h3 className="text-base">{block.name}</h3>
                <p className="mt-1 text-sm leading-relaxed text-ink/90">
                  {block.text}
                </p>
              </Reveal>
            ))}
          </ol>
        </div>
      </section>

      <CTA
        title="Come and see us for yourself"
        text={philosophy.paragraphs[3]}
        action={{ label: "Check availability", to: "/check-availability" }}
      />
    </>
  );
}
