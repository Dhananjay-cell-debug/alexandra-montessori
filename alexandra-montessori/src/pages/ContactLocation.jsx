import { useParams, Navigate, Link } from "react-router-dom";
import {
  Phone,
  Mail,
  MapPin,
  Clock,
  MapPinned,
  ArrowRight,
} from "lucide-react";
import PageHeader from "../components/PageHeader";
import Reveal from "../components/Reveal";
import Seo from "../components/Seo";
import Img from "../components/Img";
import ContactForm from "../components/ContactForm";
import { locationBySlug, locations } from "../data/site";
import { gmailHref, telHref } from "../lib/contact";

export default function ContactLocation() {
  const { slug } = useParams();
  const loc = locationBySlug(slug);
  if (!loc) return <Navigate to="/contact" replace />;

  return (
    <>
      <Seo
        title={`Contact ${loc.name}`}
        description={`Get in touch with the Alexandra Montessori ${loc.name} nursery - ${loc.address}. Call ${loc.phone} or send an enquiry to book a show-around.`}
        path={`/contact/${loc.id}`}
        image={loc.image}
      />
      <PageHeader
        region="contact-location-header"
        crumb={`Contact, ${loc.name}`}
        eyebrow="Contact us"
        title={`Contact our ${loc.name} nursery`}
        intro={`Book a show-around at ${loc.name}, ask about availability or funded hours - we'd love to hear from you.`}
      />

      <section className="container-wide py-10">
        <div className="grid gap-10 lg:grid-cols-12">
          <Reveal className="lg:col-span-7" data-am-vb-region="contact-location-form">
            <ContactForm defaultBranch={loc.id} />
          </Reveal>

          <div className="lg:col-span-5 space-y-6">
            <Reveal data-am-vb-region="contact-location-form">
              <Img
                src={loc.image}
                alt={`The Alexandra Montessori ${loc.name} nursery`}
                className="aspect-[16/10] w-full"
              />
            </Reveal>
            <Reveal delay={80} className="card p-7" data-am-vb-region="contact-location-details">
              <div className="space-y-4">
                <div className="flex items-start gap-3">
                  <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-sage-500 text-white">
                    <MapPin className="h-5 w-5" />
                  </span>
                  <div>
                    <p className="text-xs font-medium uppercase tracking-wider text-ink/85">
                      Visit us
                    </p>
                    <p className="font-heading text-sm font-medium text-ink">
                      {loc.address}
                    </p>
                  </div>
                </div>
                <a href={telHref(loc.phone)} className="flex items-start gap-3">
                  <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-sage-500 text-white">
                    <Phone className="h-5 w-5" />
                  </span>
                  <div>
                    <p className="text-xs font-medium uppercase tracking-wider text-ink/85">
                      Call us
                    </p>
                    <p className="font-heading text-sm font-medium text-ink">
                      {loc.phone}
                    </p>
                  </div>
                </a>
                <a
                  href={gmailHref(loc.email)}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="flex items-start gap-3"
                >
                  <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-sage-500 text-white">
                    <Mail className="h-5 w-5" />
                  </span>
                  <div>
                    <p className="text-xs font-medium uppercase tracking-wider text-ink/85">
                      Email us
                    </p>
                    <p className="font-heading text-sm font-medium text-ink break-all">
                      {loc.email}
                    </p>
                  </div>
                </a>
                <div className="flex items-start gap-3">
                  <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-sage-500 text-white">
                    <Clock className="h-5 w-5" />
                  </span>
                  <div>
                    <p className="text-xs font-medium uppercase tracking-wider text-ink/85">
                      Opening hours
                    </p>
                    <p className="font-heading text-sm font-medium text-ink">
                      {loc.hours}
                    </p>
                  </div>
                </div>
              </div>
            </Reveal>
            <Reveal delay={140} className="card overflow-hidden" data-am-vb-region="contact-location-gallery">
              <div className="relative">
                <Img
                  src={loc.gallery?.[0] || loc.image}
                  alt={`Children at Alexandra Montessori ${loc.name}`}
                  rounded="rounded-none"
                  className="h-40 w-full"
                />
                <div className="absolute inset-0 bg-gradient-to-t from-black/55 via-black/10 to-transparent" />
                <div className="absolute inset-x-4 bottom-4 flex items-center gap-2 text-white">
                  <MapPinned className="h-5 w-5" />
                  <span className="text-xs font-medium uppercase tracking-[0.18em]">
                    {loc.name} {loc.postcode}
                  </span>
                </div>
              </div>
            </Reveal>
          </div>
        </div>
      </section>

      {/* Other nurseries */}
      <section className="container-wide pb-16" data-am-vb-region="contact-location-other">
        <p className="text-center text-sm font-medium uppercase tracking-[0.2em] text-sage-600">
          Our other nurseries
        </p>
        <div className="mt-6 flex flex-wrap justify-center gap-3">
          {locations
            .filter((l) => l.id !== loc.id)
            .map((l) => (
              <Link key={l.id} to={`/contact/${l.id}`} className="btn-outline">
                {l.name} <ArrowRight className="h-4 w-4" />
              </Link>
            ))}
        </div>
      </section>
    </>
  );
}
