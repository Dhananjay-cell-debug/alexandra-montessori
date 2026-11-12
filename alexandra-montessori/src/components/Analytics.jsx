import { useEffect } from "react";
import { useLocation } from "react-router-dom";

const STORAGE_KEY = "am-cookie-consent:v2";
const CONSENT_EVENT = "am:cookie-consent-change";
const TAG_ID =
  typeof window !== "undefined" && window.amData
    ? window.amData.analyticsTagId || window.amData.ga4Id || ""
    : "";
const PIXEL_ID =
  typeof window !== "undefined" && window.amData
    ? window.amData.metaPixelId || ""
    : "";

let configuredTagId = "";
let configuredPixelId = "";
let lastPageView = "";
let lastPixelPageView = "";

function storedAnalyticsConsent() {
  if (typeof window === "undefined") return false;
  if (typeof window.amCookieConsent?.analytics === "boolean") {
    return window.amCookieConsent.analytics;
  }
  try {
    const saved = JSON.parse(window.localStorage.getItem(STORAGE_KEY) || "null");
    return saved?.version === 2 && saved.essential === true && saved.analytics === true;
  } catch {
    return false;
  }
}

function storedMarketingConsent() {
  if (typeof window === "undefined") return false;
  if (typeof window.amCookieConsent?.marketing === "boolean") {
    return window.amCookieConsent.marketing;
  }
  try {
    const saved = JSON.parse(window.localStorage.getItem(STORAGE_KEY) || "null");
    return saved?.version === 2 && saved.essential === true && saved.marketing === true;
  } catch {
    return false;
  }
}

function configureAnalytics() {
  if (!TAG_ID || configuredTagId === TAG_ID) return;

  window.dataLayer = window.dataLayer || [];
  window.gtag =
    window.gtag ||
    function gtag() {
      window.dataLayer.push(arguments);
    };
  window.gtag("consent", "default", {
    ad_storage: "denied",
    analytics_storage: "denied",
    functionality_storage: "denied",
    security_storage: "granted",
  });
  window.gtag("js", new Date());
  window.gtag("config", TAG_ID, { send_page_view: false });

  if (!document.querySelector("script[data-am-analytics]")) {
    const script = document.createElement("script");
    script.async = true;
    script.src = `https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(TAG_ID)}`;
    script.dataset.amAnalytics = "true";
    document.head.appendChild(script);
  }

  configuredTagId = TAG_ID;
}

function configureMetaPixel() {
  if (!PIXEL_ID || configuredPixelId === PIXEL_ID) return;

  (function (f, b, e, v, n, t, s) {
    if (f.fbq) return;
    n = f.fbq = function fbq() {
      n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments);
    };
    if (!f._fbq) f._fbq = n;
    n.push = n;
    n.loaded = true;
    n.version = "2.0";
    n.queue = [];
    t = b.createElement(e);
    t.async = true;
    t.src = v;
    t.dataset.amMetaPixel = "true";
    s = b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t, s);
  })(window, document, "script", "https://connect.facebook.net/en_US/fbevents.js");

  window.fbq("init", PIXEL_ID);
  configuredPixelId = PIXEL_ID;
}

/**
 * Fire a Meta Pixel "Lead" conversion event from a form's own submit handler
 * (ContactForm / BookingModal / Availability — the parent-facing enquiry
 * forms). Deliberately NOT wired into ApplicationForm: job candidates are a
 * different funnel, and mixing them into "Lead" would corrupt the signal
 * Meta uses to find more parents wanting childcare.
 * No-ops silently if marketing consent hasn't been granted or no pixel ID
 * is configured, same as the page_view tracking above.
 */
export function trackMetaLead() {
  if (typeof window === "undefined" || !PIXEL_ID) return;
  if (!storedMarketingConsent()) return;
  configureMetaPixel();
  if (typeof window.fbq === "function") {
    window.fbq("track", "Lead");
  }
}

export default function Analytics() {
  const location = useLocation();

  useEffect(() => {
    const pagePath = `${location.pathname}${location.search}`;
    const track = (consent) => {
      const choices = consent || window.amCookieConsent || {};
      const granted =
        typeof consent?.analytics === "boolean"
          ? consent.analytics
          : storedAnalyticsConsent();

      if (!granted) {
        if (typeof window.gtag === "function") {
          window.gtag("consent", "update", { analytics_storage: "denied" });
        }
        return;
      }

      configureAnalytics();
      window.gtag("consent", "update", {
        ad_storage: choices.marketing ? "granted" : "denied",
        analytics_storage: "granted",
        functionality_storage: choices.functional ? "granted" : "denied",
        security_storage: "granted",
      });
      if (lastPageView !== pagePath) {
        window.gtag("event", "page_view", {
          page_title: document.title,
          page_location: window.location.href,
          page_path: pagePath,
        });
        lastPageView = pagePath;
      }
    };

    const trackPixel = (consent) => {
      const granted =
        typeof consent?.marketing === "boolean"
          ? consent.marketing
          : storedMarketingConsent();
      if (!granted) return;

      configureMetaPixel();
      if (typeof window.fbq === "function" && lastPixelPageView !== pagePath) {
        window.fbq("track", "PageView");
        lastPixelPageView = pagePath;
      }
    };

    const handleConsent = (event) => {
      track(event.detail);
      trackPixel(event.detail);
    };
    track();
    trackPixel();
    window.addEventListener(CONSENT_EVENT, handleConsent);
    return () => window.removeEventListener(CONSENT_EVENT, handleConsent);
  }, [location.pathname, location.search]);

  return null;
}
