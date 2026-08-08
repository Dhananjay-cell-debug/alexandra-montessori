import Reveal from "./Reveal";

// Shared section heading - MRN style: a large Playfair serif title (in MRN blue),
// optional small eyebrow and optional intro. No decorative flourish.
export function SectionHeading({
  eyebrow,
  title,
  intro,
  align = "center",
  light = false,
  maxWidthClass = "max-w-2xl",
}) {
  const centered = align === "center";
  const alignment = centered
    ? "mx-auto text-center items-center"
    : "text-left items-start";
  return (
    <Reveal className={`flex ${maxWidthClass} flex-col gap-3 ${alignment}`}>
      {eyebrow && (
        <span className={`eyebrow ${light ? "!text-white/90" : ""}`}>
          {eyebrow}
        </span>
      )}
      <h2
        className={`text-balance font-heading text-3xl font-medium leading-tight sm:text-4xl ${
          light ? "text-white" : "text-sage-800"
        }`}
      >
        {title}
      </h2>
      {intro && (
        <p
          className={`text-pretty text-base leading-relaxed ${light ? "text-white/92" : "text-ink/85"}`}
        >
          {intro}
        </p>
      )}
    </Reveal>
  );
}
