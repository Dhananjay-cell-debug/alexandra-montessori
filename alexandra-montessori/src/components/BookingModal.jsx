import { useEffect, useRef, useState } from "react";
import { X, CheckCircle2 } from "lucide-react";
import { createSubmissionKey, submitForm } from "../lib/api";
import PhoneField from "./PhoneField";
import { emailError, normalizeEmail } from "../lib/validation";
import { addDaysISO } from "../lib/dates";

// Booking popup opened from a nursery hero "Book a visit" button. The page
// already knows which nursery, so the request is routed to THAT nursery.
// Submissions are saved in WordPress (Enquiries) and emailed to the nursery
// inbox — no external scheduler dependency.
export default function BookingModal({ open, onClose, nursery }) {
  const [status, setStatus] = useState("idle");
  const [error, setError] = useState("");
  const [emailErr, setEmailErr] = useState("");
  const [reference, setReference] = useState("");
  const phoneRef = useRef(null);
  const loadedAt = useRef(0);
  const submissionKey = useRef(createSubmissionKey());
  // Earliest selectable visit date: at least two days from today (T+2).
  const minVisitDate = addDaysISO(2);

  // Lock background scroll + close on Escape while open.
  useEffect(() => {
    if (!open) return;
    loadedAt.current = Date.now();
    document.body.style.overflow = "hidden";
    const onKey = (e) => e.key === "Escape" && onClose();
    window.addEventListener("keydown", onKey);
    return () => {
      document.body.style.overflow = "";
      window.removeEventListener("keydown", onKey);
    };
  }, [open, onClose]);

  if (!open) return null;

  const onSubmit = async (e) => {
    e.preventDefault();
    if (status === "sending") return;
    const data = new FormData(e.currentTarget);

    const eErr = emailError(data.get("email"), { required: true });
    const phoneOk = phoneRef.current ? phoneRef.current.validate() : true;
    if (eErr || !phoneOk) {
      setEmailErr(eErr);
      return;
    }

    setStatus("sending");
    setError("");
    setEmailErr("");
    try {
      const result = await submitForm("enquiry", {
        kind: "booking",
        firstName: data.get("firstName") || "",
        lastName: data.get("lastName") || "",
        email: normalizeEmail(data.get("email")),
        phone: data.get("phone") || "",
        branch: nursery?.id || "",
        preferredDate: data.get("preferredDate") || "",
        message: data.get("message") || "",
        website: data.get("website") || "", // honeypot
        formLoadedAt: loadedAt.current,
      }, submissionKey.current);
      setReference(result.reference || "");
      setStatus("sent");
    } catch (err) {
      setError(err.message);
      setStatus("idle");
    }
  };

  const field =
    "w-full rounded border border-sage-300 bg-white px-4 py-3 text-sm text-ink outline-none transition-colors placeholder:text-ink/60 focus:border-sage-600";
  const label = "mb-1.5 block text-sm font-medium text-sage-800";

  return (
    <div
      className="fixed inset-0 z-[100] flex items-center justify-center p-4"
      role="dialog"
      aria-modal="true"
      aria-label={`Book a visit to ${nursery.name}`}
    >
      <div className="absolute inset-0 bg-black/50" onClick={onClose} />

      <div className="relative z-10 flex max-h-[92vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-white shadow-xl">
        {/* Header */}
        <div className="flex items-center justify-between border-b border-sage-100 px-5 py-4">
          <h3 className="font-heading text-lg font-medium text-sage-800">
            Book a visit to {nursery.name}
          </h3>
          <button
            type="button"
            onClick={onClose}
            aria-label="Close"
            className="rounded-full p-2 text-ink/85 transition-colors hover:bg-sage-50 hover:text-sage-800"
          >
            <X className="h-5 w-5" />
          </button>
        </div>

        <div className="flex-1 overflow-auto bg-white p-6">
          {status === "sent" ? (
            <div className="py-6 text-center">
              <CheckCircle2 className="mx-auto h-12 w-12 text-sage-700" />
              <h4 className="mt-4 font-heading text-2xl font-medium text-sage-800">
                Visit request sent
              </h4>
              <p className="mx-auto mt-3 max-w-sm text-sm leading-relaxed text-ink/85">
                Thank you. The {nursery.name} team will be in touch to confirm a
                time for your visit.
              </p>
              {reference && (
                <p className="mt-3 text-sm font-semibold text-sage-800">
                  Reference: {reference}
                </p>
              )}
              <button
                type="button"
                onClick={onClose}
                className="btn-primary mt-6"
              >
                Done
              </button>
            </div>
          ) : (
            <form onSubmit={onSubmit} className="space-y-4">
              <p className="text-sm leading-relaxed text-ink/85">
                Tell us a little about your family and a preferred date, and the{" "}
                {nursery.name} team will arrange your visit.
              </p>
              {/* Honeypot */}
              <input
                type="text"
                name="website"
                tabIndex={-1}
                autoComplete="off"
                aria-hidden="true"
                className="hidden"
              />
              <div className="grid gap-4 sm:grid-cols-2">
                <label className="block">
                  <span className={label}>First name *</span>
                  <input
                    required
                    name="firstName"
                    autoComplete="given-name"
                    className={field}
                  />
                </label>
                <label className="block">
                  <span className={label}>Last name</span>
                  <input
                    name="lastName"
                    autoComplete="family-name"
                    className={field}
                  />
                </label>
                <label className="block">
                  <span className={label}>Email *</span>
                  <input
                    required
                    type="email"
                    name="email"
                    autoComplete="email"
                    className={field}
                    aria-invalid={emailErr ? true : undefined}
                    onChange={() => emailErr && setEmailErr("")}
                    onBlur={(e) => setEmailErr(emailError(e.target.value))}
                  />
                  {emailErr && (
                    <p
                      role="alert"
                      className="mt-1.5 text-xs font-medium text-red-700"
                    >
                      {emailErr}
                    </p>
                  )}
                </label>
                <PhoneField
                  ref={phoneRef}
                  name="phone"
                  id="booking-phone"
                  label="Phone"
                  required
                  labelClassName={label}
                  fieldClassName={field}
                />
              </div>
              <label className="block">
                <span className={label}>Preferred visit date</span>
                <input
                  type="date"
                  name="preferredDate"
                  min={minVisitDate}
                  className={field}
                />
                <span className="mt-1.5 block text-xs leading-relaxed text-ink/70">
                  The earliest visit we can offer is two days from today.
                </span>
              </label>
              <label className="block">
                <span className={label}>Anything else?</span>
                <textarea
                  rows={3}
                  name="message"
                  className={`${field} resize-none`}
                  placeholder="Your child's age, questions, best times to visit…"
                />
              </label>
              {error && (
                <p role="alert" className="text-sm font-medium text-red-700">
                  {error}
                </p>
              )}
              <button
                type="submit"
                disabled={status === "sending"}
                className="btn-primary w-full"
              >
                {status === "sending" ? "Sending…" : "Request a visit"}
              </button>
            </form>
          )}
        </div>
      </div>
    </div>
  );
}
