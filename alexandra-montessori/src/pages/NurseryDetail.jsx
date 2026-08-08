import { useState } from "react";
import { useParams, Link, Navigate } from "react-router-dom";
import {
  ArrowRight,
  MapPin,
  Phone,
  Mail,
  Quote,
  ShieldCheck,
  Star,
} from "lucide-react";
import Reveal from "../components/Reveal";
import Icon from "../components/Icon";
import Img from "../components/Img";
import Seo from "../components/Seo";
import { safeJsonLd } from "../lib/safeJsonLd";
// BookingModal is intentionally retained (email/WP-enquiry flow) so the
// swap back from Calendly is a one-line change - see src/lib/calendly.js
import CalendlyModal from "../components/CalendlyModal";
import { SectionHeading } from "../components/Section";
import {
  brand,
  locationBySlug,
  team,
  partnership,
  testimonials as staticTestimonials,
  accreditations,
} from "../data/site";
import { gmailHref, telHref } from "../lib/contact";
import { cmsCollection } from "../lib/cms";

const mealMoments = [
  {
    label: "Breakfast",
    image: "/assets/organisation/practical-kitchen.webp",
    alt: "A child-height practical life kitchen area in the nursery classroom",
    // Show more of the lower part of this portrait so the food stays visible.
    position: "50% 100%",
  },
  {
    label: "Lunch",
    image: "/assets/organisation/bake-together.webp",
    alt: "Two children in Alexandra Montessori aprons baking together at nursery",
    // Landscape photo; keep the two children's faces centred in the square crop.
    position: "50% 34%",
  },
];

// Photo + crop framing for each nursery team card. Positions bias the crop so
// faces/heads stay in view (aspect-[4/3] otherwise cuts them off).
const teamMoments = [
  {
    image: "/assets/organisation/teacher-hug.webp",
    position: "50% 0%",
    transform: "scale(1.1) translateX(4%)",
  },
  {
    image: "/assets/organisation/friends-two.webp",
    // This 3:2 photograph only crops at the sides in the 4:3 card, preserving
    // both children's complete heads at every nursery breakpoint.
    position: "50% 50%",
  },
];

function NurseryWelcomeImage({ loc }) {
  return (
    <div className="group relative overflow-hidden rounded-5xl border border-sage-100 bg-sage-50 shadow-card">
      <Img
        src={loc.welcomeImage || loc.image}
        alt={`A nursery moment at the Alexandra Montessori ${loc.name} nursery`}
        rounded="rounded-none"
        priority
        position={loc.welcomePosition}
        imageTransform={loc.welcomeTransform}
        className="block aspect-[4/3] w-full transition-transform duration-700 group-hover:scale-[1.04]"
      />
      <div className="pointer-events-none absolute left-4 top-4 rounded-full bg-white/90 px-3 py-1.5 text-xs font-medium uppercase tracking-[0.16em] text-sage-700 shadow-soft">
        Nursery moments
      </div>
    </div>
  );
}

export default function NurseryDetail() {
  const { slug } = useParams();
  const [calOpen, setCalOpen] = useState(false);
  const loc = locationBySlug(slug);
  // Lock every nursery page to the exact same three testimonials shown in the
  // homepage "What Parents Say" section (client request: they must match).
  const nurseryTestimonials = cmsCollection(
    "testimonials",
    staticTestimonials,
  ).slice(0, 3);

  if (!loc) return <Navigate to="/nurseries" replace />;
  const galleryImages = Array.isArray(loc.gallery)
    ? loc.gallery.filter((image) => typeof image === "string" && image.trim())
    : [];
  const galleryGridClass =
    galleryImages.length === 1
      ? "mx-auto mt-12 grid max-w-md gap-4"
      : galleryImages.length === 2
        ? "mx-auto mt-12 grid max-w-3xl gap-4 sm:grid-cols-2"
        : "mt-12 grid gap-4 sm:grid-cols-3";
  const featureHeading =
    loc.offerHeading || `Everything your child needs to thrive in ${loc.name}`;
  const showMealSection = loc.showMealSection !== false;
  const schemaImage = /^https?:\/\//i.test(loc.image)
    ? loc.image
    : `https://alexandramontessori.co.uk${loc.image}`;

  const jsonLd = {
    "@context": "https://schema.org",
    "@type": "ChildCare",
    name: `Alexandra Montessori - ${loc.name}`,
    description: loc.welcome,
    image: schemaImage,
    telephone: loc.phone,
    url: `https://alexandramontessori.co.uk/nurseries/${loc.id}`,
    address: {
      "@type": "PostalAddress",
      streetAddress: loc.address,
      addressLocality: "London",
      postalCode: loc.postcode,
      addressCountry: "GB",
    },
    openingHours: "Mo-Fr 08:00-18:00",
  };

  return (
    <>
      <Seo
        title={`${loc.name} Nursery`}
        description={loc.short}
        path={`/nurseries/${loc.id}`}
        image={loc.image}
      />
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: safeJsonLd(jsonLd) }}
      />

      {/* 1 - Hero */}
      <section className="relative flex min-h-[70vh] items-center overflow-hidden pt-24" data-am-vb-region="nursery-hero">
        <Img
          src={loc.image}
          alt={`The Alexandra Montessori ${loc.name} nursery`}
          priority
          rounded="rounded-none"
          position={loc.heroPosition}
          className="absolute inset-0 h-full w-full"
        />
        <div className="absolute inset-0 bg-black/40" />
        <div className="absolute inset-0 bg-gradient-to-t from-black/55 via-transparent to-black/20" />
        <div className="container-wide relative py-16">
          <Reveal as="nav" className="text-xs font-medium text-cream/70">
            <Link to="/" className="hover:text-white">
              Home
            </Link>
            {" / "}
            <Link to="/nurseries" className="hover:text-white">
              Our Nurseries
            </Link>
            {" / "}
            <span className="text-sage-200">{loc.name}</span>
          </Reveal>
          <Reveal
            as="h1"
            delay={80}
            className="mt-5 max-w-2xl text-balance font-heading text-4xl font-medium leading-tight text-white sm:text-5xl"
          >
            {loc.heroTagline}
          </Reveal>
          <Reveal
            as="p"
            delay={150}
            className="mt-5 max-w-xl text-pretty text-lg text-cream/85"
          >
            Montessori Inspired care and education for {brand.ageRange}, in
            the heart of {loc.area}.
          </Reveal>
          <Reveal delay={220} className="mt-8 flex flex-col gap-3 sm:flex-row">
            <button
              type="button"
              onClick={() => setCalOpen(true)}
              className="btn-cream"
            >
              Book a visit to {loc.name}
              <ArrowRight className="h-4 w-4" />
            </button>
            <a
              href={`tel:${loc.phone.replace(/\s/g, "")}`}
              className="btn border border-cream/40 text-cream hover:bg-cream/10"
            >
              <Phone className="h-4 w-4" /> {loc.phone}
            </a>
          </Reveal>
        </div>
      </section>

      {/* 2 - Welcome */}
      <section className="container-wide py-16 sm:py-20" data-am-vb-region="nursery-welcome">
        <div className="grid items-center gap-12 lg:grid-cols-2">
          <Reveal>
            <NurseryWelcomeImage loc={loc} />
          </Reveal>
          <div>
            <SectionHeading
              align="left"
              eyebrow={`Welcome to ${loc.name}`}
              title="A second home for your child"
              intro={loc.welcome}
            />
          </div>
        </div>
      </section>

      {/* 3 - Philosophy strip */}
      <section className="relative overflow-hidden bg-sage-500 py-14" data-am-vb-region="nursery-philosophy">
        <div className="grid-bg absolute inset-0 opacity-15" />
        <div className="container-wide relative flex flex-col items-center gap-5 text-center">
          <span className="flex h-16 w-16 items-center justify-center rounded-full bg-white/15 text-white ring-1 ring-inset ring-white/25">
            <Icon name="Sprout" className="h-8 w-8" />
          </span>
          <Reveal
            as="p"
            className="max-w-3xl text-balance font-heading text-2xl font-medium leading-snug text-white sm:text-3xl"
          >
            We build curiosity, independence and confidence through consistent
            care, affection and respect.
          </Reveal>
        </div>
      </section>

      {/* 3b - A peek inside (gallery) */}
      {galleryImages.length > 0 ? (
        <section className="container-wide py-16 sm:py-20" data-am-vb-region="nursery-gallery">
          <SectionHeading
            eyebrow="A peek inside"
            title={`Life at our ${loc.name} nursery`}
          />
          <div className={galleryGridClass}>
            {galleryImages.map((g, i) => (
              <Reveal key={g} delay={i * 80}>
                <Img
                  src={g}
                  alt={`Children at the Alexandra Montessori ${loc.name} nursery`}
                  className="aspect-[4/5] w-full sm:aspect-[3/4]"
                />
              </Reveal>
            ))}
          </div>
        </section>
      ) : null}

      {/* 4 - Features grid */}
      <section className="container-wide py-16 sm:py-20" data-am-vb-region="nursery-features">
        <SectionHeading
          eyebrow="What we offer"
          title={featureHeading}
          maxWidthClass="max-w-[60rem]"
        />
        <div className="mt-12 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
          {loc.features.map((f, i) => (
            <Reveal
              key={f.label}
              delay={i * 50}
              className="card card-hover flex flex-col items-center gap-3 p-6 text-center"
            >
              <span className="icon-badge h-14 w-14">
                <Icon name={f.icon} className="h-7 w-7" />
              </span>
              <span className="font-heading text-sm font-medium text-ink">
                {f.label}
              </span>
            </Reveal>
          ))}
        </div>
      </section>

      {/* 5 - Healthy meals */}
      {showMealSection ? (
        <section className="container-wide py-16 sm:py-20" data-am-vb-region="nursery-meals">
          <div className="grid items-center gap-12 lg:grid-cols-2">
            <div>
              <SectionHeading
                align="left"
                eyebrow="Healthy meals & nutrition"
                title="Freshly prepared meals and snacks"
                intro="Nutritious, balanced meals and snacks are prepared with careful support for allergies and dietary needs."
              />
            </div>
            <Reveal className="grid grid-cols-2 gap-4">
              {mealMoments.map((m) => (
                <div
                  key={m.label}
                  className="group relative overflow-hidden rounded-4xl shadow-soft"
                >
                  <Img
                    src={m.image}
                    alt={m.alt}
                    rounded="rounded-none"
                    position={m.position}
                    className="aspect-square w-full transition-transform duration-500 group-hover:scale-[1.04]"
                  />
                  <span className="absolute inset-x-3 bottom-3 rounded-full bg-white/90 px-3 py-2 text-center text-xs font-medium uppercase tracking-[0.16em] text-sage-700 shadow-soft">
                    {m.label}
                  </span>
                </div>
              ))}
            </Reveal>
          </div>
        </section>
      ) : null}

      {/* 6 - Meet the team */}
      <section className="relative overflow-hidden bg-sand py-20" data-am-vb-region="nursery-team">
        <div className="grid-bg absolute inset-0 opacity-10" />
        <div className="container-wide relative">
          <SectionHeading
            eyebrow="Meet the team"
            title={`The people who'll care for your child`}
          />
          <div className="mx-auto mt-12 grid max-w-3xl gap-6 sm:grid-cols-2">
            {team.map((m, i) => (
              <Reveal
                key={i}
                delay={i * 100}
                className="overflow-hidden rounded-4xl bg-cream/95 shadow-card"
              >
                <Img
                  src={teamMoments[i % teamMoments.length].image}
                  position={teamMoments[i % teamMoments.length].position}
                  imageTransform={
                    teamMoments[i % teamMoments.length].transform
                  }
                  alt={`Children supported by the Alexandra Montessori ${m.role.toLowerCase()}`}
                  rounded="rounded-none"
                  className="aspect-[4/3] w-full"
                />
                <div className="p-6">
                  <h3 className="font-heading text-lg font-medium text-ink">
                    {m.role}
                  </h3>
                  <p className="mt-2 text-sm leading-relaxed text-ink/90">
                    {m.note}
                  </p>
                </div>
              </Reveal>
            ))}
          </div>
        </div>
      </section>

      {/* 8 - Partnership with parents */}
      <section className="container-wide py-16 sm:py-20" data-am-vb-region="nursery-partnership">
        <SectionHeading
          eyebrow="Partnership with parents"
          title="We work hand in hand with families"
          intro="A close, honest partnership with parents is at the heart of everything we do."
        />
        <div className="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
          {partnership.map((p, i) => (
            <Reveal
              key={p.title}
              delay={i * 70}
              className="card card-hover h-full p-7"
            >
              <span className="icon-badge h-14 w-14">
                <Icon name={p.icon} className="h-7 w-7" />
              </span>
              <h3 className="mt-5 font-heading text-base font-medium text-ink">
                {p.title}
              </h3>
              <p className="mt-2 text-sm leading-relaxed text-ink/90">
                {p.text}
              </p>
            </Reveal>
          ))}
        </div>
      </section>

      {/* 9 - Contact info block */}
      <section className="container-wide pb-16" data-am-vb-region="nursery-contact">
        <Reveal className="grid gap-4 rounded-4xl bg-sage-500 p-8 text-cream sm:grid-cols-3 sm:p-10">
          <div className="flex items-start gap-3">
            <MapPin className="mt-0.5 h-5 w-5 shrink-0 text-sage-200" />
            <div>
              <p className="text-xs font-medium uppercase tracking-wider text-sage-200">
                Visit us
              </p>
              <p className="mt-1 text-sm">{loc.address}</p>
            </div>
          </div>
          <div className="flex items-start gap-3">
            <Phone className="mt-0.5 h-5 w-5 shrink-0 text-sage-200" />
            <div>
              <p className="text-xs font-medium uppercase tracking-wider text-sage-200">
                Call us
              </p>
              <a
                href={telHref(loc.phone)}
                className="mt-1 block text-sm hover:underline"
              >
                {loc.phone}
              </a>
            </div>
          </div>
          <div className="flex items-start gap-3">
            <Mail className="mt-0.5 h-5 w-5 shrink-0 text-sage-200" />
            <div>
              <p className="text-xs font-medium uppercase tracking-wider text-sage-200">
                Email us
              </p>
              <a
                href={gmailHref(loc.email)}
                target="_blank"
                rel="noopener noreferrer"
                className="mt-1 block break-all text-sm hover:underline"
              >
                {loc.email}
              </a>
            </div>
          </div>
        </Reveal>
      </section>

      {/* 11 - Accreditations */}
      <section className="container-wide pb-16" data-am-vb-region="nursery-accreditations">
        <Reveal className="rounded-4xl border border-sage-100 bg-white p-8 shadow-soft">
          <div className="mx-auto grid w-full max-w-[26rem] gap-4 sm:max-w-4xl sm:grid-cols-2 sm:gap-5 lg:grid-cols-4">
            {accreditations.map((b) => (
              <div
                key={b}
                className="grid min-h-20 grid-cols-[3.5rem_1fr] items-center gap-4 rounded-3xl bg-cream px-5 py-5 text-left sm:min-h-40 sm:grid-cols-1 sm:justify-items-center sm:px-4 sm:py-6 sm:text-center"
              >
                <span className="icon-badge h-14 w-14">
                  <ShieldCheck className="h-7 w-7" strokeWidth={1.6} />
                </span>
                <span className="font-heading text-sm font-medium leading-snug text-ink">
                  {b}
                </span>
              </div>
            ))}
          </div>
        </Reveal>
      </section>

      {/* 12 - Testimonials */}
      {nurseryTestimonials.length > 0 ? (
        <section className="container-wide pb-20" data-am-vb-region="nursery-testimonials">
          <SectionHeading
            eyebrow="What parents say"
            title={`Families love ${loc.name}`}
          />
          <div className="mt-12 grid gap-6 md:grid-cols-3">
            {nurseryTestimonials.map((testimonial, index) => (
              <Reveal
                key={testimonial.id || `${testimonial.title}-${index}`}
                delay={index * 90}
                className="card flex h-full flex-col p-7"
              >
                <div className="flex gap-1">
                  {[...Array(5)].map((_, star) => (
                    <Star
                      key={star}
                      className="h-4 w-4 fill-sage-400 text-sage-400"
                    />
                  ))}
                </div>
                <Quote className="mt-4 h-7 w-7 text-sage-300" />
                <p className="mt-3 flex-1 text-pretty text-sm leading-relaxed text-ink/85">
                  &ldquo;{testimonial.quote}&rdquo;
                </p>
                <p className="mt-5 border-t border-sage-100 pt-4 font-heading text-sm font-medium text-ink">
                  {testimonial.name}
                  <span className="font-normal text-ink/85">
                    {testimonial.location
                      ? `, ${testimonial.location}`
                      : ""}
                  </span>
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
      ) : null}

      {/* Booking popup for THIS nursery - opens from the hero "Book a visit" button */}
      <CalendlyModal
        region="nursery-booking-modal"
        open={calOpen}
        onClose={() => setCalOpen(false)}
        nursery={loc}
      />
    </>
  );
}
