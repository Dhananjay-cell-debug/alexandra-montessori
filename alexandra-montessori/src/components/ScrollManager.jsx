import { useEffect } from "react";
import { useLocation } from "react-router-dom";

// Scrolls to top on route change, or to the #hash target if present.
export default function ScrollManager() {
  const { pathname, hash } = useLocation();

  useEffect(() => {
    if (hash) {
      // A redirected anchor can arrive before its lazy route has rendered.
      const id = decodeURIComponent(hash.slice(1));
      let observer;
      const scrollToTarget = () => {
        const el = document.getElementById(id);
        if (el) {
          el.scrollIntoView({ behavior: window.matchMedia("(prefers-reduced-motion: reduce)").matches ? "auto" : "smooth", block: "start" });
          observer?.disconnect();
          return true;
        }
        return false;
      };
      const frame = requestAnimationFrame(() => {
        if (scrollToTarget()) return;
        observer = new MutationObserver(scrollToTarget);
        observer.observe(document.getElementById("main") || document.body, { childList: true, subtree: true });
      });
      const timeout = window.setTimeout(() => observer?.disconnect(), 5000);
      return () => {
        cancelAnimationFrame(frame);
        observer?.disconnect();
        window.clearTimeout(timeout);
      };
    } else {
      window.scrollTo({ top: 0 });
    }
  }, [pathname, hash]);

  return null;
}
