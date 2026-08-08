import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { X } from "lucide-react";
import {
  editableFrameProps,
  editableTextProps,
  homeHref,
  homeText,
} from "../lib/homeVisual";

const STORAGE_KEY = "am-cookie-consent:v2";
const LEGACY_KEY = "am-cookie-consent";
const OPEN_SETTINGS_EVENT = "am:open-cookie-settings";

const EMPTY_CHOICES = {
  functional: false,
  analytics: false,
  marketing: false,
};

const FULL_CHOICES = {
  functional: true,
  analytics: true,
  marketing: true,
};

const COOKIE_OPTIONS = [
  {
    key: "essential",
    title: "Essential cookies",
    text: "Required for security, page navigation, forms and core website functionality. These cannot be disabled.",
    locked: true,
  },
  {
    key: "functional",
    title: "Functional cookies",
    text: "Remember site preferences and choices so the experience stays consistent when you return.",
  },
  {
    key: "analytics",
    title: "Analytics cookies",
    text: "Help us understand which pages are useful and where the website needs improvement. Analytics run only after consent.",
  },
  {
    key: "marketing",
    title: "Marketing cookies",
    text: "Allow campaign measurement and relevant advertising tools if the nursery adds them in future.",
  },
];

function inVisualBuilderEditMode() {
  if (typeof window === "undefined") return false;
  try {
    return (
      window.self !== window.top &&
      Boolean(window.frameElement) &&
      new URLSearchParams(window.location.search).get("am_visual_mode") ===
        "edit"
    );
  } catch {
    return false;
  }
}

function readStoredConsent() {
  if (typeof window === "undefined") return null;
  try {
    const raw = window.localStorage.getItem(STORAGE_KEY);
    if (!raw) return null;
    const parsed = JSON.parse(raw);
    if (parsed?.version !== 2 || parsed.essential !== true) return null;
    return {
      essential: true,
      functional: Boolean(parsed.functional),
      analytics: Boolean(parsed.analytics),
      marketing: Boolean(parsed.marketing),
      savedAt: typeof parsed.savedAt === "string" ? parsed.savedAt : "",
    };
  } catch {
    return null;
  }
}

function applyConsent(consent) {
  if (typeof window === "undefined") return;

  window.amCookieConsent = {
    essential: true,
    functional: Boolean(consent.functional),
    analytics: Boolean(consent.analytics),
    marketing: Boolean(consent.marketing),
  };

  document.documentElement.dataset.cookieConsent = "saved";
  document.documentElement.dataset.cookieFunctional = window.amCookieConsent
    .functional
    ? "granted"
    : "denied";
  document.documentElement.dataset.cookieAnalytics = window.amCookieConsent
    .analytics
    ? "granted"
    : "denied";
  document.documentElement.dataset.cookieMarketing = window.amCookieConsent
    .marketing
    ? "granted"
    : "denied";

  if (typeof window.gtag === "function") {
    window.gtag("consent", "update", {
      ad_storage: window.amCookieConsent.marketing ? "granted" : "denied",
      analytics_storage: window.amCookieConsent.analytics
        ? "granted"
        : "denied",
      functionality_storage: window.amCookieConsent.functional
        ? "granted"
        : "denied",
      security_storage: "granted",
    });
  }

  window.dispatchEvent(
    new CustomEvent("am:cookie-consent-change", {
      detail: window.amCookieConsent,
    }),
  );
}

function saveConsent(choices) {
  const consent = {
    version: 2,
    essential: true,
    functional: Boolean(choices.functional),
    analytics: Boolean(choices.analytics),
    marketing: Boolean(choices.marketing),
    savedAt: new Date().toISOString(),
  };

  try {
    window.localStorage.setItem(STORAGE_KEY, JSON.stringify(consent));
    window.localStorage.removeItem(LEGACY_KEY);
  } catch {
    // Consent still applies for the current page if localStorage is unavailable.
  }

  applyConsent(consent);
  return consent;
}

function Toggle({ checked, disabled, onClick }) {
  return (
    <button
      type="button"
      role="switch"
      aria-checked={checked}
      disabled={disabled}
      onClick={onClick}
      className={`relative inline-flex h-7 w-12 shrink-0 items-center rounded-full border transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-sage-600 focus-visible:ring-offset-2 ${
        checked ? "border-sage-500 bg-sage-500" : "border-sage-200 bg-sage-100"
      } ${disabled ? "cursor-not-allowed opacity-70" : "cursor-pointer"}`}
    >
      <span
        className={`inline-block h-5 w-5 rounded-full bg-white shadow-sm transition-transform ${
          checked ? "translate-x-5" : "translate-x-1"
        }`}
      />
    </button>
  );
}

export default function CookieBar() {
  const [bannerOpen, setBannerOpen] = useState(
    () => inVisualBuilderEditMode() || readStoredConsent() === null,
  );
  const [settingsOpen, setSettingsOpen] = useState(false);
  const [choices, setChoices] = useState(() => {
    const stored = readStoredConsent();
    return stored
      ? {
          functional: stored.functional,
          analytics: stored.analytics,
          marketing: stored.marketing,
        }
      : EMPTY_CHOICES;
  });

  useEffect(() => {
    const stored = readStoredConsent();
    if (stored) applyConsent(stored);

    const openSettings = () => {
      const latest = readStoredConsent();
      setChoices(
        latest
          ? {
              functional: latest.functional,
              analytics: latest.analytics,
              marketing: latest.marketing,
            }
          : EMPTY_CHOICES,
      );
      setSettingsOpen(true);
    };

    window.addEventListener(OPEN_SETTINGS_EVENT, openSettings);
    return () => window.removeEventListener(OPEN_SETTINGS_EVENT, openSettings);
  }, []);

  useEffect(() => {
    if (!settingsOpen) return undefined;
    const handleKeyDown = (event) => {
      if (event.key === "Escape") setSettingsOpen(false);
    };
    window.addEventListener("keydown", handleKeyDown);
    return () => window.removeEventListener("keydown", handleKeyDown);
  }, [settingsOpen]);

  const openSettings = () => setSettingsOpen(true);

  const saveAndClose = (nextChoices) => {
    const nextConsent = saveConsent(nextChoices);
    setChoices({
      functional: nextConsent.functional,
      analytics: nextConsent.analytics,
      marketing: nextConsent.marketing,
    });
    setBannerOpen(false);
    setSettingsOpen(false);
  };

  const toggleChoice = (key) => {
    setChoices((current) => ({ ...current, [key]: !current[key] }));
  };

  return (
    <>
      {bannerOpen && (
        <div
          {...editableFrameProps("cookie-banner-frame", "Cookie banner")}
          className="fixed inset-x-0 bottom-0 z-[60] border-t border-sage-100 bg-white shadow-[0_-4px_18px_-12px_rgba(29,58,42,0.4)]"
          data-am-vb-region="cookie-control"
        >
          <div className="container-wide flex flex-col items-center gap-2 py-2 text-xs leading-relaxed text-ink sm:flex-row sm:justify-between sm:gap-3 sm:py-3 sm:text-sm">
            <p className="max-w-3xl text-center sm:text-left">
              <span {...editableTextProps("cookie-message", "Cookie message", true, "We use essential cookies to run this website and optional cookies to improve how it works. You can accept, reject or manage your choices.")}>
                {homeText("cookie-message", "We use essential cookies to run this website and optional cookies to improve how it works. You can accept, reject or manage your choices.")}{" "}
              </span>
              <Link
                {...editableTextProps("cookie-privacy", "Cookie privacy link", false, "Privacy Policy")}
                to={homeHref("cookie-privacy", "/privacy")}
                className="font-medium text-sage-700 underline underline-offset-2"
              >
                {homeText("cookie-privacy", "Privacy Policy")}
              </Link>
            </p>
            <div className="flex shrink-0 flex-wrap items-center justify-center gap-2 sm:flex-nowrap">
              <button
                {...editableTextProps("cookie-settings", "Cookie settings button", false, "Settings")}
                type="button"
                onClick={openSettings}
                className="rounded border border-sage-300 px-3 py-1.5 text-xs font-medium text-sage-700 transition-colors hover:bg-sage-50 sm:px-4 sm:py-2 sm:text-sm"
              >
                {homeText("cookie-settings", "Settings")}
              </button>
              <button
                {...editableTextProps("cookie-reject", "Cookie reject button", false, "Reject optional")}
                type="button"
                onClick={() => saveAndClose(EMPTY_CHOICES)}
                className="rounded border border-sage-300 px-3 py-1.5 text-xs font-medium text-sage-700 transition-colors hover:bg-sage-50 sm:px-4 sm:py-2 sm:text-sm"
              >
                {homeText("cookie-reject", "Reject optional")}
              </button>
              <button
                {...editableTextProps("cookie-accept", "Cookie accept button", false, "Accept all")}
                type="button"
                onClick={() => saveAndClose(FULL_CHOICES)}
                className="rounded bg-sage-500 px-4 py-1.5 text-xs font-medium text-white transition-colors hover:bg-sage-400 sm:px-5 sm:py-2 sm:text-sm"
              >
                {homeText("cookie-accept", "Accept all")}
              </button>
              <button
                type="button"
                onClick={() => saveAndClose(EMPTY_CHOICES)}
                aria-label="Close cookie notice and use essential cookies only"
                className="text-ink/85 transition-colors hover:text-ink"
              >
                <X className="h-5 w-5" />
              </button>
            </div>
          </div>
        </div>
      )}

      {settingsOpen && (
        <div
          className="fixed inset-0 z-[80] flex items-end justify-center bg-ink/60 px-4 py-6 sm:items-center"
          data-am-vb-region="cookie-control"
        >
          <div
            role="dialog"
            aria-modal="true"
            aria-labelledby="cookie-settings-title"
            className="max-h-[88vh] w-full max-w-2xl overflow-hidden rounded bg-white shadow-2xl"
          >
            <div className="flex items-start justify-between gap-4 border-b border-sage-100 px-5 py-4 sm:px-6">
              <div>
                <h2
                  id="cookie-settings-title"
                  className="font-heading text-2xl font-medium text-ink"
                >
                  Cookie settings
                </h2>
                <p className="mt-1 text-sm leading-relaxed text-ink/85">
                  Manage optional cookies for this website. Essential cookies
                  remain active because the site cannot work correctly without
                  them.
                </p>
              </div>
              <button
                type="button"
                onClick={() => setSettingsOpen(false)}
                aria-label="Close cookie settings"
                className="rounded p-1 text-ink/85 transition-colors hover:bg-sage-50 hover:text-ink"
              >
                <X className="h-5 w-5" />
              </button>
            </div>

            <div className="max-h-[52vh] divide-y divide-sage-100 overflow-y-auto px-5 sm:px-6">
              {COOKIE_OPTIONS.map((option) => {
                const checked = option.locked ? true : choices[option.key];
                return (
                  <div
                    key={option.key}
                    className="flex items-start justify-between gap-5 py-5"
                  >
                    <div>
                      <h3 className="font-body text-base font-semibold text-ink">
                        {option.title}
                      </h3>
                      <p className="mt-1 text-sm leading-relaxed text-ink/85">
                        {option.text}
                      </p>
                    </div>
                    <Toggle
                      checked={checked}
                      disabled={option.locked}
                      onClick={
                        option.locked
                          ? undefined
                          : () => toggleChoice(option.key)
                      }
                    />
                  </div>
                );
              })}
            </div>

            <div className="flex flex-col-reverse gap-2 border-t border-sage-100 bg-sage-50/70 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
              <button
                type="button"
                onClick={() => saveAndClose(EMPTY_CHOICES)}
                className="rounded border border-sage-300 bg-white px-4 py-2 text-sm font-medium text-sage-800 transition-colors hover:bg-sage-50"
              >
                Reject optional
              </button>
              <button
                type="button"
                onClick={() => saveAndClose(choices)}
                className="rounded border border-sage-700 bg-white px-4 py-2 text-sm font-semibold text-sage-800 transition-colors hover:bg-sage-100"
              >
                Save choices
              </button>
              <button
                type="button"
                onClick={() => saveAndClose(FULL_CHOICES)}
                className="rounded bg-sage-500 px-5 py-2 text-sm font-semibold text-white transition-colors hover:bg-sage-400"
              >
                Accept all
              </button>
            </div>
          </div>
        </div>
      )}
    </>
  );
}
