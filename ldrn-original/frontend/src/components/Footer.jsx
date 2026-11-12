import { useState } from "react";
import { Link } from "react-router-dom";
import { BadgeCheck, ChevronDown, Mail, MapPin, Phone } from "lucide-react";
import Logo from "./Logo";
import TbcBadge from "./TbcBadge";
import GroupNotice, { GroupBadge } from "./GroupNotice";
import { moreLinks } from "../data/site";
import { brand, footer, settings } from "../lib/content";
import { isTbc } from "../lib/tbc";
import { useWorkspacePages } from "../lib/workspace";

/**
 * The footer keeps its detail collapsed by default. Most visitors want the
 * phone number and the Ofsted link; the full sitemap is one tap away rather
 * than a wall of links on every page.
 */
export default function Footer() {
  const directory = useWorkspacePages();
  const visible = (route) => directory?.find((page) => page.route === route)?.visible !== false;
  const quickLinks = directory ? directory.filter((page) => page.visible && page.group === 'other').map((page) => ({to:page.route,label:page.label})) : moreLinks;
  const [expanded, setExpanded] = useState(false);
  const year = new Date().getFullYear();

  return (
    <footer id="site-footer" className="scroll-mt-20 bg-sky-300 text-ink">
      <div className="container-wide py-7 sm:py-8 lg:py-9">
        <div className="flex flex-col items-center justify-between gap-6 text-center lg:flex-row lg:items-start lg:text-left">
          <Logo size="footer" />
          <GroupBadge />
          <div className="flex flex-col items-center gap-2 lg:items-end lg:text-right">
            <p className="max-w-sm text-sm font-semibold leading-relaxed text-ink/85 sm:text-base">
              {footer.blurb}
            </p>
            <a
              href={`mailto:${brand.email}`}
              className="break-all text-sm font-semibold text-ink transition-colors hover:text-sky-700 sm:text-base"
            >
              {brand.email}
            </a>
            <p className="text-sm text-ink/85 sm:text-base">{footer.hoursLine}</p>
            <Link
              to="/about/ofsted"
              className="mt-2 inline-flex min-w-48 items-center gap-3 rounded bg-white px-4 py-3 text-left shadow-soft transition-transform hover:-translate-y-0.5 hover:shadow-card"
              aria-label={`${footer.ofstedTitle}, ${footer.ofstedDetail}`}
            >
              <BadgeCheck
                className="h-9 w-9 shrink-0 text-sky-700"
                strokeWidth={1.6}
                aria-hidden
              />
              <span className="flex flex-col leading-tight">
                <span className="font-heading text-xl font-semibold text-ink">
                  {footer.ofstedTitle}
                </span>
                <span className="mt-1 text-[0.68rem] font-semibold uppercase tracking-[0.08em] text-ink/70">
                  {footer.ofstedDetail}
                </span>
              </span>
            </Link>
          </div>
        </div>

        <GroupNotice
          className="mt-6 text-center text-sm font-semibold leading-relaxed text-ink/85 sm:text-base"
          linkClassName="font-bold text-ink underline hover:text-sky-700"
        />

        {expanded ? (
          <div id="footer-more" className="pt-6">
            <div className="grid gap-9 border-t border-sky-900/20 pt-7 md:grid-cols-2 lg:grid-cols-3">
              <div className="text-center lg:text-left">
                <h2 className="mb-4 font-body text-xs font-bold uppercase tracking-[0.18em] text-ink">
                  {footer.quickLinksHeading}
                </h2>
                <div className="space-y-2.5">
                  {quickLinks.map((link) => (
                    <Link
                      key={link.to}
                      to={link.to}
                      className="block text-sm font-semibold text-ink/85 transition-colors hover:text-ink"
                    >
                      {link.label}
                    </Link>
                  ))}
                </div>
              </div>

              <div className="text-center lg:text-left">
                <h2 className="mb-4 font-body text-xs font-bold uppercase tracking-[0.18em] text-ink">
                  {footer.settingsHeading}
                </h2>
                <div className="space-y-4">
                  {settings.filter((setting) => visible(`/our-nursery/${setting.slug}`)).map((setting) => (
                    <div
                      key={setting.slug}
                      className="text-sm leading-relaxed text-ink/85"
                    >
                      <Link
                        to={`/our-nursery/${setting.slug}`}
                        className="font-bold text-ink hover:text-sky-700"
                      >
                        {setting.name}
                      </Link>
                      {isTbc(setting.address) ? (
                        <div>
                          <span className="block italic text-ink/70">
                            {footer.pendingLabel}
                          </span>
                          <TbcBadge value={setting.address} className="mt-1" />
                        </div>
                      ) : (
                        <p className="mx-auto mt-0.5 max-w-xs lg:mx-0">
                          {setting.address.join(", ")}
                        </p>
                      )}
                      {setting.statusNote && (
                        <p className="mx-auto mt-1 max-w-xs text-xs text-ink/55 lg:mx-0">
                          {setting.statusNote}
                        </p>
                      )}
                    </div>
                  ))}
                </div>
              </div>

              <div className="text-center md:col-span-2 lg:col-span-1 lg:text-left">
                <h2 className="mb-4 font-body text-xs font-bold uppercase tracking-[0.18em] text-ink">
                  {footer.contactHeading}
                </h2>
                <div className="flex flex-col items-center gap-3 lg:items-start">
                  <a
                    href={`tel:${brand.phone.general.replace(/\s/g, "")}`}
                    className="inline-flex items-center gap-2.5 text-sm font-semibold text-ink/85 hover:text-ink"
                  >
                    <Phone className="h-4 w-4" strokeWidth={1.8} aria-hidden />
                    {brand.phone.general}
                  </a>
                  <a
                    href={`mailto:${brand.email}`}
                    className="inline-flex items-start gap-2.5 break-all text-sm font-semibold text-ink/85 hover:text-ink"
                  >
                    <Mail
                      className="mt-0.5 h-4 w-4 shrink-0"
                      strokeWidth={1.8}
                      aria-hidden
                    />
                    {brand.email}
                  </a>
                  {footer.address && (
                    <p className="inline-flex items-start gap-2.5 text-sm font-semibold text-ink/85">
                      <MapPin
                        className="mt-0.5 h-4 w-4 shrink-0"
                        strokeWidth={1.8}
                        aria-hidden
                      />
                      {footer.address}
                    </p>
                  )}
                  <Link
                    to="/contact"
                    className="inline-flex items-center gap-2.5 text-sm font-semibold text-ink/85 hover:text-ink"
                  >
                    <MapPin className="h-4 w-4" strokeWidth={1.8} aria-hidden />
                    {footer.directionsLabel}
                  </Link>
                  {footer.socials.length > 0 && (
                    <div className="flex flex-wrap justify-center gap-3 lg:justify-start">
                      {footer.socials.map((social) => (
                        <a
                          key={social.href}
                          href={social.href}
                          target="_blank"
                          rel="noopener noreferrer"
                          className="rounded-full border border-sky-900/20 bg-white/60 px-3 py-1 text-xs font-bold text-ink/85 hover:text-ink"
                        >
                          {social.label || social.href}
                        </a>
                      ))}
                    </div>
                  )}
                </div>
              </div>
            </div>

            <div className="mt-7 flex flex-col items-center justify-between gap-3 border-t border-sky-900/20 pt-5 text-xs font-semibold text-ink/75 sm:flex-row">
              <p>
                {"© "}
                {year} {brand.name}. {footer.copyrightSuffix}
              </p>
              <div className="flex items-center gap-5">
                <Link to="/privacy" className="hover:text-ink">
                  {footer.privacyLabel}
                </Link>
                <Link to="/contact" className="hover:text-ink">
                  {footer.contactLabel}
                </Link>
                <TbcBadge value={footer.company} />
              </div>
            </div>

            <div className="mt-5 flex justify-center">
              <button
                type="button"
                onClick={() => setExpanded(false)}
                className="inline-flex items-center gap-2 rounded-full border border-sky-900/30 bg-white/15 px-5 py-2 text-sm font-bold text-ink transition-colors hover:bg-white/45 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-900"
                aria-expanded="true"
                aria-controls="footer-more"
              >
                {footer.collapseLabel}
                <ChevronDown className="h-4 w-4 rotate-180" aria-hidden />
              </button>
            </div>
          </div>
        ) : (
          <div className="mt-5 flex justify-center border-t border-sky-900/20 pt-5">
            <button
              type="button"
              onClick={() => setExpanded(true)}
              className="inline-flex items-center gap-2 rounded-full border border-sky-900/30 bg-white/15 px-5 py-2 text-sm font-bold text-ink transition-colors hover:bg-white/45 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-900"
              aria-expanded="false"
              aria-controls="footer-more"
            >
              {footer.expandLabel}
              <ChevronDown className="h-4 w-4" aria-hidden />
            </button>
          </div>
        )}
      </div>
    </footer>
  );
}
