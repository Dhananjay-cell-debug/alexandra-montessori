import { FormGuard, SubmissionReceipt } from "../components/FormDelivery";
import { useRef, useState } from "react";
import { Link } from "react-router-dom";
import {
  ArrowRight,
  CircleCheckBig,
  FileText,
  Mail,
  Plus,
  ShieldCheck,
} from "lucide-react";
import Seo from "../components/Seo";
import Reveal from "../components/Reveal";
import Img from "../components/Img";
import Icon from "../components/Icon";
import PageHeader from "../components/PageHeader";
import FormField from "../components/FormField";
import PhoneField from "../components/PhoneField";
import GalleryModal from "../components/GalleryModal";
import { allPhotos, approach, careers, heroPhotos } from "../data/site";
import { brand, settings } from "../lib/content";
import { emailError } from "../lib/validation";
import {
  createSubmissionKey,
  submitFormWithFile,
  submissionsEnabled,
} from "../lib/api";

const MAX_CV_BYTES = 5 * 1024 * 1024;

const SETTING_OPTIONS = [
  { value: "any", label: "Any / not sure yet" },
  ...settings.map((setting) => ({ value: setting.slug, label: setting.name })),
];

function QuickApplyForm() {
  const formRef = useRef(null);
  const phoneRef = useRef(null);
  const successRef = useRef(null);
  const submissionKey = useRef(createSubmissionKey());
  const [errors, setErrors] = useState({});
  const [status, setStatus] = useState("idle");
  const [receipt, setReceipt] = useState(null);
  const [serverError, setServerError] = useState("");
  const [fileName, setFileName] = useState("");

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

  const handleSubmit = async (event) => {
    event.preventDefault();
    const form = event.currentTarget;
    const formData = new FormData(form);
    const found = {};

    form.querySelectorAll("[required]").forEach((field) => {
      if (field.type === "checkbox" && !field.checked) {
        found[field.name] = "Please confirm this before continuing.";
      } else if (field.type === "file" && !field.files?.length) {
        found[field.name] = "Please attach your CV or résumé.";
      } else if (field.type !== "file" && !String(field.value ?? "").trim()) {
        found[field.name] = "Please complete this field.";
      }
    });

    const email = emailError(formData.get("email"), { required: true });
    if (email) found.email = email;
    if (!phoneRef.current?.validate()) {
      found.phone = "Please check your phone number.";
    }

    // Type and size are checked here for a fast answer, and again by
    // WordPress, which is the check that actually protects the server.
    const resume = form.elements.resume?.files?.[0];
    if (resume) {
      if (!/\.(pdf|doc|docx)$/i.test(resume.name)) {
        found.resume = "Use a PDF, DOC or DOCX file.";
      } else if (resume.size > MAX_CV_BYTES) {
        found.resume = "The file must be 5 MB or smaller.";
      }
    }

    if (Object.keys(found).length) {
      const first = Object.keys(found)[0];
      setErrors(found);
      setStatus("error");
      requestAnimationFrame(() => {
        if (first === "phone") phoneRef.current?.focus();
        else form.elements.namedItem(first)?.focus?.();
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
      const accepted = await submitFormWithFile(
        "quick-application",
        formData,
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
        className="mx-auto max-w-2xl border border-green-300 bg-green-50 p-8 text-center outline-none sm:p-10"
      >
        <CircleCheckBig className="mx-auto h-12 w-12 text-green-700" />
        <h3 className="mt-5 text-2xl">
          {sendsToServer
            ? "Thank you — your application has been received"
            : "Your application is ready"}
        </h3>
        <p className="mx-auto mt-3 max-w-lg text-sm leading-relaxed text-ink/80">
          {sendsToServer
            ? "The Little Rainbows team will be in touch about your application."
            : careers.localOnlyNotice}
        </p>
        <div className="mt-6 flex flex-wrap justify-center gap-3">
          <a href={`mailto:${brand.email}`} className="btn-primary">
            <Mail className="h-4 w-4" />
            Email Little Rainbows
          </a>
          <button
            type="button"
            onClick={() => {
              formRef.current?.reset();
              phoneRef.current?.reset();
              setFileName("");
              submissionKey.current = createSubmissionKey();
              setStatus("idle");
            }}
            className="btn-outline"
          >
            Start again
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
      onSubmit={handleSubmit}
      onChange={(event) => clearError(event.target.name)}
      className="mx-auto max-w-3xl space-y-5"
    >
      {!sendsToServer && (
        <div className="flex items-start gap-3 border border-sky-200 bg-sky-50 p-4 text-sm leading-relaxed text-ink/80">
          <ShieldCheck className="mt-0.5 h-5 w-5 shrink-0 text-sky-700" />
          <p>{careers.localOnlyNotice}</p>
        </div>
      )}

      {status === "error" && (
        <p
          className="border border-red-300 bg-red-50 p-4 text-sm font-semibold text-red-800"
          role="alert"
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
          required
          error={errors.lastName}
          autoComplete="family-name"
        />
        <FormField
          id="email"
          label="Email"
          type="email"
          required
          error={errors.email}
          autoComplete="email"
        />
        <PhoneField ref={phoneRef} id="careerPhone" name="phone" label="Phone" required />
        <FormField
          id="qualification"
          label="Qualification level"
          required
          error={errors.qualification}
        />
        <FormField
          id="position"
          label="Position"
          required
          error={errors.position}
        />
        <div className="sm:col-span-2">
          <FormField
            id="setting"
            label="Which setting are you applying to?"
            as="select"
            options={SETTING_OPTIONS}
          />
        </div>
      </div>

      <FormField
        id="about"
        label="Message"
        as="textarea"
        rows={5}
        className="resize-y"
      />

      <div>
        <label htmlFor="resume" className="form-label">
          Attach CV / Résumé <span aria-hidden="true">*</span>
        </label>
        {/* The native file input is visually hidden so the drop zone can be
            styled, but it stays a real, focusable, labelled input. */}
        <label className="flex cursor-pointer items-center justify-between gap-3 border border-dashed border-sky-300 bg-white px-4 py-3 text-sm text-ink/80 hover:border-sky-600">
          <span className="min-w-0 truncate">
            {fileName || "Upload PDF or Word document"}
          </span>
          <FileText className="h-5 w-5 shrink-0 text-sky-700" />
          <input
            id="resume"
            name="resume"
            type="file"
            required
            accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
            className="sr-only"
            onChange={(event) => {
              setFileName(event.target.files?.[0]?.name || "");
              clearError("resume");
            }}
          />
        </label>
        <p className="form-hint">{careers.formUi.cvHelp}</p>
        {errors.resume && (
          <p className="form-error" role="alert">
            {errors.resume}
          </p>
        )}
      </div>

      <div className="flex items-start gap-3 border border-sky-200 bg-white p-4">
        <input
          id="careerConsent"
          name="careerConsent"
          type="checkbox"
          required
          className="mt-1 h-4 w-4 accent-sky-900"
        />
        <div>
          <label
            htmlFor="careerConsent"
            className="text-sm leading-relaxed text-ink/80"
          >
            I am happy for Little Rainbows to use these details to process my
            application, in line with the{" "}
            <Link to="/privacy" className="font-semibold text-sky-800 underline">
              privacy policy
            </Link>
            . <span aria-hidden="true">*</span>
          </label>
          {errors.careerConsent && (
            <p className="form-error" role="alert">
              {errors.careerConsent}
            </p>
          )}
        </div>
      </div>

      <button
        type="submit"
        className="btn-primary w-full"
        disabled={status === "submitting"}
      >
        {status === "submitting" ? "Sending…" : "Submit"}
      </button>
      <FormGuard />
    </form>
  );
}

const WHY_WORK_HERE = [
  {
    icon: "Sprout",
    title: "A rewarding childcare career",
    text: careers.body,
  },
  {
    icon: "Users",
    title: "A passionate team",
    text: "Join our passionate team and help shape the future of our little rainbows.",
  },
  { icon: "BookOpen", title: approach.title, text: approach.body },
];

export default function Careers() {
  const [gallery, setGallery] = useState({ open: false, index: null });

  return (
    <>
      <Seo path="/careers" />
      <PageHeader title="Careers" tone="canvas" />

      <section data-ldrn-ws="careers.section-1" className="bg-white pb-10">
        <div className="container-wide text-center">
          <Reveal>
            <Link to="/careers/vacancies" className="btn-outline group">
              Check out our current vacancies
              <span className="flex h-6 w-6 items-center justify-center rounded-full bg-sky-900 text-white transition-transform group-hover:translate-x-0.5">
                <ArrowRight className="h-3.5 w-3.5" />
              </span>
            </Link>
          </Reveal>
          <Reveal
            as="p"
            delay={100}
            className="mx-auto mt-6 max-w-2xl text-lg font-semibold text-ink"
          >
            {careers.applicationIntro}
          </Reveal>
        </div>
      </section>

      <section data-ldrn-ws="careers.section-2" id="apply" className="scroll-mt-28 bg-white pb-16">
        <div className="container-wide">
          <Reveal>
            <QuickApplyForm />
          </Reveal>
        </div>
      </section>

      <section data-ldrn-ws="careers.section-3" className="bg-band py-16 sm:py-20">
        <div className="container-wide text-center">
          <h2>Why work with us?</h2>
          <div className="mt-12 grid gap-10 sm:grid-cols-3">
            {WHY_WORK_HERE.map((item, index) => (
              <Reveal
                key={item.title}
                delay={index * 90}
                className="flex flex-col items-center"
              >
                <Icon name={item.icon} className="h-16 w-16 text-sky-800" />
                <h3 className="mt-4 text-xl">{item.title}</h3>
                <p className="mt-2 max-w-xs text-sm leading-relaxed text-ink/85">
                  {item.text}
                </p>
              </Reveal>
            ))}
          </div>
        </div>
      </section>

      <section data-ldrn-ws="careers.section-4" className="bg-white py-16 sm:py-20">
        <div className="container-wide">
          <h2 className="text-center">View Gallery</h2>
          <div className="mt-10 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
            {heroPhotos.slice(0, 4).map((photo, index) => (
              <Reveal key={photo} delay={index * 60}>
                <button
                  type="button"
                  onClick={() => setGallery({ open: true, index })}
                  className="group block w-full overflow-hidden"
                  aria-label={`Open Little Rainbows photo ${index + 1}`}
                >
                  <Img
                    src={photo}
                    alt="Life and learning at Little Rainbows"
                    shape="square"
                    className="aspect-square w-full transition-transform duration-500 group-hover:scale-105"
                  />
                </button>
              </Reveal>
            ))}
            <Reveal delay={240}>
              <button
                type="button"
                onClick={() => setGallery({ open: true, index: null })}
                className="group relative block w-full overflow-hidden"
                aria-label="View more Little Rainbows photos"
              >
                <Img
                  src={heroPhotos[4]}
                  alt="More photos from Little Rainbows"
                  shape="square"
                  className="aspect-square w-full"
                />
                <span className="absolute inset-0 z-[2] flex flex-col items-center justify-center gap-1.5 bg-sky-900/55 text-white transition-colors group-hover:bg-sky-900/65">
                  <Plus className="h-8 w-8" />
                  <span className="text-sm font-semibold tracking-wide">
                    View more
                  </span>
                </span>
              </button>
            </Reveal>
          </div>
        </div>
      </section>

      <section data-ldrn-ws="careers.section-5" className="bg-band py-16 sm:py-20">
        <div className="container-wide text-center">
          <h2>What We Are Looking For?</h2>
          <div className="mt-12 grid gap-10 sm:grid-cols-3">
            {careers.beforeApplying.map((item, index) => (
              <Reveal
                key={item.title}
                delay={index * 90}
                className="flex flex-col items-center"
              >
                <Icon name={item.icon} className="h-16 w-16 text-sky-800" />
                <h3 className="mt-4 text-xl">{item.title}</h3>
                <p className="mt-2 max-w-xs text-sm leading-relaxed text-ink/85">
                  {item.text}
                </p>
              </Reveal>
            ))}
          </div>
        </div>
      </section>

      <section data-ldrn-ws="careers.section-6" className="bg-band py-16 text-center">
        <div className="container-wide">
          <h2>Ready to start your career at Little Rainbows?</h2>
          <a href="#apply" className="btn-primary mt-7">
            Start your application
          </a>
        </div>
      </section>

      {gallery.open && (
        // Remounting on index change lets the modal open straight onto the
        // chosen photo rather than resetting to the grid.
        <GalleryModal
          key={gallery.index ?? "grid"}
          images={allPhotos}
          open={gallery.open}
          initialIndex={gallery.index}
          onClose={() => setGallery({ open: false, index: null })}
        />
      )}
    </>
  );
}
