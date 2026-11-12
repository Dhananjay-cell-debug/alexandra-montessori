import { FormGuard, SubmissionReceipt } from "../components/FormDelivery";
import { useRef, useState } from "react";
import { CircleCheckBig, FileText, ShieldCheck } from "lucide-react";
import Seo from "../components/Seo";
import Reveal from "../components/Reveal";
import PageHeader from "../components/PageHeader";
import FormField from "../components/FormField";
import DateField from "../components/DateField";
import PhoneField from "../components/PhoneField";
import { careers } from "../data/site";
import { brand } from "../lib/content";
import { emailError } from "../lib/validation";
import {
  createSubmissionKey,
  submitFormWithFile,
  submissionsEnabled,
} from "../lib/api";

const REQUIRED_MESSAGE = "Please complete this field.";
const CV_PATTERN = /\.(pdf|doc|docx)$/i;
const MAX_CV_BYTES = 5 * 1024 * 1024;

/** Yes/No radio pair, used throughout the safer-recruitment questions. */
function YesNo({ name, legend, required = false, error = "" }) {
  return (
    <fieldset
      className="min-w-0"
      aria-invalid={!!error || undefined}
      aria-describedby={error ? `${name}-error` : undefined}
    >
      <legend className="form-label">
        {legend} {required && <span aria-hidden="true">*</span>}
      </legend>
      <div className="flex min-h-12 flex-wrap items-center gap-5 rounded border border-sky-200 bg-white px-4 py-3">
        {[careers.formUi.yes, careers.formUi.no].map((option) => (
          <label
            key={option}
            className="inline-flex cursor-pointer items-center gap-2 text-sm font-medium text-ink/85"
          >
            <input
              type="radio"
              name={name}
              value={option.toLowerCase()}
              required={required}
              className="h-4 w-4 accent-sky-900"
            />
            {option}
          </label>
        ))}
      </div>
      {error && (
        <p id={`${name}-error`} role="alert" className="form-error">
          {error}
        </p>
      )}
    </fieldset>
  );
}

function FormSection({ number, title, intro, children }) {
  return (
    <fieldset className="form-section">
      <legend className="form-section-legend">
        <span aria-hidden="true">{String(number).padStart(2, "0")}</span>
        {title}
      </legend>
      {intro && <p className="form-section-intro">{intro}</p>}
      {children}
    </fieldset>
  );
}

function RefereeBlock({ number, errors }) {
  const prefix = `referee${number}`;
  return (
    <div className="rounded-4xl border border-sky-200 bg-sky-50/60 p-5 sm:p-6">
      <h4 className="font-heading text-xl font-semibold text-ink">
        Referee {number}
      </h4>
      <div className="mt-5 grid gap-5 sm:grid-cols-2">
        <FormField
          id={`${prefix}Name`}
          label="Full name"
          required
          error={errors[`${prefix}Name`]}
          autoComplete="name"
        />
        <FormField
          id={`${prefix}Relationship`}
          label="Position held and relationship to you"
          required
          error={errors[`${prefix}Relationship`]}
        />
        <FormField
          id={`${prefix}Organisation`}
          label="Organisation name"
          required
          error={errors[`${prefix}Organisation`]}
        />
        <FormField
          id={`${prefix}Phone`}
          label="Telephone number"
          type="tel"
          inputMode="tel"
          required
          error={errors[`${prefix}Phone`]}
          autoComplete="tel"
        />
        <FormField
          id={`${prefix}Address`}
          label="Organisation address"
          as="textarea"
          rows={3}
          required
          error={errors[`${prefix}Address`]}
          className="resize-y"
        />
        <FormField
          id={`${prefix}Email`}
          label="Email address"
          type="email"
          required
          error={errors[`${prefix}Email`]}
          autoComplete="email"
        />
        <div className="sm:col-span-2">
          <YesNo
            name={`${prefix}ContactBeforeInterview`}
            legend="May we contact this referee before an interview?"
            required
            error={errors[`${prefix}ContactBeforeInterview`]}
          />
        </div>
      </div>
    </div>
  );
}

function QualificationBlock({ number, errors, current = false }) {
  const prefix = current ? "currentStudy" : `qualification${number}`;
  return (
    <div className="rounded-4xl border border-sky-200 bg-white p-5">
      <p className="mb-4 text-sm font-bold uppercase tracking-[0.12em] text-sky-700">
        {current ? "Current study" : `Qualification ${number}`}
      </p>
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <FormField
          id={`${prefix}Institution`}
          label="School, college or university"
          error={errors[`${prefix}Institution`]}
          className="lg:min-w-[11rem]"
        />
        <FormField
          id={`${prefix}From`}
          label="From"
          type="month"
          error={errors[`${prefix}From`]}
        />
        <FormField
          id={`${prefix}To`}
          label="To"
          type="month"
          error={errors[`${prefix}To`]}
        />
        <FormField
          id={`${prefix}Course`}
          label="Course or qualification"
          error={errors[`${prefix}Course`]}
        />
        <FormField
          id={`${prefix}Grade`}
          label={current ? "Expected grade" : "Grade"}
          error={errors[`${prefix}Grade`]}
        />
      </div>
    </div>
  );
}

function EmploymentBlock({ number, errors }) {
  const prefix = `employment${number}`;
  return (
    <div className="rounded-4xl border border-sky-200 bg-white p-5">
      <p className="mb-4 text-sm font-bold uppercase tracking-[0.12em] text-sky-700">
        Previous role {number}
      </p>
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <FormField
          id={`${prefix}Post`}
          label="Post held"
          error={errors[`${prefix}Post`]}
        />
        <FormField
          id={`${prefix}From`}
          label="From"
          type="month"
          error={errors[`${prefix}From`]}
        />
        <FormField
          id={`${prefix}To`}
          label="To"
          type="month"
          error={errors[`${prefix}To`]}
        />
        <FormField
          id={`${prefix}Employer`}
          label="Employer name and address"
          error={errors[`${prefix}Employer`]}
        />
        <div className="sm:col-span-2 lg:col-span-4">
          <FormField
            id={`${prefix}ReasonLeaving`}
            label="Reason for leaving"
            error={errors[`${prefix}ReasonLeaving`]}
          />
        </div>
      </div>
    </div>
  );
}

/**
 * The full LDRN staff application: the paper form reproduced online, section
 * for section, so safer-recruitment questions are asked in the same order and
 * nothing an interviewer relies on is dropped.
 */
function JobApplicationForm() {
  const formRef = useRef(null);
  const phoneRef = useRef(null);
  const dobRef = useRef(null);
  const successRef = useRef(null);
  const submissionKey = useRef(createSubmissionKey());

  const [errors, setErrors] = useState({});
  const [status, setStatus] = useState("idle");
  const [receipt, setReceipt] = useState(null);
  const [serverError, setServerError] = useState("");
  const [dateOfBirth, setDateOfBirth] = useState("");

  const today = new Date().toISOString().slice(0, 10);
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

  const focusField = (field) => {
    requestAnimationFrame(() => {
      if (field === "mobilePhone") {
        phoneRef.current?.focus();
        return;
      }
      formRef.current?.querySelector(`[name="${field}"]`)?.focus();
    });
  };

  const handleSubmit = async (event) => {
    event.preventDefault();
    const form = event.currentTarget;
    const formData = new FormData(form);
    const found = {};
    const checkedRadioGroups = new Set();

    form.querySelectorAll("[required]").forEach((field) => {
      if (field.type === "radio") {
        // A radio group is one answer, so check the group once.
        if (checkedRadioGroups.has(field.name)) return;
        checkedRadioGroups.add(field.name);
        if (!form.querySelector(`input[name="${field.name}"]:checked`)) {
          found[field.name] = "Please choose Yes or No.";
        }
        return;
      }
      if (field.type === "checkbox" && !field.checked) {
        found[field.name] = "Please confirm this before continuing.";
        return;
      }
      if (field.type !== "file" && !String(field.value ?? "").trim()) {
        found[field.name] = REQUIRED_MESSAGE;
      }
    });

    ["email", "referee1Email", "referee2Email"].forEach((field) => {
      const message = emailError(formData.get(field), { required: true });
      if (message) found[field] = message;
    });

    if (!phoneRef.current?.validate()) {
      found.mobilePhone = "Please enter a valid mobile number.";
    }

    const cv = form.elements.cv?.files?.[0];
    if (!cv) {
      found.cv = "Please attach your CV.";
    } else if (!CV_PATTERN.test(cv.name)) {
      found.cv = "Please attach a PDF, DOC or DOCX file.";
    } else if (cv.size > MAX_CV_BYTES) {
      found.cv = "Your CV must be 5 MB or smaller.";
    }

    if (Object.keys(found).length) {
      const first = Object.keys(found)[0];
      setErrors(found);
      setStatus("error");
      focusField(first);
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
      const accepted = await submitFormWithFile("application", formData, submissionKey.current);
      setReceipt(accepted);
      setStatus("success");
      requestAnimationFrame(() => successRef.current?.focus());
    } catch (error) {
      setServerError(error.message);
      setStatus("error");
    }
  };

  return (
    <form
      ref={formRef}
      noValidate
      encType="multipart/form-data"
      onSubmit={handleSubmit}
      onChange={(event) => clearError(event.target.name)}
      className="job-application-form"
    >
      {!sendsToServer && (
        <div className="mb-8 flex items-start gap-3 rounded-4xl border border-sky-200 bg-sky-50 p-4 text-sm leading-relaxed text-ink/80 sm:p-5">
          <ShieldCheck
            className="mt-0.5 h-5 w-5 shrink-0 text-sky-700"
            strokeWidth={1.8}
            aria-hidden
          />
          <p>{careers.localOnlyNotice}</p>
        </div>
      )}

      {status === "error" && (
        <div
          role="alert"
          className="mb-8 rounded-4xl border border-red-300 bg-red-50 p-5 text-sm font-semibold text-red-800"
        >
          {serverError || careers.formUi.errorSummary}
        </div>
      )}

      <fieldset disabled={status === "success"}>
      <FormSection number={1} title={careers.formUi.sections.role}>
        <div className="grid gap-5 sm:grid-cols-2">
          <FormField
            id="position"
            label="Position applying for"
            required
            error={errors.position}
          />
          <FormField
            id="jobReference"
            label="Job reference number"
            error={errors.jobReference}
          />
          <FormField
            id="closingDate"
            label="Closing date"
            type="date"
            error={errors.closingDate}
          />
        </div>
      </FormSection>

      <FormSection number={2} title={careers.formUi.sections.personal}>
        <div className="grid gap-5 sm:grid-cols-2">
          <FormField
            id="forename"
            label="Forename"
            required
            error={errors.forename}
            autoComplete="given-name"
          />
          <FormField
            id="surname"
            label="Surname"
            required
            error={errors.surname}
            autoComplete="family-name"
          />
          <div className="sm:col-span-2">
            <FormField
              id="address"
              label="Home address"
              as="textarea"
              rows={4}
              required
              error={errors.address}
              autoComplete="street-address"
              className="resize-y"
            />
          </div>
          <FormField
            id="homePhone"
            label="Home number"
            type="tel"
            inputMode="tel"
            error={errors.homePhone}
            autoComplete="tel-national"
          />
          <PhoneField
            ref={phoneRef}
            id="mobilePhone"
            name="mobilePhone"
            label="Mobile number"
            onValidityChange={(valid) => {
              if (valid) clearError("mobilePhone");
            }}
          />
          <FormField
            id="workPhone"
            label="Work number"
            type="tel"
            inputMode="tel"
            error={errors.workPhone}
          />
          <YesNo
            name="mayCallAtWork"
            legend="May we call you at work?"
            error={errors.mayCallAtWork}
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
        </div>
      </FormSection>

      <FormSection
        number={3}
        title={careers.formUi.sections.references}
        intro={careers.formUi.refereeNote}
      >
        <div className="grid gap-6 lg:grid-cols-2">
          <RefereeBlock number={1} errors={errors} />
          <RefereeBlock number={2} errors={errors} />
        </div>
      </FormSection>

      <FormSection
        number={4}
        title={careers.formUi.sections.monitoring}
        intro="These questions come from the LDRN staff application and support safer recruitment, eligibility checks and equal-opportunities monitoring."
      >
        <div className="grid gap-5 sm:grid-cols-2">
          <FormField
            id="monitoringName"
            label="Full name"
            error={errors.monitoringName}
            autoComplete="name"
          />
          <DateField
            ref={dobRef}
            id="dateOfBirth"
            name="dateOfBirth"
            label="Date of birth"
            value={dateOfBirth}
            max={today}
            onChange={setDateOfBirth}
          />
          <FormField
            id="age"
            label="Age"
            type="number"
            min="16"
            max="100"
            error={errors.age}
          />
          <YesNo
            name="disabled"
            legend="Do you consider yourself to have a disability?"
            error={errors.disabled}
          />
          <div className="sm:col-span-2">
            <FormField
              id="interviewAdjustments"
              label="Special arrangements needed for interview or work"
              as="textarea"
              rows={3}
              error={errors.interviewAdjustments}
              className="resize-y"
            />
          </div>
          <FormField
            id="gender"
            label="Gender"
            as="select"
            options={careers.formUi.genders}
            error={errors.gender}
          />
          <FormField
            id="ethnicity"
            label="Ethnic background"
            as="select"
            options={careers.formUi.ethnicities}
            error={errors.ethnicity}
          />
          <YesNo
            name="convictions"
            legend="Do you have any convictions, cautions or reprimands?"
            required
            error={errors.convictions}
          />
          <YesNo
            name="barredList"
            legend="Are you included on a DBS barred list?"
            required
            error={errors.barredList}
          />
          <div className="sm:col-span-2">
            <FormField
              id="safeguardingDetails"
              label="If yes to either question, give details and dates"
              as="textarea"
              rows={4}
              error={errors.safeguardingDetails}
              className="resize-y"
            />
          </div>
          <YesNo
            name="workPermit"
            legend="Do you hold a current work permit where required?"
            required
            error={errors.workPermit}
          />
          <FormField
            id="nationalInsurance"
            label="National Insurance number"
            error={errors.nationalInsurance}
            autoComplete="off"
          />
          <FormField
            id="vacancySource"
            label="Where did you hear about this vacancy?"
            error={errors.vacancySource}
          />
          <div className="flex items-start gap-3 rounded-4xl border border-sky-200 bg-white p-4 sm:col-span-2">
            <input
              id="equalOpportunitiesConsent"
              name="equalOpportunitiesConsent"
              type="checkbox"
              className="mt-1 h-4 w-4 shrink-0 accent-sky-900"
            />
            <label
              htmlFor="equalOpportunitiesConsent"
              className="text-sm leading-relaxed text-ink/80"
            >
              I consent to Little Rainbows using the monitoring information in
              this section for equal-opportunities reporting.
            </label>
          </div>
        </div>
      </FormSection>

      <FormSection
        number={5}
        title={careers.formUi.sections.qualifications}
        intro={careers.formUi.qualificationNote}
      >
        <div className="space-y-5">
          {[1, 2, 3].map((number) => (
            <QualificationBlock key={number} number={number} errors={errors} />
          ))}
          <QualificationBlock current errors={errors} />
          <FormField
            id="professionalQualifications"
            label="Professional qualifications, apprenticeships and memberships"
            as="textarea"
            rows={4}
            error={errors.professionalQualifications}
            className="resize-y"
          />
          <FormField
            id="otherTraining"
            label="Other relevant training"
            as="textarea"
            rows={4}
            error={errors.otherTraining}
            className="resize-y"
          />
        </div>
      </FormSection>

      <FormSection
        number={6}
        title={careers.formUi.sections.employment}
        intro={careers.formUi.employmentNote}
      >
        <div className="rounded-4xl border border-sky-200 bg-sky-50/60 p-5 sm:p-6">
          <h4 className="font-heading text-xl font-semibold text-ink">
            Current or most recent employer
          </h4>
          <div className="mt-5 grid gap-5 sm:grid-cols-2">
            <FormField
              id="currentEmployer"
              label="Employer name and address"
              as="textarea"
              rows={3}
              error={errors.currentEmployer}
              className="resize-y"
            />
            <FormField
              id="currentJobTitle"
              label="Job title"
              error={errors.currentJobTitle}
            />
            <FormField
              id="currentStartDate"
              label="Date started"
              type="date"
              max={today}
              error={errors.currentStartDate}
            />
            <FormField
              id="currentUntilDate"
              label="Until"
              type="date"
              error={errors.currentUntilDate}
            />
            <FormField
              id="noticePeriod"
              label="Notice period"
              error={errors.noticePeriod}
            />
            <FormField id="salary" label="Salary" error={errors.salary} />
            <div className="sm:col-span-2">
              <FormField
                id="currentDuties"
                label="Main duties"
                as="textarea"
                rows={4}
                error={errors.currentDuties}
                className="resize-y"
              />
            </div>
            <div className="sm:col-span-2">
              <FormField
                id="currentReasonLeaving"
                label="Reason for leaving or wishing to leave"
                error={errors.currentReasonLeaving}
              />
            </div>
          </div>
        </div>

        <div className="mt-6 space-y-5">
          {[1, 2, 3, 4].map((number) => (
            <EmploymentBlock key={number} number={number} errors={errors} />
          ))}
          {/* Required: safer recruitment depends on gaps being explained. */}
          <FormField
            id="employmentGaps"
            label="Complete employment history and explanation of any gaps"
            as="textarea"
            rows={5}
            required
            error={errors.employmentGaps}
            className="resize-y"
          />
          <FormField
            id="interestsAndVoluntaryWork"
            label="Other interests and voluntary organisations"
            as="textarea"
            rows={4}
            error={errors.interestsAndVoluntaryWork}
            className="resize-y"
          />
        </div>
      </FormSection>

      <FormSection number={7} title={careers.formUi.sections.other}>
        <div className="grid gap-5 sm:grid-cols-2">
          <YesNo
            name="drivingLicence"
            legend="Do you hold a current driving licence?"
            error={errors.drivingLicence}
          />
          <YesNo
            name="vehicleAccess"
            legend="Do you have access to a vehicle?"
            error={errors.vehicleAccess}
          />
          <YesNo
            name="publicTransport"
            legend="Can you use public transport for work where needed?"
            error={errors.publicTransport}
          />
          <YesNo
            name="relatedToEmployee"
            legend="Are you related to anyone working at the provision?"
            error={errors.relatedToEmployee}
          />
          <div className="sm:col-span-2">
            <FormField
              id="employeeRelationshipDetails"
              label="If yes, give their name and your relationship"
              error={errors.employeeRelationshipDetails}
            />
          </div>
        </div>
      </FormSection>

      <FormSection
        number={8}
        title={careers.formUi.sections.statement}
        intro="Use the role description and person specification to explain why you are suitable."
      >
        <FormField
          id="supportingStatement"
          label="Your supporting statement"
          as="textarea"
          rows={12}
          required
          error={errors.supportingStatement}
          className="resize-y"
        />
      </FormSection>

      <FormSection number={9} title={careers.formUi.sections.declaration}>
        <div className="rounded-4xl border border-sky-200 bg-sky-50/60 p-5 text-sm leading-relaxed text-ink/80 sm:p-6">
          <p className="font-semibold text-ink">
            By completing this section, you confirm that:
          </p>
          <ul className="mt-3 list-disc space-y-2 pl-5">
            <li>The information in this application is complete and correct.</li>
            <li>
              You understand that false or omitted information may lead to an
              offer being withdrawn or employment ending.
            </li>
            <li>
              Little Rainbows may process the information supplied for the
              recruitment process.
            </li>
          </ul>
        </div>

        <div className="mt-6 grid gap-5 sm:grid-cols-2">
          <FormField
            id="declarationName"
            label="Type your full name as your signature"
            required
            error={errors.declarationName}
            autoComplete="name"
          />
          <FormField
            id="declarationDate"
            label="Declaration date"
            type="date"
            max={today}
            required
            error={errors.declarationDate}
          />
          <div className="sm:col-span-2">
            <FormField
              id="cv"
              label="Attach your CV"
              type="file"
              accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
              required
              error={errors.cv}
              hint={careers.formUi.cvHelp}
              className="file:mr-4 file:rounded file:border-0 file:bg-sky-100 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-sky-900 hover:file:bg-sky-200"
            />
          </div>
          <div className="flex items-start gap-3 rounded-4xl border border-sky-200 bg-white p-4 sm:col-span-2">
            <input
              id="declarationAccepted"
              name="declarationAccepted"
              type="checkbox"
              required
              aria-invalid={!!errors.declarationAccepted || undefined}
              aria-describedby={
                errors.declarationAccepted
                  ? "declarationAccepted-error"
                  : undefined
              }
              className="mt-1 h-4 w-4 shrink-0 accent-sky-900"
            />
            <div>
              <label
                htmlFor="declarationAccepted"
                className="text-sm font-semibold leading-relaxed text-ink"
              >
                I confirm the declaration above.{" "}
                <span aria-hidden="true">*</span>
              </label>
              {errors.declarationAccepted && (
                <p
                  id="declarationAccepted-error"
                  role="alert"
                  className="form-error"
                >
                  {errors.declarationAccepted}
                </p>
              )}
            </div>
          </div>
        </div>
      </FormSection>

      <div className="mt-8 flex flex-col items-start gap-4 border-t border-sky-200 pt-8 sm:flex-row sm:items-center sm:justify-between">
        <p className="max-w-xl text-sm leading-relaxed text-ink/65">
          Need help? Call {careers.phone} or email {brand.email}.
        </p>
        <button
          type="submit"
          className="btn-primary shrink-0"
          disabled={status === "submitting"}
        >
          <CircleCheckBig className="h-4 w-4" strokeWidth={1.8} aria-hidden />
          {status === "submitting" ? "Sending…" : careers.formUi.submitLabel}
        </button>
      </div>

      </fieldset>
      {status === "success" && (
        <div
          ref={successRef}
          tabIndex={-1}
          role="status"
          className="mt-8 rounded-4xl border border-green-300 bg-green-50 p-6 outline-none focus-visible:ring-2 focus-visible:ring-sky-500"
        >
          <div className="flex items-start gap-3">
            <CircleCheckBig
              className="mt-0.5 h-6 w-6 shrink-0 text-green-700"
              strokeWidth={1.8}
              aria-hidden
            />
            <div>
              <h3 className="text-xl">
                {sendsToServer
                  ? "Your application has been received"
                  : careers.formUi.successHeading}
              </h3>
              <p className="mt-2 text-sm leading-relaxed text-ink/80">
                {sendsToServer
                  ? "The Little Rainbows team will be in touch about your application."
                  : careers.formUi.successText}
              </p>
              <SubmissionReceipt receipt={receipt} />
              <a href={careers.form} download className="btn-outline mt-5">
                <FileText className="h-4 w-4" strokeWidth={1.8} aria-hidden />
                Download the paper form
              </a>
            </div>
          </div>
        </div>
      )}
      <FormGuard />
    </form>
  );
}

export default function Apply() {
  return (
    <>
      <Seo path="/careers/apply" />
      <PageHeader title="Apply" tone="canvas" />

      <section data-ldrn-ws="apply.section-1" className="bg-white py-16 sm:py-20">
        <div className="container-wide">
          <div className="mx-auto mb-10 max-w-3xl text-center">
            <h2>{careers.formHeading}</h2>
            <p className="mt-4 text-ink/85">{careers.formIntro}</p>
          </div>
          <Reveal className="mx-auto max-w-5xl">
            <JobApplicationForm />
          </Reveal>
        </div>
      </section>
    </>
  );
}
