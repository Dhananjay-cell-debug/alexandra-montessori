import { Link, Navigate, useParams } from "react-router-dom";
import {
  ArrowRight,
  ExternalLink,
  Mail,
  MapPin,
  Phone,
  Quote,
  ShieldCheck,
} from "lucide-react";
import Seo from "../components/Seo";
import Reveal from "../components/Reveal";
import Img from "../components/Img";
import Icon from "../components/Icon";
import Pending from "../components/Pending";
import TbcBadge from "../components/TbcBadge";
import { SectionHeading } from "../components/Section";
import { approach, approachPoints, credentials, curriculum, healthSafety, philosophy } from "../data/site";
import { allSettings, brand, testimonials, mediaIdentity } from "../lib/content";
import { isTbc } from "../lib/tbc";
import { settingSchema } from "../lib/schema";

const GALLERY = [
  "/assets/photography/child-at-story-and-circle-time.jpg",
  "/assets/photography/children-building-wooden-train-track.jpg",
  "/assets/photography/child-drawing-with-crayons.jpg",
];

const TEAM_PHOTOS = [
  "/assets/photography/child-waving-from-play-window.jpg",
  "/assets/photography/children-at-craft-table.jpg",
];

// Icons for the first five EYFS areas, in published order.
const EYFS_ICONS = ["Palette", "Music", "Activity", "BookOpen", "Sigma"];

export default function SettingDetail() {
  const { slug } = useParams();
  const setting = allSettings.find((candidate) => candidate.slug === slug);

  // An unknown or unpublished setting redirects rather than 404s: the
  // directory is the useful destination.
  if (!setting) return <Navigate to="/our-nursery" replace />;

  const address = isTbc(setting.address) ? null : setting.address.join(", ");
  const phone = isTbc(setting.phone) ? null : setting.phone;
  const ages = isTbc(setting.ages) ? null : setting.ages;
  const hours = isTbc(setting.hours) ? null : setting.hours;

  const offerings = [
    ...approachPoints.map((point) => ({
      label: point.title,
      icon: point.icon,
    })),
    ...curriculum.blocks.slice(0, 5).map((block, index) => ({
      label: block.name,
      icon: EYFS_ICONS[index],
    })),
  ];

  // Only a setting with a confirmed open Ofsted record may show the real
  // credential badges; anything else says plainly that it is unverified.
  const badges =
    setting.slug === "hounslow"
      ? credentials
      : [
          { label: "EYFS curriculum" },
          { label: "Learning Through Play" },
          { label: "Availability to confirm" },
          { label: "Ofsted status unverified" },
        ];

  return (
    <>
      <Seo
        path={`/our-nursery/${setting.slug}`}
        schema={
          setting.structuredDataEligible === false
            ? undefined
            : settingSchema(setting)
        }
      />

      <section data-ldrn-ws={'setting-detail.' + slug + '.section-1'} className="relative flex min-h-[70vh] items-center overflow-hidden pt-24">
        <Img
          src={setting.image}
          alt={setting.imageAlt}
          priority
          shape="square"
          className="absolute inset-0 h-full w-full"
        />
        <div className="absolute inset-0 z-[2] bg-black/40" />
        <div className="absolute inset-0 z-[2] bg-gradient-to-t from-black/60 via-transparent to-black/20" />

        <div className="container-wide relative z-[3] py-16">
          <Reveal as="nav" className="text-xs font-semibold text-white/75">
            <Link to="/" className="hover:text-white">
              Home
            </Link>
            {" / "}
            <Link to="/our-nursery" className="hover:text-white">
              Our Nursery
            </Link>
            {" / "}
            <span className="text-sky-100">{setting.name}</span>
          </Reveal>

          <Reveal as="h1" delay={80} className="mt-5 max-w-2xl !text-white">
            Little Rainbows {setting.name}
          </Reveal>

          <Reveal
            as="p"
            delay={150}
            className="mt-5 max-w-xl text-pretty text-lg text-white/90"
          >
            {[ages, hours].filter(Boolean).join(" · ") ||
              "Learning through play at Little Rainbows Day Nursery."}
          </Reveal>

          <Reveal delay={220} className="mt-8 flex flex-col gap-3 sm:flex-row">
            <Link
              to={`/check-availability?setting=${setting.slug}`}
              className="btn-white"
            >
              Check availability for {setting.name}
              <ArrowRight className="h-4 w-4" />
            </Link>
            {phone && (
              <a
                href={`tel:${phone.replace(/\s/g, "")}`}
                className="btn border border-white/50 text-white hover:bg-white/10"
              >
                <Phone className="h-4 w-4" /> {phone}
              </a>
            )}
          </Reveal>
        </div>
      </section>

      {setting.statusNote && (
        <div className="border-b border-sky-100 bg-white px-5 py-3 sm:px-8">
          <p className="mx-auto max-w-5xl text-center text-xs leading-relaxed text-ink/55">
            {setting.statusNote}{" "}
            <a
              href={setting.ofsted.providerUrl}
              target="_blank"
              rel="noreferrer noopener"
              className="inline-flex items-center gap-1 font-semibold text-sky-800 hover:underline"
            >
              View Ofsted record <ExternalLink className="h-3 w-3" />
            </a>
          </p>
        </div>
      )}

      <section data-ldrn-ws={'setting-detail.' + slug + '.section-2'} className="container-wide py-16 sm:py-20">
        <div className="grid items-center gap-12 lg:grid-cols-2">
          <Reveal className="group relative overflow-hidden rounded-5xl border border-sky-100 bg-sky-50 shadow-card">
            <Img
              src="/assets/photography/children-playing-together-at-table.jpg"
              alt="Children learning through play at Little Rainbows"
              shape="square"
              className="aspect-[4/3] w-full transition-transform duration-700 group-hover:scale-[1.04]"
            />
            <span className="pointer-events-none absolute left-4 top-4 z-[2] rounded-full bg-white/90 px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.16em] text-sky-800 shadow-soft">
              Nursery moments
            </span>
          </Reveal>
          <SectionHeading
            align="left"
            eyebrow={`Welcome to ${setting.name}`}
            title="A warm place to explore and grow"
            intro={approach.body}
          />
        </div>
      </section>

      <section data-ldrn-ws={'setting-detail.' + slug + '.section-3'} className="relative overflow-hidden bg-sky-700 py-14">
        <div className="grid-bg absolute inset-0 opacity-15" />
        <div className="container-wide relative flex flex-col items-center gap-5 text-center">
          <span className="flex h-16 w-16 items-center justify-center rounded-full bg-white/15 text-white ring-1 ring-inset ring-white/25">
            <Icon name="Sprout" className="h-8 w-8" />
          </span>
          <Reveal
            as="p"
            className="max-w-3xl text-balance font-heading text-2xl font-medium leading-snug text-white sm:text-3xl"
          >
            {philosophy.paragraphs[0]}
          </Reveal>
        </div>
      </section>

      <section data-ldrn-ws={'setting-detail.' + slug + '.section-4'} className="container-wide py-16 sm:py-20">
        <SectionHeading
          eyebrow="A peek inside"
          title={`Life at Little Rainbows ${setting.name}`}
        />
        <div className="mt-12 grid gap-4 sm:grid-cols-3">
          {(setting.gallery ?? GALLERY).map((src, index) => (
            <Reveal key={src} data-ldrn-ws={'setting-detail.' + slug + '.gallery.' + mediaIdentity(src)} delay={index * 80}>
              <Img
                src={src}
                alt="A Little Rainbows nursery activity"
                className="aspect-[4/5] w-full sm:aspect-[3/4]"
              />
            </Reveal>
          ))}
        </div>
      </section>

      <section data-ldrn-ws={'setting-detail.' + slug + '.section-5'} className="container-wide py-16 sm:py-20">
        <SectionHeading
          eyebrow="What we offer"
          title={`Learning and care at ${setting.name}`}
        />
        <div className="mt-12 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
          {offerings.map((offering, index) => (
            <Reveal
              key={offering.label}
              delay={index * 50}
              className="card card-hover flex flex-col items-center gap-3 p-6 text-center"
            >
              <span className="icon-badge h-14 w-14">
                <Icon name={offering.icon} className="h-7 w-7" />
              </span>
              <span className="font-heading text-sm font-semibold text-ink">
                {offering.label}
              </span>
            </Reveal>
          ))}
        </div>
      </section>

      <section data-ldrn-ws={'setting-detail.' + slug + '.section-6'} className="relative overflow-hidden bg-band py-20">
        <div className="grid-bg absolute inset-0 opacity-10" />
        <div className="container-wide relative">
          <SectionHeading
            eyebrow="Meet the team"
            title="The people who care for your child"
          />
          <div className="mx-auto mt-12 grid max-w-3xl gap-6 sm:grid-cols-2">
            {[philosophy.paragraphs[2], healthSafety.points[1].text].map(
              (text, index) => (
                <Reveal
                  key={text}
                  delay={index * 100}
                  className="overflow-hidden rounded-4xl bg-white shadow-card"
                >
                  <Img
                    src={TEAM_PHOTOS[index]}
                    alt="Little Rainbows children learning and playing"
                    shape="square"
                    className="aspect-[4/3] w-full"
                  />
                  <div className="p-6">
                    <h3 className="text-lg">
                      {index === 0 ? "Caring relationships" : "Staff training"}
                    </h3>
                    <p className="mt-2 text-sm leading-relaxed text-ink/90">
                      {text}
                    </p>
                  </div>
                </Reveal>
              ),
            )}
          </div>
        </div>
      </section>

      <section data-ldrn-ws={'setting-detail.' + slug + '.section-7'} className="container-wide py-16 sm:py-20">
        <SectionHeading
          eyebrow="Partnership with parents"
          title="Working with families"
          intro={approachPoints[1].text}
        />
        <div className="mx-auto mt-12 grid max-w-4xl gap-6 sm:grid-cols-3">
          {approachPoints.map((point, index) => (
            <Reveal
              key={point.title}
              delay={index * 70}
              className="card card-hover h-full p-7"
            >
              <span className="icon-badge h-14 w-14">
                <Icon name={point.icon} className="h-7 w-7" />
              </span>
              <h3 className="mt-5 text-base">{point.title}</h3>
              <p className="mt-2 text-sm leading-relaxed text-ink/90">
                {point.text}
              </p>
            </Reveal>
          ))}
        </div>
      </section>

      <section data-ldrn-ws={'setting-detail.' + slug + '.section-8'} className="container-wide pb-16">
        <Reveal className="grid gap-4 rounded-4xl bg-sky-700 p-8 text-white sm:grid-cols-3 sm:p-10">
          <div className="flex items-start gap-3">
            <MapPin className="mt-0.5 h-5 w-5 shrink-0 text-sky-100" />
            <div>
              <p className="text-xs font-semibold uppercase tracking-wider text-sky-100">
                Visit us
              </p>
              {address ? (
                <p className="mt-1 text-sm">{address}</p>
              ) : (
                <Pending className="mt-1 !text-white/80" />
              )}
              <TbcBadge value={setting.address} />
            </div>
          </div>

          <div className="flex items-start gap-3">
            <Phone className="mt-0.5 h-5 w-5 shrink-0 text-sky-100" />
            <div>
              <p className="text-xs font-semibold uppercase tracking-wider text-sky-100">
                Call us
              </p>
              {phone ? (
                <a
                  href={`tel:${phone.replace(/\s/g, "")}`}
                  className="mt-1 block text-sm hover:underline"
                >
                  {phone}
                </a>
              ) : (
                <Pending className="mt-1 !text-white/80" />
              )}
              <TbcBadge value={setting.phone} />
            </div>
          </div>

          <div className="flex items-start gap-3">
            <Mail className="mt-0.5 h-5 w-5 shrink-0 text-sky-100" />
            <div>
              <p className="text-xs font-semibold uppercase tracking-wider text-sky-100">
                Email us
              </p>
              <a
                href={`mailto:${setting.email || brand.email}`}
                className="mt-1 block break-all text-sm hover:underline"
              >
                {setting.email || brand.email}
              </a>
            </div>
          </div>
        </Reveal>
      </section>

      <section data-ldrn-ws={'setting-detail.' + slug + '.section-9'} className="container-wide pb-16">
        <Reveal className="rounded-4xl border border-sky-100 bg-white p-8 shadow-soft">
          <div className="mx-auto grid w-full max-w-[26rem] gap-4 sm:max-w-4xl sm:grid-cols-2 lg:grid-cols-4">
            {badges.map((badge) => (
              <div
                key={badge.label}
                className="grid min-h-20 grid-cols-[3.5rem_1fr] items-center gap-4 rounded-3xl bg-sky-50 px-5 py-5 text-left sm:min-h-40 sm:grid-cols-1 sm:justify-items-center sm:text-center"
              >
                <span className="icon-badge h-14 w-14">
                  <ShieldCheck className="h-7 w-7" />
                </span>
                <span className="font-heading text-sm font-semibold leading-snug text-ink">
                  {badge.label}
                </span>
              </div>
            ))}
          </div>
        </Reveal>
      </section>

      <section data-ldrn-ws={'setting-detail.' + slug + '.section-10'} className="container-wide pb-20">
        <SectionHeading
          eyebrow="What parents say"
          title="Families at Little Rainbows"
        />
        <div className="mt-12 grid gap-6 md:grid-cols-3">
          {testimonials.slice(0, 3).map((testimonial, index) => (
            <Reveal
              key={testimonial.id || testimonial.attribution}
              data-ldrn-ws={`setting.${slug}.testimonial.${testimonial.id || index}`}
              delay={index * 90}
              className="card flex h-full flex-col p-7"
            >
              <Quote className="h-7 w-7 text-sky-300" />
              <p className="mt-3 flex-1 text-pretty text-sm leading-relaxed text-ink/85">
                {testimonial.body[0]}
              </p>
              <p className="mt-5 border-t border-sky-100 pt-4 font-heading text-sm font-semibold text-ink">
                {testimonial.attribution}
              </p>
            </Reveal>
          ))}
        </div>
        <div className="mt-8 text-center">
          <Link to="/testimonials" className="btn-outline">
            Read all parent stories
          </Link>
        </div>
      </section>
    </>
  );
}
