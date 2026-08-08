import { useState } from "react";
import { Link } from "react-router-dom";
import { CalendarHeart, MapPin, Clock } from "lucide-react";
import PageHeader from "../components/PageHeader";
import Reveal from "../components/Reveal";
import Img from "../components/Img";
import Seo from "../components/Seo";
import CTA from "../components/CTA";
import { events as staticEvents } from "../data/site";
import { cmsCollection } from "../lib/cms";
import { eventSlug } from "../lib/slug";
import { todayISO } from "../lib/dates";

const eventData = cmsCollection("events", staticEvents);

const PAGE_SIZE = 6;

function formatDate(iso) {
  if (!iso) return "";
  return new Date(iso).toLocaleDateString("en-GB", {
    day: "numeric",
    month: "long",
    year: "numeric",
  });
}

// Past = event date is before today; today + future = upcoming.
function splitEvents(list) {
  const today = todayISO();
  const upcoming = [];
  const past = [];
  list.forEach((ev) => {
    if (ev.date && ev.date < today) past.push(ev);
    else upcoming.push(ev);
  });
  // Upcoming: soonest first. Past: most recent first.
  upcoming.sort((a, b) => (a.date || "").localeCompare(b.date || ""));
  past.sort((a, b) => (b.date || "").localeCompare(a.date || ""));
  return { upcoming, past };
}

function EventCard({ ev, index }) {
  return (
    <Reveal delay={index * 60}>
      <div className="card flex h-full flex-col overflow-hidden">
        <Img
          src={ev.image}
          alt={ev.title}
          rounded="rounded-none"
          className="aspect-[16/10] w-full"
        />
        <div className="flex flex-1 flex-col p-6">
          <div className="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs font-medium text-sage-600">
            <span className="inline-flex items-center gap-1.5">
              <CalendarHeart className="h-3.5 w-3.5" />
              {formatDate(ev.date)}
            </span>
            {ev.time && (
              <span className="inline-flex items-center gap-1.5">
                <Clock className="h-3.5 w-3.5" />
                {ev.time}
              </span>
            )}
          </div>
          <h3 className="mt-3 font-heading text-lg font-medium text-ink">
            {ev.title}
          </h3>
          {ev.location && (
            <p className="mt-1 inline-flex items-center gap-1.5 text-sm font-medium text-sage-600">
              <MapPin className="h-3.5 w-3.5" />
              {ev.location}
            </p>
          )}
          {ev.excerpt && (
            <p className="mt-2 flex-1 text-sm leading-relaxed text-ink/90 line-clamp-3">
              {ev.excerpt}
            </p>
          )}
          <Link
            to={`/events/${eventSlug(ev)}`}
            className="btn-primary mt-5 self-start text-sm"
          >
            View details
          </Link>
        </div>
      </div>
    </Reveal>
  );
}

function EventSection({ title, list }) {
  const [expanded, setExpanded] = useState(false);
  if (list.length === 0) return null;

  const visible = expanded ? list : list.slice(0, PAGE_SIZE);
  const hasMore = list.length > PAGE_SIZE;

  return (
    <div className="mt-4">
      <Reveal>
        <h2 className="mb-6 font-heading text-2xl font-medium text-sage-800 sm:text-3xl">
          {title}
        </h2>
      </Reveal>
      <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        {visible.map((ev, i) => (
          <EventCard key={eventSlug(ev)} ev={ev} index={i % PAGE_SIZE} />
        ))}
      </div>
      {hasMore && (
        <div className="mt-8 text-center">
          <button
            type="button"
            onClick={() => setExpanded((v) => !v)}
            className="btn-outline"
            aria-expanded={expanded}
          >
            {expanded ? "View less" : "View more"}
          </button>
        </div>
      )}
    </div>
  );
}

export default function Events() {
  const { upcoming, past } = splitEvents(eventData);
  const hasAny = upcoming.length > 0 || past.length > 0;

  return (
    <>
      <Seo
        title="Events"
        description="Open mornings, taster sessions and seasonal celebrations at Alexandra Montessori. See upcoming and past events, or get in touch to be notified."
        path="/events"
      />
      <PageHeader title="News & Events" region="events-header" />

      {!hasAny ? (
        <section className="bg-white py-20 sm:py-28" data-am-vb-region="events-state">
          <div className="container-wide">
            <Reveal className="mx-auto flex max-w-xl flex-col items-center text-center">
              <span className="flex h-16 w-16 items-center justify-center rounded-full bg-sage-50 text-sage-700">
                <CalendarHeart className="h-8 w-8" strokeWidth={1.6} />
              </span>
              <h2 className="mt-6 font-heading text-3xl font-medium text-sage-800 sm:text-4xl">
                We currently have no events coming up
              </h2>
              <p className="mt-4 text-[1.02rem] leading-relaxed text-ink/90">
                Don't worry - we're always planning open mornings, taster
                sessions and seasonal celebrations. Get in touch and we'll let
                you know as soon as our next event is announced.
              </p>
              <Link to="/contact" className="btn-primary mt-8">
                Register your interest
              </Link>
            </Reveal>
          </div>
        </section>
      ) : (
        <section className="bg-white py-16 sm:py-20" data-am-vb-region="events-state">
          <div className="container-wide space-y-16">
            <EventSection title="Upcoming Events" list={upcoming} />
            <EventSection title="Past Events" list={past} />
          </div>
        </section>
      )}

      <CTA
        region="events-closing"
        title="Want to visit us in person?"
        text="We'd love to show you around. Get in touch and we'll arrange a convenient time."
      />
    </>
  );
}
