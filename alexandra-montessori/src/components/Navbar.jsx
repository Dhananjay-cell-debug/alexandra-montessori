import { useEffect, useRef, useState } from "react";
import { NavLink } from "react-router-dom";
import { ChevronLeft, Menu, X } from "lucide-react";
import Logo from "./Logo";
import { nav, parentInfoLinks, locations } from "../data/site";
import { editableTextProps, homeText } from "../lib/homeVisual";

export default function Navbar() {
  const [mobileOpen, setMobileOpen] = useState(false);
  const [infoOpen, setInfoOpen] = useState(false);
  const [nurseriesOpen, setNurseriesOpen] = useState(false);
  const [mobileParentInfoOpen, setMobileParentInfoOpen] = useState(false);
  const menuRef = useRef(null);

  useEffect(() => {
    document.body.style.overflow = mobileOpen ? "hidden" : "";
    return () => {
      document.body.style.overflow = "";
    };
  }, [mobileOpen]);

  useEffect(() => {
    const onPointerDown = (event) => {
      if (menuRef.current && !menuRef.current.contains(event.target)) {
        setInfoOpen(false);
      }
    };

    document.addEventListener("mousedown", onPointerDown);
    return () => document.removeEventListener("mousedown", onPointerDown);
  }, []);

  const navLink = ({ isActive }) =>
    `whitespace-nowrap font-body text-[0.8rem] font-semibold tracking-wide transition-colors xl:text-[0.9rem] ${
      isActive
        ? "text-white underline decoration-2 underline-offset-[6px]"
        : "text-white/88 hover:text-white"
    }`;

  const availabilityLink = ({ isActive }) =>
    `ml-5 hidden shrink-0 whitespace-nowrap rounded-full px-5 py-2.5 text-[0.78rem] font-bold uppercase tracking-wide text-sage-800 shadow-soft transition-colors lg:inline-flex xl:ml-7 xl:px-6 xl:text-[0.82rem] ${
      isActive
        ? "bg-sage-100 shadow-card ring-1 ring-sage-800/20"
        : "bg-white hover:shadow-card"
    }`;

  const closeMobileMenu = () => {
    setMobileOpen(false);
    setMobileParentInfoOpen(false);
  };

  const toggleMobileMenu = () => {
    if (mobileOpen) {
      closeMobileMenu();
      return;
    }

    setMobileParentInfoOpen(false);
    setMobileOpen(true);
  };

  return (
    <header
      className="relative z-50 bg-[#a3bc9a] text-white shadow-nav"
      data-am-vb-region="global-header"
    >
      <div className="w-full px-5 sm:px-8 lg:px-10 xl:px-12">
        <nav className="relative flex h-[4.75rem] items-center py-2 lg:h-[5rem]">
          {/* Logo detached from navbar height, absolutely positioned to hang out */}
          <div className="absolute top-1 z-10 flex items-center lg:top-2">
            <Logo
              variant="light"
              size="nav"
              showText={false}
              className="drop-shadow-md"
              editableKey="header-logo"
            />
          </div>

          {/* Spacer to keep flex items centered relative to the remaining space */}
          <div className="w-[7.5rem] shrink-0 sm:w-[8.75rem] lg:w-[10.5rem]"></div>

          <ul className="hidden flex-1 items-center justify-center gap-5 pl-4 pr-4 lg:flex xl:gap-8 xl:pl-6 xl:pr-5">
            {nav.map((item) =>
              item.to === "/nurseries" && locations.length > 0 ? (
                <li
                  key={item.label}
                  className="relative"
                  onMouseEnter={() => setNurseriesOpen(true)}
                  onMouseLeave={() => setNurseriesOpen(false)}
                >
                  <NavLink to={item.to} className={navLink}>
                    <span {...editableTextProps(`header-nav-${item.to === "/" ? "home" : item.to.slice(1)}`, `${item.label} navigation label`, false, item.label)}>
                      {homeText(`header-nav-${item.to === "/" ? "home" : item.to.slice(1)}`, item.label)}
                    </span>
                  </NavLink>
                  {nurseriesOpen && (
                    <div className="absolute left-1/2 top-full z-50 w-56 -translate-x-1/2 pt-3">
                      <div className="rounded-3xl border border-sage-200 bg-white p-2 shadow-card">
                        {locations.map((loc) => (
                          <NavLink
                            key={loc.id}
                            to={`/nurseries/${loc.id}`}
                            onClick={() => setNurseriesOpen(false)}
                            className={({ isActive }) =>
                              `block rounded-2xl px-4 py-2.5 text-sm font-semibold transition-colors ${
                                isActive
                                  ? "bg-sage-500 text-white"
                                  : "text-sage-700 hover:bg-sage-50 hover:text-sage-700"
                              }`
                            }
                          >
                            {loc.name}
                          </NavLink>
                        ))}
                      </div>
                    </div>
                  )}
                </li>
              ) : (
                <li key={item.label}>
                  <NavLink
                    to={item.to}
                    end={item.to === "/"}
                    className={navLink}
                  >
                    <span {...editableTextProps(`header-nav-${item.to === "/" ? "home" : item.to.slice(1)}`, `${item.label} navigation label`, false, item.label)}>
                      {homeText(`header-nav-${item.to === "/" ? "home" : item.to.slice(1)}`, item.label)}
                    </span>
                  </NavLink>
                </li>
              ),
            )}
          </ul>

          <NavLink to="/check-availability" className={availabilityLink}>
            <span {...editableTextProps("header-availability", "Availability button", false, "Check availability")}>{homeText("header-availability", "Check availability")}</span>
          </NavLink>

          {/* Desktop hamburger icon for Parent Info dropdown */}
          <div
            ref={menuRef}
            className="hidden shrink-0 lg:ml-4 lg:block xl:ml-5"
          >
            <button
              type="button"
              data-am-vb-allow-action="true"
              onClick={() => setInfoOpen((value) => !value)}
              className={`inline-flex h-11 w-11 items-center justify-center rounded-full border transition-colors ${
                infoOpen
                  ? "border-white/80 bg-white/20 text-white shadow-soft"
                  : "border-white/40 bg-white/10 text-white/92 hover:bg-white/18 hover:text-white"
              }`}
              aria-label={infoOpen ? "Close parent information menu" : "Open parent information menu"}
              aria-expanded={infoOpen}
            >
              {infoOpen ? (
                <X className="h-5 w-5" />
              ) : (
                <Menu className="h-5 w-5" />
              )}
            </button>

            {infoOpen && (
              <div className="absolute right-0 top-full z-50 mt-3 w-64 rounded-4xl border border-sage-200 bg-white p-2 shadow-card">
                {parentInfoLinks.map((item) => (
                  <NavLink
                    key={item.label}
                    to={item.to}
                    onClick={() => setInfoOpen(false)}
                    className={({ isActive }) =>
                      `block rounded-3xl px-4 py-3 text-sm font-semibold transition-colors ${
                        isActive
                          ? "bg-sage-500 text-white"
                          : "text-sage-700 hover:bg-sage-50 hover:text-sage-700"
                      }`
                    }
                  >
                    <span {...editableTextProps(`header-nav-${item.to === "/" ? "home" : item.to.slice(1)}`, `${item.label} navigation label`, false, item.label)}>
                      {homeText(`header-nav-${item.to === "/" ? "home" : item.to.slice(1)}`, item.label)}
                    </span>
                  </NavLink>
                ))}
              </div>
            )}
          </div>

          <button
            type="button"
            data-am-vb-allow-action="true"
            onClick={toggleMobileMenu}
            className="ml-auto inline-flex h-11 w-11 items-center justify-center text-white transition-opacity hover:opacity-80 lg:hidden"
            aria-label={mobileOpen ? "Close menu" : "Open menu"}
            aria-expanded={mobileOpen}
          >
            {mobileOpen ? <X className="h-8 w-8" /> : <Menu className="h-8 w-8" />}
          </button>
        </nav>
      </div>

      {mobileOpen && (
        <div className="border-t border-white/45 bg-[#a3bc9a] lg:hidden">
          {!mobileParentInfoOpen ? (
            <ul className="container-wide flex flex-col pb-6 pt-16 sm:pt-20">
              {nav.map((item) => (
                <li
                  key={item.label}
                  className="border-b border-white/45"
                >
                  <NavLink
                    to={item.to}
                    end={item.to === "/"}
                    onClick={closeMobileMenu}
                    className="block py-3.5 font-body text-base font-semibold text-white"
                  >
                    {item.label}
                  </NavLink>
                  {item.to === "/nurseries" && locations.length > 0 && (
                    <ul className="pb-2">
                      {locations.map((loc) => (
                        <li key={loc.id}>
                          <NavLink
                            to={`/nurseries/${loc.id}`}
                            onClick={closeMobileMenu}
                            className="block py-2.5 font-body text-sm font-medium text-white/85"
                          >
                            {loc.name}
                          </NavLink>
                        </li>
                      ))}
                    </ul>
                  )}
                </li>
              ))}
              <li className="border-b border-white/45">
                <NavLink
                  to="/check-availability"
                  onClick={closeMobileMenu}
                  className="block py-3.5 font-body text-base font-semibold text-white"
                >
                  <span {...editableTextProps("header-availability", "Availability button", false, "Check availability")}>{homeText("header-availability", "Check availability")}</span>
                </NavLink>
              </li>
              <li className="pt-5">
                <button
                  type="button"
                  data-am-vb-allow-action="true"
                  onClick={() => setMobileParentInfoOpen(true)}
                  className="inline-flex border-b border-white/75 pb-1 font-body text-base font-semibold text-white transition-opacity hover:opacity-85"
                  aria-label="View more parent information links"
                >
                  View more
                </button>
              </li>
            </ul>
          ) : (
            <div className="container-wide pb-8 pt-20 sm:pt-24">
              <button
                type="button"
                data-am-vb-allow-action="true"
                onClick={() => setMobileParentInfoOpen(false)}
                className="mb-8 inline-flex items-center gap-2 font-body text-base font-semibold text-white transition-opacity hover:opacity-85"
              >
                <ChevronLeft className="h-5 w-5" />
                <span>Back</span>
              </button>

              <p className="font-body text-sm font-semibold uppercase tracking-[0.24em] text-white">
                Parent Info
              </p>

              <div className="mt-8 space-y-6 pl-8">
                {parentInfoLinks.map((item) => (
                  <NavLink
                    key={item.label}
                    to={item.to}
                    onClick={closeMobileMenu}
                    className={({ isActive }) =>
                      `block font-body text-base font-semibold ${
                        isActive ? "text-white" : "text-white hover:text-white/85"
                      }`
                    }
                  >
                    {item.label}
                  </NavLink>
                ))}
              </div>
            </div>
          )}
        </div>
      )}
    </header>
  );
}
