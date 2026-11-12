import { useState } from "react";
import { Image as ImagePlaceholder } from "lucide-react";
import { asset } from "../lib/asset";

const SHAPES = {
  arch: "arch",
  rounded: "rounded-4xl",
  soft: "rounded-5xl",
  square: "",
  circle: "rounded-full",
};

/**
 * Image with a branded skeleton and a graceful fallback.
 *
 * Editors upload photographs through WordPress, so a missing or broken file
 * has to degrade into a placeholder rather than a broken-image icon or a
 * collapsed layout.
 */
export default function Img({
  src,
  srcSet,
  sizes,
  alt = "",
  className = "",
  shape = "rounded",
  priority = false,
  position,
  fit = "cover",
  rainbow = false,
}) {
  const [status, setStatus] = useState({ src, failed: false, loaded: false });

  const wrapperClass = [
    // Only add `relative` when the caller has not already positioned the box.
    /\b(?:absolute|fixed|sticky)\b/.test(className) ? "" : "relative",
    "isolate overflow-hidden",
    SHAPES[shape] ?? SHAPES.rounded,
    rainbow ? "arch-rainbow" : "",
    className,
  ]
    .filter(Boolean)
    .join(" ");

  // Reset the tracked state when the caller swaps the source.
  const current =
    status.src === src ? status : { src, failed: false, loaded: false };

  if (!src || current.failed) {
    return (
      <div className={`${wrapperClass} border border-sky-200 bg-sky-100`} aria-hidden>
        <div className="absolute inset-0 bg-gradient-to-br from-sky-200 via-sky-100 to-white" />
        <div className="grid-bg absolute inset-0 opacity-60" />
        <div className="absolute inset-0 flex items-center justify-center text-sky-500">
          <ImagePlaceholder className="h-6 w-6" strokeWidth={1.6} />
        </div>
      </div>
    );
  }

  return (
    <div className={wrapperClass}>
      <div
        aria-hidden
        className={`absolute inset-0 transition-opacity duration-300 ${
          current.loaded ? "opacity-0" : "opacity-100"
        }`}
      >
        <div className="absolute inset-0 bg-gradient-to-br from-sky-200 via-sky-100 to-white" />
        <div className="grid-bg absolute inset-0 opacity-60" />
      </div>
      <img
        src={asset(src)}
        srcSet={srcSet}
        sizes={srcSet ? sizes : undefined}
        alt={alt}
        loading={priority ? "eager" : "lazy"}
        fetchPriority={priority ? "high" : "auto"}
        decoding="async"
        style={position ? { objectPosition: position } : undefined}
        onLoad={() => setStatus({ src, failed: false, loaded: true })}
        onError={() => setStatus({ src, failed: true, loaded: false })}
        className={`relative z-[1] block h-full w-full ${
          fit === "contain" ? "object-contain" : "object-cover"
        } transition-opacity duration-500 ${
          current.loaded ? "opacity-100" : "opacity-0"
        }`}
      />
    </div>
  );
}
