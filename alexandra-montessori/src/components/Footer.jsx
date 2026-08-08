import { useState } from "react";
import { Link } from "react-router-dom";
import { ChevronDown, ExternalLink } from "lucide-react";
import Logo from "./Logo";
import { brand, locations, parentInfoLinks } from "../data/site";
import { gmailHref, telHref } from "../lib/contact";
import {
  editableFrameProps,
  editableMediaProps,
  editableTextProps,
  homeHref,
  homeMedia,
  homeText,
} from "../lib/homeVisual";

function OfstedMark() {
  const mark = homeMedia("footer-ofsted", "", "Ofsted");
  return (
    <span
      {...editableMediaProps("footer-ofsted", "Footer Ofsted logo", "move", "", "Ofsted")}
      className="am-vb-editable-media mt-2 block h-auto w-32 rounded bg-white px-3 py-2 shadow-sm sm:w-36 lg:ml-auto"
    >
    {mark.src ? <img src={mark.src} alt={mark.alt} className="h-full w-full object-contain" /> : <svg
      className="h-auto w-full"
      viewBox="0 0 460 310"
      role="img"
      aria-label="Ofsted"
    >
      <rect width="460" height="310" fill="#fff" />
      <g
        fill="none"
        stroke="#2796ad"
        strokeLinecap="round"
        strokeWidth="14"
      >
        <path d="M196 72 235 96" />
        <path d="M235 72 196 96" />
        <path d="M216 52v53" />
        <path d="M298 50 348 80" />
        <path d="M348 50 298 80" />
        <path d="M323 24v72" />
        <path d="M381 24 441 60" />
        <path d="M441 24 381 60" />
        <path d="M411 4v84" />
      </g>
      <text
        x="10"
        y="190"
        fill="#1f2d5c"
        fontFamily="Arial, Helvetica, sans-serif"
        fontSize="116"
        fontWeight="700"
        letterSpacing="-6"
      >
        Ofsted
      </text>
      <text
        x="12"
        y="246"
        fill="#1f2d5c"
        fontFamily="Arial, Helvetica, sans-serif"
        fontSize="38"
      >
        raising standards
      </text>
      <text
        x="12"
        y="292"
        fill="#1f2d5c"
        fontFamily="Arial, Helvetica, sans-serif"
        fontSize="38"
      >
        improving lives
      </text>
    </svg>}
    </span>
  );
}

export default function Footer() {
  const [showMore, setShowMore] = useState(false);
  const year = new Date().getFullYear();

  return (
    <footer
      className="mt-auto bg-[#a3bc9a] text-white"
      data-am-vb-region="global-footer"
    >
      <div className="container-wide py-6 sm:py-7 lg:py-8">
        <div className="flex flex-col items-center justify-between gap-5 text-center lg:flex-row lg:items-start lg:text-left">
          <Logo variant="light" size="lg" editableKey="footer-logo" />
          <div className="flex flex-col items-center gap-2 lg:items-end lg:text-right">
            <p
              {...editableTextProps("footer-tagline", "Footer tagline", true, brand.tagline)}
              className="max-w-[18rem] text-sm leading-snug text-white/88 sm:max-w-md sm:text-base"
            >
              {homeText("footer-tagline", brand.tagline)}
            </p>
            <a
              {...editableTextProps("footer-email", "Footer email", false, brand.email)}
              href={homeHref("footer-email", gmailHref(brand.email))}
              target="_blank"
              rel="noopener noreferrer"
              className="break-all text-sm font-medium text-white transition-colors hover:text-white/85 sm:text-base"
            >
              {homeText("footer-email", brand.email)}
            </a>
            <p
              {...editableTextProps("footer-hours", "Footer opening hours", false, brand.hours)}
              className="text-sm text-white/88 sm:text-base"
            >
              {homeText("footer-hours", brand.hours)}
            </p>
            <OfstedMark />
          </div>
        </div>

        {!showMore && (
          <div className="mt-4 flex justify-center border-t border-white/25 pt-4">
            <div
              {...editableFrameProps("footer-view-more-button", "Footer View more button")}
              role="button"
              tabIndex={0}
              aria-expanded={showMore}
              aria-controls="footer-more"
              onClick={() => setShowMore(true)}
              onKeyDown={(event) => {
                if (event.key === "Enter" || event.key === " ") {
                  event.preventDefault();
                  setShowMore(true);
                }
              }}
              className="inline-flex items-center gap-2 rounded-full border border-white/45 bg-white/10 px-5 py-2 text-sm font-semibold text-white transition-colors hover:bg-white/16 focus:outline-none focus-visible:ring-2 focus-visible:ring-white/70"
            >
              <span data-am-vb-frame-handle aria-hidden="true">⋮⋮</span>
              <span>
                {homeText("footer-view-more", "View more")}
              </span>
              <button
                type="button"
                data-am-vb-allow-action="true"
                aria-label="Expand footer"
                onClick={() => setShowMore(true)}
                className="-m-2 inline-flex h-8 w-8 items-center justify-center rounded-full"
              >
                <ChevronDown aria-hidden="true" className="h-4 w-4" />
              </button>
            </div>
          </div>
        )}

        {showMore && (
          <div id="footer-more" className="pt-5 sm:pt-6">
            <div className="grid gap-8 border-t border-white/25 pt-5 lg:grid-cols-3">
              <div className="text-center lg:text-left">
                <p
                  {...editableTextProps("footer-quick-links-heading", "Footer quick links heading", false, "Quick links")}
                  className="mb-3 text-sm font-semibold uppercase tracking-[0.16em] text-white/90"
                >
                  {homeText("footer-quick-links-heading", "Quick links")}
                </p>
                <div className="space-y-2">
                  {parentInfoLinks.map((item, index) => (
                    <Link
                      key={item.label}
                      {...editableTextProps(`footer-quick-link-${index + 1}`, `Footer quick link ${index + 1}`, false, item.label)}
                      to={homeHref(`footer-quick-link-${index + 1}`, item.to)}
                      className="block text-sm text-white/92 transition-colors hover:text-white"
                    >
                      {homeText(`footer-quick-link-${index + 1}`, item.label)}
                    </Link>
                  ))}
                </div>
              </div>

              <div className="text-center lg:text-left">
                <p
                  {...editableTextProps("footer-nurseries-heading", "Footer nursery contacts heading", false, "Nursery contacts")}
                  className="mb-3 text-sm font-semibold uppercase tracking-[0.16em] text-white/90"
                >
                  {homeText("footer-nurseries-heading", "Nursery contacts")}
                </p>
                <div className="space-y-4">
                  {locations.map((loc) => (
                    <div key={loc.id} className="text-sm leading-relaxed">
                      <p {...editableTextProps(`footer-${loc.id}-name`, `${loc.name} footer name`, false, loc.name)} className="font-semibold text-white">
                        {homeText(`footer-${loc.id}-name`, loc.name)}
                      </p>
                      <p {...editableTextProps(`footer-${loc.id}-address`, `${loc.name} footer address`, true, loc.address)} className="text-white/90">
                        {homeText(`footer-${loc.id}-address`, loc.address)}
                      </p>
                      <a
                        {...editableTextProps(`footer-${loc.id}-phone`, `${loc.name} footer phone`, false, loc.phone)}
                        href={homeHref(`footer-${loc.id}-phone`, telHref(loc.phone))}
                        className="mt-1 block text-white/92 transition-colors hover:text-white"
                      >
                        {homeText(`footer-${loc.id}-phone`, loc.phone)}
                      </a>
                    </div>
                  ))}
                </div>
              </div>

              <div className="text-center lg:text-left">
                <p
                  {...editableTextProps("footer-ofsted-heading", "Footer Ofsted heading", false, "Official Ofsted reports")}
                  className="mb-3 text-sm font-semibold uppercase tracking-[0.16em] text-white/90"
                >
                  {homeText("footer-ofsted-heading", "Official Ofsted reports")}
                </p>
                <div className="space-y-3">
                  {locations.map((loc) => {
                    const content = (
                      <>
                      <span {...editableTextProps(`footer-${loc.id}-ofsted-name`, `${loc.name} Ofsted name`, false, loc.name)} className="flex items-center justify-center gap-2 font-semibold text-white lg:justify-start">
                        {homeText(`footer-${loc.id}-ofsted-name`, loc.name)}
                        {loc.ofstedUrl ? (
                          <ExternalLink className="h-3.5 w-3.5" />
                        ) : null}
                      </span>
                      <span {...editableTextProps(`footer-${loc.id}-ofsted-label`, `${loc.name} Ofsted label`, false, loc.ofstedLabel || "Ofsted information pending")} className="mt-1 block text-white/88">
                        {homeText(`footer-${loc.id}-ofsted-label`, loc.ofstedLabel || "Ofsted information pending")}
                      </span>
                      </>
                    );
                    return loc.ofstedUrl ? (
                      <a
                        key={loc.id}
                        href={loc.ofstedUrl}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="block rounded border border-white/25 bg-white/8 px-4 py-3 text-sm transition-colors hover:bg-white/14"
                      >
                        {content}
                      </a>
                    ) : (
                      <div
                        key={loc.id}
                        className="block rounded border border-white/25 bg-white/8 px-4 py-3 text-sm"
                      >
                        {content}
                      </div>
                    );
                  })}
                </div>
              </div>
            </div>

            <div className="mt-5 flex flex-col items-center justify-between gap-3 border-t border-white/25 pt-4 text-xs text-white/88 sm:flex-row">
              <p {...editableTextProps("footer-copyright", "Footer copyright", false, `© ${year} ${brand.name}. All rights reserved.`)}>
                {homeText("footer-copyright", `© ${year} ${brand.name}. All rights reserved.`)}
              </p>
              <div className="flex items-center gap-5">
                <Link {...editableTextProps("footer-privacy", "Footer privacy link", false, "Privacy Policy")} to={homeHref("footer-privacy", "/privacy")} className="hover:text-white">
                  {homeText("footer-privacy", "Privacy Policy")}
                </Link>
                <Link {...editableTextProps("footer-contact", "Footer contact link", false, "Contact")} to={homeHref("footer-contact", "/contact")} className="hover:text-white">
                  {homeText("footer-contact", "Contact")}
                </Link>
              </div>
            </div>

            <div className="mt-4 flex justify-center">
              <div
                {...editableFrameProps("footer-view-less-button", "Footer View less button")}
                role="button"
                tabIndex={0}
                aria-expanded={showMore}
                aria-controls="footer-more"
                onClick={() => setShowMore(false)}
                onKeyDown={(event) => {
                  if (event.key === "Enter" || event.key === " ") {
                    event.preventDefault();
                    setShowMore(false);
                  }
                }}
                className="inline-flex items-center gap-2 rounded-full border border-white/45 bg-white/10 px-5 py-2 text-sm font-semibold text-white transition-colors hover:bg-white/16 focus:outline-none focus-visible:ring-2 focus-visible:ring-white/70"
              >
                <span data-am-vb-frame-handle aria-hidden="true">⋮⋮</span>
                <span>
                  {homeText("footer-view-less", "View less")}
                </span>
                <button
                  type="button"
                  data-am-vb-allow-action="true"
                  aria-label="Collapse footer"
                  onClick={() => setShowMore(false)}
                  className="-m-2 inline-flex h-8 w-8 items-center justify-center rounded-full"
                >
                  <ChevronDown aria-hidden="true" className="h-4 w-4 rotate-180" />
                </button>
              </div>
            </div>
          </div>
        )}
      </div>
    </footer>
  );
}
