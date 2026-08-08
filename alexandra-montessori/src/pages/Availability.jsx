import { useEffect, useRef, useState } from "react";
import { CalendarDays, CheckCircle2, Clock, MapPin, Phone } from "lucide-react";
import DateField from "../components/DateField";
import Icon from "../components/Icon";
import PageHeader from "../components/PageHeader";
import Reveal from "../components/Reveal";
import Seo from "../components/Seo";
import { brand, locations } from "../data/site";
import { createSubmissionKey, submitForm } from "../lib/api";
import { asset } from "../lib/asset";
import PhoneField from "../components/PhoneField";
import { emailError, normalizeEmail } from "../lib/validation";
import { todayISO } from "../lib/dates";

const highlights = [
  {
    icon: CalendarDays,
    title: "Preferred start date",
    text: "Tell us when you would like your child to begin.",
  },
  {
    icon: Clock,
    title: "Session pattern",
    text: "Share the days and hours you are considering.",
  },
  {
    icon: MapPin,
    title: "Best nursery fit",
    text: "We will route your enquiry to the right branch team.",
  },
];

const WEEK_DAYS = ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"];
const SESSION_OPTIONS = ["Morning", "Afternoon", "Full Day", "Other"];

export default function Availability() {
  const [branch, setBranch] = useState("");
  const [status, setStatus] = useState("idle");
  const [error, setError] = useState("");
  const [emailErr, setEmailErr] = useState("");
  const [reference, setReference] = useState("");
  // Structured "care needed" selections (item 16).
  const [startDate, setStartDate] = useState("");
  const [needsEndDate, setNeedsEndDate] = useState(false);
  const [endDate, setEndDate] = useState("");
  const [days, setDays] = useState([]);
  const [sessionType, setSessionType] = useState("");
  const [sessionOther, setSessionOther] = useState("");
  const [startTime, setStartTime] = useState("");
  const [endTime, setEndTime] = useState("");
  const phoneRef = useRef(null);
  const startDateRef = useRef(null);
  const endDateRef = useRef(null);
  const loadedAt = useRef(0);
  const submissionKey = useRef(createSubmissionKey());
  useEffect(() => {
    loadedAt.current = Date.now();
  }, []);
  const selectedLocation = locations.find((location) => location.id === branch);

  const minStart = todayISO();
  const showTimes = sessionType && sessionType !== "Full Day";

  const toggleDay = (day) =>
    setDays((current) =>
      current.includes(day)
        ? current.filter((d) => d !== day)
        : [...current, day],
    );

  // Build a human-readable summary the branch team receives (keeps the existing
  // "Days / sessions" admin field + email lossless without a backend change).
  const buildSessionSummary = () => {
    const sessionLabel =
      sessionType === "Other" ? sessionOther.trim() || "Other" : sessionType;
    const orderedDays = WEEK_DAYS.filter((d) => days.includes(d));
    const parts = [];
    if (orderedDays.length) parts.push(orderedDays.join(", "));
    if (sessionLabel) parts.push(sessionLabel);
    if (showTimes && (startTime || endTime)) {
      parts.push(`${startTime || "?"}–${endTime || "?"}`);
    }
    if (needsEndDate && endDate) parts.push(`until ${endDate}`);
    return parts.join(" · ");
  };

  const onSubmit = async (event) => {
    event.preventDefault();
    if (status === "sending") return;
    const data = new FormData(event.currentTarget);

    const eErr = emailError(data.get("email"), { required: true });
    // Every validate() runs before the guard so the visitor sees each problem
    // at once rather than one field per attempt.
    const phoneOk = phoneRef.current ? phoneRef.current.validate() : true;
    const startOk = startDateRef.current ? startDateRef.current.validate() : true;
    const endOk =
      !needsEndDate || !endDateRef.current || endDateRef.current.validate();
    if (eErr || !phoneOk || !startOk || !endOk) {
      setEmailErr(eErr);
      return;
    }

    setStatus("sending");
    setError("");
    setEmailErr("");
    try {
      const result = await submitForm("availability", {
        name: data.get("name") || "",
        email: normalizeEmail(data.get("email")),
        phone: data.get("phone") || "",
        childName: data.get("childName") || "",
        childAge: data.get("childAge") || "",
        branch: data.get("branch") || "",
        startDate,
        endDate: needsEndDate ? endDate : "",
        days: days.join(", "),
        sessionType:
          sessionType === "Other"
            ? `Other: ${sessionOther.trim()}`
            : sessionType,
        startTime: showTimes ? startTime : "",
        endTime: showTimes ? endTime : "",
        sessions: buildSessionSummary(),
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

  return (
    <>
      <Seo
        title="Check Availability"
        description="Check nursery place availability at Alexandra Montessori in Hounslow, Heston and Hammersmith."
        path="/check-availability"
        image="/assets/organisation/friends-two.webp"
      />
      <PageHeader
        region="availability-header"
        title="Check Availability"
        intro="Tell us what you need and our admissions team will confirm current places, funded hours and the best next step for your family."
      />

      <section className="bg-cream pb-16 pt-6 sm:pb-20">
        <div className="container-wide grid gap-10 lg:grid-cols-[0.9fr_1.1fr] lg:items-start">
          <Reveal className="space-y-6">
            <div className="overflow-hidden rounded-4xl bg-white shadow-card">
              <div className="relative min-h-[26rem] bg-sage-700" data-am-vb-region="availability-visual">
                <img
                  src={asset("/assets/organisation/friends-two.webp")}
                  alt="Children at Alexandra Montessori"
                  className="absolute inset-0 h-full w-full object-cover"
                />
                <div className="absolute inset-0 bg-gradient-to-t from-sage-950/80 via-sage-900/25 to-transparent" />
                <div className="absolute inset-x-0 bottom-0 p-7 text-white sm:p-8">
                  <p className="text-sm font-semibold uppercase tracking-[0.18em] text-white/85">
                    Admissions support
                  </p>
                  <h2 className="mt-3 max-w-sm font-heading text-3xl font-medium leading-tight sm:text-4xl">
                    Find the right place with a real nursery team
                  </h2>
                </div>
              </div>
            </div>

            <div className="grid gap-4 sm:grid-cols-3 lg:grid-cols-1" data-am-vb-region="availability-highlights">
              {highlights.map(({ icon: HighlightIcon, title, text }) => (
                <div
                  key={title}
                  className="flex gap-4 rounded-2xl bg-white p-5 shadow-soft"
                >
                  <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-sage-100 text-sage-700">
                    <HighlightIcon className="h-5 w-5" strokeWidth={1.8} />
                  </span>
                  <div>
                    <h3 className="font-heading text-lg font-medium text-sage-800">
                      {title}
                    </h3>
                    <p className="mt-1 text-sm leading-relaxed text-ink/85">
                      {text}
                    </p>
                  </div>
                </div>
              ))}
            </div>
          </Reveal>

          <Reveal delay={100}>
            <div className="rounded-4xl border border-sage-100 bg-white p-6 shadow-card sm:p-8">
              <div className="flex flex-col gap-4 border-b border-sage-100 pb-6 sm:flex-row sm:items-start sm:justify-between" data-am-vb-region="availability-form-header">
                <div>
                  <p className="eyebrow">Availability enquiry</p>
                  <h2 className="mt-3 font-heading text-3xl font-medium text-sage-800">
                    Tell us about your childcare needs
                  </h2>
                </div>
                <span className="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-sand text-sage-700">
                  <Icon name="CalendarHeart" className="h-8 w-8" />
                </span>
              </div>

              {status === "sent" ? (
                <div className="mt-7 rounded-2xl border border-sage-200 bg-sage-50 p-8 text-center" data-am-vb-region="availability-success">
                  <CheckCircle2 className="mx-auto h-12 w-12 text-sage-700" />
                  <h3 className="mt-4 font-heading text-2xl font-medium text-sage-800">
                    Availability request received
                  </h3>
                  <p className="mx-auto mt-3 max-w-md text-sm leading-relaxed text-ink/85">
                    Thank you. Our team will review your preferred nursery,
                    sessions and start date, then come back to you with the
                    next step.
                  </p>
                  {reference && (
                    <p className="mt-3 text-sm font-semibold text-sage-800">
                      Reference: {reference}
                    </p>
                  )}
                  <button
                    type="button"
                    onClick={() => {
                      submissionKey.current = createSubmissionKey();
                      loadedAt.current = Date.now();
                      setReference("");
                      setStatus("idle");
                    }}
                    className="btn-outline mt-6"
                  >
                    Send another request
                  </button>
                </div>
              ) : (
                <form onSubmit={onSubmit} className="mt-7 space-y-5">
                  {/* Honeypot — hidden from real users; rejects bots. */}
                  <input
                    type="text"
                    name="website"
                    tabIndex={-1}
                    autoComplete="off"
                    aria-hidden="true"
                    className="hidden"
                  />
                  <div className="grid gap-5 sm:grid-cols-2" data-am-vb-region="availability-parent-child">
                    <label className="block">
                      <span className="mb-1.5 block text-sm font-medium text-sage-800">
                        Parent name *
                      </span>
                      <input
                        required
                        name="name"
                        autoComplete="name"
                        className="w-full rounded border border-sage-300 bg-white px-4 py-3 text-sm text-ink outline-none transition-colors placeholder:text-ink/60 focus:border-sage-600"
                        placeholder="Parent or carer name"
                      />
                    </label>
                    <PhoneField
                      ref={phoneRef}
                      name="phone"
                      id="availability-phone"
                      label="Phone"
                      required
                      labelClassName="mb-1.5 block text-sm font-medium text-sage-800"
                      fieldClassName="w-full rounded border border-sage-300 bg-white px-4 py-3 text-sm text-ink outline-none transition-colors placeholder:text-ink/60 focus:border-sage-600"
                    />
                    <label className="block" style={{ gridColumn: "1 / -1" }}>
                      <span className="mb-1.5 block text-sm font-medium text-sage-800">
                        Email *
                      </span>
                      <input
                        required
                        type="email"
                        name="email"
                        autoComplete="email"
                        className="w-full rounded border border-sage-300 bg-white px-4 py-3 text-sm text-ink outline-none transition-colors placeholder:text-ink/60 focus:border-sage-600"
                        placeholder="Email address"
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
                    <label className="block">
                      <span className="mb-1.5 block text-sm font-medium text-sage-800">
                        Child name *
                      </span>
                      <input
                        required
                        name="childName"
                        className="w-full rounded border border-sage-300 bg-white px-4 py-3 text-sm text-ink outline-none transition-colors placeholder:text-ink/60 focus:border-sage-600"
                        placeholder="Child's full name"
                      />
                    </label>
                    <label className="block">
                      <span className="mb-1.5 block text-sm font-medium text-sage-800">
                        Child age *
                      </span>
                      <input
                        required
                        name="childAge"
                        className="w-full rounded border border-sage-300 bg-white px-4 py-3 text-sm text-ink outline-none transition-colors placeholder:text-ink/60 focus:border-sage-600"
                        placeholder="Example: 2 years"
                      />
                    </label>
                    <div className="block" data-am-vb-region="availability-nursery">
                      <label className="block">
                        <span className="mb-1.5 block text-sm font-medium text-sage-800">
                          Preferred nursery
                        </span>
                        <select
                          name="branch"
                          value={branch}
                          onChange={(event) => setBranch(event.target.value)}
                          className="w-full rounded border border-sage-300 bg-white px-4 py-3 text-sm text-ink outline-none transition-colors focus:border-sage-600"
                        >
                          <option value="">Choose a nursery</option>
                          {locations.map((location) => (
                            <option key={location.id} value={location.id}>
                              {location.name}, {location.area}
                            </option>
                          ))}
                        </select>
                      </label>
                      {selectedLocation && (
                        <div className="mt-2 space-y-1 text-xs leading-relaxed text-ink/85">
                          <span className="block">
                            {selectedLocation.name} is open{" "}
                            {selectedLocation.hours}.
                          </span>
                          <span className="block">
                            This enquiry will be routed to{" "}
                            <span className="font-semibold underline underline-offset-2">
                              {selectedLocation.email}
                            </span>
                            .
                          </span>
                        </div>
                      )}
                    </div>
                    <div data-am-vb-region="availability-dates">
                      <DateField
                        ref={startDateRef}
                        id="availability-start-date"
                        label="Required start date"
                        required
                        value={startDate}
                        min={minStart}
                        onChange={(iso) => {
                          setStartDate(iso);
                          if (endDate && iso && iso > endDate) setEndDate("");
                        }}
                      />
                    </div>
                  </div>

                  {/* Fixed-period end date — appears only when needed (item 16) */}
                  <div className="rounded-2xl border border-sage-100 bg-sage-50/40 p-4" data-am-vb-region="availability-dates">
                    <label className="flex items-start gap-2.5 text-sm text-ink/90">
                      <input
                        type="checkbox"
                        checked={needsEndDate}
                        onChange={(e) => {
                          setNeedsEndDate(e.target.checked);
                          if (!e.target.checked) setEndDate("");
                        }}
                        className="mt-0.5 h-4 w-4 shrink-0 rounded border-sage-300 text-sage-600 focus:ring-sage-500"
                      />
                      <span>I need care for a fixed period (add an end date)</span>
                    </label>
                    {needsEndDate && (
                      <DateField
                        ref={endDateRef}
                        id="availability-end-date"
                        label="Required end date"
                        value={endDate}
                        min={startDate || minStart}
                        onChange={setEndDate}
                        className="mt-3 block sm:max-w-xs"
                      />
                    )}
                  </div>

                  {/* Required days of the week */}
                  <div data-am-vb-region="availability-schedule">
                    <span className="mb-1.5 block text-sm font-medium text-sage-800">
                      Required days of the week
                    </span>
                    <div className="flex flex-wrap gap-2">
                      {WEEK_DAYS.map((day) => {
                        const active = days.includes(day);
                        return (
                          <button
                            key={day}
                            type="button"
                            aria-pressed={active}
                            onClick={() => toggleDay(day)}
                            className={`rounded-full border px-4 py-1.5 text-xs font-medium transition-colors ${
                              active
                                ? "border-sage-500 bg-sage-500 text-white"
                                : "border-sage-300 text-sage-800 hover:bg-sage-50"
                            }`}
                          >
                            {day}
                          </button>
                        );
                      })}
                    </div>
                  </div>

                  {/* Preferred session + dynamic times */}
                  <div className="grid gap-5 sm:grid-cols-2" data-am-vb-region="availability-schedule">
                    <label className="block">
                      <span className="mb-1.5 block text-sm font-medium text-sage-800">
                        Preferred session
                      </span>
                      <select
                        value={sessionType}
                        onChange={(e) => setSessionType(e.target.value)}
                        className="w-full rounded border border-sage-300 bg-white px-4 py-3 text-sm text-ink outline-none transition-colors focus:border-sage-600"
                      >
                        <option value="">Choose a session</option>
                        {SESSION_OPTIONS.map((opt) => (
                          <option key={opt} value={opt}>
                            {opt}
                          </option>
                        ))}
                      </select>
                    </label>
                    {sessionType === "Other" && (
                      <label className="block">
                        <span className="mb-1.5 block text-sm font-medium text-sage-800">
                          Please describe the session you need
                        </span>
                        <input
                          value={sessionOther}
                          onChange={(e) => setSessionOther(e.target.value)}
                          className="w-full rounded border border-sage-300 bg-white px-4 py-3 text-sm text-ink outline-none transition-colors placeholder:text-ink/60 focus:border-sage-600"
                          placeholder="e.g. school pick-up only"
                        />
                      </label>
                    )}
                    {showTimes && (
                      <>
                        <label className="block">
                          <span className="mb-1.5 block text-sm font-medium text-sage-800">
                            Preferred start time
                          </span>
                          <input
                            type="time"
                            value={startTime}
                            onChange={(e) => setStartTime(e.target.value)}
                            className="w-full rounded border border-sage-300 bg-white px-4 py-3 text-sm text-ink outline-none transition-colors focus:border-sage-600"
                          />
                        </label>
                        <label className="block">
                          <span className="mb-1.5 block text-sm font-medium text-sage-800">
                            Preferred end time
                          </span>
                          <input
                            type="time"
                            value={endTime}
                            onChange={(e) => setEndTime(e.target.value)}
                            className="w-full rounded border border-sage-300 bg-white px-4 py-3 text-sm text-ink outline-none transition-colors focus:border-sage-600"
                          />
                        </label>
                      </>
                    )}
                  </div>

                  <div className="space-y-5" data-am-vb-region="availability-submit">
                    <label className="block">
                      <span className="mb-1.5 block text-sm font-medium text-sage-800">
                        Additional requirements
                      </span>
                      <textarea
                        rows={4}
                        name="message"
                        className="w-full resize-none rounded border border-sage-300 bg-white px-4 py-3 text-sm text-ink outline-none transition-colors placeholder:text-ink/60 focus:border-sage-600"
                        placeholder="Funding, dietary needs, settling-in questions or nursery preferences"
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
                        I am happy for {brand.short} to use these details to
                        respond to my availability enquiry.
                      </span>
                    </label>

                    {error && (
                      <p role="alert" className="text-sm font-medium text-red-700" data-am-vb-region="availability-states">
                        {error}
                      </p>
                    )}
                    <button
                      type="submit"
                      disabled={status === "sending"}
                      className="btn-primary w-full sm:w-auto"
                    >
                      {status === "sending"
                        ? "Sending..."
                        : "Check availability"}
                    </button>
                  </div>
                </form>
              )}
            </div>
          </Reveal>
        </div>
      </section>

      <section className="bg-white py-12" data-am-vb-region="availability-support">
        <div className="container-wide grid gap-5 md:grid-cols-3">
          {locations.map((location) => (
            <Reveal
              key={location.id}
              className="rounded-2xl border border-sage-100 bg-sage-50 p-6"
            >
              <h2 className="font-heading text-xl font-medium text-sage-800">
                {location.name}
              </h2>
              <p className="mt-2 text-sm leading-relaxed text-ink/85">
                {location.address}
              </p>
              <a
                href={`tel:${location.phone.replace(/\s/g, "")}`}
                className="mt-4 inline-flex items-center gap-2 font-semibold text-sage-700 hover:underline"
              >
                <Phone className="h-4 w-4" />
                {location.phone}
              </a>
            </Reveal>
          ))}
        </div>
        <p className="container-wide mt-8 text-center text-sm text-ink/75">
          You can also call{" "}
          <a
            href={`tel:${brand.phonePrimary.replace(/\s/g, "")}`}
            className="font-semibold text-sage-700 hover:underline"
          >
            {brand.phonePrimary}
          </a>{" "}
          during {brand.hours}.
        </p>
      </section>
    </>
  );
}
