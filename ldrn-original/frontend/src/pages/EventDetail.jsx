import { Link, useParams } from "react-router-dom";
import { CalendarHeart, Clock, MapPin } from "lucide-react";
import Seo from "../components/Seo";
import Reveal from "../components/Reveal";
import Img from "../components/Img";
import PageHeader from "../components/PageHeader";
import { eventSlug } from "../lib/events";
import { events } from "../lib/content";

function formatDate(date) {
  if (!date) return "";
  return new Date(`${date}T12:00:00`).toLocaleDateString("en-GB", {
    weekday: "long",
    day: "numeric",
    month: "long",
    year: "numeric",
  });
}

export default function EventDetail() {
  const { slug } = useParams();
  const event = events.find((candidate) => eventSlug(candidate) === slug);

  // An unpublished or removed event must not be indexed under its old URL.
  if (!event) {
    return (
      <>
        <Seo title="Event not found" path={`/events/${slug}`} noindex />
        <PageHeader title="Event not found" tone="canvas" />
        <section data-ldrn-ws={'event-detail.' + slug + '.section-1'} className="bg-white py-16 sm:py-20">
          <div className="container-wide text-center">
            <p className="mx-auto max-w-xl text-sm text-ink/85">
              We could not find that event. It may no longer be published.
            </p>
            <Link to="/events" className="btn-primary mt-6">
              Back to events
            </Link>
          </div>
        </section>
      </>
    );
  }

  return (
    <>
      <Seo
        title={`${event.title} | Little Rainbows Day Nursery`}
        description={event.excerpt}
        path={`/events/${eventSlug(event)}`}
      />
      <PageHeader title={event.title} tone="canvas" />

      <section data-ldrn-ws={'event-detail.' + slug + '.section-2'} className="bg-white py-14 sm:py-20">
        <div className="container-wide max-w-4xl">
          {event.image && (
            <Reveal>
              <Img
                src={event.image}
                alt={event.title}
                className="aspect-[16/9] w-full"
              />
            </Reveal>
          )}

          <Reveal className="mt-8 flex flex-wrap gap-x-6 gap-y-2 text-sm font-semibold text-sky-700">
            <span className="inline-flex items-center gap-2">
              <CalendarHeart className="h-4 w-4" /> {formatDate(event.date)}
            </span>
            {event.time && (
              <span className="inline-flex items-center gap-2">
                <Clock className="h-4 w-4" /> {event.time}
              </span>
            )}
            {event.location && (
              <span className="inline-flex items-center gap-2">
                <MapPin className="h-4 w-4" /> {event.location}
              </span>
            )}
          </Reveal>

          {event.excerpt && (
            <Reveal as="p" className="mt-6 text-lg leading-relaxed text-ink/90">
              {event.excerpt}
            </Reveal>
          )}

          <Reveal className="mt-10 flex flex-wrap items-center gap-4">
            <Link
              to={`/contact?event=${encodeURIComponent(event.title)}`}
              className="btn-primary"
            >
              Ask about this event
            </Link>
            <Link
              to="/events"
              className="text-sm font-semibold text-sky-800 underline underline-offset-4"
            >
              Back to events
            </Link>
          </Reveal>
        </div>
      </section>
    </>
  );
}
