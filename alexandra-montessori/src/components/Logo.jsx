import { Link } from "react-router-dom";
import logoBadge from "../assets/logo-badge-optimized.webp";
import { asset } from "../lib/asset";
import {
  editableMediaProps,
  editableTextProps,
  homeMedia,
  homeText,
} from "../lib/homeVisual";

// Brand lockup: circular badge + stacked wordmark (mirrors MRN's logo + name layout).
// `variant="light"` renders cream text for dark backgrounds (navbar / footer).
// `size` tweaks the badge diameter; `showText={false}` shows the badge alone.
export default function Logo({
  variant = "light",
  size = "md",
  showText = true,
  className = "",
  editableKey = "",
}) {
  const text = variant === "light" ? "text-white" : "text-sage-800";
  const sub = variant === "light" ? "text-white/85" : "text-sage-600";
  const badge =
    size === "lg"
      ? "h-[9rem] w-[9rem]"
      : size === "nav"
        ? "h-28 w-28 sm:h-32 sm:w-32 lg:h-[10rem] lg:w-[10rem]"
      : size === "xl"
        ? "h-[8rem] w-[8rem]"
      : size === "sm"
        ? "h-14 w-14"
      : "h-[3.6rem] w-[3.6rem] sm:h-16 sm:w-16";
  const logo = homeMedia(editableKey, logoBadge, "Alexandra Montessori");
  const logoSizes =
    size === "nav"
      ? "(min-width: 1024px) 160px, (min-width: 640px) 128px, 112px"
      : size === "lg"
        ? "144px"
        : size === "xl"
          ? "128px"
          : size === "sm"
            ? "56px"
            : "64px";
  const editable = editableKey
    ? editableMediaProps(editableKey, "Brand logo", "move", logoBadge, "Alexandra Montessori")
    : {};

  return (
    <Link
      {...editable}
      to="/"
      className={`group inline-flex shrink-0 items-center gap-3 ${editableKey ? "am-vb-editable-media" : ""} ${className}`}
      aria-label="Alexandra Montessori - home"
    >
      <span
        className={`flex ${badge} shrink-0 items-center justify-center transition-transform duration-300 group-hover:-rotate-2`}
      >
        <img
          src={asset(logo.src)}
          srcSet={logo.srcSet || undefined}
          sizes={logo.srcSet ? logoSizes : undefined}
          alt={logo.alt}
          className="h-full w-full object-contain drop-shadow-[0_10px_18px_rgba(47,51,38,0.16)]"
        />
      </span>
      {showText && (
        <span className="hidden flex-col leading-[1.1] sm:flex">
          <span
            {...(editableKey ? editableTextProps(`${editableKey}-brand-line-1`, "Brand name line 1", false, "Alexandra") : {})}
            className={`font-heading ${size === "lg" ? "text-2xl" : "text-xl"} font-medium tracking-tight ${text}`}
          >
            {editableKey ? homeText(`${editableKey}-brand-line-1`, "Alexandra") : "Alexandra"}
          </span>
          <span
            {...(editableKey ? editableTextProps(`${editableKey}-brand-line-2`, "Brand name line 2", false, "Montessori") : {})}
            className={`font-heading ${size === "lg" ? "text-2xl" : "text-xl"} font-medium tracking-tight ${text}`}
          >
            {editableKey ? homeText(`${editableKey}-brand-line-2`, "Montessori") : "Montessori"}
          </span>
          <span
            {...(editableKey ? editableTextProps(`${editableKey}-strapline`, "Brand strapline", false, "Learning for life") : {})}
            className={`mt-1 font-medium uppercase tracking-[0.2em] ${size === "lg" ? "text-[0.68rem]" : "text-[0.62rem]"} ${sub}`}
          >
            {editableKey ? homeText(`${editableKey}-strapline`, "Learning for life") : "Learning for life"}
          </span>
        </span>
      )}
    </Link>
  );
}
