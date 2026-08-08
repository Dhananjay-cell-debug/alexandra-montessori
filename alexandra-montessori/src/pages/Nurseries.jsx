import { Link } from "react-router-dom";
import Reveal from "../components/Reveal";
import Img from "../components/Img";
import Seo from "../components/Seo";
import { locations } from "../data/site";

export default function Nurseries() {
  const names = new Intl.ListFormat("en-GB", {
    style: "long",
    type: "conjunction",
  }).format(locations.map((location) => location.name));
  const approvedThree =
    locations.length === 3 &&
    ["hounslow", "heston", "hammersmith"].every((id) =>
      locations.some((location) => location.id === id),
    );

  return (
    <>
      <Seo
        title="Our Nurseries"
        description="Welcoming Alexandra Montessori nurseries offering thoughtful, child-centred early years care."
        path="/nurseries"
      />

      <section className="bg-sand py-12 sm:py-16" data-am-vb-region="nurseries-introduction">
        <div className="container-wide text-center">
          <Reveal
            as="h1"
            className="font-heading text-4xl font-medium text-sage-800 sm:text-5xl"
          >
            Our Nurseries
          </Reveal>
          <Reveal
            delay={100}
            className="mx-auto mt-5 max-w-5xl space-y-4 text-pretty leading-relaxed text-ink/90"
          >
            {approvedThree ? (
              <>
                <p>
                  We have three settings, each with its own warm, home-like feel,
                  all sharing the same Montessori-inspired, EYFS-led approach:
                  Hounslow, Heston, and Hammersmith. Wherever your child joins
                  us, they'll find calm, thoughtfully prepared spaces, dedicated
                  key workers who take the time to know them individually, and
                  an environment built for exploring, playing, and growing.
                  Every setting is led with the same standards and the same
                  care, so families can feel just as at home in any one of our
                  three nurseries.
                </p>
                <p>
                  Hounslow and Heston welcome children from 6 months to 5 years;
                  Hammersmith welcomes children from 12 months to 5 years.
                  Choose a location below to take a closer look.
                </p>
              </>
            ) : locations.length > 0 ? (
              <>
                <p>
                  Our {locations.length === 1 ? "setting" : "settings"} in{" "}
                  {names} {locations.length === 1 ? "has" : "have"} a warm,
                  home-like feel and the same Montessori-inspired, EYFS-led
                  approach. Children find calm, thoughtfully prepared spaces
                  and dedicated key workers who take the time to know them
                  individually.
                </p>
                <p>
                  Each nursery's admission age and opening hours are shown
                  below. Choose a location to take a closer look.
                </p>
              </>
            ) : (
              <p>
                Nursery information is being updated. Please contact our team
                and we will help you find the right setting for your family.
              </p>
            )}
          </Reveal>
        </div>
      </section>

      <section className="bg-white py-14 sm:py-16" data-am-vb-region="nurseries-directory">
        {locations.length === 0 ? (
          <div className="container-wide text-center">
            <Link to="/contact" className="btn-primary">
              Contact our team
            </Link>
          </div>
        ) : null}
        <div className="container-wide grid gap-x-8 gap-y-14 sm:grid-cols-2 lg:grid-cols-3">
          {locations.map((loc, i) => (
            <Reveal
              key={loc.id}
              delay={i * 90}
              className="flex flex-col items-center text-center"
            >
              <Link
                to={`/nurseries/${loc.id}`}
                className="group block overflow-hidden rounded-full"
              >
                <Img
                  src={loc.cardImage || loc.image}
                  alt={`A happy child at the ${loc.name} nursery`}
                  rounded="rounded-full"
                  position={loc.cardPosition || "50% 30%"}
                  className="aspect-square w-44 transition-transform duration-700 group-hover:scale-105 sm:w-48"
                />
              </Link>
              <Link
                to={`/nurseries/${loc.id}`}
                className="mrn-label mt-6 transition-colors hover:bg-sage-400"
              >
                {loc.name}
              </Link>
              <p className="mt-4 text-sm leading-relaxed text-ink/85">
                {loc.address}
              </p>
              <p className="mt-2 text-sm font-medium text-ink/85">
                {loc.ageRange}. {loc.hours}
              </p>
              <Link
                to={`/nurseries/${loc.id}`}
                className="mt-3 font-body text-sm font-medium text-sage-700 hover:underline"
              >
                More details -&gt;
              </Link>
            </Reveal>
          ))}
        </div>
      </section>
    </>
  );
}
