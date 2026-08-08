import { useEffect } from "react";
import { useLocation } from "react-router-dom";

const STORAGE_KEY = "am-cookie-consent:v2";
const CONSENT_EVENT = "am:cookie-consent-change";
const TAG_ID =
  typeof window !== "undefined" && window.amData
    ? window.amData.analyticsTagId || window.amData.ga4Id || ""
    : "";

let configuredTagId = "";
let lastPageView = "";

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
      if (lastPageView === pagePath) return;
      window.gtag("event", "page_view", {
        page_title: document.title,
        page_location: window.location.href,
        page_path: pagePath,
      });
      lastPageView = pagePath;
    };

    const handleConsent = (event) => track(event.detail);
    track();
    window.addEventListener(CONSENT_EVENT, handleConsent);
    return () => window.removeEventListener(CONSENT_EVENT, handleConsent);
  }, [location.pathname, location.search]);

  return null;
}
