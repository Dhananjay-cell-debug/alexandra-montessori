import { Link, useSearchParams } from "react-router-dom";
import PageHeader from "../components/PageHeader";
import Reveal from "../components/Reveal";
import Seo from "../components/Seo";
import { socialLinks } from "../data/socialLinks";
import { locations } from "../data/site";
import { gmailHref, telHref } from "../lib/contact";
import { asset } from "../lib/asset";
import { homeElement, homeHref, homeMedia } from "../lib/homeVisual";

export default function Contact() {
  const [params] = useSearchParams();
  // "Book a place" on an event detail page arrives with ?event=<title>.
  const eventTitle = params.get("event");
  const eventQuery = eventTitle ? `?event=${encodeURIComponent(eventTitle)}` : "";
  return (
    <>
      <Seo
        title="Contact Us"
        description="Book a show-around, ask about availability or funded hours, or just say hello. Contact Alexandra Montessori in Hammersmith, Heston or Hounslow."
        path="/contact"
      />
      <PageHeader title="Contact us" region="contact-header" />

      <section data-am-ws="contact.section-1" className="bg-white pb-10 pt-2" data-am-vb-region="contact-directory">
        <div className="container-wide">
          <div className="grid gap-4 md:grid-cols-3">
            {locations.map((location, index) => (
              <Reveal
                key={location.id}
                delay={index * 80}
                className="rounded-4xl border border-sage-100 bg-sage-50 p-6 shadow-soft"
              >
                <h2 className="font-heading text-xl font-medium text-sage-800">
                  {location.name}
                </h2>
                <p className="mt-2 text-sm leading-relaxed text-ink/85">
                  {location.address}
                </p>
                <a
                  href={telHref(location.phone)}
                  className="mt-4 block font-semibold text-sage-700 hover:underline"
                >
                  {location.phone}
                </a>
                <a
                  href={gmailHref(location.email)}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="mt-1 block break-all text-sm font-semibold text-sage-700 hover:underline"
                >
                  {location.email}
                </a>
                <p className="mt-3 text-xs font-medium uppercase tracking-[0.16em] text-ink/85">
                  Ages {location.ageRange}
                </p>
                <Link to={`/nurseries/${location.id}${eventQuery}#contact`} className="btn-primary mt-5">
                  Enquire at {location.name}
                </Link>
              </Reveal>
            ))}
          </div>

          <div className="mt-6 flex items-center justify-center gap-3 text-sage-800" data-am-vb-region="contact-social">
            {socialLinks.map(({ label, href, Icon }, index) => {
              const key = `social-${index + 1}-icon`;
              const icon = homeMedia(key, "", `${label} icon`);
              const item = homeElement(key);
              const destination = homeHref(key, href);
              return (
              <a
                key={label}
                href={destination}
                target={destination === "#" ? undefined : "_blank"}
                rel="noopener noreferrer"
                aria-label={item.linkDescription || icon.alt || label}
                title={item.linkDescription || undefined}
                className="transition-opacity hover:opacity-70"
              >
                {icon.src ? <img src={asset(icon.src)} alt={icon.alt} className="h-5 w-5 object-contain" /> : <Icon className="h-5 w-5" />}
              </a>
              );
            })}
          </div>
        </div>
      </section>

      <p className="container-wide pb-12 text-center text-sm text-ink/85">
        Choose your nursery above to send the team an enquiry directly.
      </p>
    </>
  );
}
