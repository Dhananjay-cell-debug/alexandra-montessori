import { Link } from "react-router-dom";
import Reveal from "./Reveal";
import { primaryCta } from "../data/site";

/** Closing call to action reused at the foot of most content pages. */
export default function CTA({
  title = "Come and see us",
  text = "The best way to know whether a nursery is right for your child is to stand in it. Meet the team, look around the rooms, and ask us anything.",
  action = primaryCta,
  tone = "strong",
}) {
  return (
    <section className={tone === "strong" ? "bg-band-strong" : "bg-band"}>
      <Reveal className="container-wide py-16 text-center lg:py-20">
        <h2>{title}</h2>
        <span className="rainbow-rule mx-auto mt-4" aria-hidden />
        <p className="mx-auto mt-5 max-w-xl text-pretty leading-relaxed text-ink/85">
          {text}
        </p>
        <Link to={action.to} className="btn-primary mt-8">
          {action.label}
        </Link>
      </Reveal>
    </section>
  );
}
