import { useEffect } from "react";
import { X } from "lucide-react";
import { calendlyUrl } from "../lib/calendly";

// Popup that opens Calendly for a specific nursery from the hero
// "Book a visit" button. Embedded as an iframe so no external script is
// needed. The email-based BookingModal is intentionally kept in the codebase -
// see src/lib/calendly.js for the temporary-vs-final plan.
export default function CalendlyModal({ open, onClose, nursery, region }) {
  // Lock background scroll + close on Escape while open.
  useEffect(() => {
    if (!open) return;
    document.body.style.overflow = "hidden";
    const onKey = (e) => e.key === "Escape" && onClose();
    window.addEventListener("keydown", onKey);
    return () => {
      document.body.style.overflow = "";
      window.removeEventListener("keydown", onKey);
    };
  }, [open, onClose]);

  if (!open) return null;

  return (
    <div
      className="fixed inset-0 z-[100] flex items-center justify-center bg-ink/60 p-4"
      role="dialog"
      aria-modal="true"
      aria-label={`Book a visit to ${nursery?.name || "our nursery"}`}
      data-am-vb-region={region}
      onClick={onClose}
    >
      <div
        className="relative flex h-[90vh] w-full max-w-3xl flex-col overflow-hidden rounded-3xl bg-white shadow-2xl"
        onClick={(e) => e.stopPropagation()}
      >
        <div className="flex items-center justify-between border-b border-sage-200 px-5 py-4">
          <h2 className="font-heading text-xl font-medium text-sage-800">
            Book a visit to {nursery?.name}
          </h2>
          <button
            type="button"
            onClick={onClose}
            aria-label="Close"
            className="rounded-full p-2 text-sage-700 transition-colors hover:bg-sage-100"
          >
            <X className="h-5 w-5" />
          </button>
        </div>
        <iframe
          src={calendlyUrl(nursery)}
          title={`Calendly booking for ${nursery?.name}`}
          className="h-full w-full flex-1 border-0"
          loading="lazy"
        />
      </div>
    </div>
  );
}
