import { useEffect, useRef, useState } from "react";
import { useLocation, NavLink } from "react-router-dom";
import { ArrowLeft, Menu, X } from "lucide-react";
import Logo from "./Logo";
import { moreLinks, navLabels, navLinks, primaryCta } from "../data/site";
import { allSettings } from "../lib/content";
import { useWorkspacePages } from "../lib/workspace";

/**
 * A top-level nav item. The nursery item opens a hover/focus dropdown on
 * desktop; every other item is a plain link.
 */
function NavItem({ item }) {
  const hasDropdown = item.to === "/our-nursery" && !!item.children?.length;

  const link = (
    <NavLink
      to={item.to}
      end={item.to === "/"}
      onClick={(event) => {
        // Blur after clicking the parent so the dropdown does not stay open
        // over the page the visitor just navigated to.
        if (hasDropdown) event.currentTarget.blur();
      }}
      className={({ isActive }) =>
        `whitespace-nowrap font-body text-[0.75rem] font-bold tracking-wide transition-colors xl:text-[0.86rem] ${
          isActive
            ? "text-ink underline decoration-2 underline-offset-[7px]"
            : "text-ink/85 hover:text-ink"
        }`
      }
    >
      {item.label}
    </NavLink>
  );

  if (!hasDropdown) return <li>{link}</li>;

  return (
    <li className="group relative">
      {link}
      <div className="pointer-events-none absolute left-1/2 top-full z-50 w-56 -translate-x-1/2 pt-4 opacity-0 transition-opacity duration-150 group-hover:pointer-events-auto group-hover:opacity-100 group-focus-within:pointer-events-auto group-focus-within:opacity-100">
        <div className="rounded-4xl border border-sky-200 bg-white p-2 shadow-card">
          {item.children.map((child) => (
            <NavLink
              key={child.to}
              to={child.to}
              className={({ isActive }) =>
                `block rounded-3xl px-4 py-2.5 text-sm font-semibold transition-colors ${
                  isActive ? "bg-sky-900 text-white" : "text-ink hover:bg-sky-50"
                }`
              }
            >
              {child.label}
            </NavLink>
          ))}
        </div>
      </div>
    </li>
  );
}

export default function Navbar() {
  const directory = useWorkspacePages();
  const isVisible = (route) => directory?.find((page) => page.route === route)?.visible !== false;
  const navigation = directory ? directory.filter((page) => page.visible && page.group === "primary").map((page) => ({
    ...(navLinks.find((item) => item.to === page.route) || {}), label: page.label, to: page.route,
    ...(page.route === "/our-nursery" ? { children: allSettings.filter((setting) => isVisible(`/our-nursery/${setting.slug}`)).map((setting) => ({ label: setting.name, to: `/our-nursery/${setting.slug}` })) } : {}),
  })) : navLinks;
  const moreNavigation = moreLinks.filter((item) => isVisible(item.to) && !navigation.some((primary) => primary.to === item.to));
  const [mobileOpen, setMobileOpen] = useState(false);
  const [infoOpen, setInfoOpen] = useState(false);
  const [mobileMoreOpen, setMobileMoreOpen] = useState(false);
  const infoRef = useRef(null);
  const { pathname } = useLocation();

  const closeMobile = () => {
    setMobileOpen(false);
    setMobileMoreOpen(false);
  };

  // Navigating always closes every menu, including on a same-page anchor.
  // Adjusted during render rather than in an effect so the new page never
  // paints once with the old menu still open.
  const [lastPathname, setLastPathname] = useState(pathname);
  if (lastPathname !== pathname) {
    setLastPathname(pathname);
    setMobileOpen(false);
    setMobileMoreOpen(false);
    setInfoOpen(false);
  }

  // The mobile menu is a full-screen overlay, so the page behind it must not
  // scroll underneath.
  useEffect(() => {
    document.body.style.overflow = mobileOpen ? "hidden" : "";
    return () => {
      document.body.style.overflow = "";
    };
  }, [mobileOpen]);

  useEffect(() => {
    const onPointerDown = (event) => {
      if (infoRef.current && !infoRef.current.contains(event.target)) {
        setInfoOpen(false);
      }
    };
    const onKeyDown = (event) => {
      if (event.key === "Escape") {
        closeMobile();
        setInfoOpen(false);
      }
    };
    document.addEventListener("mousedown", onPointerDown);
    window.addEventListener("keydown", onKeyDown);
    return () => {
      document.removeEventListener("mousedown", onPointerDown);
      window.removeEventListener("keydown", onKeyDown);
    };
  }, []);

  return (
    <>
      <span className="rainbow-rule-thin fixed inset-x-0 top-0 z-[60]" aria-hidden />
      <header className="fixed inset-x-0 top-[3px] z-50 bg-sky-300 text-ink shadow-nav">
        <div className="w-full px-5 sm:px-8 lg:px-10 xl:px-12">
          <nav
            className="relative flex h-[76px] items-center py-2 lg:h-20"
            aria-label="Primary"
          >
            {/* The badge overhangs the bar, so it is positioned out of flow
                and a spacer of the same width keeps the links clear of it. */}
            <div className="absolute left-0 top-1 z-10 lg:top-2">
              <Logo size="nav" showText={false} />
            </div>
            <div
              className="w-[7.5rem] shrink-0 sm:w-[8.75rem] lg:w-[9.75rem]"
              aria-hidden
            />

            <ul className="hidden flex-1 items-center justify-center gap-3 px-3 lg:flex xl:gap-7 xl:px-5">
              {navigation.map((item) => (
                <NavItem key={item.to} item={item} />
              ))}
            </ul>

            <NavLink
              to={primaryCta.to}
              className={({ isActive }) =>
                `hidden shrink-0 whitespace-nowrap rounded-full bg-white px-5 py-2.5 text-[0.76rem] font-bold uppercase tracking-[0.08em] text-ink shadow-soft transition-shadow hover:shadow-card lg:inline-flex xl:px-6 xl:text-[0.8rem] ${
                  isActive ? "ring-2 ring-sky-900/20" : ""
                }`
              }
            >
              {primaryCta.label}
            </NavLink>

            <div ref={infoRef} className="relative ml-4 hidden shrink-0 lg:block xl:ml-5">
              <button
                type="button"
                onClick={() => setInfoOpen((open) => !open)}
                className={`inline-flex h-11 w-11 items-center justify-center rounded-full border transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-900 focus-visible:ring-offset-2 focus-visible:ring-offset-sky-300 ${
                  infoOpen
                    ? "border-sky-900/60 bg-sky-900/10 text-ink"
                    : "border-sky-900/35 bg-white/20 text-ink hover:bg-white/45"
                }`}
                aria-label={
                  infoOpen
                    ? "Close more information menu"
                    : "Open more information menu"
                }
                aria-expanded={infoOpen}
                aria-controls="desktop-info-menu"
              >
                {infoOpen ? <X className="h-5 w-5" /> : <Menu className="h-5 w-5" />}
              </button>
              <div
                id="desktop-info-menu"
                className={`absolute right-0 top-full z-50 mt-3 w-64 rounded-4xl border border-sky-200 bg-white p-2 shadow-card transition-opacity duration-150 ${
                  infoOpen ? "opacity-100" : "pointer-events-none opacity-0"
                }`}
              >
                {moreNavigation.map((link) => (
                  <NavLink
                    key={link.to}
                    to={link.to}
                    onClick={() => setInfoOpen(false)}
                    className={({ isActive }) =>
                      `block rounded-3xl px-4 py-3 text-sm font-semibold transition-colors ${
                        isActive
                          ? "bg-sky-900 text-white"
                          : "text-ink hover:bg-sky-50"
                      }`
                    }
                  >
                    {link.label}
                  </NavLink>
                ))}
              </div>
            </div>

            <button
              type="button"
              onClick={() => {
                setMobileMoreOpen(false);
                setMobileOpen((open) => !open);
              }}
              className="ml-auto inline-flex h-11 w-11 items-center justify-center rounded-full border border-sky-900/35 bg-white/20 text-ink transition-colors hover:bg-white/45 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-900 lg:hidden"
              aria-label={mobileOpen ? "Close menu" : "Open menu"}
              aria-expanded={mobileOpen}
              aria-controls="mobile-nav"
            >
              {mobileOpen ? <X className="h-7 w-7" /> : <Menu className="h-7 w-7" />}
            </button>
          </nav>
        </div>
      </header>

      <div
        id="mobile-nav"
        className={`fixed inset-x-0 bottom-0 top-[79px] z-40 overflow-y-auto bg-sky-300 transition-opacity duration-200 lg:hidden ${
          mobileOpen
            ? "pointer-events-auto opacity-100"
            : "pointer-events-none opacity-0"
        }`}
      >
        {mobileMoreOpen ? (
          <div className="container-wide pb-10 pt-20">
            <button
              type="button"
              onClick={() => setMobileMoreOpen(false)}
              className="mb-8 inline-flex items-center gap-2 font-body text-base font-bold text-ink"
            >
              <ArrowLeft className="h-5 w-5" aria-hidden />
              {navLabels.backLabel}
            </button>
            <p className="font-body text-sm font-bold uppercase tracking-[0.2em] text-ink">
              {navLabels.moreHeading}
            </p>
            <div className="mt-7 space-y-1 pl-5">
              {moreNavigation.map((link) => (
                <NavLink
                  key={link.to}
                  to={link.to}
                  onClick={closeMobile}
                  className="block border-b border-sky-900/15 py-3 font-body text-base font-semibold text-ink"
                >
                  {link.label}
                </NavLink>
              ))}
            </div>
          </div>
        ) : (
          <ul className="container-wide flex flex-col pb-10 pt-16 sm:pt-20">
            {navigation.map((item) => (
              <li key={item.to} className="border-b border-sky-900/20">
                <NavLink
                  to={item.to}
                  end={item.to === "/"}
                  onClick={closeMobile}
                  className="block py-3.5 font-body text-base font-bold text-ink"
                >
                  {item.label}
                </NavLink>
                {item.to === "/our-nursery" && item.children?.length ? (
                  <ul className="pb-3 pl-4">
                    {item.children.map((child) => (
                      <li key={child.to}>
                        <NavLink
                          to={child.to}
                          onClick={closeMobile}
                          className="block py-2 font-body text-sm font-semibold text-ink/75"
                        >
                          {child.label}
                        </NavLink>
                      </li>
                    ))}
                  </ul>
                ) : null}
              </li>
            ))}
            <li className="pt-6">
              <NavLink
                to={primaryCta.to}
                onClick={closeMobile}
                className="btn-white w-full rounded-full"
              >
                {primaryCta.label}
              </NavLink>
            </li>
            <li className="pt-6">
              <button
                type="button"
                onClick={() => setMobileMoreOpen(true)}
                className="border-b border-sky-900/50 pb-1 font-body text-base font-bold text-ink"
              >
                {navLabels.moreLabel}
              </button>
            </li>
          </ul>
        )}
      </div>
    </>
  );
}
