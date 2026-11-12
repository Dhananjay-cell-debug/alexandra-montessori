import { FormGuard, SubmissionReceipt } from "../components/FormDelivery";
import { useRef, useState } from "react";
import { Link, useSearchParams } from "react-router-dom";
import {
  CalendarDays,
  CircleCheckBig,
  Mail,
  Phone,
  ShieldCheck,
} from "lucide-react";
import Seo from "../components/Seo";
import Reveal from "../components/Reveal";
import Icon from "../components/Icon";
import PageHeader from "../components/PageHeader";
import FormField from "../components/FormField";
import DateField from "../components/DateField";
import PhoneField from "../components/PhoneField";
import Pending from "../components/Pending";
import TbcBadge from "../components/TbcBadge";
import { availability } from "../data/site";
import { brand, settings } from "../lib/content";
import { isTbc } from "../lib/tbc";
import { emailError } from "../lib/validation";
import { createSubmissionKey, submitForm, submissionsEnabled } from "../lib/api";

const WEEKDAYS = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"];
const SESSION_TYPES = ["Morning", "Afternoon", "Full day", "Other"];
const SETTING_OPTIONS = settings.map((setting) => ({
  value: setting.slug,
  label: setting.name,
}));

function AvailabilityForm() {
  const [searchParams] = useSearchParams();
  // A setting page can deep-link here with its slug pre-selected, but only a
  // slug that actually exists is honoured.
  const requestedSetting = searchParams.get("setting");
  const defaultSetting = settings.some(
    (setting) => setting.slug === requestedSetting,
  )
    ? requestedSetting
    : "";

  const formRef = useRef(null);
  const phoneRef = useRef(null);
  const startDateRef = useRef(null);
  const endDateRef = useRef(null);
  const successRef = useRef(null);
  const submissionKey = useRef(createSubmissionKey());

  const [errors, setErrors] = useState({});
  const [status, setStatus] = useState("idle");
  const [receipt, setReceipt] = useState(null);
  const [serverError, setServerError] = useState("");
  const [startDate, setStartDate] = useState("");
  const [hasEndDate, setHasEndDate] = useState(false);
  const [endDate, setEndDate] = useState("");
  const [days, setDays] = useState([]);
  const [sessionType, setSessionType] = useState("");
  const [otherSession, setOtherSession] = useState("");
  const [startTime, setStartTime] = useState("");
  const [endTime, setEndTime] = useState("");

  const today = new Date().toISOString().slice(0, 10);
  // A full day has fixed hours, so times are only asked for other patterns.
  const needsTimes = sessionType && sessionType !== "Full day";
  const sendsToServer = submissionsEnabled();

  const clearError = (field) => {
    if (!field || !errors[field]) return;
    setErrors((current) => {
      const next = { ...current };
      delete next[field];
      return next;
    });
    setStatus("idle");
  };

  const toggleDay = (day) => {
    setDays((current) =>
      current.includes(day)
        ? current.filter((value) => value !== day)
        : [...current, day],
    );
    clearError("days");
  };

  const resetForm = () => {
    setStatus("idle");
    setErrors({});
    setServerError("");
    setStartDate("");
    setHasEndDate(false);
    setEndDate("");
    setDays([]);
    setSessionType("");
    setOtherSession("");
    setStartTime("");
    setEndTime("");
    submissionKey.current = createSubmissionKey();
  };

  const handleSubmit = async (event) => {
    event.preventDefault();
    const form = event.currentTarget;
    const formData = new FormData(form);
    const found = {};

    form.querySelectorAll("[required]").forEach((field) => {
      if (field.type === "checkbox" && !field.checked) {
        found[field.name] = "Please confirm this before continuing.";
      } else if (!String(field.value ?? "").trim()) {
        found[field.name] = "Please complete this field.";
      }
    });

    const email = emailError(formData.get("email"), { required: true });
    if (email) found.email = email;
    if (!phoneRef.current?.validate()) {
      found.phone = "Please check your phone number.";
    }
    if (!startDateRef.current?.validate()) {
      found.startDate = "Please add a valid required start date.";
    }
    if (hasEndDate && !endDateRef.current?.validate()) {
      found.endDate = "Please add a valid end date.";
    }
    if (!days.length) {
      found.days = "Please choose at least one weekday.";
    }
    if (sessionType === "Other" && !otherSession.trim()) {
      found.otherSession = "Please describe the session you need.";
    }

    if (Object.keys(found).length) {
      const first = Object.keys(found)[0];
      setErrors(found);
      setStatus("error");
      requestAnimationFrame(() => {
        if (first === "phone") phoneRef.current?.focus();
        else if (first === "startDate") startDateRef.current?.focus();
        else if (first === "endDate") endDateRef.current?.focus();
        else if (first === "days")
          form.querySelector("[data-day-button]")?.focus();
        else {
          const field = form.elements.namedItem(first);
          if (field && typeof field.focus === "function") field.focus();
        }
      });
      return;
    }

    setErrors({});
    setServerError("");

    if (!sendsToServer) {
      setStatus("success");
      requestAnimationFrame(() => successRef.current?.focus());
      return;
    }

    setStatus("submitting");
    try {
      const accepted = await submitForm(
        "availability",
        // The weekday chips are not native inputs, so they are added
        // explicitly rather than coming through FormData.
        { ...Object.fromEntries(formData.entries()), days },
        submissionKey.current,
      );
      setReceipt(accepted);
      setStatus("success");
      requestAnimationFrame(() => successRef.current?.focus());
    } catch (error) {
      setServerError(error.message);
      setStatus("error");
    }
  };

  if (status === "success") {
    return (
      <div
        ref={successRef}
        tabIndex={-1}
        role="status"
        className="mt-7 rounded-4xl border border-green-300 bg-green-50 p-7 text-center outline-none focus-visible:ring-2 focus-visible:ring-sky-500 sm:p-9"
      >
        <CircleCheckBig
          className="mx-auto h-12 w-12 text-green-700"
          strokeWidth={1.7}
          aria-hidden
        />
        <h3 className="mt-5 text-2xl">
          {sendsToServer
            ? "Thank you — your request has been sent"
            : availability.successHeading}
        </h3>
        <p className="mx-auto mt-3 max-w-lg text-sm leading-relaxed text-ink/80">
          {sendsToServer
            ? "The Little Rainbows team will confirm current availability with you directly."
            : availability.successText}
        </p>
        <div className="mt-6 flex flex-wrap justify-center gap-3">
          <a href={`mailto:${brand.email}`} className="btn-primary">
            <Mail className="h-4 w-4" strokeWidth={1.8} aria-hidden />
            Email Little Rainbows
          </a>
          <button type="button" onClick={resetForm} className="btn-outline">
            Start another request
          </button>
        </div>
      <SubmissionReceipt receipt={receipt} />
      </div>
    );
  }

  return (
    <form
      ref={formRef}
      noValidate
      aria-label="Check childcare availability"
      onSubmit={handleSubmit}
      onChange={(event) => clearError(event.target.name)}
      className="mt-7 space-y-5"
    >
      {!sendsToServer && (
        <div className="flex items-start gap-3 rounded-4xl border border-sky-200 bg-sky-50 p-4 text-sm leading-relaxed text-ink/80">
          <ShieldCheck
            className="mt-0.5 h-5 w-5 shrink-0 text-sky-700"
            strokeWidth={1.8}
            aria-hidden
          />
          <p>{availability.localOnlyNotice}</p>
        </div>
      )}

      {status === "error" && (
        <p
          role="alert"
          className="rounded-4xl border border-red-300 bg-red-50 p-4 text-sm font-semibold text-red-800"
        >
          {serverError ||
            "Please complete the highlighted fields before continuing."}
        </p>
      )}

      <div className="grid gap-5 sm:grid-cols-2">
        <FormField
          id="parentName"
          label="Parent or carer name"
          required
          error={errors.parentName}
          autoComplete="name"
        />
        <PhoneField
          ref={phoneRef}
          id="availabilityPhone"
          name="phone"
          label="Phone number"
          onValidityChange={(valid) => {
            if (valid) clearError("phone");
          }}
        />
        <div className="sm:col-span-2">
          <FormField
            id="email"
            label="Email address"
            type="email"
            required
            error={errors.email}
            autoComplete="email"
          />
        </div>
        <FormField
          id="childName"
          label="Child's name"
          required
          error={errors.childName}
          autoComplete="off"
        />
        <FormField
          id="childAge"
          label="Child's age"
          required
          error={errors.childAge}
          placeholder="For example, 2 years"
        />
        <FormField
          id="setting"
          label="Preferred setting"
          as="select"
          options={SETTING_OPTIONS}
          defaultValue={defaultSetting}
          required
          error={errors.setting}
        />
        <DateField
          ref={startDateRef}
          id="availabilityStartDate"
          name="startDate"
          label="Required start date"
          required
          value={startDate}
          min={today}
          onChange={(value) => {
            setStartDate(value);
            clearError("startDate");
            // Never leave an end date stranded before the new start date.
            if (endDate && value && endDate < value) setEndDate("");
          }}
        />
      </div>

      <div className="rounded-4xl border border-sky-200 bg-sky-50/70 p-4">
        <label className="flex items-start gap-3 text-sm leading-relaxed text-ink/80">
          <input
            type="checkbox"
            checked={hasEndDate}
            onChange={(event) => {
              setHasEndDate(event.target.checked);
              if (!event.target.checked) setEndDate("");
            }}
            className="mt-1 h-4 w-4 shrink-0 accent-sky-900"
          />
          <span>
            I need childcare for a fixed period and want to add an end date.
          </span>
        </label>
        {hasEndDate && (
          <div className="mt-4 max-w-sm">
            <DateField
              ref={endDateRef}
              id="availabilityEndDate"
              name="endDate"
              label="Required end date"
              required
              value={endDate}
              min={startDate || today}
              onChange={(value) => {
                setEndDate(value);
                clearError("endDate");
              }}
            />
          </div>
        )}
      </div>

      <fieldset
        aria-invalid={!!errors.days || undefined}
        aria-describedby={errors.days ? "days-error" : undefined}
      >
        <legend className="form-label">
          Required weekdays <span aria-hidden="true">*</span>
        </legend>
        <div className="flex flex-wrap gap-2">
          {WEEKDAYS.map((day) => {
            const selected = days.includes(day);
            return (
              <button
                key={day}
                type="button"
                data-day-button
                aria-pressed={selected}
                onClick={() => toggleDay(day)}
                className={`rounded-full border px-4 py-2 text-xs font-semibold transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-600 ${
                  selected
                    ? "border-sky-900 bg-sky-900 text-white"
                    : "border-sky-200 bg-white text-ink hover:bg-sky-50"
                }`}
              >
                {day.slice(0, 3)}
              </button>
            );
          })}
        </div>
        {errors.days && (
          <p id="days-error" role="alert" className="form-error">
            {errors.days}
          </p>
        )}
      </fieldset>

      <div className="grid gap-5 sm:grid-cols-2">
        <FormField
          id="sessionType"
          label="Preferred session"
          as="select"
          options={SESSION_TYPES}
          value={sessionType}
          onChange={(event) => {
            setSessionType(event.target.value);
            clearError("sessionType");
          }}
          error={errors.sessionType}
        />
        {sessionType === "Other" && (
          <FormField
            id="otherSession"
            label="Describe the session you need"
            value={otherSession}
            onChange={(event) => {
              setOtherSession(event.target.value);
              clearError("otherSession");
            }}
            error={errors.otherSession}
          />
        )}
        {needsTimes && (
          <>
            <FormField
              id="startTime"
              label="Preferred start time"
              type="time"
              value={startTime}
              onChange={(event) => setStartTime(event.target.value)}
              error={errors.startTime}
            />
            <FormField
              id="endTime"
              label="Preferred end time"
              type="time"
              value={endTime}
              onChange={(event) => setEndTime(event.target.value)}
              error={errors.endTime}
            />
          </>
        )}
      </div>

      <FormField
        id="message"
        label="Additional requirements"
        as="textarea"
        rows={5}
        error={errors.message}
        placeholder="Funding, dietary needs, settling-in questions or anything else we should know"
        className="resize-y"
      />

      <div className="flex items-start gap-3 rounded-4xl border border-sky-200 bg-white p-4">
        <input
          id="availabilityConsent"
          name="availabilityConsent"
          type="checkbox"
          required
          aria-invalid={!!errors.availabilityConsent || undefined}
          aria-describedby={
            errors.availabilityConsent ? "availabilityConsent-error" : undefined
          }
          className="mt-1 h-4 w-4 shrink-0 accent-sky-900"
        />
        <div>
          <label
            htmlFor="availabilityConsent"
            className="text-sm leading-relaxed text-ink/80"
          >
            I am happy for Little Rainbows to use these details to respond to my
            availability enquiry, in line with the{" "}
            <Link to="/privacy" className="font-semibold text-sky-800 underline">
              privacy policy
            </Link>
            . <span aria-hidden="true">*</span>
          </label>
          {errors.availabilityConsent && (
            <p
              id="availabilityConsent-error"
              role="alert"
              className="form-error"
            >
              {errors.availabilityConsent}
            </p>
          )}
        </div>
      </div>

      <div className="flex flex-col gap-4 pt-2 sm:flex-row sm:items-center sm:justify-between">
        <a
          href={`tel:${brand.phone.general.replace(/\s/g, "")}`}
          className="inline-flex items-center gap-2 text-sm font-semibold text-sky-800 hover:underline"
        >
          <Phone className="h-4 w-4" strokeWidth={1.8} aria-hidden />
          {brand.phone.general}
        </a>
        <button
          type="submit"
          className="btn-primary"
          disabled={status === "submitting"}
        >
          {status === "submitting" ? "Sending…" : "Check availability"}
        </button>
      </div>
      <FormGuard />
    </form>
  );
}

export default function BookAVisit() {
  return (
    <>
      <Seo path="/check-availability" />
      <PageHeader
        title={availability.title}
        intro={availability.intro}
        tone="canvas"
      />

      <section data-ldrn-ws="book-avisit.section-1" className="border-t border-sky-100 bg-band pb-16 pt-10 sm:pb-20 sm:pt-12">
        <div className="container-wide grid gap-10 lg:grid-cols-[0.9fr_1.1fr] lg:items-start">
          <Reveal className="space-y-6">
            <div className="relative min-h-[26rem] overflow-hidden rounded-4xl bg-sky-900 shadow-card">
              <img
                src="/assets/photography/children-playing-together-at-table.jpg"
                alt="Children playing together at a nursery table"
                loading="eager"
                fetchPriority="high"
                decoding="async"
                className="absolute inset-0 h-full w-full object-cover"
              />
              <div
                className="absolute inset-0 z-[2] bg-gradient-to-t from-sky-900/90 via-sky-900/25 to-transparent"
                aria-hidden="true"
              />
              <div className="absolute inset-x-0 bottom-0 z-[3] p-7 text-white sm:p-8">
                <p className="text-xs font-bold uppercase tracking-[0.18em] text-sky-100">
                  Admissions support
                </p>
                <h2 className="mt-3 max-w-md !text-white">
                  Find the right place with a real nursery team
                </h2>
              </div>
            </div>

            <div className="grid gap-4 sm:grid-cols-3 lg:grid-cols-1">
              {availability.highlights.map((highlight) => (
                <div
                  key={highlight.title}
                  className="flex gap-4 rounded-4xl border border-sky-100 bg-white p-5 shadow-soft"
                >
                  <span className="icon-badge h-11 w-11 shrink-0">
                    <Icon name={highlight.icon} className="h-5 w-5" />
                  </span>
                  <div>
                    <h3 className="text-lg">{highlight.title}</h3>
                    <p className="mt-1 text-sm leading-relaxed text-ink/75">
                      {highlight.text}
                    </p>
                  </div>
                </div>
              ))}
            </div>
          </Reveal>

          <Reveal delay={100}>
            <div className="rounded-4xl border border-sky-100 bg-white p-6 shadow-card sm:p-8">
              <div className="flex flex-col gap-4 border-b border-sky-100 pb-6 sm:flex-row sm:items-start sm:justify-between">
                <div>
                  <p className="eyebrow">Availability enquiry</p>
                  <h2 className="mt-3 text-3xl">{availability.formHeading}</h2>
                  <p className="mt-3 max-w-xl text-sm leading-relaxed text-ink/75">
                    {availability.formIntro}
                  </p>
                </div>
                <span className="icon-badge h-14 w-14 shrink-0">
                  <CalendarDays
                    className="h-7 w-7"
                    strokeWidth={1.7}
                    aria-hidden
                  />
                </span>
              </div>
              <AvailabilityForm />
            </div>
          </Reveal>
        </div>
      </section>

      <section data-ldrn-ws="book-avisit.section-2" className="bg-canvas py-14 sm:py-16">
        <div className="container-wide">
          <div className="grid gap-5 md:grid-cols-3">
            {settings.map((setting, index) => {
              const address = isTbc(setting.address)
                ? null
                : setting.address.join(", ");
              const phone = isTbc(setting.phone) ? null : setting.phone;

              return (
                <Reveal
                  key={setting.slug}
                  delay={index * 70}
                  className="card flex flex-col p-6"
                >
                  <h2 className="text-xl">{setting.name}</h2>
                  {address ? (
                    <p className="mt-3 text-sm leading-relaxed text-ink/75">
                      {address}
                    </p>
                  ) : (
                    <div className="mt-3">
                      <Pending>
                        Please contact the Little Rainbows team for this
                        setting’s address.
                      </Pending>
                    </div>
                  )}
                  {phone && (
                    <a
                      href={`tel:${phone.replace(/\s/g, "")}`}
                      className="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-sky-800 hover:underline"
                    >
                      <Phone className="h-4 w-4" strokeWidth={1.8} aria-hidden />
                      {phone}
                    </a>
                  )}
                  <div className="mt-3 flex flex-wrap gap-1.5">
                    <TbcBadge value={setting.address} />
                    <TbcBadge value={setting.phone} />
                  </div>
                </Reveal>
              );
            })}
          </div>
          <p className="mt-8 text-center text-sm text-ink/75">
            You can also call{" "}
            <a
              href={`tel:${brand.phone.general.replace(/\s/g, "")}`}
              className="font-semibold text-sky-800 hover:underline"
            >
              {brand.phone.general}
            </a>
          </p>
        </div>
      </section>
    </>
  );
}
