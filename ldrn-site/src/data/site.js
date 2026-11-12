/**
 * Little Rainbows Day Nursery content.
 *
 * This is the verified baseline that the approved static site publishes. Any
 * value the client has not yet confirmed is wrapped in `tbc()` so it renders
 * as a visible "details to follow" state rather than an invented claim.
 *
 * Phase 3 replaces the editable parts of this file with the LDRN WordPress
 * REST contract (`/wp-json/ldrn/v1/site`). Until a record exists in WordPress
 * these values remain the fallback, which is why the shapes here and in the
 * PHP content contract must stay in step.
 */

/**
 * Marks a value the client still has to confirm.
 *
 * @param {string} question Review-tracker reference, e.g. "Q6".
 * @param {string|null} note Why it is blocked, shown in review mode.
 */
export const tbc = (question, note = null) => ({ tbc: true, question, note });

export const brand = {
  name: "Little Rainbows Day Nursery",
  shortName: "Little Rainbows",
  tagline: "Learning Through Play",
  wordmark: { line1: "Little Rainbows", line2: "Day  Nursery" },
  founded: 2005,
  email: "littlerainbowsdaynursery@yahoo.co.uk",
  phone: {
    general: "020 4619 6231",
    admissions: "020 3488 5577",
  },
};

/**
 * Group affiliation approved by the client. Little Rainbows and Alexandra
 * Montessori are separate limited companies that trade as one nursery group,
 * so the relationship is stated on the hero and in the footer. The two
 * websites stay entirely separate systems: separate WordPress installs,
 * separate databases, separate recipients.
 */
export const groupAffiliation = {
  name: "Alexandra Montessori Nursery Group",
  badge: "/assets/logo/alexandra-montessori-badge.webp",
  badgeAlt: "Alexandra Montessori",
  members: [
    {
      label: "Alexandra Montessori Limited",
      href: "https://alexandramontessori.co.uk",
      external: true,
    },
    {
      label: "LRDN Limited",
      href: "/ldrn/",
      external: false,
    },
  ],
  suffix: "are part of Alexandra Montessori Nursery Group",
  joiner: " and ",
};

export const navLinks = [
  { label: "Home", to: "/" },
  {
    label: "Our Nursery",
    to: "/our-nursery",
    children: [{ label: "Hounslow", to: "/our-nursery/hounslow" }],
  },
  { label: "Our Curriculum", to: "/curriculum" },
  { label: "Careers", to: "/careers" },
  { label: "Events", to: "/events" },
  { label: "Contact Us", to: "/contact" },
];

export const primaryCta = { label: "Check Availability", to: "/check-availability" };

export const moreLinks = [
  { label: "Fees", to: "/fees" },
  { label: "Fee Calculator", to: "/fee-calculator" },
  { label: "Funded Childcare", to: "/funded-childcare" },
  { label: "Blog", to: "/blogs" },
  { label: "Food Hygiene Rating", to: "/food-hygiene-rating" },
];

export const navLabels = {
  moreLabel: "View more",
  backLabel: "Back",
  moreHeading: "More information",
};

export const hero = {
  eyebrow: "Welcome to",
  title: "Little Rainbows Day Nursery",
  tagline: "Learning Through Play",
  image: "/assets/photography/home-hero-child-discovery-20261002.webp",
  imageAlt:
    "A child exploring materials at a nursery activity table",
  imageFlagged: true,
};

export const homeCards = [
  {
    title: "Our Nursery",
    cta: "View settings",
    to: "/our-nursery",
    image: "/assets/photography/children-at-craft-table.jpg",
    imageAlt: "Children working together at a craft table",
    icon: "MapPin",
  },
  {
    title: "Careers",
    cta: "View careers",
    to: "/careers",
    image: "/assets/photography/child-waving-from-play-window.jpg",
    imageAlt: "A child smiling and waving from a play window",
    icon: "Users",
  },
  {
    title: "Admissions",
    cta: "How to apply",
    to: "/admissions",
    image: "/assets/photography/child-drawing-with-crayons.jpg",
    imageAlt: "A child drawing with crayons",
    icon: "CalendarCheck",
  },
];

/** Settings with a current, open Ofsted record. */
const openSettings = [
  {
    slug: "hounslow",
    name: "Hounslow",
    strapline: "Our main hub",
    status: "Open",
    active: true,
    address: ["64 Pears Road", "Hounslow", "TW3 1SR"],
    phone: "020 4619 6231",
    email: "littlerainbowsdaynursery@yahoo.co.uk",
    hours: "Monday – Friday, 8am – 6pm",
    ages: "6 months – 5 years",
    places: 40,
    ofsted: { grade: "Good", date: "27 June 2025", urn: "EY545805" },
    image: "/assets/photography/children-at-craft-table.jpg",
    imageAlt: "Children working together at a craft table",
    published: true,
  },
];

/**
 * Settings that are published but whose current availability is unverified.
 *
 * The Chelsea and Chiswick locations were removed from the public site when
 * the client confirmed Hounslow as the only operating nursery. They are kept
 * as an explicit empty list rather than deleted code so a future WordPress
 * record can repopulate this list without a front-end change.
 */
const unverifiedSettings = [];

export const settings = [...openSettings, ...unverifiedSettings];

/** Every setting rendered in the public directory. */
export const allSettings = settings;

export const approach = {
  eyebrow: "Our approach",
  title: "Learning Through Play",
  body: "Little Rainbows Day Nursery believes in fostering a love for learning through play. Our approach combines a professional and fun atmosphere, providing children with a safe and nurturing environment to explore, discover, and grow.",
};

export const approachPoints = [
  {
    icon: "Sprout",
    title: "Curious explorers",
    text: "We view children as curious explorers and active participants in their own learning journey.",
  },
  {
    icon: "HandHeart",
    title: "Child-led and adult-led",
    text: "A good balance of both, because parents are a child's first educators — and we welcome their input.",
  },
  {
    icon: "ShieldCheck",
    title: "Safe and nurturing",
    text: "A warm, inclusive and joyful atmosphere where children explore, discover and grow at their own pace.",
  },
];

export const philosophy = {
  eyebrow: "Our philosophy",
  title: "Play is not just entertainment",
  paragraphs: [
    "At Little Rainbows Day Nursery, we believe that children learn best when they feel happy, safe, and free to explore through play.",
    "Play is at the heart of everything we do. Through fun, age-appropriate activities and hands-on experiences, children are encouraged to use their imagination, build confidence, solve problems, make friends, and develop important social, emotional, physical, and thinking skills.",
    "We understand that every child is unique and develops at their own pace. Our caring team creates a warm, welcoming, and inclusive environment where children feel supported to explore, discover new interests, and enjoy their learning journey.",
    "Our aim is to help every child grow with confidence, curiosity, and joy while creating happy memories and meaningful friendships along the way.",
  ],
  image: "/assets/photography/child-waving-from-play-window.jpg",
  imageAlt: "A child waving from a play window",
};

export const testimonials = [
  {
    body: [
      "To all the staff at Little Rainbows,",
      "A huge thank you to all of you for not only taking care of and teaching my little one but for making him feel loved!",
      "He joined Little Rainbows 3 years ago crying because he didn't want to leave my side; he now begs me to come to nursery and leaves every day with a smile :)",
      "He will miss you all every day and I will also miss you all!",
    ],
    attribution: "Love O's Mummy",
  },
  {
    body: [
      "To all the teachers in Little Rainbows, it was a pleasure to have you take care of our niece and help her learn and get ready for reception.",
      "Many thanks once again from",
    ],
    attribution: "N's Auntie & Uncle",
  },
  {
    body: [
      "Little Rainbows,",
      "Where do I even start? I cannot even begin to say thank you for nurturing and loving my child for these last three and a half years. I remember his first day so clearly and now he's leaving and ready for school ...! How did that happen so quickly? Since the age of 8 months he's been with you! Thank you for allowing me to have endless chats with you most days about how his day has been. I know I'm ‘that’ parent! I just want to let you guys know I am nothing but grateful for all you've done for S. He has always loved nursery and has made a lovely group of friends. I just hope that he is cared for just as well in school.",
      "Thanks again and I will bring S in to see you guys from time to time.",
    ],
    attribution: "Many Thanks S's Mummy & Daddy",
  },
  {
    body: [
      "The Little Rainbows team,",
      "I would just like to thank you all for looking after our little ones. You've been nothing but loving and caring towards not just them but us as well, and we thank you for that. We are going to miss seeing their smiles when they walk through the nursery doors.",
    ],
    attribution: "J's Family",
  },
  {
    body: [
      "LITTLE RAINBOWS, WE ARE GOING TO MISS YOU!",
      "C & E always talk about going to nursery even on weekends! They never want a break from Little Rainbows! Thank you for making it such a safe, loving and fun environment for them to be in. I couldn't have applied for a better nursery!",
    ],
    attribution: "C & E's Family",
  },
  {
    body: [
      "THANK YOU SO MUCH!",
      "For all your patience day to day,\nFor every tear you wipe away,\nFor the little hands you gently hold,\nFor teaching how to share,\nFor being kind and being there,",
      "YOUR TEAM IS SO WONDERFUL!",
    ],
    attribution: "T's Mummy",
    poem: true,
  },
  {
    body: [
      "It takes a big heart to help shape a little human. Huge thank you to all the Little Rainbows staff!",
      "V's Daddy and Family :)",
    ],
    attribution: "V's Daddy & Family",
  },
];

export const credentials = [
  {
    icon: "BadgeCheck",
    label: "Ofsted Good",
    detail: "Hounslow, inspected 27 June 2025",
  },
  { icon: "BookOpen", label: "EYFS", detail: "Early Years Foundation Stage" },
  {
    icon: "CalendarCheck",
    label: "Ofsted registered",
    detail: "Registered since 2017",
  },
  { icon: "Users", label: "40 places", detail: "At our Hounslow setting" },
];

export const footer = {
  blurb:
    "A day nursery in Hounslow, welcoming children through a learning-through-play approach.",
  hoursLine: "Hounslow: Monday – Friday, 8am – 6pm",
  ofstedTitle: "Ofsted Good",
  ofstedDetail: "Hounslow · inspected 27 June 2025",
  expandLabel: "View more",
  collapseLabel: "View less",
  quickLinksHeading: "Quick links",
  settingsHeading: "Our nursery",
  contactHeading: "Get in touch",
  pendingLabel: "Details to follow",
  directionsLabel: "Contact and directions",
  privacyLabel: "Privacy Policy",
  contactLabel: "Contact",
  copyrightSuffix: "All rights reserved.",
  company: tbc(
    "Q14",
    "Registered entity appears to be LRDN Limited (10610476) — confirm before publishing",
  ),
  social: tbc("Q15", "No social media accounts are linked anywhere on the site"),
};

export const healthSafety = {
  eyebrow: "Health & safety",
  title: "A secure environment to explore with confidence",
  intro:
    "At Little Rainbows Day Nursery, we prioritise the health and safety of every child in our care while upholding our ethos of learning through play. Our health and safety policy is designed to create a secure and nurturing environment where children can thrive and explore with confidence. Here are some key components of our health and safety approach:",
  points: [
    {
      title: "Safe Environment",
      text: "We aim to make children, parents, staff and volunteers aware of health and safety issues and to minimise the hazards and risks to enable the children to thrive in a healthy and safe environment.",
    },
    {
      title: "Staff Training",
      text: "We provide a variety of training for our team through our local authorities training program.",
    },
    {
      title: "Hygiene Practices",
      text: "We promote good hygiene practices among children and staff, including handwashing routines, appropriate sanitation of toys and equipment, and maintaining cleanliness throughout the premises.",
    },
    {
      title: "Allergies and Medical Needs",
      text: "We work closely with parents and carers to understand and accommodate children's allergies and medical needs, including administering medications as prescribed by healthcare professionals.",
    },
  ],
  closing:
    "Little Rainbows Day Nursery endeavours to remain a place where children can learn, play, and grow.",
};

export const curriculum = {
  title: "Early Years Foundation Stage",
  subtitle: "Fun & Creativity",
  intro:
    "Our curriculum follows the Early Years Foundation Stage. Six areas, each one an invitation to play.",
  blocks: [
    {
      name: "Art",
      icon: "/assets/illustrations/eyfs-icons/01-art-crayons.svg",
      text: "Offer resources for mixing colours, joining things together and combining materials supporting where appropriate.",
    },
    {
      name: "Music",
      icon: "/assets/illustrations/eyfs-icons/02-music-drum.svg",
      text: "Share rhymes books and stories from many cultures.",
    },
    {
      name: "Movement",
      icon: "/assets/illustrations/eyfs-icons/03-movement-playball.svg",
      text: "Planned activities and in-the-moment activities where children can practice movements in different ways and at different speeds — balancing, target throwing, rolling, kicking and catching.",
    },
    {
      name: "Language",
      icon: "/assets/illustrations/eyfs-icons/04-language-abc-cubes.svg",
      text: "Provide stories with repetitive phrases and structures to read aloud to children to support specific vocabulary and language structure.",
    },
    {
      name: "Mathematics",
      icon: "/assets/illustrations/eyfs-icons/05-mathematics-abacus.svg",
      text: "Provide a numerical rich environment — for example in role play areas, numbers on trikes and toilet doors.",
    },
    {
      name: "Outside Play",
      icon: "/assets/illustrations/eyfs-icons/06-outside-play-bicycle.svg",
      text: "Provide time and space to enjoy energetic play outdoors daily.",
    },
  ],
};

export const ofsted = {
  title: "Ofsted Reports",
  intro: "Hounslow has a current open Ofsted record.",
  reports: [
    {
      setting: "Hounslow",
      status: "Open",
      grade: "Good",
      date: "27 June 2025",
      dateLabel: "Inspection date",
      urn: "EY545805",
      allCategories: true,
      reportUrl: "https://files.ofsted.gov.uk/v1/file/50283440",
      providerUrl: "https://reports.ofsted.gov.uk/provider/16/EY545805",
      superseded: {
        label: "Previous report (2019)",
        url: "https://files.ofsted.gov.uk/v1/file/50119981",
        archived:
          "/assets/documents/ofsted-reports/ofsted-report-hounslow-2019.pdf",
      },
    },
  ],
};

export const admissions = {
  title: "Admissions",
  intro: "Welcome to Little Rainbows Day Nursery.",
  body: [
    "Whether you're looking to enrol your child or join our amazing team, we've got you covered.",
    "For nursery applications, we make the process easy and enjoyable, ensuring a smooth transition for your child into our nurturing environment.",
  ],
  ages: tbc("Q3", "Four conflicting age ranges are published across the site"),
  funded:
    "We offer funded spaces for children aged 9 months and older, ensuring accessible early education opportunities for families.",
  steps: tbc("Q6", "No admissions process is described anywhere on the site"),
  closing: "Let's embark on this exciting journey together!",
  phone: "020 3488 5577",
  form: "/assets/documents/application-forms/hounslow-nursery-application-form.docx",
};

export const careers = {
  title: "Careers",
  intro: "Want to join our team? Please see the application form below.",
  body: "If you're interested in a rewarding career in childcare, join our passionate team and help shape the future of our little rainbows.",
  applicationIntro:
    "If you are interested in working at our nursery, please complete the application form below and we will get back to you as soon as possible.",
  vacanciesCta: "Ask about current vacancies",
  formHeading: "Apply to join Little Rainbows",
  formIntro:
    "Please complete every section that applies to you. Fields marked with an asterisk are required.",
  localOnlyNotice:
    "This form currently checks your answers on this device only. No personal information is sent or stored until secure online submission is connected.",
  paperHeading: "Prefer a paper application?",
  paperIntro:
    "Download the original staff application form and return the completed document to Little Rainbows by email.",
  beforeApplying: [
    {
      title: "Prepare two referees",
      text: "One referee should be your current or most recent line manager. Referees must not be relatives, partners or friends.",
      icon: "Users",
    },
    {
      title: "Include your full history",
      text: "Give a complete employment record and explain any gaps so that your application can be assessed fairly.",
      icon: "BookOpen",
    },
    {
      title: "Add your supporting statement",
      text: "Explain how your experience meets the requirements of the role and person specification.",
      icon: "BadgeCheck",
    },
  ],
  formUi: {
    sections: {
      role: "Role details",
      personal: "Personal details",
      references: "References",
      monitoring: "Safeguarding, eligibility and monitoring",
      qualifications: "Qualifications and training",
      employment: "Employment history",
      other: "Other information",
      statement: "Supporting statement",
      declaration: "Declaration and documents",
    },
    yes: "Yes",
    no: "No",
    optional: "Optional",
    refereeNote:
      "Please provide two referees. One must be your current or most recent line manager; do not use relatives, partners or friends.",
    qualificationNote:
      "List your most relevant schools, colleges, universities and qualifications. Add any current study separately.",
    employmentNote:
      "Start with your current or most recent employer and account for your full employment history.",
    cvHelp: "PDF, DOC or DOCX, up to 5 MB.",
    submitLabel: "Check application",
    successHeading: "Your application is ready to send",
    successText:
      "The required fields have passed the checks on this device. Nothing has been sent or stored yet; please use the paper form or contact Little Rainbows while secure online submission is being connected.",
    errorSummary: "Please complete the highlighted fields before continuing.",
    ethnicities: [
      "Prefer not to say",
      "White British",
      "White Irish",
      "Any other White background",
      "Black British",
      "Black African",
      "Black Caribbean",
      "Any other Black background",
      "Asian Bangladeshi",
      "Asian Pakistani",
      "Asian Indian",
      "Any other Asian background",
      "Chinese",
      "Any other Chinese background",
      "Mixed White and Black Caribbean",
      "Mixed White and Black African",
      "Mixed White and Asian",
      "Any other Mixed background",
      "Any other ethnic background",
    ],
    genders: ["Prefer not to say", "Female", "Male", "Another description"],
  },
  vacancies: tbc("Q10", "No vacancies are listed anywhere on the site"),
  whyWorkHere: tbc("Q1", "No staff-facing content exists — needs the client"),
  form: "/assets/documents/application-forms/staff-job-application-form.docx",
  phone: "020 4619 6231",
};

/** Photographs used in the home and landing layouts. */
export const heroPhotos = [
  "/assets/photography/children-at-craft-table.jpg",
  "/assets/photography/child-waving-from-play-window.jpg",
  "/assets/photography/child-drawing-with-crayons.jpg",
  "/assets/photography/child-rolling-pin-and-cutter-dough.jpg",
  "/assets/photography/children-playing-together-at-table.jpg",
];

/** Every approved photograph, used by galleries. */
export const allPhotos = [
  ...heroPhotos,
  "/assets/photography/child-at-story-and-circle-time.jpg",
  "/assets/photography/children-building-wooden-train-track.jpg",
  "/assets/photography/child-exploring-sensory-bag.jpg",
  "/assets/photography/child-hands-green-playdough.jpg",
  "/assets/photography/child-hands-on-picture-book-fox.jpg",
  "/assets/photography/child-holding-fabric-book-bear-bananas.jpg",
  "/assets/photography/child-pointing-in-classroom.jpg",
  "/assets/photography/child-pointing-shapes-and-numbers-wall.png",
  "/assets/photography/child-pressing-cutter-into-dough.jpg",
  "/assets/photography/child-reading-fabric-book-bear-bee.jpg",
  "/assets/photography/child-reading-fabric-book-elephant-egg.jpg",
];

/** No events have been supplied yet; the archive renders its empty state. */
export const events = [];

export const feesAndFunding = {
  title: "Fees & Funding",
  intro: tbc("Q6", "No fee or session information is published anywhere"),
  fees: tbc("Q6", null),
  sessions: tbc("Q6", null),
  funded:
    "We offer funded spaces for children aged 9 months and older, ensuring accessible early education opportunities for families.",
  schemes: tbc(
    "Q7",
    "Which schemes — 15h, 30h, 2-year-old funding, Tax-Free Childcare — at which settings",
  ),
};

export const feeCalculator = {
  dayHours: 10,
  defaultDays: 3,
  defaultFundedHours: 15,
  fundedHourOptions: [0, 15, 30],
};

export const foodHygiene = [
  {
    name: "Hounslow",
    rating: "5",
    ratingDate: "1 April 2025",
    authority: "London Borough of Hounslow",
    address: "Hounslow Town Primary School, Pears Road, Hounslow, TW3 1SR",
    href: "https://ratings.food.gov.uk/business/855345/little-rainbows-day-nursery-hounslow",
  },
];

export const blog = {
  title: "Blog",
  emptyMessage: "No blog posts are currently published.",
};

export const availability = {
  title: "Check Availability",
  intro:
    "Tell us what your family needs and our team will confirm current places and the best next step for your child.",
  formHeading: "Tell us about your childcare needs",
  formIntro:
    "Share your preferred setting, start date, days and session pattern so the right Little Rainbows team can respond.",
  localOnlyNotice:
    "This form currently checks your answers on this device only. No personal information is sent or stored until secure online submission is connected.",
  highlights: [
    {
      title: "Preferred start date",
      text: "Tell us when you would like your child to begin.",
      icon: "CalendarCheck",
    },
    {
      title: "Session pattern",
      text: "Share the weekdays and hours you are considering.",
      icon: "Clock",
    },
    {
      title: "Right setting",
      text: "Choose the Little Rainbows setting you prefer.",
      icon: "MapPin",
    },
  ],
  successHeading: "Your availability request is ready",
  successText:
    "The required details have passed the checks on this device. Nothing has been sent or stored yet; please call or email Little Rainbows while secure online submission is being connected.",
  note: "An enquiry does not reserve a place. The Little Rainbows team will confirm current availability directly with you.",
};

export const contact = {
  title: "Contact",
  intro: "We'd love to hear from you.",
  formHeading: "Send us a message here",
  formIntro:
    "For an admissions question, a visit or a general enquiry, send us a message and the Little Rainbows team will get back to you.",
  localOnlyNotice:
    "This form currently checks your message on this device only. No personal information is sent or stored until secure online submission is connected.",
  successHeading: "Your message is ready",
  successText:
    "Your details have passed the checks on this device. Nothing has been sent or stored yet; please email or call Little Rainbows while secure online submission is being connected.",
  phones: [
    { number: "020 4619 6231", label: "General enquiries" },
    { number: "020 3488 5577", label: "Admissions" },
  ],
  phoneNote: tbc("Q5", "Which number belongs to which setting or purpose"),
  email: "littlerainbowsdaynursery@yahoo.co.uk",
  emailNote: tbc("Q13", "Move to a domain address; split admissions/recruitment"),
  maps: {
    hounslow: "64 Pears Road, Hounslow, TW3 1SR",
  },
  transport: tbc("Q6", "No transport, parking or drop-off guidance exists"),
};

export const privacy = {
  title: "Privacy Policy",
  blocked: "Q13, Q14",
  note: tbc(
    "Q13",
    "Needs the data controller, retention periods and the registered entity before it can be accurate",
  ),
};

export const notFound = {
  title: "We can't find that page",
  body: "The page you're looking for may have moved. Try our nursery, or get in touch and we'll point you the right way.",
};

/** Per-route title and description defaults consumed by the `Seo` component. */
export const seoRoutes = {
  "/": {
    title: "Little Rainbows Day Nursery | Day Care in West London",
    description:
      "A day nursery in Hounslow, rated Good by Ofsted. Learning through play for children aged 6 months–5 years. Check availability.",
  },
  "/our-nursery": {
    title: "Our Nursery | Little Rainbows Day Nursery",
    description:
      "Explore Little Rainbows Day Nursery in Hounslow, with current details and clear availability notes.",
  },
  "/our-nursery/hounslow": {
    title: "Hounslow Nursery | Little Rainbows Day Nursery",
    description:
      "Little Rainbows Hounslow, 64 Pears Road, TW3 1SR. Ages 6 months–5 years, 40 places, Monday to Friday 8am–6pm. Rated Good by Ofsted, June 2025.",
  },
  "/about": {
    title: "About Us | Little Rainbows Day Nursery",
    description:
      "Children learn best through play. Our philosophy, our approach to health and safety, and what a day at Little Rainbows looks like.",
  },
  "/about/ofsted": {
    title: "Ofsted Reports | Little Rainbows Day Nursery",
    description:
      "View the published Ofsted inspection report for Little Rainbows Day Nursery in Hounslow.",
  },
  "/about/meet-the-team": {
    title: "Meet the Team | Little Rainbows Day Nursery",
    description: tbc("Q1", "Blocked until ownership is confirmed"),
  },
  "/curriculum": {
    title: "Curriculum | Little Rainbows Day Nursery",
    description:
      "Art, music, movement, language, mathematics and outside play — the Early Years Foundation Stage at Little Rainbows.",
  },
  "/fees-and-funding": {
    title: "Fees & Funding | Little Rainbows Day Nursery",
    description: tbc("Q6", "No fee information exists to describe"),
  },
  "/fees": {
    title: "Admissions & Fees | Little Rainbows Day Nursery",
    description:
      "Contact your preferred Little Rainbows setting for current nursery fees and sessions, or estimate funded weekly hours online.",
  },
  "/fee-calculator": {
    title: "Funding Estimate | Little Rainbows Day Nursery",
    description:
      "Estimate weekly childcare hours remaining after funded hours. Exact fees and funding eligibility are confirmed by Little Rainbows.",
  },
  "/funded-childcare": {
    title: "Funded Childcare | Little Rainbows Day Nursery",
    description:
      "Read Little Rainbows funded childcare information and contact your preferred nursery setting for eligibility details.",
  },
  "/blogs": {
    title: "Blog | Little Rainbows Day Nursery",
    description: "News and updates from Little Rainbows Day Nursery.",
  },
  "/food-hygiene-rating": {
    title: "Food Hygiene Rating | Little Rainbows Day Nursery",
    description:
      "View the current Food Standards Agency hygiene rating for Little Rainbows Day Nursery in Hounslow.",
  },
  "/admissions": {
    title: "Admissions | Little Rainbows Day Nursery",
    description:
      "How to apply for a place at Little Rainbows. Funded places available for children aged 9 months and older.",
  },
  "/book-a-visit": {
    title: "Check Availability | Little Rainbows Day Nursery",
    description:
      "Tell Little Rainbows your preferred setting, start date, days and session pattern so the nursery team can check current availability.",
  },
  "/check-availability": {
    title: "Check Availability | Little Rainbows Day Nursery",
    description:
      "Tell Little Rainbows your preferred setting, start date, days and session pattern so the nursery team can check current availability.",
  },
  "/events": {
    title: "News & Events | Little Rainbows Day Nursery",
    description:
      "View upcoming and past events from Little Rainbows Day Nursery, or contact the nursery team to register your interest.",
  },
  "/careers": {
    title: "Careers | Little Rainbows Day Nursery",
    description:
      "Join our team. A rewarding career in childcare with a nursery that believes children learn best through play.",
  },
  "/careers/vacancies": {
    title: "Current Vacancies | Little Rainbows Day Nursery",
    description:
      "View current vacancies or send a general childcare application to Little Rainbows Day Nursery.",
  },
  "/careers/apply": {
    title: "Apply | Little Rainbows Day Nursery",
    description:
      "Complete the Little Rainbows Day Nursery staff application form.",
  },
  "/testimonials": {
    title: "Parent Testimonials | Little Rainbows Day Nursery",
    description:
      "Read what parents and carers say about their experience with Little Rainbows Day Nursery.",
  },
  "/contact": {
    title: "Contact | Little Rainbows Day Nursery",
    description:
      "Contact Little Rainbows Day Nursery in Hounslow by phone or email and view our published details.",
  },
  "/privacy": {
    title: "Privacy Policy | Little Rainbows Day Nursery",
    description:
      "How Little Rainbows Day Nursery collects, uses and protects your personal information.",
  },
};
