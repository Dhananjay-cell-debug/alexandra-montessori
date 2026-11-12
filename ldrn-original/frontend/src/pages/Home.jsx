import { Link } from "react-router-dom";
import { ArrowRight, Quote } from "lucide-react";
import Seo from "../components/Seo";
import Reveal from "../components/Reveal";
import Img from "../components/Img";
import Icon from "../components/Icon";
import GroupNotice from "../components/GroupNotice";
import { asset } from "../lib/asset";
import { approachPoints, credentials, hero, homeCards, philosophy, primaryCta } from "../data/site";
import { about, brand, testimonials } from "../lib/content";
import { organizationSchema } from "../lib/schema";

function Hero() {
  return (
    <section data-ldrn-ws="home.section-1" className="relative isolate flex h-[440px] items-center overflow-hidden sm:h-[520px] lg:h-[600px]">
      {/* The hero image is the largest paint on the page, so it is a plain
          <img> with high fetch priority rather than a lazy <Img>. */}
      <img
        src={asset(hero.image)}
        alt={hero.imageAlt}
        className="absolute inset-0 -z-20 h-full w-full object-cover object-[70%_center] lg:object-[center_45%]"
        fetchPriority="high"
      />
      <div className="hero-photo-scrim absolute inset-0 -z-10" aria-hidden="true" />
      <div className="container-wide relative text-center lg:text-left">
        <Reveal className="mx-auto flex max-w-3xl flex-col items-center gap-5 lg:mx-0 lg:max-w-[42rem] lg:items-start">
          <span className="eyebrow !text-sky-200">{hero.eyebrow}</span>
          <h1 className="!text-white">{brand.name}</h1>
          <span className="rainbow-rule" aria-hidden />
          <p className="font-heading text-xl text-sky-100 sm:text-2xl">
            {hero.tagline}
          </p>
          <div className="mt-3 flex flex-col gap-3 sm:flex-row">
            <Link to={primaryCta.to} className="btn-white">
              {primaryCta.label}
            </Link>
            <Link
              to="/our-nursery"
              className="btn border border-white/50 text-white hover:bg-white/10"
            >
              Our nursery
              <ArrowRight className="h-4 w-4" strokeWidth={1.8} aria-hidden />
            </Link>
          </div>
          <GroupNotice
            className="mt-1 max-w-2xl text-center text-sm font-semibold leading-relaxed text-sky-100 sm:text-base lg:text-left"
            linkClassName="font-bold text-white underline"
          />
        </Reveal>
      </div>
    </section>
  );
}

function Signposts() {
  return (
    <section data-ldrn-ws="home.section-2" className="bg-band py-12 sm:py-14">
      <div className="container-wide grid gap-10 sm:grid-cols-2 lg:grid-cols-3 lg:gap-12">
        {homeCards.map((card, index) => (
          <Reveal
            key={card.title}
            delay={index * 100}
            className="flex flex-col items-center text-center"
          >
            <h2 className="text-2xl sm:text-[1.7rem]">{card.title}</h2>
            <Link
              to={card.to}
              className="group mt-5 block rounded-t-full focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-600 focus-visible:ring-offset-4"
              aria-label={`${card.cta}: ${card.title}`}
            >
              <Img
                src={card.image}
                alt={card.imageAlt}
                shape="arch"
                className="h-52 w-44 border border-sky-200 shadow-soft transition-transform duration-700 group-hover:scale-[1.03] sm:h-56 sm:w-48"
              />
            </Link>
            <Link
              to={card.to}
              className="mt-4 text-base font-semibold text-ink underline-offset-4 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-600"
            >
              {card.cta}
            </Link>
            <span className="mt-3 flex h-11 w-11 items-center justify-center text-sky-800">
              <Icon name={card.icon} className="h-8 w-8" />
            </span>
          </Reveal>
        ))}
      </div>
    </section>
  );
}

function Approach() {
  return (
    <section data-ldrn-ws="home.section-3" className="bg-white py-12 sm:py-16">
      <div className="container-wide grid gap-10 md:grid-cols-3">
        {approachPoints.map((point, index) => (
          <Reveal
            key={point.title}
            delay={index * 90}
            className="flex flex-col items-center text-center"
          >
            <span className="flex h-12 w-12 items-center justify-center text-sky-800">
              <Icon name={point.icon} className="h-9 w-9" />
            </span>
            <p className="mt-4 max-w-[17rem] text-lg font-semibold leading-snug text-ink">
              {point.text}
            </p>
          </Reveal>
        ))}
      </div>
    </section>
  );
}

// Editable in wp-admin (Website Content -> About us); each blank field keeps
// the built-in copy below.
function AboutSummary() {
  const chips = about.milestones.length
    ? about.milestones
    : [
        `Welcoming families since ${brand.founded}`,
        brand.tagline,
        "Ofsted Good — Hounslow",
      ];
  const paragraphs = about.paragraphs.length
    ? about.paragraphs
    : philosophy.paragraphs;

  return (
    <section data-ldrn-ws="home.section-4" className="bg-sky-50 py-14 sm:py-20">
      <div className="container-wide grid gap-10 lg:grid-cols-[1.05fr_0.95fr] lg:items-center">
        <Reveal className="order-2 lg:order-1">
          <h2 className="text-4xl sm:text-5xl">{about.heading || "About us"}</h2>
          <div className="mt-6 space-y-5 text-base leading-relaxed text-ink/85">
            {paragraphs.map((paragraph) => (
              <p key={paragraph}>{paragraph}</p>
            ))}
          </div>
          <div className="mt-8 flex flex-wrap gap-3">
            {chips.map((chip) => (
              <span
                key={chip}
                className="rounded-full border border-sky-200 bg-white px-4 py-2 text-sm font-semibold text-sky-800 shadow-soft"
              >
                {chip}
              </span>
            ))}
            {about.upcoming && (
              <span className="rounded-full border border-sky-800 bg-sky-800 px-4 py-2 text-sm font-semibold text-white shadow-soft">
                {about.upcoming}
              </span>
            )}
          </div>
        </Reveal>
        <Reveal delay={120} className="order-1 flex justify-center lg:order-2">
          <Img
            src={about.image || philosophy.image}
            srcSet={about.image ? about.imageSrcSet || undefined : undefined}
            sizes={about.image ? about.imageSizes || undefined : undefined}
            alt={about.image ? about.imageAlt : philosophy.imageAlt}
            shape="circle"
            className="aspect-square w-full max-w-md shadow-card"
          />
        </Reveal>
      </div>
    </section>
  );
}

function ParentStories() {
  return (
    <section data-ldrn-ws="home.section-5" className="relative overflow-hidden py-16 sm:py-24">
      <img
        src="/assets/photography/home-testimonials-creative-play-20261002.webp"
        alt=""
        className="absolute inset-0 h-full w-full object-cover object-bottom"
      />
      <div className="pointer-events-none absolute inset-0 z-10 bg-sky-900/78" />
      <div className="container-wide relative z-20">
        <h2 className="text-center !text-white">What Parents Say</h2>
        <div className="mt-12 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
          {testimonials.slice(0, 3).map((testimonial, index) => (
            <Reveal
              key={testimonial.id || testimonial.attribution}
              data-ldrn-ws={`home.testimonial.${testimonial.id || index}`}
              delay={index * 110}
              className="flex h-full flex-col rounded-4xl bg-white/95 p-8 text-left shadow-card sm:p-9"
            >
              <Quote
                className="h-9 w-9 shrink-0 text-sky-300"
                strokeWidth={1.6}
                aria-hidden
              />
              <h3 className="mt-5 text-xl sm:text-2xl">
                {testimonial.attribution}
              </h3>
              <div className="mt-4 flex-1 space-y-3 text-sm leading-relaxed text-ink/85">
                {testimonial.body.map((paragraph, paragraphIndex) => (
                  <p key={`${testimonial.attribution}-${paragraphIndex}`}>
                    {paragraph}
                  </p>
                ))}
              </div>
            </Reveal>
          ))}
        </div>
        <div className="mt-10 text-center">
          <Link
            to="/testimonials"
            className="inline-flex rounded-full border border-white/60 bg-white/10 px-6 py-3 text-sm font-semibold text-white transition-colors hover:bg-white/20"
          >
            Read all parent stories
          </Link>
        </div>
      </div>
    </section>
  );
}

function Credentials() {
  return (
    <section data-ldrn-ws="home.section-6" className="bg-white py-12 sm:py-16">
      <div className="container-wide grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
        {credentials.map((credential) => (
          <div
            key={credential.label}
            className="grid grid-cols-[2.25rem_1fr] items-center gap-4 text-left text-sky-900 sm:grid-cols-1 sm:justify-items-center sm:text-center"
          >
            <span className="flex h-8 w-8 items-center justify-center">
              <Icon name={credential.icon} className="h-7 w-7" />
            </span>
            <div>
              <p className="text-sm font-bold uppercase tracking-wide">
                {credential.label}
              </p>
              <p className="mt-1 text-xs text-ink/65">{credential.detail}</p>
            </div>
          </div>
        ))}
      </div>
    </section>
  );
}

export default function Home() {
  return (
    <>
      <Seo path="/" schema={organizationSchema()} />
      <Hero />
      <Signposts />
      <Approach />
      <AboutSummary />
      <ParentStories />
      <Credentials />
    </>
  );
}
