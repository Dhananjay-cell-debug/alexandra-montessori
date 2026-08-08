import Reveal from "./Reveal";
import { editable } from "../lib/pageVisual";

// MRN-style inner-page header: a centred Playfair serif title in MRN blue with a
// short intro. No breadcrumb, no eyebrow, no flourish - a plain Wix-like title.
//
// Seventeen pages render through this one component, so naming the title and
// intro here is what gives all of them a properly labelled, stably keyed header
// in the builder instead of a heuristic "Text 1".
export default function PageHeader({ title, intro, region }) {
  // About, Privacy and Testimonials render this header but are not builder
  // routes and pass no region. Annotating them anyway would mint keys literally
  // named "undefined-title", so they stay unannotated and the runtime leaves
  // them alone.
  const titleProps = region
    ? editable(`${region}-title`, "Page title", "text", title)
    : {};
  const introProps = region
    ? editable(`${region}-intro`, "Page introduction", "textarea", intro)
    : {};

  return (
    <section
      className="bg-white pb-6 pt-12 sm:pt-16"
      data-am-vb-region={region}
    >
      <div className="container-wide text-center">
        <Reveal
          as="h1"
          className="font-heading text-4xl font-medium text-sage-800 sm:text-5xl"
          {...titleProps}
        >
          {title}
        </Reveal>
        {intro && (
          <Reveal
            as="p"
            delay={100}
            className="mx-auto mt-5 max-w-3xl text-pretty text-base leading-relaxed text-ink/85 sm:text-lg"
            {...introProps}
          >
            {intro}
          </Reveal>
        )}
      </div>
    </section>
  );
}
