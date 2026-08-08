import Reveal from "../components/Reveal";
import Img from "../components/Img";
import Seo from "../components/Seo";
import { editable, editableImage } from "../lib/pageVisual";

const philosophyParagraphs = [
  "Our philosophy is simple: childhood isn't a rehearsal for later life, it's a precious stage in its own right, and our job is to protect the wonder in it.",
  "That means we follow the child. We watch what lights each individual up, then build their days around it, rather than asking every child to fit the same mould at the same pace. We create calm, welcoming spaces where children feel safe enough to take risks, make mistakes, and try again. And we hold relationships at the centre of everything - between children, between children and their key workers, and between our team and every family who trusts us with their little one.",
  "We're just as invested in the grown-ups behind the scenes. Happy, supported staff make for happy, secure children, so we care for our team with the same warmth we ask them to bring to the nursery floor.",
  "Underpinning all of this is a simple belief: structure and freedom aren't opposites. With the right foundations in place, children don't need to be told who to be - they get the space to discover it for themselves.",
  "It's why our motto is learning for life: not just preparing children for their next classroom, but nurturing curiosity, confidence, and character that will stay with them well beyond it.",
];

const eyfsParagraphs = [
  "The Early Years Foundation Stage (EYFS) is the framework set by the government for the care and education of children from birth to age five. It sets out the standards every early years setting must meet to keep children healthy, safe, and happy, while giving them the best possible start in life.",
  "At its heart are seven areas of learning. Three prime areas - communication and language, physical development, and personal, social and emotional development - lay the foundations for everything that follows, helping children build confidence, form friendships, and find their voice. Four specific areas - literacy, mathematics, understanding the world, and expressive arts and design - build on these foundations, broadening children's knowledge and giving them room to be curious and creative. Progress is tracked through observation, not testing, because at this age learning happens through play.",
];

const montessoriApproachParagraphs = [
  "Montessori is an educational philosophy, developed over a century ago by Dr Maria Montessori, built on a simple but powerful idea: children learn best when they're trusted to lead their own exploration. Rather than being directed at every turn, children are given carefully prepared environments, hands-on materials, and the freedom to choose activities that capture their interest.",
  "This independence isn't just about learning to read or count. It's about developing concentration, resilience, and a genuine love of learning, alongside practical life skills like pouring, dressing, and caring for their surroundings.",
];

const whereTheyMeetParagraphs = [
  "The EYFS and Montessori aren't two separate systems pulling in different directions - they share the same starting point: the belief that young children learn through hands-on, child-led play. The EYFS tells us what children need to learn and experience; Montessori offers a beautifully practical way of delivering it, through freedom of choice, carefully chosen materials, and respect for each child's own pace.",
  "That's why we weave the two together. We meet every EYFS requirement with confidence, while giving your child the independence, curiosity, and joy that only a Montessori-inspired approach can offer.",
];

function CurriculumSection({
  title,
  paragraphs,
  image,
  alt,
  background,
  imageLeft = true,
  imagePosition,
  imageClassName = "aspect-[4/3] w-full",
  circle = false,
  region,
}) {
  // Named so the builder shows "Our Philosophy photo" rather than a generated
  // "Image 1", and so the key survives a copy change above it.
  const imageProps = editableImage(`${region}-image`, `${title} photo`, alt);

  const imageBlock = circle ? (
    // Clean circular portrait (e.g. the owners photo) - no card/white box, just
    // the round image cropped edge-to-edge.
    <Reveal className="flex justify-center">
      <Img
        src={image}
        alt={alt}
        position={imagePosition}
        rounded="rounded-full"
        className="aspect-square w-full max-w-sm"
        editable={imageProps}
      />
    </Reveal>
  ) : (
    <Reveal>
      <Img
        src={image}
        alt={alt}
        position={imagePosition}
        className={imageClassName}
        editable={imageProps}
      />
    </Reveal>
  );

  const textBlock = (
    <Reveal
      delay={120}
      className="space-y-5 text-[1.02rem] leading-relaxed text-ink"
    >
      {paragraphs.map((paragraph, index) => (
        <p
          key={paragraph}
          {...editable(
            `${region}-paragraph-${index + 1}`,
            `${title} paragraph ${index + 1}`,
            "textarea",
            paragraph,
          )}
        >
          {paragraph}
        </p>
      ))}
    </Reveal>
  );

  return (
    <section className={`${background} py-14 sm:py-20`} data-am-vb-region={region}>
      <div className="container-wide">
        <Reveal
          as="h2"
          className="text-center font-heading text-4xl font-medium text-sage-800 sm:text-5xl"
          {...editable(`${region}-heading`, `${title} heading`, "text", title)}
        >
          {title}
        </Reveal>
        <div className="mt-10 grid items-center gap-10 lg:grid-cols-2 lg:gap-14">
          {imageLeft ? imageBlock : textBlock}
          {imageLeft ? textBlock : imageBlock}
        </div>
      </div>
    </section>
  );
}

export default function Curriculum() {
  return (
    <>
      <Seo
        title="Our Curriculum"
        description="Montessori-inspired practice woven with the EYFS, including our philosophy, the EYFS framework and the Montessori approach at Alexandra Montessori."
        path="/curriculum"
      />

      <CurriculumSection
        region="curriculum-philosophy"
        title="Our Philosophy"
        paragraphs={philosophyParagraphs}
        image="/assets/organisation/philosophy-painting-children.jpeg"
        alt="Two children painting in a Montessori classroom"
        background="bg-white"
        imagePosition="center"
        imageClassName="mx-auto aspect-[3/4] w-full max-w-md"
      />

      <CurriculumSection
        region="curriculum-eyfs"
        title="The EYFS"
        paragraphs={eyfsParagraphs}
        image="/assets/organisation/water-pouring.webp"
        alt="A child learning through a hands-on early years activity"
        background="bg-sand"
        imagePosition="center 22%"
      />

      <CurriculumSection
        region="curriculum-montessori"
        title="The Montessori Approach"
        paragraphs={montessoriApproachParagraphs}
        image="/assets/organisation/shape-work.webp"
        alt="A child using Montessori sensorial materials"
        background="bg-white"
        imageLeft={false}
        imagePosition="center 15%"
      />

      <CurriculumSection
        region="curriculum-together"
        title="Where They Meet"
        paragraphs={whereTheyMeetParagraphs}
        image="/assets/organisation/collage-activity.webp"
        alt="A child engaged in creative, child-led learning"
        background="bg-sage-50"
        imagePosition="center top"
      />
    </>
  );
}
