import { useEffect, useState } from "react";
import { createPortal } from "react-dom";
import { X, ArrowLeft, ChevronLeft, ChevronRight } from "lucide-react";
import { asset } from "../lib/asset";

// Fullscreen gallery overlay. Opens either on the grid of all photos, or - when
// `initialIndex` is a number - straight onto that single photo. From a single
// photo, "Back" returns to the grid; from the grid, "Back"/close returns the
// visitor to where they were on the page.
export default function GalleryModal({
  images = [],
  open,
  initialIndex = null,
  onClose,
}) {
  const [active, setActive] = useState(initialIndex);

  // Keyboard support + scroll lock while open.
  useEffect(() => {
    if (!open) return;
    const onKey = (e) => {
      if (e.key === "Escape") {
        if (active !== null) setActive(null);
        else onClose();
      } else if (active !== null && e.key === "ArrowRight") {
        setActive((i) => (i + 1) % images.length);
      } else if (active !== null && e.key === "ArrowLeft") {
        setActive((i) => (i - 1 + images.length) % images.length);
      }
    };
    document.addEventListener("keydown", onKey);
    document.body.style.overflow = "hidden";
    return () => {
      document.removeEventListener("keydown", onKey);
      document.body.style.overflow = "";
    };
  }, [open, active, images.length, onClose]);

  if (!open) return null;

  const inSingle = active !== null;
  const goBack = () => (inSingle ? setActive(null) : onClose());

  return createPortal(
    <div className="fixed inset-0 z-[100] flex flex-col bg-[#3a5040]/95 backdrop-blur-sm">
      {/* Top bar - Back / title / close */}
      <div className="flex items-center justify-between gap-4 px-5 py-4 sm:px-8">
        <button
          type="button"
          onClick={goBack}
          className="inline-flex items-center gap-2 rounded-full border border-white/30 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-white/10"
        >
          <ArrowLeft className="h-4 w-4" />
          Back
        </button>
        <p className="font-heading text-lg font-medium text-white">Gallery</p>
        <button
          type="button"
          onClick={onClose}
          aria-label="Close gallery"
          className="inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/30 text-white transition-colors hover:bg-white/10"
        >
          <X className="h-5 w-5" />
        </button>
      </div>

      {!inSingle ? (
        /* Natural-ratio masonry gallery */
        <div className="flex-1 overflow-y-auto px-5 pb-12 sm:px-8">
          <div className="mx-auto max-w-5xl columns-2 gap-3 sm:columns-3 lg:columns-4">
            {images.map((src, i) => (
              <button
                key={src}
                type="button"
                onClick={() => setActive(i)}
                className="group mb-3 block w-full break-inside-avoid overflow-hidden rounded-lg"
                aria-label={`Open photo ${i + 1}`}
              >
                <img
                  src={asset(src)}
                  alt={`Alexandra Montessori - photo ${i + 1}`}
                  loading="lazy"
                  decoding="async"
                  className="h-auto w-full transition-transform duration-500 group-hover:scale-105"
                />
              </button>
            ))}
          </div>
        </div>
      ) : (
        /* Single enlarged photo */
        <div className="relative flex flex-1 items-center justify-center px-4 pb-10 sm:px-16">
          <button
            type="button"
            onClick={() =>
              setActive((i) => (i - 1 + images.length) % images.length)
            }
            aria-label="Previous photo"
            className="absolute left-3 inline-flex h-11 w-11 items-center justify-center rounded-full bg-white/15 text-white transition-colors hover:bg-white/25 sm:left-6"
          >
            <ChevronLeft className="h-6 w-6" />
          </button>
          <img
            src={asset(images[active])}
            alt={`Alexandra Montessori - photo ${active + 1}`}
            className="max-h-[76vh] max-w-full rounded-lg object-contain shadow-2xl"
          />
          <button
            type="button"
            onClick={() => setActive((i) => (i + 1) % images.length)}
            aria-label="Next photo"
            className="absolute right-3 inline-flex h-11 w-11 items-center justify-center rounded-full bg-white/15 text-white transition-colors hover:bg-white/25 sm:right-6"
          >
            <ChevronRight className="h-6 w-6" />
          </button>
          <p className="absolute bottom-3 left-1/2 -translate-x-1/2 rounded-full bg-black/40 px-3 py-1 text-xs text-white/90">
            {active + 1} / {images.length}
          </p>
        </div>
      )}
    </div>,
    document.body,
  );
}
