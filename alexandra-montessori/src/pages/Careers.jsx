import { useState } from "react";
import { Link } from "react-router-dom";
import { ArrowRight, Plus } from "lucide-react";
import PageHeader from "../components/PageHeader";
import Reveal from "../components/Reveal";
import Icon from "../components/Icon";
import Img from "../components/Img";
import Seo from "../components/Seo";
import GalleryModal from "../components/GalleryModal";
import ApplicationForm from "../components/ApplicationForm";
import {
  careersBenefits,
  careersLookingFor,
  careersGallery,
  galleryImages,
} from "../data/site";

export default function Careers() {
  const [gallery, setGallery] = useState({ open: false, index: null });

  return (
    <>
      <Seo
        title="Careers"
        description="Join a supportive Montessori nursery team with real training, genuine support and career pathways from apprentice to manager."
        path="/careers"
      />
      <PageHeader title="Careers" region="careers-header" />

      {/* Vacancies link + intro */}
      <section className="bg-white pb-10" data-am-vb-region="careers-vacancies-intro">
        <div className="container-wide text-center">
          <Reveal>
            <Link
              to="/careers/vacancies"
              className="inline-flex items-center gap-3 rounded border border-sage-300 px-6 py-3 font-body text-sm font-medium text-sage-800 transition-colors hover:bg-sage-50"
            >
              Check out our current vacancies
              <span className="flex h-6 w-6 items-center justify-center rounded-full bg-sage-700 text-white">
                <ArrowRight className="h-3.5 w-3.5" />
              </span>
            </Link>
          </Reveal>
          <Reveal
            as="p"
            delay={100}
            className="mx-auto mt-6 max-w-2xl font-body text-lg font-medium text-sage-800"
          >
            If you are interested in working at one of our nurseries, please
            fill out the form below and we will get back to you as soon as
            possible.
          </Reveal>
        </div>
      </section>

      {/* Application form */}
      <section id="apply" className="scroll-mt-28 bg-white pb-16" data-am-vb-region="careers-application">
        <div className="container-wide">
          <Reveal>
            <ApplicationForm />
          </Reveal>
        </div>
      </section>

      {/* Why work with us */}
      <section className="bg-sand py-16 sm:py-20" data-am-vb-region="careers-benefits">
        <div className="container-wide text-center">
          <h2 className="font-heading text-3xl font-medium text-sage-800 sm:text-4xl">
            Why work with us?
          </h2>
          <div className="mt-12 grid gap-10 sm:grid-cols-3">
            {careersBenefits.map((b, i) => (
              <Reveal
                key={b.title}
                delay={i * 90}
                className="flex flex-col items-center"
              >
                <Icon name={b.icon} className="h-16 w-16 text-sage-700" />
                <h3 className="mt-4 font-heading text-xl font-medium text-sage-800">
                  {b.title}
                </h3>
                <p className="mt-2 max-w-xs text-sm leading-relaxed text-ink/85">
                  {b.text}
                </p>
              </Reveal>
            ))}
          </div>
        </div>
      </section>

      {/* Square preview grid + a natural-ratio "View more" gallery */}
      <section className="bg-white py-16 sm:py-20" data-am-vb-region="careers-gallery">
        <div className="container-wide">
          <h2 className="text-center font-heading text-3xl font-medium text-sage-800 sm:text-4xl">
            View Gallery
          </h2>
          <div className="mt-10 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
            {careersGallery.slice(0, 4).map((g, i) => (
              <Reveal key={g} delay={i * 60}>
                <button
                  type="button"
                  onClick={() => setGallery({ open: true, index: i })}
                  className="group block w-full overflow-hidden"
                  aria-label="Open photo"
                >
                  <Img
                    src={g}
                    alt="Life and learning at Alexandra Montessori"
                    rounded="rounded-none"
                    className="aspect-square w-full transition-transform duration-500 group-hover:scale-105"
                  />
                </button>
              </Reveal>
            ))}

            {/* Fifth tile - last photo, dimmed, with a "View more" prompt */}
            <Reveal delay={240}>
              <button
                type="button"
                onClick={() => setGallery({ open: true, index: null })}
                className="group relative block w-full overflow-hidden"
                aria-label="View more photos"
              >
                <Img
                  src={careersGallery[4]}
                  alt="More photos from Alexandra Montessori"
                  rounded="rounded-none"
                  className="aspect-square w-full"
                />
                <span className="absolute inset-0 z-[2] flex flex-col items-center justify-center gap-1.5 bg-[#3a5040]/55 text-white transition-colors group-hover:bg-[#3a5040]/65">
                  <Plus className="h-8 w-8" strokeWidth={2} />
                  <span className="font-body text-sm font-medium tracking-wide">
                    View more
                  </span>
                </span>
              </button>
            </Reveal>
          </div>
        </div>
      </section>

      {/* What we're looking for */}
      <section className="bg-sand py-16 sm:py-20" data-am-vb-region="careers-qualities">
        <div className="container-wide text-center">
          <h2 className="font-heading text-3xl font-medium text-sage-800 sm:text-4xl">
            What We Are Looking For?
          </h2>
          <div className="mt-12 grid gap-10 sm:grid-cols-3">
            {careersLookingFor.map((c, i) => (
              <Reveal
                key={c.title}
                delay={i * 90}
                className="flex flex-col items-center"
              >
                <Icon name={c.icon} className="h-16 w-16 text-sage-700" />
                <h3 className="mt-4 font-heading text-xl font-medium text-sage-800">
                  {c.title}
                </h3>
                <p className="mt-2 max-w-xs text-sm leading-relaxed text-ink/85">
                  {c.text}
                </p>
              </Reveal>
            ))}
          </div>
        </div>
      </section>

      {/* Closing band */}
      <section className="bg-sand py-16 text-center" data-am-vb-region="careers-closing">
        <div className="container-wide">
          <h2 className="font-heading text-3xl font-medium text-sage-800 sm:text-4xl">
            Ready to start your career at Alexandra Montessori?
          </h2>
          <a href="#apply" className="btn-primary mt-7">
            Start your application
          </a>
        </div>
      </section>

      {gallery.open && (
        <GalleryModal
          key={gallery.index ?? "grid"}
          images={galleryImages}
          open={gallery.open}
          initialIndex={gallery.index}
          onClose={() => setGallery({ open: false, index: null })}
        />
      )}
    </>
  );
}
