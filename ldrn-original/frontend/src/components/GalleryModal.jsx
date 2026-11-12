import { useEffect, useState } from "react";
import { createPortal } from "react-dom";
import { ArrowLeft, ChevronLeft, ChevronRight, X } from "lucide-react";
import { asset } from "../lib/asset";

/**
 * Full-screen photo gallery with a masonry index and a single-photo view.
 *
 * Rendered in a portal on `document.body` so no ancestor's overflow or stacking
 * context can clip it. Escape steps back one level (photo → grid → closed)
 * rather than dismissing everything at once, which matches what a visitor
 * browsing photos expects.
 */
export default function GalleryModal({
  images = [],
  open,
  initialIndex = null,
  onClose,
}) {
  const [index, setIndex] = useState(initialIndex);

  useEffect(() => {
    if (!open) return;

    const onKeyDown = (event) => {
      if (event.key === "Escape") {
        if (index === null) onClose();
        else setIndex(null);
      } else if (index !== null && event.key === "ArrowRight") {
        setIndex((current) => (current + 1) % images.length);
      } else if (index !== null && event.key === "ArrowLeft") {
        setIndex((current) => (current - 1 + images.length) % images.length);
      }
    };

    document.addEventListener("keydown", onKeyDown);
    document.body.style.overflow = "hidden";
    return () => {
      document.removeEventListener("keydown", onKeyDown);
      document.body.style.overflow = "";
    };
  }, [index, images.length, onClose, open]);

  if (!open || images.length === 0) return null;

  const viewingPhoto = index !== null;

  return createPortal(
    <div
      className="fixed inset-0 z-[100] flex flex-col bg-[#182758]/95 backdrop-blur-sm"
      role="dialog"
      aria-modal="true"
      aria-label="Little Rainbows photo gallery"
    >
      <div className="flex items-center justify-between gap-4 px-5 py-4 sm:px-8">
        <button
          type="button"
          onClick={() => (viewingPhoto ? setIndex(null) : onClose())}
          className="inline-flex items-center gap-2 rounded-full border border-white/30 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-white/10"
        >
          <ArrowLeft className="h-4 w-4" /> Back
        </button>
        <p className="font-heading text-lg font-semibold text-white">Gallery</p>
        <button
          type="button"
          onClick={onClose}
          aria-label="Close gallery"
          className="inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/30 text-white transition-colors hover:bg-white/10"
        >
          <X className="h-5 w-5" />
        </button>
      </div>

      {viewingPhoto ? (
        <div className="relative flex flex-1 items-center justify-center px-4 pb-10 sm:px-16">
          <button
            type="button"
            onClick={() =>
              setIndex((current) => (current - 1 + images.length) % images.length)
            }
            aria-label="Previous photo"
            className="absolute left-3 inline-flex h-11 w-11 items-center justify-center rounded-full bg-white/15 text-white transition-colors hover:bg-white/25 sm:left-6"
          >
            <ChevronLeft className="h-6 w-6" />
          </button>

          <img
            src={asset(images[index])}
            alt={`Life and learning at Little Rainbows — photo ${index + 1}`}
            className="max-h-[76vh] max-w-full rounded-lg object-contain shadow-2xl"
          />

          <button
            type="button"
            onClick={() => setIndex((current) => (current + 1) % images.length)}
            aria-label="Next photo"
            className="absolute right-3 inline-flex h-11 w-11 items-center justify-center rounded-full bg-white/15 text-white transition-colors hover:bg-white/25 sm:right-6"
          >
            <ChevronRight className="h-6 w-6" />
          </button>

          <p className="absolute bottom-3 left-1/2 -translate-x-1/2 rounded-full bg-black/40 px-3 py-1 text-xs text-white/90">
            {index + 1} / {images.length}
          </p>
        </div>
      ) : (
        <div className="flex-1 overflow-y-auto px-5 pb-12 sm:px-8">
          <div className="mx-auto max-w-5xl columns-2 gap-3 sm:columns-3 lg:columns-4">
            {images.map((image, imageIndex) => (
              <button
                key={image}
                type="button"
                onClick={() => setIndex(imageIndex)}
                className="group mb-3 block w-full break-inside-avoid overflow-hidden rounded-lg"
                aria-label={`Open photo ${imageIndex + 1}`}
              >
                <img
                  src={asset(image)}
                  alt={`Life and learning at Little Rainbows — photo ${imageIndex + 1}`}
                  loading="lazy"
                  decoding="async"
                  className="h-auto w-full transition-transform duration-500 group-hover:scale-105"
                />
              </button>
            ))}
          </div>
        </div>
      )}
    </div>,
    document.body,
  );
}
