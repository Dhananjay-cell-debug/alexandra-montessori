import { Link } from "react-router-dom";
import Seo from "../components/Seo";
import Reveal from "../components/Reveal";
import Img from "../components/Img";
import Pending from "../components/Pending";
import TbcBadge from "../components/TbcBadge";
import { approach } from "../data/site";
import { allSettings } from "../lib/content";
import { isTbc } from "../lib/tbc";

function SettingCard({ setting, delay = 0 }) {
  const address = isTbc(setting.address) ? null : setting.address.join(", ");
  // Only show facts that are actually confirmed for this setting.
  const facts = [setting.ages, setting.hours].filter(
    (fact) => fact && !isTbc(fact),
  );

  return (
    <Reveal delay={delay} className="flex flex-col items-center text-center">
      <Link
        to={`/our-nursery/${setting.slug}`}
        className="group block overflow-hidden rounded-full focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-600 focus-visible:ring-offset-4"
      >
        <Img
          src={setting.image}
          alt={setting.imageAlt}
          shape="circle"
          className="aspect-square w-44 transition-transform duration-700 group-hover:scale-105 sm:w-48"
        />
      </Link>

      <Link
        to={`/our-nursery/${setting.slug}`}
        className="ldrn-label mt-6 transition-colors hover:bg-sky-700"
      >
        {setting.name}
      </Link>

      {address ? (
        <p className="mt-4 max-w-xs text-sm leading-relaxed text-ink/85">
          {address}
        </p>
      ) : (
        <Pending className="mt-4" />
      )}

      {facts.length > 0 && (
        <p className="mt-2 max-w-xs text-sm font-semibold text-ink/85">
          {facts.join(" · ")}
        </p>
      )}

      {setting.statusNote && (
        <p className="mt-3 max-w-xs text-xs leading-relaxed text-ink/55">
          {setting.statusNote}
        </p>
      )}

      <Link
        to={`/our-nursery/${setting.slug}`}
        className="mt-3 text-sm font-semibold text-sky-800 hover:underline"
      >
        {setting.active === false ? "View status" : "More details"} {"->"}
      </Link>

      <span className="mt-3 flex flex-wrap justify-center gap-1.5">
        <TbcBadge value={setting.address} />
        <TbcBadge value={setting.hours} />
        <TbcBadge value={setting.ages} />
      </span>
    </Reveal>
  );
}

export default function Settings() {
  return (
    <>
      <Seo path="/our-nursery" />

      <section data-ldrn-ws="settings.section-1" className="bg-band py-12 sm:py-16">
        <div className="container-wide text-center">
          <Reveal as="h1">Our Nursery</Reveal>
          <Reveal
            delay={100}
            className="mx-auto mt-5 max-w-4xl space-y-4 text-pretty leading-relaxed text-ink/85"
          >
            <p>{approach.body}</p>
            <p>Choose a setting below to view its published details.</p>
          </Reveal>
        </div>
      </section>

      <section data-ldrn-ws="settings.section-2" className="bg-white py-14 sm:py-16">
        {/* A single setting reads as an orphan in a three-column grid, so the
            layout narrows until there is more than one to show. */}
        <div
          className={
            allSettings.length > 1
              ? "container-wide grid max-w-6xl gap-x-8 gap-y-14 sm:grid-cols-2 lg:grid-cols-3"
              : "container-wide grid max-w-sm gap-x-8 gap-y-14"
          }
        >
          {allSettings.map((setting, index) => (
            <SettingCard
              key={setting.slug}
              setting={setting}
              delay={index * 90}
            />
          ))}
        </div>
      </section>
    </>
  );
}
