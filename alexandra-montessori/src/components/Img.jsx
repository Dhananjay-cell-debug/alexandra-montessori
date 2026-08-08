import { useState } from "react";
import { ImageIcon } from "lucide-react";
import { asset } from "../lib/asset";

// Real <img> with lazy-loading, async decode and a graceful sage fallback
// if the source is missing or fails to load. `className` carries sizing
// (aspect ratio / width); object-cover keeps photos nicely framed.
export default function Img({
  src,
  srcSet,
  sizes,
  alt = "",
  className = "",
  rounded = "rounded-4xl",
  priority = false,
  position,
  imageTransform,
  fit = "cover",
  editable,
}) {
  const [imageState, setImageState] = useState(() => ({
    src,
    failed: false,
    loaded: false,
  }));
  const hasPositionClass = /\b(?:absolute|fixed|sticky)\b/.test(className);
  // `isolate` keeps the placeholder/image stacking inside this frame so a
  // background-style Img cannot paint above sibling overlays or page copy.
  const frameClassName = `${hasPositionClass ? "" : "relative"} isolate overflow-hidden ${rounded} ${editable ? "am-vb-editable-media" : ""} ${className}`;
  const editableProps = editable || {};
  const stateForSrc =
    imageState.src === src
      ? imageState
      : { src, failed: false, loaded: false };
  const { failed, loaded } = stateForSrc;

  if (!src || failed) {
    return (
      <div
        {...editableProps}
        className={`${frameClassName} border border-sage-200/70 bg-sage-100`}
        aria-hidden="true"
      >
        <div className="absolute inset-0 bg-gradient-to-br from-sage-200 via-sage-100 to-cream" />
        <div className="grid-bg absolute inset-0 opacity-60" />
        <div className="absolute inset-0 flex items-center justify-center text-sage-500">
          <ImageIcon className="h-6 w-6" strokeWidth={1.6} />
        </div>
      </div>
    );
  }

  return (
    <div {...editableProps} className={frameClassName}>
      <div
        aria-hidden="true"
        className={`absolute inset-0 transition-opacity duration-300 ${loaded ? "opacity-0" : "opacity-100"}`}
      >
        <div className="absolute inset-0 bg-gradient-to-br from-sage-200 via-sage-100 to-cream" />
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
        style={
          position || imageTransform
            ? {
                ...(position ? { objectPosition: position } : {}),
                ...(imageTransform ? { transform: imageTransform } : {}),
              }
            : undefined
        }
        onLoad={() => setImageState({ src, failed: false, loaded: true })}
        onError={() => setImageState({ src, failed: true, loaded: false })}
        className={`relative z-[1] block h-full w-full ${fit === "contain" ? "object-contain" : "object-cover"} transition-opacity duration-500 ${loaded ? "opacity-100" : "opacity-0"}`}
      />
    </div>
  );
}
