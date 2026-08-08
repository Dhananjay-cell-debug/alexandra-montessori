import PageHeader from "../components/PageHeader";
import Reveal from "../components/Reveal";
import { brand } from "../data/site";
import { gmailHref } from "../lib/contact";

const policyEmail = "info@alexandramontessori.co.uk";
const effectiveDate = "23 July 2025";

// Real privacy notice supplied by the client (Web-Privacy-Policy). Each section
// is rendered as a heading followed by paragraphs and optional bullet lists.
const sections = [
  {
    h: "1. Information we collect",
    blocks: [
      {
        p: "When you visit our Site, we may collect the following types of information:",
      },
      {
        p: "Information You Voluntarily Provide: We collect the personal information you provide when you use our services or otherwise communicate with us, such as when you provide contact information, or send us an email. The types of information we may collect include your name, email address, postal address, and telephone number.",
      },
      {
        p: "Automatically Collected Information: When you access our Site, we may automatically collect certain information about your device and usage of our Site, including your IP address, browser type, device type, operating system, page views, referring URLs, destination URLs, dates and times of site visits, and other such information. We may use cookies, pixel tags, web beacons, or other tracking technologies to collect this information.",
      },
    ],
  },
  {
    h: "2. How we use your information",
    blocks: [
      { p: "We use the information we collect for several purposes, such as:" },
      {
        list: [
          "To provide, operate, maintain, improve, and promote the Site and our services.",
          "To enable you to access and use our Site and services.",
          "To process and complete transactions, and send you related information, including confirmations and invoices.",
          "To send transactional messages, such as responding to your comments, questions, and requests; providing customer service and support; and sending you technical notices, updates, security alerts, and support and administrative messages.",
        ],
      },
    ],
  },
  {
    h: "3. How we share your information",
    blocks: [
      {
        p: "We may share the information we collect in certain circumstances, including the following:",
      },
      {
        list: [
          "Service Providers: We may share your information with third-party service providers that provide services on our behalf, such as payment processing, data analysis, email delivery, hosting services, customer service, and marketing assistance.",
          "Legal Requirements: We may disclose your information if required to do so by law or in the good faith belief that such action is necessary to comply with a legal obligation, protect and defend our rights or property, protect the personal safety of users of the Site or the public, or protect against legal liability.",
        ],
      },
    ],
  },
  {
    h: "4. Security",
    blocks: [
      {
        p: "We take reasonable measures, including administrative, technical, and physical safeguards, to protect your information from loss, theft, misuse, unauthorized access, disclosure, alteration, and destruction. However, the internet is not 100% secure, and we cannot ensure or warrant the security of any information you provide to us.",
      },
    ],
  },
  {
    h: "5. Children's privacy",
    blocks: [
      {
        p: `Our services are not directed to children, and we do not knowingly collect personal information from children under the age of 13. If we find out that a child under 13 has given us personal information, we will take steps to delete that information. If you believe that a child under the age of 13 has given us personal information, please contact us at ${policyEmail}.`,
      },
    ],
  },
  {
    h: "6. Changes to this policy",
    blocks: [
      {
        p: "We may change this Privacy Policy from time to time. If we make changes, we will notify you by revising the effective date at the top of this policy and, in some cases, we may provide additional notice (such as adding a statement to our homepage or sending you an email notification).",
      },
    ],
  },
  {
    h: "7. Contact us",
    blocks: [
      {
        p: "If you have any questions about this Privacy Policy, please contact us at:",
      },
      { p: `${brand.name}, Ravenscourt, Heston, Hounslow` },
      { email: policyEmail },
      { p: "We're committed to responding to your enquiry within 30 days." },
    ],
  },
];

export default function Privacy() {
  const openCookieSettings = () => {
    window.dispatchEvent(new Event("am:open-cookie-settings"));
  };

  return (
    <>
      <PageHeader
        crumb="Privacy"
        eyebrow="Legal"
        title="Privacy Policy"
        intro={`Effective date: ${effectiveDate}`}
      />
      <section className="container-wide py-10">
        <div className="mx-auto max-w-3xl space-y-8">
          <Reveal>
            <p className="text-pretty leading-relaxed text-ink/85">
              This privacy policy ("Policy") explains how personal information
              is collected, used, and disclosed by {brand.name} ("we," "our," or
              "us"), located at Ravenscourt, Heston, Hounslow, phone number:{" "}
              <a
                href="tel:02046183477"
                className="font-medium text-sage-700 hover:underline"
              >
                0204 618 3477
              </a>
              . It applies to users of our website (the "Site") -
              alexandramontessori.co.uk.
            </p>
          </Reveal>

          {sections.map((s, i) => (
            <Reveal key={s.h} delay={i * 40}>
              <h2 className="font-heading text-xl font-medium text-ink">
                {s.h}
              </h2>
              {s.blocks.map((b, j) => {
                if (b.list) {
                  return (
                    <ul
                      key={j}
                      className="mt-3 list-disc space-y-2 pl-5 text-pretty leading-relaxed text-ink/90"
                    >
                      {b.list.map((item) => (
                        <li key={item}>{item}</li>
                      ))}
                    </ul>
                  );
                }
                if (b.email) {
                  return (
                    <p
                      key={j}
                      className="mt-2 text-pretty leading-relaxed text-ink/90"
                    >
                      Email:{" "}
                      <a
                        href={gmailHref(b.email)}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="font-medium text-sage-700 hover:underline"
                      >
                        {b.email}
                      </a>
                    </p>
                  );
                }
                return (
                  <p
                    key={j}
                    className="mt-2 text-pretty leading-relaxed text-ink/90"
                  >
                    {b.p}
                  </p>
                );
              })}
            </Reveal>
          ))}

          <Reveal>
            <div className="border-t border-sage-100 pt-6">
              <h2 className="font-heading text-xl font-medium text-ink">
                Cookie preferences
              </h2>
              <p className="mt-2 text-pretty leading-relaxed text-ink/90">
                You can review or update optional cookie choices for this
                website at any time.
              </p>
              <button
                type="button"
                onClick={openCookieSettings}
                className="btn-outline mt-4"
              >
                Manage cookie settings
              </button>
            </div>
          </Reveal>

          <Reveal>
            <p className="border-t border-sage-100 pt-6 text-pretty leading-relaxed text-ink/90">
              Please note that this Policy does not apply to any third-party
              websites, services, or applications, even if they are accessible
              through our Site. Also, please note that we are not responsible
              for the privacy practices of other websites, services, or
              applications. Your use of our Site and services is subject to this
              Policy and our Terms of Service. By using our Site and services,
              you are accepting the terms of this Privacy Policy.
            </p>
          </Reveal>
        </div>
      </section>
    </>
  );
}
