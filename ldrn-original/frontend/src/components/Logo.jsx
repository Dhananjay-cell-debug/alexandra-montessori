import { Link } from "react-router-dom";
import { brand } from "../lib/content";
import { asset } from "../lib/asset";

const SIZES = {
  sm: "h-16 w-16",
  nav: "h-28 w-28 sm:h-32 sm:w-32 lg:h-36 lg:w-36",
  footer: "h-32 w-32 sm:h-36 sm:w-36",
};

export default function Logo({ className = "", size = "sm", showText = true }) {
  const sizeClass = SIZES[size] ?? SIZES.sm;

  return (
    <Link
      to="/"
      className={`group inline-flex shrink-0 items-center gap-3 ${className}`}
      aria-label={`${brand.name} — home`}
    >
      <span
        className={`flex ${sizeClass} shrink-0 items-center justify-center overflow-hidden rounded-full border border-sky-100 bg-white shadow-card transition-transform duration-300 group-hover:-rotate-2`}
      >
        <img
          src={asset("/assets/logo/logo-primary-navy-wordmark.png")}
          alt=""
          aria-hidden
          className="h-full w-full scale-[1.2] object-contain"
        />
      </span>
      {showText && (
        <span className="hidden flex-col leading-[1.05] sm:flex">
          <span className="whitespace-nowrap font-heading text-xl font-semibold tracking-tight text-ink sm:text-2xl">
            {brand.wordmark.line1}
          </span>
          <span className="mt-1 font-body text-[0.62rem] font-semibold uppercase tracking-[0.28em] text-ink/75 sm:text-[0.68rem]">
            Day Nursery
          </span>
          <span className="mt-2 font-body text-[0.58rem] font-semibold uppercase tracking-[0.2em] text-ink/70 sm:text-[0.64rem]">
            {brand.tagline}
          </span>
        </span>
      )}
    </Link>
  );
}
