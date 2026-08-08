import { useRef, useState } from "react";
import { qualificationLevels, positions, locations } from "../data/site";
import PhoneField from "./PhoneField";
import { emailError, normalizeEmail } from "../lib/validation";
import { gmailHref } from "../lib/contact";
import { createSubmissionKey } from "../lib/api";

const label = "mb-1.5 block font-body text-sm font-medium text-sage-800";
const field =
  "w-full rounded border border-sage-300 bg-white px-4 py-3 text-sm text-ink outline-none transition-colors placeholder:text-ink/85 focus:border-sage-600";
const DIRECT_UPLOAD_MAX_BYTES = 5 * 1024 * 1024;
const DIRECT_UPLOAD_MAX_LABEL = "5 MB";
const DIRECT_UPLOAD_EXTENSIONS = /\.(pdf|doc|docx)$/i;
const ATTACHMENT_ACCEPT = [
  ".pdf",
  ".doc",
  ".docx",
  "application/pdf",
  "application/msword",
  "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
].join(",");

function formatFileSize(bytes) {
  if (!Number.isFinite(bytes) || bytes <= 0) return "0 bytes";

  const units = ["bytes", "KB", "MB", "GB"];
  let size = bytes;
  let unit = 0;

  while (size >= 1024 && unit < units.length - 1) {
    size /= 1024;
    unit += 1;
  }

  const value = unit === 0 || size >= 10 ? Math.round(size) : size.toFixed(1);
  return `${value} ${units[unit]}`;
}

function getDirectAttachmentRejection(file) {
  if (!file?.name) return "";

  if (!DIRECT_UPLOAD_EXTENSIONS.test(file.name)) {
    return "Please upload your CV / resume as a PDF or Word document only.";
  }

  if (file.size > DIRECT_UPLOAD_MAX_BYTES) {
    return `"${file.name}" is ${formatFileSize(file.size)}. CV / resume attachments must be ${DIRECT_UPLOAD_MAX_LABEL} or smaller.`;
  }

  return "";
}

export default function ApplicationForm({ selectedJob = null }) {
  const [status, setStatus] = useState("idle");
  const [fileName, setFileName] = useState("");
  const [fileNotice, setFileNotice] = useState("");
  const [formError, setFormError] = useState("");
  const [emailErr, setEmailErr] = useState("");
  const [reference, setReference] = useState("");
  const [loadedAt] = useState(() => Date.now());
  const phoneRef = useRef(null);
  const submissionKey = useRef(createSubmissionKey());
  // "Other" handling for qualification (item 12) and position (item 13).
  const [qualification, setQualification] = useState("");
  const [qualOther, setQualOther] = useState("");
  const [position, setPosition] = useState("");
  const [posOther, setPosOther] = useState("");
  // General applicants can pick which nursery they're applying to, so the
  // application is routed to that branch's inbox (item: careers flexibility).
  const [branch, setBranch] = useState("");
  const selectedBranch = locations.find((l) => l.id === branch);

  const onFileChange = (e) => {
    const file = e.target.files?.[0];
    setFormError("");

    if (!file) {
      setFileName("");
      return;
    }

    const rejection = getDirectAttachmentRejection(file);
    if (rejection) {
      e.target.value = "";
      setFileName("");
      setFileNotice(rejection);
      return;
    }

    setFileNotice("");
    setFileName(`${file.name} (${formatFileSize(file.size)})`);
  };

  const onSubmit = async (e) => {
    e.preventDefault();
    if (status === "sending") return;

    const form = e.currentTarget;
    const fd = new FormData(form);

    // Email + phone validation (client-side, no OTP).
    const eErr = emailError(fd.get("email"), { required: true });
    const phoneOk = phoneRef.current ? phoneRef.current.validate() : true;
    if (eErr || !phoneOk) {
      setEmailErr(eErr);
      return;
    }

    // "Other" specify fields must be filled when chosen.
    if (qualification === "Other" && !qualOther.trim()) {
      setFormError("Please specify your qualification.");
      return;
    }
    if (!selectedJob && position === "Other" && !posOther.trim()) {
      setFormError("Please tell us what position you would like to apply for.");
      return;
    }

    setEmailErr("");
    setFormError("");
    fd.set("email", normalizeEmail(fd.get("email")));
    // Submit the specified free-text value when "Other" is selected.
    if (qualification === "Other") fd.set("qualification", qualOther.trim());
    if (!selectedJob && position === "Other") fd.set("position", posOther.trim());

    const resume = fd.get("resume");

    if (resume instanceof File && resume.name) {
      const rejection = getDirectAttachmentRejection(resume);
      if (rejection) {
        setFileNotice(rejection);
        setFormError(
          "Please attach a PDF or Word CV / resume file under 5 MB.",
        );
        return;
      }
    }

    if (!(resume instanceof File && resume.name)) {
      setFormError("Please attach your CV / resume as a PDF or Word document.");
      return;
    }

    setStatus("sending");

    try {
      const controller = new AbortController();
      const timeout = window.setTimeout(() => controller.abort(), 45000);
      const res = await fetch("/wp-json/am/v1/apply", {
        method: "POST",
        headers: {
          "X-AM-Idempotency-Key": submissionKey.current,
        },
        body: fd,
        signal: controller.signal,
      }).finally(() => window.clearTimeout(timeout));

      const json = await res.json().catch(() => null);
      if (!res.ok || !json.success) {
        setStatus("error");
        return;
      }
      setReference(json.reference || "");
      setStatus("sent");
    } catch {
      setStatus("error");
    }
  };

  if (status === "sent") {
    return (
      <div className="mx-auto max-w-2xl border border-sage-200 bg-white p-10 text-center">
        <h3 className="font-heading text-2xl font-medium text-sage-800">
          Form submitted successfully
        </h3>
        <p className="mt-3 text-sm text-ink/85">
          Thank you for your application. Our team has been notified and you
          will be updated once your application has been reviewed and approved.
        </p>
        {reference && (
          <p className="mt-3 text-sm font-semibold text-sage-800">
            Reference: {reference}
          </p>
        )}
      </div>
    );
  }

  if (status === "error") {
    return (
      <div className="mx-auto max-w-2xl border border-sage-200 bg-white p-10 text-center">
        <h3 className="font-heading text-2xl font-medium text-sage-800">
          Something went wrong
        </h3>
        <p className="mt-3 text-sm text-ink/85">
          We couldn't send your application right now. Please try again or email
          us directly at{" "}
          <a
            href={gmailHref("info@alexandramontessori.co.uk")}
            target="_blank"
            rel="noopener noreferrer"
            className="underline underline-offset-2"
          >
            info@alexandramontessori.co.uk
          </a>
          .
        </p>
        <button
          type="button"
          onClick={() => setStatus("idle")}
          className="btn-primary mt-6"
        >
          Try again
        </button>
      </div>
    );
  }

  return (
    <form onSubmit={onSubmit} className="mx-auto max-w-3xl space-y-5">
      {/* Hidden spam protection - honeypot (CSS-hidden, bots fill it; humans never see it) */}
      <input
        type="text"
        name="website"
        tabIndex={-1}
        autoComplete="off"
        aria-hidden="true"
        style={{ position: "absolute", left: "-9999px", opacity: 0, height: 0 }}
      />

      {/* Hidden spam protection - timing (submission too fast = bot) */}
      <input type="hidden" name="formLoadedAt" value={loadedAt} />

      {/* Hidden job context fields */}
      {selectedJob ? (
        <>
          <input type="hidden" name="jobSlug" value={selectedJob.id} />
          <input type="hidden" name="jobTitle" value={selectedJob.title} />
        </>
      ) : (
        <input type="hidden" name="jobSlug" value="general" />
      )}

      {/* Job context banner - only shown on /careers/apply?job=slug */}
      {selectedJob && (
        <div className="rounded border border-sage-200 bg-sage-50 px-5 py-4">
          <p className="font-body text-sm font-medium text-sage-800">
            Applying for:{" "}
            <span className="font-semibold">
              {selectedJob.title}
              {selectedJob.location ? `, ${selectedJob.location}` : ""}
            </span>
          </p>
        </div>
      )}

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
          <span className={label}>Last Name *</span>
          <input
            required
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
            <p role="alert" className="mt-1.5 text-xs font-medium text-red-700">
              {emailErr}
            </p>
          )}
        </label>
        <PhoneField
          ref={phoneRef}
          name="phone"
          id="application-phone"
          label="Phone"
          required
          labelClassName={label}
          fieldClassName={field}
        />
        <label className="block">
          <span className={label}>Qualification level *</span>
          <select
            required
            name="qualification"
            className={field}
            value={qualification}
            onChange={(e) => setQualification(e.target.value)}
          >
            <option value="" disabled>
              Choose an option
            </option>
            {qualificationLevels.map((q) => (
              <option key={q}>{q}</option>
            ))}
            <option value="Other">Other</option>
          </select>
          {qualification === "Other" && (
            <input
              required
              value={qualOther}
              onChange={(e) => setQualOther(e.target.value)}
              className={`${field} mt-2`}
              placeholder="Please specify your qualification"
              aria-label="Please specify your qualification"
            />
          )}
        </label>
        <label className="block">
          <span className={label}>Position *</span>
          {selectedJob ? (
            <>
              {/* Read-only display; value submitted via hidden input */}
              <div className={`${field} cursor-default bg-sage-50 text-ink/85`}>
                {selectedJob.title}
              </div>
              <input type="hidden" name="position" value={selectedJob.title} />
            </>
          ) : (
            <>
              <select
                required
                name="position"
                className={field}
                value={position}
                onChange={(e) => setPosition(e.target.value)}
              >
                <option value="" disabled>
                  Choose an option
                </option>
                {positions.map((p) => (
                  <option key={p}>{p}</option>
                ))}
                <option value="Other">Other</option>
              </select>
              {position === "Other" && (
                <input
                  required
                  value={posOther}
                  onChange={(e) => setPosOther(e.target.value)}
                  className={`${field} mt-2`}
                  placeholder="What position would you like to apply for?"
                  aria-label="What position would you like to apply for?"
                />
              )}
            </>
          )}
        </label>
        {/* General applicants choose a nursery so we route to that branch.
            Vacancy applications route by the job's own location instead. */}
        {!selectedJob && (
          <label className="block">
            <span className={label}>Which nursery are you applying to?</span>
            <select
              name="branch"
              className={field}
              value={branch}
              onChange={(e) => setBranch(e.target.value)}
            >
              <option value="">Any / not sure yet</option>
              {locations.map((l) => (
                <option key={l.id} value={l.id}>
                  {l.name}, {l.area}
                </option>
              ))}
            </select>
            {selectedBranch && (
              <span className="mt-2 block text-xs leading-relaxed text-ink/85">
                <span className="block">Your application will be sent to</span>
                <a
                  href={gmailHref(selectedBranch.email)}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="mt-0.5 block w-fit font-semibold underline underline-offset-2"
                >
                  {selectedBranch.email}
                </a>
              </span>
            )}
          </label>
        )}
      </div>
      <label className="block">
        <span className={label}>Message</span>
        <textarea rows={5} name="about" className={`${field} resize-none`} />
      </label>
      <label className="block">
        <span className={label}>Attach CV / Resume *</span>
        <span className="flex cursor-pointer items-center justify-between gap-3 rounded border border-dashed border-sage-300 bg-white px-4 py-3 text-sm text-ink/85 transition-colors hover:border-sage-600">
          <span className="min-w-0 truncate">
            {fileName || "Upload File (PDF / Word)"}
          </span>
          <input
            type="file"
            name="resume"
            accept={ATTACHMENT_ACCEPT}
            className="hidden"
            onChange={onFileChange}
          />
        </span>
        <p className="mt-2 text-xs leading-relaxed text-ink/85">
          Accepted formats: PDF, DOC, DOCX. Maximum file size:{" "}
          {DIRECT_UPLOAD_MAX_LABEL}.
        </p>
        {fileNotice && (
          <div
            className="mt-3 rounded border border-amber-200 bg-amber-50 px-4 py-3 text-xs leading-relaxed text-amber-900"
            role="alert"
          >
            <p>{fileNotice}</p>
          </div>
        )}
      </label>
      {formError && (
        <p
          className="rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"
          role="alert"
        >
          {formError}
        </p>
      )}
      <label className="flex items-start gap-2.5 text-xs leading-relaxed text-ink/90">
        <input
          required
          type="checkbox"
          name="consent"
          className="mt-0.5 h-4 w-4 shrink-0 rounded border-sage-300 text-sage-600 focus:ring-sage-500"
        />
        <span>
          I am happy for Alexandra Montessori to hold these details and
          attachments to process my application, in line with the{" "}
          <a href="/privacy" className="font-medium text-sage-700 underline">
            privacy policy
          </a>
          .
        </span>
      </label>
      <button
        type="submit"
        disabled={status === "sending"}
        className="btn-primary w-full"
      >
        {status === "sending" ? "Sending..." : "Submit"}
      </button>
    </form>
  );
}
