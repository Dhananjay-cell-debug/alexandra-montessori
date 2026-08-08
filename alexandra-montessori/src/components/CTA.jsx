import { Link } from "react-router-dom";
import Reveal from "./Reveal";

// Simple MRN-style closing band: light sand background with dark text headings
// and a solid green button. Warm, inviting, readable.
export default function CTA({
  title = "Come and see us in person",
  text = "The best way to feel what makes Alexandra Montessori special is to visit. Book a show-around and meet the team who'll care for your child.",
  region,
}) {
  return (
    <section className="bg-sand py-16 text-center" data-am-vb-region={region}>
      <Reveal className="container-wide">
        <h2 className="font-heading text-3xl font-medium text-sage-800 sm:text-4xl">
          {title}
        </h2>
        <p className="mx-auto mt-4 max-w-xl text-pretty leading-relaxed text-ink/85">
          {text}
        </p>
        <Link to="/nurseries" className="btn-primary mt-7">
          Choose a nursery
        </Link>
      </Reveal>
    </section>
  );
}
