import { FormGuard, SubmissionReceipt } from "../components/FormDelivery";
import { useRef, useState } from "react";
import { Link } from "react-router-dom";
import { CircleCheckBig, Clock, Mail, Phone, ShieldCheck } from "lucide-react";
import Seo from "../components/Seo";
import Reveal from "../components/Reveal";
import PageHeader from "../components/PageHeader";
import FormField from "../components/FormField";
import PhoneField from "../components/PhoneField";
import Pending from "../components/Pending";
import TbcBadge from "../components/TbcBadge";
import { contact } from "../data/site";
import { allSettings, brand } from "../lib/content";
import { isTbc } from "../lib/tbc";
import { emailError } from "../lib/validation";
import { createSubmissionKey, submitForm, submissionsEnabled } from "../lib/api";

// The visitor picks a setting by slug; WordPress resolves that to an approved
// recipient. No email address is ever chosen in the browser.
const SETTING_OPTIONS = allSettings.map((setting) => ({
  value: setting.slug,
  label:
    setting.active === false
      ? `${setting.name} — current availability unverified`
      : setting.name,
}));

function ContactForm() {
  const formRef = useRef(null);
  const phoneRef = useRef(null);
  const successRef = useRef(null);
  const submissionKey = useRef(createSubmissionKey());
  const [errors, setErrors] = useState({});
  const [status, setStatus] = useState("idle");
  const [receipt, setReceipt] = useState(null);
  const [serverError, setServerError] = useState("");

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

  /** Client-side pass. WordPress repeats all of this server-side. */
  const collectErrors = (form, formData) => {
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

    return found;
  };

  const focusFirstError = (form, found) => {
    const first = Object.keys(found)[0];
    requestAnimationFrame(() => {
      if (first === "phone") {
        phoneRef.current?.focus();
      } else {
        form.querySelector(`[name="${first}"]`)?.focus();
      }
    });
  };

  const handleSubmit = async (event) => {
    event.preventDefault();
    const form = event.currentTarget;
    const formData = new FormData(form);

    const found = collectErrors(form, formData);
    if (Object.keys(found).length) {
      setErrors(found);
      setStatus("error");
      focusFirstError(form, found);
      return;
    }

    setErrors({});
    setServerError("");

    // Until the nursery activates server-side submissions this form only
    // checks the answers on this device; nothing is sent or stored.
    if (!sendsToServer) {
      setStatus("success");
      requestAnimationFrame(() => successRef.current?.focus());
      return;
    }

    setStatus("submitting");
    try {
      const accepted = await submitForm(
        "enquiry",
        Object.fromEntries(formData.entries()),
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
        className="rounded-4xl border border-green-300 bg-green-50 p-7 text-center outline-none focus-visible:ring-2 focus-visible:ring-sky-500 sm:p-10"
      >
        <CircleCheckBig
          className="mx-auto h-12 w-12 text-green-700"
          strokeWidth={1.7}
          aria-hidden
        />
        <h3 className="mt-5 text-2xl">
          {sendsToServer ? "Thank you — your message has been received" : contact.successHeading}
        </h3>
        <p className="mx-auto mt-3 max-w-lg text-sm leading-relaxed text-ink/80">
          {sendsToServer
            ? "The Little Rainbows team will get back to you as soon as they can."
            : contact.successText}
        </p>
        <div className="mt-6 flex flex-wrap justify-center gap-3">
          <a href={`mailto:${brand.email}`} className="btn-primary">
            <Mail className="h-4 w-4" strokeWidth={1.8} aria-hidden />
            Email Little Rainbows
          </a>
          <button
            type="button"
            onClick={() => {
              formRef.current?.reset();
              phoneRef.current?.reset();
              submissionKey.current = createSubmissionKey();
              setStatus("idle");
            }}
            className="btn-outline"
          >
            Write another message
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
      aria-label="Contact Little Rainbows Day Nursery"
      onSubmit={handleSubmit}
      onChange={(event) => clearError(event.target.name)}
      className="space-y-5"
    >
      {!sendsToServer && (
        <div className="flex items-start gap-3 rounded-4xl border border-sky-200 bg-sky-50 p-4 text-sm leading-relaxed text-ink/80">
          <ShieldCheck
            className="mt-0.5 h-5 w-5 shrink-0 text-sky-700"
            strokeWidth={1.8}
            aria-hidden
          />
          <p>{contact.localOnlyNotice}</p>
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
          id="firstName"
          label="First name"
          required
          error={errors.firstName}
          autoComplete="given-name"
        />
        <FormField
          id="lastName"
          label="Last name"
          error={errors.lastName}
          autoComplete="family-name"
        />
        <FormField
          id="email"
          label="Email address"
          type="email"
          required
          error={errors.email}
          autoComplete="email"
        />
        <PhoneField
          ref={phoneRef}
          id="contactPhone"
          name="phone"
          label="Phone number"
          required={false}
          onValidityChange={(valid) => {
            if (valid) clearError("phone");
          }}
        />
      </div>

      <FormField
        id="setting"
        label="Preferred setting"
        as="select"
        options={SETTING_OPTIONS}
        required
        error={errors.setting}
      />

      <FormField
        id="message"
        label="Message"
        as="textarea"
        rows={6}
        required
        error={errors.message}
        className="resize-y"
      />

      <div className="flex items-start gap-3 rounded-4xl border border-sky-200 bg-white p-4">
        <input
          id="contactConsent"
          name="contactConsent"
          type="checkbox"
          required
          aria-invalid={!!errors.contactConsent || undefined}
          aria-describedby={
            errors.contactConsent ? "contactConsent-error" : undefined
          }
          className="mt-1 h-4 w-4 shrink-0 accent-sky-900"
        />
        <div>
          <label
            htmlFor="contactConsent"
            className="text-sm leading-relaxed text-ink/80"
          >
            I am happy for Little Rainbows to use these details to respond to my
            enquiry, in line with the{" "}
            <Link to="/privacy" className="font-semibold text-sky-800 underline">
              privacy policy
            </Link>
            . <span aria-hidden="true">*</span>
          </label>
          {errors.contactConsent && (
            <p id="contactConsent-error" role="alert" className="form-error">
              {errors.contactConsent}
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
          {status === "submitting" ? "Sending…" : "Submit enquiry"}
        </button>
      </div>
      <FormGuard />
    </form>
  );
}

export default function Contact() {
  return (
    <>
      <Seo path="/contact" />
      <PageHeader title="Contact us" tone="canvas" />

      <section data-ldrn-ws="contact.section-1" className="bg-white pb-10 pt-2">
        <div className="container-wide">
          <div className="grid gap-4 md:grid-cols-3">
            {allSettings.map((setting, index) => {
              const address = isTbc(setting.address)
                ? null
                : setting.address.join(", ");
              // Fall back to the central number rather than showing nothing.
              const phone = isTbc(setting.phone)
                ? brand.phone.general
                : setting.phone;
              const ages = isTbc(setting.ages) ? null : setting.ages;

              return (
                <Reveal
                  key={setting.slug}
                  delay={index * 80}
                  className="rounded-4xl border border-sky-100 bg-sky-50 p-6 shadow-soft"
                >
                  <h2 className="text-xl">{setting.name}</h2>
                  {address ? (
                    <p className="mt-2 text-sm leading-relaxed text-ink/85">
                      {address}
                    </p>
                  ) : (
                    <Pending className="mt-2" />
                  )}
                  <a
                    href={`tel:${phone.replace(/\s/g, "")}`}
                    className="mt-4 block font-semibold text-sky-800 hover:underline"
                  >
                    {phone}
                  </a>
                  <a
                    href={`mailto:${setting.email || brand.email}`}
                    className="mt-1 block break-all text-sm font-semibold text-sky-800 hover:underline"
                  >
                    {setting.email || brand.email}
                  </a>
                  {ages && (
                    <p className="mt-3 text-xs font-semibold uppercase tracking-[0.16em] text-ink/70">
                      Ages {ages}
                    </p>
                  )}
                  {setting.hours && !isTbc(setting.hours) && (
                    <p className="mt-2 flex items-center gap-2 text-xs text-ink/70">
                      <Clock className="h-3.5 w-3.5" /> {setting.hours}
                    </p>
                  )}
                  {setting.statusNote && (
                    <p className="mt-3 text-xs leading-relaxed text-ink/55">
                      {setting.statusNote}
                    </p>
                  )}
                  <span className="mt-3 flex flex-wrap gap-1.5">
                    <TbcBadge value={setting.address} />
                    <TbcBadge value={setting.phone} />
                    <TbcBadge value={setting.ages} />
                    <TbcBadge value={setting.hours} />
                  </span>
                </Reveal>
              );
            })}
          </div>
        </div>
      </section>

      <section data-ldrn-ws="contact.section-2" className="bg-white py-12 sm:py-16">
        <div className="container-wide">
          <div className="text-center">
            <Reveal as="h2">{contact.formHeading}</Reveal>
            <Reveal
              as="p"
              delay={100}
              className="mx-auto mt-3 max-w-xl text-sm text-ink/85"
            >
              {contact.formIntro}
            </Reveal>
          </div>

          <Reveal delay={150} className="mx-auto mt-10 max-w-3xl">
            <ContactForm />
          </Reveal>

          <div className="mx-auto mt-8 flex max-w-3xl flex-wrap justify-center gap-x-6 gap-y-3 text-sm">
            <a
              href={`tel:${brand.phone.general.replace(/\s/g, "")}`}
              className="inline-flex items-center gap-2 font-semibold text-sky-800 hover:underline"
            >
              <Phone className="h-4 w-4" /> {brand.phone.general}
            </a>
            <a
              href={`mailto:${brand.email}`}
              className="inline-flex items-center gap-2 font-semibold text-sky-800 hover:underline"
            >
              <Mail className="h-4 w-4" /> {brand.email}
            </a>
          </div>
        </div>
      </section>
    </>
  );
}
