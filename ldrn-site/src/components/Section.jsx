import Reveal from "./Reveal";

// Alternating page bands. Keeping the tone as a named token rather than a raw
// colour means the restrained/faithful theme switch changes every section at
// once.
const TONES = {
  canvas: "bg-canvas",
  band: "bg-band",
  strong: "bg-band-strong",
};

export default function Section({
  tone = "canvas",
  className = "",
  children,
  ...rest
}) {
  return (
    <section
      className={`${TONES[tone] ?? TONES.canvas} py-16 sm:py-20 lg:py-24 ${className}`}
      {...rest}
    >
      <div className="container-wide">{children}</div>
    </section>
  );
}

export function SectionHeading({
  eyebrow,
  title,
  intro,
  align = "center",
  light = false,
  flourish = true,
}) {
  return (
    <Reveal
      className={`flex max-w-2xl flex-col gap-4 ${
        align === "center"
          ? "mx-auto items-center text-center"
          : "items-start text-left"
      }`}
    >
      {eyebrow && (
        <span className={`eyebrow ${light ? "!text-sky-200" : ""}`}>
          {eyebrow}
        </span>
      )}
      <h2 className={light ? "!text-white" : ""}>{title}</h2>
      {flourish && <span className="rainbow-rule" aria-hidden="true" />}
      {intro && (
        <p
          className={`text-pretty text-base leading-relaxed ${
            light ? "text-sky-100" : "text-ink/85"
          }`}
        >
          {intro}
        </p>
      )}
    </Reveal>
  );
}
