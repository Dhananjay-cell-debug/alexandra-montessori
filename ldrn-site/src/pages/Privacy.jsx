import { Mail } from "lucide-react";
import Seo from "../components/Seo";
import Reveal from "../components/Reveal";
import Section from "../components/Section";
import PageHeader from "../components/PageHeader";
import Pending from "../components/Pending";
import TbcBadge from "../components/TbcBadge";
import { privacy } from "../data/site";
import { brand } from "../lib/content";

// What the finished policy will cover. Published as an outline so visitors can
// see the scope while the legal detail is confirmed.
const SECTIONS = [
  {
    title: "Who we are",
    text: "The registered entity acting as data controller, its company number and registered office.",
  },
  {
    title: "What we collect",
    text: "Only what you type into our forms:",
    items: [
      ["Parents and carers", "name, email address, phone number, preferred nursery and your message."],
      ["Your child", "name, age, the start and end dates, days and session times you need, and anything else you choose to tell us (such as funding or dietary needs)."],
      ["Job applicants", "contact details, date of birth, National Insurance number, work history, qualifications, referees, CV, and answers about right to work and criminal records. Equal-opportunities answers are saved only if you choose to give them."],
    ],
  },
  {
    title: "Why we collect it, and our lawful basis",
    text: "The purpose behind each type of data, and the UK GDPR basis relied on for it.",
  },
  {
    title: "How long we keep it",
    text: "Retention periods, including the statutory periods that apply to early-years records.",
  },
  {
    title: "Who we share it with",
    text: "Local authorities, Ofsted, safeguarding partners and any processors used.",
  },
  {
    title: "Your rights",
    text: "Access, rectification, erasure, restriction, portability and objection — and how to exercise them.",
  },
  {
    title: "Cookies",
    text: "Any cookies the website sets. At present it sets none.",
  },
  {
    title: "How to complain",
    text: "Contact details for us, and for the Information Commissioner's Office.",
  },
];

/**
 * The policy is noindex until it is in force: an incomplete privacy notice
 * should not be the page a search engine surfaces, and no form may store
 * personal data until this is published.
 */
export default function Privacy() {
  return (
    <>
      <Seo path="/privacy" noindex />
      <PageHeader
        eyebrow="Legal"
        title={privacy.title}
        intro="This page explains how online form submissions are handled. The nursery is finalising its wider privacy policy."
      />

      <Section tone="canvas" className="!pt-8 sm:!pt-10 lg:!pt-12">
        <div className="mx-auto max-w-2xl">
          <Pending className="!text-base">
            This policy is being finalised with the nursery and is not yet in
            force.
          </Pending>
          <div className="mt-4 flex flex-wrap gap-1.5">
            <TbcBadge value={privacy.note} />
          </div>

          <div className="mt-8 rounded-4xl border border-sky-200 bg-white p-6">
            <h3>Online form submissions</h3>
            <p className="mt-3 leading-relaxed">
              Contact messages, availability requests and job applications are saved in a private WordPress submissions inbox so they can be reviewed and answered. Notifications are currently delivered through Gmail to the website manager during setup. Replies use the contact address you provide.
            </p>
            <p className="mt-3 leading-relaxed">
              CVs are stored outside the public website and can be downloaded only by authorised staff. Detailed recruitment answers are available in the private inbox; sensitive monitoring answers are excluded from email notifications. Optional equal-opportunities answers are saved only when you tick the monitoring consent box.
            </p>
            <p className="mt-3 leading-relaxed">
              Spam prevention uses a hidden form field, submission timing and rate limits. Contact the nursery using the link below to ask about your submission or request a correction or removal. The wider policy and confirmed retention periods are being finalised with the nursery.
            </p>
          </div>

          <ol className="mt-6 flex flex-col gap-8">
            {SECTIONS.map((section, index) => (
              <Reveal
                as="li"
                key={section.title}
                delay={index * 40}
                className="flex gap-5"
              >
                <span
                  className="font-heading text-2xl font-semibold text-sky-300"
                  aria-hidden
                >
                  {String(index + 1).padStart(2, "0")}
                </span>
                <div>
                  <h3 className="!text-lg">{section.title}</h3>
                  <p className="mt-1.5 text-base leading-relaxed text-ink/85">
                    {section.text}
                  </p>
                  {section.items && (
                    <ul className="mt-2 flex flex-col gap-1.5 text-base leading-relaxed text-ink/85">
                      {section.items.map(([who, what]) => (
                        <li key={who}>
                          <strong className="font-semibold text-ink">{who}:</strong>{" "}
                          {what}
                        </li>
                      ))}
                    </ul>
                  )}
                </div>
              </Reveal>
            ))}
          </ol>

          <Reveal className="card mt-12 flex flex-col gap-4 p-8">
            <h3>Questions about your data?</h3>
            <p className="text-base leading-relaxed text-ink/85">
              If you would like to know what information we hold about you or
              your child, or you want to make a request under UK GDPR, please
              contact us and we will respond.
            </p>
            <a
              href={`mailto:${brand.email}?subject=Data%20protection%20enquiry`}
              className="btn-primary self-start"
            >
              <Mail className="h-4 w-4" strokeWidth={1.8} aria-hidden />
              Email us
            </a>
          </Reveal>
        </div>
      </Section>
    </>
  );
}
