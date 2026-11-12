import { Link } from "react-router-dom";
import Seo from "../components/Seo";
import Reveal from "../components/Reveal";
import Section from "../components/Section";
import { notFound } from "../data/site";
import { allSettings } from "../lib/content";

export default function NotFound() {
  return (
    <>
      {/* noindex matters here: WordPress serves the SPA shell with a 200 for
          unknown paths, so the meta tag is what keeps 404s out of the index. */}
      <Seo
        path="/404"
        title="Page not found | Little Rainbows Day Nursery"
        description="The page you're looking for may have moved."
        noindex
      />

      <Section tone="canvas" className="ldrn-not-found">
        <Reveal className="mx-auto flex max-w-xl flex-col items-center gap-5 py-10 text-center">
          <img
            src="/assets/illustrations/rainbow-arc.svg"
            alt=""
            aria-hidden
            className="h-20 w-20"
          />
          <h1>{notFound.title}</h1>
          <span className="rainbow-rule" aria-hidden />
          <p className="text-base leading-relaxed text-ink/85">
            {notFound.body}
          </p>
          <div className="mt-4 flex flex-wrap justify-center gap-3">
            <Link to="/" className="btn-primary">
              Back to home
            </Link>
            <Link to="/contact" className="btn-outline">
              Contact us
            </Link>
          </div>

          <div className="mt-8 w-full border-t border-sky-200 pt-8">
            <p className="font-body text-xs font-semibold uppercase tracking-[0.14em] text-sky-600">
              Our nursery
            </p>
            <div className="mt-4 flex flex-wrap justify-center gap-3">
              {allSettings.map((setting) => (
                <Link
                  key={setting.slug}
                  to={`/our-nursery/${setting.slug}`}
                  className="btn-ghost"
                >
                  {setting.name}
                  {setting.active === false ? " (former)" : ""}
                </Link>
              ))}
            </div>
          </div>
        </Reveal>
      </Section>
    </>
  );
}
