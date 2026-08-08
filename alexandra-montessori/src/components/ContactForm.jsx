import { useEffect, useRef, useState } from "react";
import { Link } from "react-router-dom";
import { brand, locations } from "../data/site";
import { createSubmissionKey, submitForm } from "../lib/api";
import { gmailHref } from "../lib/contact";
import PhoneField from "./PhoneField";
import { emailError, normalizeEmail } from "../lib/validation";

const label = "mb-1.5 block font-body text-sm font-medium text-sage-800";
const field =
  "w-full rounded border border-sage-300 bg-white px-4 py-3 text-sm text-ink outline-none transition-colors placeholder:text-ink/85 focus:border-sage-600";

// Shared, accessible enquiry form, MRN-styled (labelled bordered fields, no card
// panel). `defaultBranch` pre-selects a nursery on the per-location contact pages.
export default function ContactForm({ defaultBranch = "", defaultMessage = "" }) {
  const [status, setStatus] = useState("idle");
  const [error, setError] = useState("");
  const [emailErr, setEmailErr] = useState("");
  const [reference, setReference] = useState("");
  const [branch, setBranch] = useState(defaultBranch);
  const phoneRef = useRef(null);
  const loadedAt = useRef(0);
  const submissionKey = useRef(createSubmissionKey());
  useEffect(() => {
    loadedAt.current = Date.now();
  }, []);
  const selectedLocation = locations.find((location) => location.id === branch);

  const onSubmit = async (e) => {
    e.preventDefault();
    if (status === "sending") return;
    const form = e.currentTarget;
    const data = new FormData(form);

    // Client-side validation: email format + (optional) phone.
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
        firstName: data.get("firstName") || "",
        lastName: data.get("lastName") || "",
        email: normalizeEmail(data.get("email")),
        phone: data.get("phone") || "",
        branch: data.get("branch") || "",
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

  if (status === "sent") {
    return (
      <div className="border border-sage-200 bg-white p-10 text-center">
        <h3 className="font-heading text-2xl font-medium text-sage-800">
          Message sent
        </h3>
        <p className="mt-3 text-sm text-ink/85">
          Thank you for getting in touch - we'll reply very soon.
          {selectedLocation
            ? ` Your enquiry is marked for ${selectedLocation.name}.`
            : ""}
        </p>
        {reference && (
          <p className="mt-3 text-sm font-semibold text-sage-800">
            Reference: {reference}
          </p>
        )}
        <button
          onClick={() => {
            submissionKey.current = createSubmissionKey();
            loadedAt.current = Date.now();
            setReference("");
            setStatus("idle");
          }}
          className="btn-outline mt-5"
        >
          Send another
        </button>
      </div>
    );
  }

  return (
    <form
      onSubmit={onSubmit}
      className="space-y-5"
      aria-label="Contact Alexandra Montessori"
    >
      {/* Honeypot — hidden from real users; bots fill it and get rejected. */}
      <input
        type="text"
        name="website"
        tabIndex={-1}
        autoComplete="off"
        aria-hidden="true"
        className="hidden"
      />
      <div className="grid gap-5 sm:grid-cols-2">
        <label className="block">
          <span className={label}>First Name *</span>
          <input
            required
            name="firstName"
            autoComplete="given-name"
            className={field}
          />
        </label>
        <label className="block">
          <span className={label}>Last Name</span>
          <input name="lastName" autoComplete="family-name" className={field} />
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
            <p role="alert" className="mt-1.5 text-xs font-medium text-red-700">
              {emailErr}
            </p>
          )}
        </label>
        <PhoneField
          ref={phoneRef}
          name="phone"
          id="contact-phone"
          label="Phone"
          required={false}
          labelClassName={label}
          fieldClassName={field}
        />
      </div>
      <label className="block">
        <span className={label}>Branch</span>
        <select
          required
          name="branch"
          className={field}
          value={branch}
          onChange={(event) => setBranch(event.target.value)}
        >
          <option value="" disabled>
            Choose a nursery
          </option>
          {locations.map((l) => (
            <option key={l.id} value={l.id}>
              {l.name}, {l.area}
            </option>
          ))}
        </select>
        {selectedLocation && (
          <span className="mt-2 block text-xs leading-relaxed text-ink/85">
            This enquiry will be routed to{" "}
            <a
              href={gmailHref(selectedLocation.email)}
              target="_blank"
              rel="noopener noreferrer"
              className="font-semibold underline underline-offset-2"
            >
              {selectedLocation.email}
            </a>
            .
          </span>
        )}
      </label>
      <label className="block">
        <span className={label}>Message *</span>
        <textarea
          required
          rows={5}
          name="message"
          defaultValue={defaultMessage}
          className={`${field} resize-none`}
        />
      </label>
      <label className="flex items-start gap-2.5 text-xs leading-relaxed text-ink/90">
        <input
          required
          type="checkbox"
          name="consent"
          className="mt-0.5 h-4 w-4 shrink-0 rounded border-sage-300 text-sage-600 focus:ring-sage-500"
        />
        <span>
          I'm happy for {brand.short} to use these details to respond to my
          enquiry, in line with the{" "}
          <Link to="/privacy" className="font-medium text-sage-700 underline">
            privacy policy
          </Link>
          .
        </span>
      </label>
      {error && (
        <p role="alert" className="text-sm font-medium text-red-700">
          {error}
        </p>
      )}
      <button
        type="submit"
        disabled={status === "sending"}
        className="btn-primary"
      >
        {status === "sending" ? "Sending..." : "Submit"}
      </button>
    </form>
  );
}
