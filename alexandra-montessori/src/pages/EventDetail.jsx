import { useParams, Link } from "react-router-dom";
import { CalendarHeart, Clock, MapPin } from "lucide-react";
import PageHeader from "../components/PageHeader";
import Reveal from "../components/Reveal";
import Img from "../components/Img";
import Seo from "../components/Seo";
import { events as staticEvents } from "../data/site";
import { cmsCollection } from "../lib/cms";
import { eventSlug } from "../lib/slug";

const eventData = cmsCollection("events", staticEvents);

function formatDate(iso) {
  if (!iso) return "";
  return new Date(iso).toLocaleDateString("en-GB", {
    weekday: "long",
    day: "numeric",
    month: "long",
    year: "numeric",
  });
}

function InfoRow({ icon: Icon, children }) {
  if (!children) return null;
  return (
    <p className="inline-flex items-center gap-2 text-sm font-medium text-sage-700">
      <Icon className="h-4 w-4" />
      {children}
    </p>
  );
}

export default function EventDetail() {
  const { slug } = useParams();
  const ev = eventData.find((e) => eventSlug(e) === slug) ?? null;

  if (!ev) {
    return (
      <>
        <Seo title="Event not found" path={`/events/${slug}`} />
        <PageHeader title="Event not found" region="event-detail-header" />
        <section className="bg-white py-16 sm:py-20" data-am-vb-region="event-detail-fallback">
          <div className="container-wide text-center">
            <p className="mx-auto max-w-xl font-body text-sm text-ink/85">
              We couldn't find that event. It may have already taken place.
            </p>
            <div className="mt-6">
              <Link to="/events" className="btn-primary">
                Back to events
              </Link>
            </div>
          </div>
        </section>
      </>
    );
  }

  // Time can be a combined range string ("9:30am – 11:30am") or separate
  // start/end fields once the CMS exposes them.
  const timeLabel =
    ev.startTime && ev.endTime
      ? `${ev.startTime} – ${ev.endTime}`
      : ev.time || ev.startTime || "";
  // CMS events provide `description` as sanitised HTML; static events only have
  // a plain-text excerpt.
  const descriptionHtml = ev.description || "";
  const bookHref = `/contact?event=${encodeURIComponent(ev.title)}`;

  return (
    <>
      <Seo
        title={ev.title}
        description={ev.excerpt || `Join us for ${ev.title}.`}
        path={`/events/${eventSlug(ev)}`}
      />
      <PageHeader title={ev.title} crumb="Event" region="event-detail-header" />

      <section className="bg-white py-14 sm:py-18" data-am-vb-region="event-detail-content">
        <div className="container-wide max-w-4xl">
          {ev.image && (
            <Reveal>
              <Img
                src={ev.image}
                alt={ev.title}
                className="aspect-[16/9] w-full"
              />
            </Reveal>
          )}

          <Reveal>
            <div className="mt-8 flex flex-wrap gap-x-6 gap-y-2">
              <InfoRow icon={CalendarHeart}>{formatDate(ev.date)}</InfoRow>
              <InfoRow icon={Clock}>{timeLabel}</InfoRow>
              <InfoRow icon={MapPin}>{ev.location}</InfoRow>
            </div>
          </Reveal>

          {ev.excerpt && (
            <Reveal>
              <p className="mt-6 text-lg leading-relaxed text-ink/90">
                {ev.excerpt}
              </p>
            </Reveal>
          )}

          {descriptionHtml && (
            <Reveal>
              <div
                className="blog-richtext mt-4"
                dangerouslySetInnerHTML={{ __html: descriptionHtml }}
              />
            </Reveal>
          )}

          {ev.bookingInfo && (
            <Reveal>
              <div className="mt-8 rounded-2xl border border-sage-100 bg-sage-50 p-6">
                <h2 className="font-heading text-lg font-medium text-sage-800">
                  Booking information
                </h2>
                <p className="mt-2 text-sm leading-relaxed text-ink/90">
                  {ev.bookingInfo}
                </p>
              </div>
            </Reveal>
          )}

          <Reveal>
            <div className="mt-10 flex flex-wrap items-center gap-4">
              <Link to={bookHref} className="btn-primary">
                Book a place
              </Link>
              <Link
                to="/events"
                className="font-body text-sm font-medium text-sage-800 underline underline-offset-4 hover:text-sage-600"
              >
                ← Back to events
              </Link>
            </div>
          </Reveal>
        </div>
      </section>
    </>
  );
}
