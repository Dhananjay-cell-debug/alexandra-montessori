import { useState } from "react";
import { Link } from "react-router-dom";
import { CalendarHeart, Clock, MapPin } from "lucide-react";
import Seo from "../components/Seo";
import Reveal from "../components/Reveal";
import Img from "../components/Img";
import PageHeader from "../components/PageHeader";
import CTA from "../components/CTA";
import { events } from "../lib/content";
import { eventSlug } from "../lib/events";

const PAGE_SIZE = 6;

function formatDate(date) {
  if (!date) return "";
  // Midday avoids the stored date shifting a day either side of BST.
  return new Date(`${date}T12:00:00`).toLocaleDateString("en-GB", {
    day: "numeric",
    month: "long",
    year: "numeric",
  });
}

/** Upcoming soonest-first, past most-recent-first. */
function splitByDate(list) {
  const today = new Date().toISOString().slice(0, 10);
  const upcoming = [];
  const past = [];

  list.forEach((event) => {
    if (event.date && event.date < today) {
      past.push(event);
    } else {
      upcoming.push(event);
    }
  });

  upcoming.sort((a, b) => (a.date || "").localeCompare(b.date || ""));
  past.sort((a, b) => (b.date || "").localeCompare(a.date || ""));
  return { upcoming, past };
}

function EventCard({ event, index }) {
  return (
    <Reveal delay={index * 60}>
      <article className="card flex h-full flex-col overflow-hidden">
        <Img
          src={event.image}
          alt={event.title}
          shape="square"
          className="aspect-[16/10] w-full"
        />
        <div className="flex flex-1 flex-col p-6">
          <div className="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs font-semibold text-sky-700">
            <span className="inline-flex items-center gap-1.5">
              <CalendarHeart className="h-3.5 w-3.5" />
              {formatDate(event.date)}
            </span>
            {event.time && (
              <span className="inline-flex items-center gap-1.5">
                <Clock className="h-3.5 w-3.5" /> {event.time}
              </span>
            )}
          </div>

          <h3 className="mt-3 text-lg">{event.title}</h3>

          {event.location && (
            <p className="mt-1 inline-flex items-center gap-1.5 text-sm font-semibold text-sky-700">
              <MapPin className="h-3.5 w-3.5" /> {event.location}
            </p>
          )}

          {event.excerpt && (
            <p className="mt-2 line-clamp-3 flex-1 text-sm leading-relaxed text-ink/90">
              {event.excerpt}
            </p>
          )}

          <Link
            to={`/events/${eventSlug(event)}`}
            className="btn-primary mt-5 self-start"
          >
            View details
          </Link>
        </div>
      </article>
    </Reveal>
  );
}

function EventGroup({ title, items }) {
  const [expanded, setExpanded] = useState(false);
  if (items.length === 0) return null;

  const visible = expanded ? items : items.slice(0, PAGE_SIZE);

  return (
    <section data-ldrn-ws="events.section-1">
      <Reveal as="h2" className="mb-6 text-2xl sm:text-3xl">
        {title}
      </Reveal>
      <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        {visible.map((event, index) => (
          <EventCard
            key={eventSlug(event)}
            event={event}
            index={index % PAGE_SIZE}
          />
        ))}
      </div>
      {items.length > PAGE_SIZE && (
        <div className="mt-8 text-center">
          <button
            type="button"
            onClick={() => setExpanded((open) => !open)}
            className="btn-outline"
            aria-expanded={expanded}
          >
            {expanded ? "View less" : "View more"}
          </button>
        </div>
      )}
    </section>
  );
}

export default function Events() {
  const { upcoming, past } = splitByDate(events);
  const hasEvents = upcoming.length > 0 || past.length > 0;

  return (
    <>
      <Seo path="/events" />
      <PageHeader title="News & Events" tone="canvas" />

      {hasEvents ? (
        <section data-ldrn-ws="events.section-2" className="bg-white py-16 sm:py-20">
          <div className="container-wide space-y-16">
            <EventGroup title="Upcoming Events" items={upcoming} />
            <EventGroup title="Past Events" items={past} />
          </div>
        </section>
      ) : (
        <section data-ldrn-ws="events.section-3" className="bg-white py-20 sm:py-28">
          <div className="container-wide">
            <Reveal className="mx-auto flex max-w-xl flex-col items-center text-center">
              <span className="flex h-16 w-16 items-center justify-center rounded-full bg-sky-100 text-sky-800">
                <CalendarHeart className="h-8 w-8" strokeWidth={1.6} />
              </span>
              <h2 className="mt-6">We currently have no events coming up</h2>
              <p className="mt-4 text-[1.02rem] leading-relaxed text-ink/90">
                Contact the Hounslow team to hear when the next Little Rainbows
                event is announced.
              </p>
              <Link to="/contact" className="btn-primary mt-8">
                Register your interest
              </Link>
            </Reveal>
          </div>
        </section>
      )}

      <CTA
        title="Want to visit us in person?"
        text="Meet the Little Rainbows team and see the Hounslow setting for yourself."
        action={{ label: "Contact us", to: "/contact" }}
      />
    </>
  );
}
