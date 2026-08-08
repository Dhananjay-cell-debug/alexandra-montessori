import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { Quote } from "lucide-react";
import Reveal from "../components/Reveal";
import Icon from "../components/Icon";
import Img from "../components/Img";
import Seo from "../components/Seo";
import { asset } from "../lib/asset";
import { cmsCollection } from "../lib/cms";
import {
  editableMediaProps,
  editableFrameProps,
  editableTextProps,
  homeCollection,
  homeCustomSections,
  homeElement,
  homeHref,
  homeMedia,
  homeText,
} from "../lib/homeVisual";
import {
  brand,
  homeBlocks,
  testimonials as staticTestimonials,
  accreditations,
} from "../data/site";

const testimonials = cmsCollection("testimonials", staticTestimonials);

// Local Visual Builder design contract. The mu-plugin supplies a complete,
// sanitised model; the public build keeps its approved Tailwind defaults when
// the Local-only plugin is unavailable.
const homeDesign =
  typeof window !== "undefined" && window.amHomeDesign?.sections
    ? window.amHomeDesign.sections
    : {};

const homeSectionDesign = (id) => homeDesign[id] || {};
const homeSectionStyle = (id) => {
  const section = homeSectionDesign(id);
  const style = {};
  const variables = {
    height: "height",
    paddingY: "padding-y",
    gap: "gap",
    contentWidth: "content-width",
    mediaSize: "media-size",
    textSize: "text-size",
    imageSize: "image-size",
    iconSize: "icon-size",
  };

  if (section.backgroundColor) {
    style["--am-home-background"] = section.backgroundColor;
  }
  if (section.visible === false) {
    style.display = "none";
  }
  ["tint", "texture", "overlay"].forEach((key) => {
    if (Number.isFinite(Number(section[key]))) {
      style[`--am-home-${key}`] = Number(section[key]);
    }
  });
  ["desktop", "tablet", "mobile"].forEach((device) => {
    Object.entries(variables).forEach(([key, variable]) => {
      const value = Number(section[device]?.[key]);
      if (Number.isFinite(value)) {
        style[`--am-home-${variable}-${device}`] = `${value}px`;
      }
    });
  });

  return style;
};

// Small blue line icon shown under each circular feature link (MRN row).
const blockIcons = ["Trees", "GraduationCap", "CalendarHeart"];

const featureItems = [
  ...homeBlocks.map((item, index) => ({
    ...item,
    keyPrefix: `feature-${index + 1}`,
    icon: blockIcons[index],
  })),
  ...homeCollection("features").map((item) => ({
    keyPrefix: `feature-extra-${item.id}`,
    eyebrow: "New feature",
    cta: "View more",
    to: "#",
    image: "/assets/organisation/classroom-main.webp",
    icon: item.icon || "Image",
  })),
];

// White three-benefit row - MRN's short statements, Alexandra-adapted.
const benefits = [
  {
    icon: "HandHeart",
    text: "Rich experiences in a secure and nurturing environment",
  },
  { icon: "Sprout", text: "Montessori child-centred curriculum" },
  { icon: "Users", text: "Highly skilled team who are devoted and passionate" },
];

const benefitItems = [
  ...benefits.map((item, index) => ({
    ...item,
    textKey: `benefit-${index + 1}-text`,
  })),
  ...homeCollection("benefits").map((item) => ({
    icon: item.icon || "Sprout",
    text: "Add your benefit text",
    textKey: `benefit-extra-${item.id}-text`,
  })),
];

const trustItems = [
  ...accreditations.map((label, index) => ({
    label,
    labelKey: `trust-${index + 1}-label`,
    logoKey: `trust-${index + 1}-logo`,
  })),
  ...homeCollection("trust").map((item) => ({
    label: "Accreditation",
    labelKey: `trust-extra-${item.id}-label`,
    logoKey: `trust-extra-${item.id}-logo`,
  })),
];

const customSections = homeCustomSections();

const responsiveFeatureSources = {
  "/assets/organisation/classroom-main.webp": [
    ["/assets/organisation/classroom-main-home-320-4befd08b.webp", 320],
    ["/assets/organisation/classroom-main-home-480-f0f866c3.webp", 480],
    ["/assets/organisation/classroom-main-home-768-94a8e042.webp", 768],
  ],
  "/assets/organisation/portrait-girl.webp": [
    ["/assets/organisation/portrait-girl-home-320-fc02be4c.webp", 320],
    ["/assets/organisation/portrait-girl-home-480-cd1ae70f.webp", 480],
    ["/assets/organisation/portrait-girl-home-768-e9b0a94c.webp", 768],
  ],
  "/assets/organisation/painting-close.webp": [
    ["/assets/organisation/painting-close-home-320-7ebf8231.webp", 320],
    ["/assets/organisation/painting-close-home-480-b956cc97.webp", 480],
    ["/assets/organisation/painting-close-home-768-4c7eda90.webp", 768],
  ],
};

function responsiveFeatureProps(src) {
  const candidates = responsiveFeatureSources[src];
  if (!candidates) return {};
  return {
    srcSet: candidates
      .map(([candidate, width]) => `${asset(candidate)} ${width}w`)
      .join(", "),
    sizes: "(min-width: 640px) 224px, 208px",
  };
}

function responsiveTestimonialBackground(src, customSrcSet = "") {
  if (customSrcSet) return { srcSet: customSrcSet, sizes: "100vw" };
  if (src !== "/assets/organisation/playground-balance-800-fd038d59.webp") return {};
  return {
    srcSet: [
      `${asset("/assets/organisation/playground-balance-480-6ac3fd33.webp")} 480w`,
      `${asset("/assets/organisation/playground-balance-640-ef6eebf7.webp")} 640w`,
      `${asset("/assets/organisation/playground-balance-800-fd038d59.webp")} 800w`,
    ].join(", "),
    sizes: "100vw",
  };
}

function inVisualBuilderCanvas() {
  if (typeof window === "undefined") return false;
  try {
    const query = new URLSearchParams(window.location.search);
    return (
      window.self !== window.top &&
      (query.has("am_visual_canvas") || query.get("am_visual_mode") === "edit")
    );
  } catch {
    return false;
  }
}

const aboutJourney = [
  "We opened our first setting in Hounslow in 2019, with a simple aim: to create a nursery where children are genuinely known, not just minded. That first setting quickly became a second home for the families who joined us - and as word spread, so did we.",
  "In 2024, we opened our second setting in Heston, bringing the same warmth and standards to a new community of families. In 2025, we opened our doors in Hammersmith, growing again while staying true to what got us here in the first place.",
  "Across our settings, our team is led by four dedicated and inspiring Directors, who hold the children at the heart of everything they do. What hasn't changed since 2019 is our belief that every child deserves individual attention, a caring key worker, and a place where they're excited to arrive each morning. As we've grown, we've grown carefully - one setting at a time, each one held to the same standard as the first.",
];

const aboutMilestones = [
  "2019 - Hounslow",
  "2024 - Heston",
  "2025 - Hammersmith",
];

// The About us section is editable in wp-admin (Website Content -> About us).
// Fall back to the copy above whenever a field is left blank in the CMS.
const cmsAbout =
  typeof window !== "undefined" && window.amData ? window.amData.about : null;
const aboutContent = {
  heading: cmsAbout?.heading?.trim() || "About us",
  paragraphs: cmsAbout?.paragraphs?.length ? cmsAbout.paragraphs : aboutJourney,
  image: cmsAbout?.image?.trim() || "/assets/organisation/owners.webp",
  milestones: cmsAbout?.milestones?.length
    ? cmsAbout.milestones
    : aboutMilestones,
  upcoming: cmsAbout?.upcoming?.trim() || "",
};

/* ----------------------------------------------------- Hero video strip */
function Hero() {
  const defaultPoster = "/assets/videos/alexandra-promo-poster.webp";
  const video = homeMedia(
    "hero-video",
    "/assets/videos/alexandra-promo.mp4",
    "Alexandra Montessori nursery life",
  );
  const poster = homeMedia(
    "hero-poster",
    defaultPoster,
  );
  const customVideo = Boolean(homeElement("hero-video").src?.trim());
  const customPoster = Boolean(homeElement("hero-poster").src?.trim());
  const earlyPoster =
    typeof window !== "undefined" ? window.amData?.heroPoster : null;
  const mobilePoster =
    earlyPoster?.mobile ||
    (customPoster
      ? poster.src
      : "/assets/videos/alexandra-promo-poster-768-3f8765e4.webp");
  const [videoEnabled, setVideoEnabled] = useState(() => inVisualBuilderCanvas());
  const [videoReady, setVideoReady] = useState(false);
  const videoEditable = editableMediaProps(
    "hero-video",
    "Home hero video",
    "crop",
    "/assets/videos/alexandra-promo.mp4",
    "Alexandra Montessori nursery life",
  );

  useEffect(() => {
    if (videoEnabled || inVisualBuilderCanvas()) return undefined;

    const connection = navigator.connection;
    const effectiveType = String(connection?.effectiveType || "");
    const slowConnection =
      connection?.saveData === true ||
      effectiveType.includes("2g") ||
      (Number(connection?.downlink) > 0 && Number(connection.downlink) < 2);
    const desktop = window.matchMedia("(min-width: 1024px)").matches;
    const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    if (!desktop || reducedMotion || slowConnection) return undefined;

    let delayId;
    let idleId;
    const enable = () => setVideoEnabled(true);
    const schedule = () => {
      delayId = window.setTimeout(() => {
        if ("requestIdleCallback" in window) {
          idleId = window.requestIdleCallback(enable, { timeout: 1500 });
        } else {
          enable();
        }
      }, 3000);
    };

    if (document.readyState === "complete") schedule();
    else window.addEventListener("load", schedule, { once: true });

    return () => {
      window.removeEventListener("load", schedule);
      window.clearTimeout(delayId);
      if (idleId && "cancelIdleCallback" in window) window.cancelIdleCallback(idleId);
    };
  }, [videoEnabled]);

  return (
    <section
      className="am-home-design-region am-home-hero relative h-[380px] overflow-hidden sm:h-[460px] lg:h-[540px]"
      data-am-vb-region="home-hero"
      style={homeSectionStyle("home-hero")}
    >
      <picture className="absolute inset-0 block h-full w-full">
        {!customPoster ? (
          <source
            media="(max-width: 1023px)"
            srcSet={asset(mobilePoster)}
          />
        ) : mobilePoster !== poster.src ? (
          <source media="(max-width: 1023px)" srcSet={asset(mobilePoster)} />
        ) : null}
        <img
          src={asset(poster.src)}
          alt=""
          aria-hidden="true"
          decoding="async"
          fetchPriority="high"
          className="block h-full w-full object-cover brightness-110"
        />
      </picture>
      {videoEnabled ? (
        <video
          {...videoEditable}
          data-am-vb-edit-type="video"
          data-am-vb-poster-key="hero-poster"
          className={`am-vb-editable-media absolute inset-0 h-full w-full object-cover brightness-110 transition-opacity duration-700 ${videoReady ? "opacity-100" : "opacity-0"}`}
          autoPlay
          muted
          loop
          playsInline
          preload="none"
          poster={asset(poster.src)}
          aria-hidden="true"
          onCanPlay={() => setVideoReady(true)}
        >
          {customVideo ? (
            <source
              src={asset(video.src)}
              type={/\.webm(?:$|\?)/i.test(video.src) ? "video/webm" : "video/mp4"}
            />
          ) : (
            <>
              <source
                src={asset("/assets/videos/alexandra-promo-loop-960-4ae9b51b.webm")}
                type="video/webm"
              />
              <source
                src={asset("/assets/videos/alexandra-promo-loop-960-7a29c655.mp4")}
                type="video/mp4"
              />
            </>
          )}
        </video>
      ) : null}
      <div className="am-home-hero-texture pointer-events-none hero-dither absolute inset-0 opacity-35" />
      <div className="am-home-hero-tint pointer-events-none absolute inset-0 bg-[#3a5040]/14" />
      <h1 className="sr-only">{brand.name}</h1>
    </section>
  );
}

/* ------------------------------- Peach three-column circular feature band */
function FeatureLinks() {
  return (
    <section
      className="am-home-design-region bg-sand py-12 sm:py-14"
      data-am-vb-region="home-feature-links"
      style={homeSectionStyle("home-feature-links")}
    >
      <div
        className="am-home-content am-home-grid am-home-variable-grid container-wide grid gap-8"
        style={{ "--am-home-columns": Math.min(featureItems.length, 4) }}
      >
        {featureItems.map((b, i) => {
          const number = i + 1;
          const titleKey = `${b.keyPrefix}-title`;
          const ctaKey = `${b.keyPrefix}-cta`;
          const imageKey = `${b.keyPrefix}-image`;
          const iconKey = `${b.keyPrefix}-icon`;
          const image = homeMedia(imageKey, b.image, b.eyebrow);
          const featureIcon = homeMedia(iconKey, "", `${b.eyebrow} icon`);
          const title = homeText(titleKey, b.eyebrow);
          const responsiveImage = image.srcSet
            ? { srcSet: image.srcSet, sizes: "(min-width: 640px) 224px, 208px" }
            : responsiveFeatureProps(image.src);
          return (
          <Reveal
            key={titleKey}
            delay={i * 120}
            data-am-vb-collection-extra={b.keyPrefix.includes("-extra-") ? "true" : undefined}
            className="flex flex-col items-center text-center"
          >
            <h2
              {...editableTextProps(titleKey, `Feature ${number} heading`, false, b.eyebrow)}
              className="font-heading text-2xl font-medium text-sage-800"
            >
              {title}
            </h2>
            <Link to={homeHref(ctaKey, b.to)} className="group mt-4 block">
              <span
                {...editableMediaProps(imageKey, `Feature ${number} photo`, "crop", b.image, b.eyebrow)}
                className="am-vb-editable-media am-home-feature-media hexagon mx-auto block aspect-[1.1547/1] w-52 overflow-hidden bg-sage-50 sm:w-56"
              >
                <Img
                  src={image.src}
                  {...responsiveImage}
                  alt={image.alt || title}
                  rounded="rounded-none"
                  position={b.imagePosition}
                  className="h-full w-full transition-transform duration-700 group-hover:scale-110"
                />
              </span>
            </Link>
            <Link
              to={homeHref(ctaKey, b.to)}
              {...editableTextProps(ctaKey, `Feature ${number} link`, false, b.cta)}
              className="mt-4 font-body text-[1.05rem] font-medium text-sage-700 underline-offset-4 hover:underline"
            >
              {homeText(ctaKey, b.cta)}
            </Link>
            <span
              {...editableMediaProps(iconKey, `Feature ${number} icon`, "move")}
              className="am-vb-editable-media mt-3 flex h-12 w-12 items-center justify-center text-sage-700"
            >
              {featureIcon.src ? <img src={asset(featureIcon.src)} alt={featureIcon.alt} className="h-full w-full object-contain" /> : null}
              <Icon name={b.icon || "Image"} className={`h-9 w-9 ${featureIcon.src ? "hidden" : ""}`} />
            </span>
          </Reveal>
          );
        })}
      </div>
    </section>
  );
}

/* -------------------------------------------- White three-benefit icon row */
function Benefits() {
  return (
    <section
      className="am-home-design-region bg-white py-12 sm:py-16"
      data-am-vb-region="home-benefits"
      style={homeSectionStyle("home-benefits")}
    >
      <div
        className="am-home-content am-home-grid am-home-variable-grid container-wide grid gap-8 sm:gap-10"
        style={{ "--am-home-columns": Math.min(benefitItems.length, 4) }}
      >
        {benefitItems.map((b, i) => {
          const iconKey = b.textKey.replace(/-text$/, "-icon");
          const benefitIcon = homeMedia(iconKey, "", `${b.text} icon`);
          return (
          <Reveal
            key={b.textKey}
            delay={i * 100}
            data-am-vb-collection-extra={b.textKey.includes("-extra-") ? "true" : undefined}
            className="flex flex-col items-center text-center"
          >
            <span
              {...editableMediaProps(iconKey, `Benefit ${i + 1} icon`, "move")}
              className="am-vb-editable-media flex h-12 w-12 items-center justify-center text-sage-700"
            >
              {benefitIcon.src ? <img src={asset(benefitIcon.src)} alt={benefitIcon.alt} className="h-full w-full object-contain" /> : null}
              <Icon name={b.icon} className={`h-9 w-9 ${benefitIcon.src ? "hidden" : ""}`} />
            </span>
            <p
              {...editableTextProps(b.textKey, `Benefit ${i + 1} text`, true, b.text)}
              className="am-home-benefit-copy mt-4 max-w-[15rem] font-body text-lg font-medium leading-snug text-sage-800"
            >
              {homeText(b.textKey, b.text)}
            </p>
          </Reveal>
          );
        })}
      </div>
    </section>
  );
}

/* ------------------------------------------------ About us / growth brief */
function AboutUsBrief() {
  const aboutImage = homeMedia(
    "about-image",
    aboutContent.image,
    "The founders of Alexandra Montessori",
  );
  return (
    <section
      className="am-home-design-region bg-sage-50 py-14 sm:py-20"
      data-am-vb-region="home-about"
      style={homeSectionStyle("home-about")}
    >
      <div className="am-home-content am-home-grid container-wide grid gap-10 lg:grid-cols-[1.05fr_0.95fr] lg:items-center">
        <Reveal className="order-2 lg:order-1">
          <h2
            {...editableTextProps("about-heading", "About heading", false, aboutContent.heading)}
            className="max-w-2xl font-heading text-4xl font-medium text-sage-800 sm:text-5xl"
          >
            {homeText("about-heading", aboutContent.heading)}
          </h2>
          <div className="mt-6 space-y-5 text-[1.02rem] leading-relaxed text-ink/90">
            {aboutContent.paragraphs.map((paragraph, index) => (
              <p
                key={`about-paragraph-${index + 1}`}
                {...editableTextProps(`about-paragraph-${index + 1}`, `About paragraph ${index + 1}`, true, paragraph)}
              >
                {homeText(`about-paragraph-${index + 1}`, paragraph)}
              </p>
            ))}
          </div>
          <div className="mt-8 flex flex-wrap gap-3">
            {aboutContent.milestones.map((milestone, index) => (
              <span
                key={`about-milestone-${index + 1}`}
                {...editableTextProps(`about-milestone-${index + 1}`, `Milestone ${index + 1}`, false, milestone)}
                className="rounded-full border border-sage-200 bg-white px-4 py-2 text-sm font-medium text-sage-700 shadow-soft"
              >
                {homeText(`about-milestone-${index + 1}`, milestone)}
              </span>
            ))}
            {aboutContent.upcoming ? (
              <span
                {...editableTextProps("about-upcoming", "Upcoming milestone", false, aboutContent.upcoming)}
                className="rounded-full border border-sage-300 bg-sage-100 px-4 py-2 text-sm font-semibold text-sage-800 shadow-soft"
              >
                {homeText("about-upcoming", aboutContent.upcoming)}
              </span>
            ) : null}
          </div>
        </Reveal>

        <Reveal delay={140} className="am-home-about-media order-1 flex justify-center lg:order-2">
          <Img
            src={aboutImage.src}
            srcSet={aboutImage.srcSet || cmsAbout?.imageSrcSet}
            sizes={cmsAbout?.imageSizes || "(min-width: 1024px) 448px, (min-width: 496px) 448px, calc(100vw - 48px)"}
            alt={aboutImage.alt}
            rounded="rounded-full"
            className="aspect-square w-full max-w-md shadow-card"
            editable={editableMediaProps("about-image", "About photo", "crop", aboutContent.image, "The founders of Alexandra Montessori")}
          />
        </Reveal>
      </div>
    </section>
  );
}

/* ----------------------- Testimonials over a full-width photo background */
function Testimonials() {
  if (testimonials.length === 0) return null;

  const background = homeMedia(
    "testimonials-background",
    "/assets/organisation/playground-balance-800-fd038d59.webp",
  );

  return (
    <section
      className="am-home-design-region relative overflow-hidden py-16 sm:py-24"
      data-am-vb-region="home-testimonials"
      style={homeSectionStyle("home-testimonials")}
    >
      <Img
        src={background.src}
        {...responsiveTestimonialBackground(background.src, background.srcSet)}
        alt=""
        rounded="rounded-none"
        className="absolute inset-0 h-full w-full"
        position="center 50%"
        editable={editableMediaProps("testimonials-background", "Testimonials background", "crop", "/assets/organisation/playground-balance-800-fd038d59.webp")}
      />
      <div className="am-home-testimonial-overlay pointer-events-none absolute inset-0 z-10 bg-[#3a5040]/72" />
      <div className="am-home-content container-wide relative z-20">
        <h2
          {...editableTextProps("testimonials-heading", "Testimonials heading", false, "What Parents Say")}
          className="text-center font-heading text-4xl font-medium text-white sm:text-5xl"
        >
          {homeText("testimonials-heading", "What Parents Say")}
        </h2>
        <div className="am-home-grid mt-12 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
          {testimonials.slice(0, 3).map((t, i) => {
            const iconKey = `testimonial-${i + 1}-icon`;
            const quoteIcon = homeMedia(iconKey, "", `Testimonial ${i + 1} quote icon`);
            return (
            <Reveal
              key={i}
              delay={i * 130}
              {...editableFrameProps(`testimonial-${i + 1}-card`, `Testimonial ${i + 1} card`)}
              className="am-home-testimonial-card relative flex h-full min-w-0 flex-col overflow-hidden rounded-4xl bg-sand/95 p-8 text-left shadow-card sm:p-9"
            >
              <span data-am-vb-frame-handle aria-hidden="true">⋮⋮</span>
              <span
                {...editableMediaProps(iconKey, `Testimonial ${i + 1} quote icon`, "move")}
                className="am-vb-editable-media inline-flex h-9 w-9 shrink-0 items-center justify-center text-sage-400"
              >
                {quoteIcon.src ? <img src={asset(quoteIcon.src)} alt={quoteIcon.alt} className="h-full w-full object-contain" /> : null}
                <Quote className={`h-9 w-9 ${quoteIcon.src ? "hidden" : ""}`} strokeWidth={1.6} />
              </span>
              <h3
                {...editableTextProps(`testimonial-${i + 1}-title`, `Testimonial ${i + 1} title`, false, t.title)}
                className="mt-5 font-heading text-xl font-medium text-sage-800 sm:text-2xl"
              >
                {homeText(`testimonial-${i + 1}-title`, t.title)}
              </h3>
              <p
                {...editableTextProps(`testimonial-${i + 1}-quote`, `Testimonial ${i + 1} quote`, true, t.quote)}
                className="mt-4 min-w-0 max-w-full flex-1 whitespace-pre-wrap break-words text-pretty text-[0.95rem] leading-relaxed text-ink/90"
              >
                {homeText(`testimonial-${i + 1}-quote`, t.quote)}
              </p>
              <p className="mt-6 border-t border-sage-800/15 pt-4 font-body text-sm font-medium text-sage-800">
                <span {...editableTextProps(`testimonial-${i + 1}-name`, `Testimonial ${i + 1} name`, false, t.name)}>
                  {homeText(`testimonial-${i + 1}-name`, t.name)}
                </span>
                <span aria-hidden="true" className="font-normal text-ink/85">, </span>
                <span
                  {...editableTextProps(`testimonial-${i + 1}-location`, `Testimonial ${i + 1} location`, false, t.location)}
                  className="font-normal text-ink/85"
                >
                  {homeText(`testimonial-${i + 1}-location`, t.location)}
                </span>
              </p>
            </Reveal>
            );
          })}
        </div>
        <div className="mt-10 text-center">
          <Link
            to={homeHref("testimonials-cta", "/testimonials")}
            {...editableTextProps("testimonials-cta", "Testimonials link", false, "Read all parent stories")}
            className="inline-flex rounded-full border border-white/60 bg-white/10 px-6 py-3 text-sm font-semibold text-white transition-colors hover:bg-white/20"
          >
            {homeText("testimonials-cta", "Read all parent stories")}
          </Link>
        </div>
      </div>
    </section>
  );
}

/* ------------------------------------------------------ Trust / accreditation */
function Trust() {
  return (
    <section
      className="am-home-design-region bg-white py-12 sm:py-16"
      data-am-vb-region="home-trust"
      style={homeSectionStyle("home-trust")}
    >
      <div className="am-home-content container-wide">
        <div
          className="am-home-grid am-home-trust-grid am-home-variable-grid mx-auto grid w-full max-w-[26rem] gap-6 sm:max-w-6xl sm:gap-5"
          style={{ "--am-home-columns": Math.min(trustItems.length, 5) }}
        >
          {trustItems.map((item, index) => {
            const number = index + 1;
            const logoKey = item.logoKey;
            const logo = homeMedia(logoKey, "", `${item.label} logo`);
            return (
            <div
              key={item.labelKey}
              data-am-vb-collection-extra={item.labelKey.includes("-extra-") ? "true" : undefined}
              className="grid grid-cols-[2.25rem_1fr] items-center gap-4 text-left text-sage-800 sm:grid-cols-1 sm:justify-items-center sm:gap-3 sm:text-center"
            >
              <span
                {...editableMediaProps(logoKey, `Trust mark ${number}`, "move", "", `${item.label} logo`)}
                className="am-vb-editable-media am-home-trust-icon-wrap flex h-8 w-8 items-center justify-center"
              >
                {logo.src ? (
                  <img src={asset(logo.src)} alt={logo.alt} className="am-home-trust-icon h-full w-full object-contain" />
                ) : null}
                <Icon
                  name="ShieldCheck"
                  className={`am-home-trust-icon h-7 w-7 text-sage-700 ${logo.src ? "hidden" : ""}`}
                />
              </span>
              <span
                {...editableTextProps(item.labelKey, `Trust mark ${number} label`, false, item.label)}
                className="font-body text-sm font-medium uppercase leading-snug tracking-wide"
              >
                {homeText(item.labelKey, item.label)}
              </span>
            </div>
            );
          })}
        </div>
      </div>
    </section>
  );
}

/* --------------------------------------------------- User-created sections */
function customItemStyle(item) {
  const style = {
    backgroundColor:
      item.type === "shape" || item.type === "button"
        ? item.backgroundColor
        : undefined,
    color: item.color,
    borderRadius: `${Number(item.borderRadius || 0)}px`,
  };
  ["desktop", "tablet", "mobile"].forEach((device) => {
    const values = item[device] || {};
    ["x", "y", "width", "height", "fontSize"].forEach((key) => {
      const value = Number(values[key]);
      if (Number.isFinite(value)) {
        style[`--am-custom-${key}-${device}`] = `${value}px`;
      }
    });
  });
  return style;
}

function CustomHomeItem({ section, item }) {
  const common = {
    "data-am-vb-custom-element": item.id,
    "data-am-vb-custom-owner": section.id,
    style: customItemStyle(item),
  };
  const fallback = item.type === "tabs"
    ? "Overview\nLearning\nCare"
    : item.type === "button"
      ? "Learn more"
      : "Edit this text";

  if (item.type === "image" || item.type === "logo") {
    const media = homeMedia(item.key, "", item.name || "Custom image");
    const editable = editableMediaProps(
      item.key,
      item.name || (item.type === "logo" ? "Logo" : "Photo"),
      "move",
      "",
      item.name || "",
    );
    return (
      <div
        {...editable}
        {...common}
        style={{ ...editable.style, ...common.style }}
        className={`am-home-custom-item am-home-custom-media am-vb-editable-media ${item.type === "logo" ? "is-logo" : ""}`}
      >
        {media.src ? (
          <img src={asset(media.src)} alt={media.alt} />
        ) : (
          <span className="am-home-custom-placeholder">
            {item.type === "logo" ? "Add logo" : "Add photo"}
          </span>
        )}
      </div>
    );
  }

  if (item.type === "shape") {
    return (
      <span
        {...common}
        data-am-vb-editable={item.key}
        data-am-vb-edit-type="shape"
        data-am-vb-label={item.name || "Shape"}
        className={`am-home-custom-item am-home-custom-shape is-${item.shape || "rectangle"}`}
        aria-hidden="true"
      />
    );
  }

  if (item.type === "button") {
    return (
      <a
        {...editableTextProps(item.key, item.name || "Button", false, fallback)}
        {...common}
        href={homeHref(item.key, "#")}
        className="am-home-custom-item am-home-custom-button"
      >
        {homeText(item.key, fallback)}
      </a>
    );
  }

  if (item.type === "tabs") {
    const labels = homeText(item.key, fallback)
      .split(/\n|\|/)
      .map((label) => label.trim())
      .filter(Boolean);
    return (
      <div
        {...editableTextProps(item.key, item.name || "Tabs", true, fallback)}
        {...common}
        className="am-home-custom-item am-home-custom-tabs"
      >
        {labels.map((label, index) => (
          <span key={`${label}-${index}`}>{label}</span>
        ))}
      </div>
    );
  }

  return (
    <div
      {...editableTextProps(item.key, item.name || "Text", true, fallback)}
      {...common}
      className="am-home-custom-item am-home-custom-text"
    >
      {homeText(item.key, fallback)}
    </div>
  );
}

function CustomHomeSections({ after }) {
  return customSections
    .filter((section) => section.after === after)
    .map((section) => (
      <section
        key={section.id}
        className="am-home-design-region am-home-custom-section"
        data-am-vb-region={`custom-${section.id}`}
        data-am-vb-custom-section-id={section.id}
        style={{
          display: section.visible === false ? "none" : undefined,
          backgroundColor: section.backgroundColor || "#f7faf5",
          "--am-custom-height-desktop": `${Number(section.desktop?.height || 420)}px`,
          "--am-custom-height-tablet": `${Number(section.tablet?.height || 420)}px`,
          "--am-custom-height-mobile": `${Number(section.mobile?.height || 520)}px`,
        }}
      >
        <div className="am-home-custom-stage">
          {(section.items || []).map((item) => (
            <CustomHomeItem key={item.id} section={section} item={item} />
          ))}
        </div>
      </section>
    ));
}

export default function Home() {
  return (
    <>
      <Seo
        description="Montessori childcare for Babies to 5 Years, with welcoming settings in Hounslow, Heston and Hammersmith."
        path="/"
        image="/assets/organisation/classroom-main.webp"
      />
      <Hero />
      <CustomHomeSections after="home-hero" />
      <FeatureLinks />
      <CustomHomeSections after="home-feature-links" />
      <Benefits />
      <CustomHomeSections after="home-benefits" />
      <AboutUsBrief />
      <CustomHomeSections after="home-about" />
      <Testimonials />
      <CustomHomeSections after="home-testimonials" />
      <Trust />
      <CustomHomeSections after="home-trust" />
    </>
  );
}
