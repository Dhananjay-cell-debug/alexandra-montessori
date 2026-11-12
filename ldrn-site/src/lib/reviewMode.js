import { useSyncExternalStore } from "react";

/**
 * Review mode surfaces every unconfirmed `tbc()` value as an inline badge so
 * the client can walk the site and see exactly what is still outstanding. It
 * is off for the public and is enabled per-browser with:
 *
 *   localStorage.setItem("ldrn-review-mode", "true")
 */
const STORAGE_KEY = "ldrn-review-mode";
const listeners = new Set();

const enabled =
  typeof window !== "undefined" &&
  localStorage.getItem(STORAGE_KEY) === "true";

function sync() {
  document.documentElement.toggleAttribute("data-ldrn-review", enabled);
  listeners.forEach((listener) => listener());
}

if (typeof window !== "undefined") sync();

export function useReviewMode() {
  return useSyncExternalStore(
    (listener) => {
      listeners.add(listener);
      return () => listeners.delete(listener);
    },
    () => enabled,
    () => false,
  );
}
