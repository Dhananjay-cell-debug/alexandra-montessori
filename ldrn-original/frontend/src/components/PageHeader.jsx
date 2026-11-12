import Reveal from "./Reveal";
import Img from "./Img";

/**
 * Standard page introduction. With an image it becomes a two-column split
 * with the arched photo on the right; without one it centres.
 *
 * The image is marked `priority` because on an inner page this is the largest
 * contentful paint.
 */
export default function PageHeader({
  eyebrow,
  title,
  intro,
  image,
  imageAlt = "",
  tone = "band",
}) {
  const toneClass = tone === "canvas" ? "bg-canvas" : "bg-band";

  if (image) {
    return (
      <section className={`${toneClass} py-12 sm:py-16 lg:py-20`}>
        <div className="container-wide grid items-center gap-12 lg:grid-cols-[1.15fr_1fr] lg:gap-16">
          <div className="flex flex-col items-start gap-4 text-left">
            {eyebrow && (
              <Reveal as="span" className="eyebrow">
                {eyebrow}
              </Reveal>
            )}
            <Reveal as="h1">{title}</Reveal>
            <span className="rainbow-rule" aria-hidden />
            {intro && (
              <Reveal
                as="p"
                delay={100}
                className="mt-1 max-w-xl text-pretty text-base leading-relaxed text-ink/85 sm:text-lg"
              >
                {intro}
              </Reveal>
            )}
          </div>
          <Reveal delay={150} className="flex justify-center lg:justify-end">
            <Img
              src={image}
              alt={imageAlt}
              shape="arch"
              priority
              className="h-[22rem] w-[17rem] shadow-card sm:h-[26rem] sm:w-[20rem]"
            />
          </Reveal>
        </div>
      </section>
    );
  }

  return (
    <section className={`${toneClass} pb-12 pt-12 sm:pt-16 lg:pb-16`}>
      <div className="container-wide flex flex-col items-center gap-4 text-center">
        {eyebrow && (
          <Reveal as="span" className="eyebrow">
            {eyebrow}
          </Reveal>
        )}
        <Reveal as="h1">{title}</Reveal>
        <span className="rainbow-rule" aria-hidden />
        {intro && (
          <Reveal
            as="p"
            delay={100}
            className="mt-1 max-w-2xl text-pretty text-base leading-relaxed text-ink/85 sm:text-lg"
          >
            {intro}
          </Reveal>
        )}
      </div>
    </section>
  );
}
