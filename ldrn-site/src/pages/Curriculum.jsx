import Seo from "../components/Seo";
import Reveal from "../components/Reveal";
import Img from "../components/Img";
import { curriculum, healthSafety, philosophy } from "../data/site";

/** Alternating text/photo band used for each of the three curriculum topics. */
function StoryBand({
  editorId,
  title,
  paragraphs,
  image,
  alt,
  background,
  imageLeft = true,
  imagePosition,
  imageClassName = "aspect-[4/3] max-w-xl",
}) {
  const photo = (
    <Reveal>
      <Img
        src={image}
        alt={alt}
        position={imagePosition}
        shape="rounded"
        className={`mx-auto w-full ${imageClassName}`}
      />
    </Reveal>
  );

  const copy = (
    <Reveal
      delay={120}
      className="space-y-5 text-[1.02rem] leading-relaxed text-ink/90"
    >
      {paragraphs.map((paragraph, index) => (
        <p key={`${title}-${index}`} className="whitespace-pre-line">
          {paragraph}
        </p>
      ))}
    </Reveal>
  );

  return (
    <section data-ldrn-ws={editorId} className={`${background} py-14 sm:py-20`}>
      <div className="container-wide">
        <Reveal as="h2" className="text-center text-4xl sm:text-5xl">
          {title}
        </Reveal>
        <div className="mt-10 grid items-center gap-10 lg:grid-cols-2 lg:gap-14">
          {imageLeft ? photo : copy}
          {imageLeft ? copy : photo}
        </div>
      </div>
    </section>
  );
}

export default function Curriculum() {
  // The six EYFS areas read as one narrative here rather than as cards.
  const eyfsParagraphs = [
    curriculum.intro,
    ...curriculum.blocks.map((block) => `${block.name}: ${block.text}`),
  ];

  const healthSafetyParagraphs = [
    healthSafety.intro,
    ...healthSafety.points.map(
      (point, index) => `${index + 1}. ${point.title}: ${point.text}`,
    ),
    healthSafety.closing,
  ];

  return (
    <>
      <Seo path="/curriculum" />

      <StoryBand
        editorId="curriculum.section-1"
        title="Our Philosophy"
        paragraphs={philosophy.paragraphs}
        image={philosophy.image}
        alt={philosophy.imageAlt}
        background="bg-white"
        imagePosition="center"
        imageClassName="aspect-[6/5] max-w-xl"
      />

      <StoryBand
        editorId="curriculum.section-2"
        title="The EYFS"
        paragraphs={eyfsParagraphs}
        image="/assets/photography/child-pointing-shapes-and-numbers-wall.png"
        alt="A child pointing at a wall of shapes and numbers"
        background="bg-band"
        imagePosition="center top"
        imageClassName="aspect-[2/3] max-w-md"
      />

      <StoryBand
        editorId="curriculum.section-3"
        title="Health & Safety"
        paragraphs={healthSafetyParagraphs}
        image="/assets/photography/child-hands-on-picture-book-fox.jpg"
        alt="A child's hands resting on an open picture book"
        background="bg-white"
        imageLeft={false}
        imagePosition="center top"
        imageClassName="aspect-[2/3] max-w-md"
      />
    </>
  );
}
