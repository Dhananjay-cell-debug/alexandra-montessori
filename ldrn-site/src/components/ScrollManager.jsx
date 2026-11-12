import { useEffect } from "react";
import { useLocation } from "react-router-dom";

/**
 * Restores the expected scroll position on navigation: top of the page for a
 * normal route change, and the named element for an in-page anchor. Without
 * this a client-side route change leaves the visitor halfway down the new
 * page.
 */
export default function ScrollManager() {
  const { pathname, hash } = useLocation();

  useEffect(() => {
    if (!hash) {
      window.scrollTo({ top: 0 });
      return;
    }
    const id = hash.replace("#", "");
    // Wait a frame so the target exists after the route has rendered.
    requestAnimationFrame(() => {
      const target = document.getElementById(id);
      if (target) {
        target.scrollIntoView({ behavior: "smooth", block: "start" });
      } else {
        window.scrollTo({ top: 0 });
      }
    });
  }, [pathname, hash]);

  return null;
}
