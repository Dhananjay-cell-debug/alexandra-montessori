import { hasWordPressDataContract } from "../lib/cms";

// Central content for Alexandra Montessori.
// Real facts (locations, phones, hours, age range, philosophy, funding, training,
// daily routine, apply portal) extracted page-by-page from alexandramontessori.co.uk.
// Layout/structure mirrors mrnnursery.co.uk. Imagery uses the client's own brand
// assets (logos + real nursery photography + branded lifestyle illustrations).

// Global Site Settings come from WordPress (window.amData.settings, injected via
// wp_localize_script). A WordPress value is used only when it is a non-empty
// string; otherwise the static fallback below is kept, so an empty/blank CMS
// field never overwrites the working static value.
const wpSettings =
  (typeof window !== "undefined" && window.amData && window.amData.settings) ||
  {};

const wpNurseries =
  typeof window !== "undefined" &&
  window.amData &&
  Array.isArray(window.amData.nurseries)
    ? window.amData.nurseries
    : [];

const isNonEmptyString = (value) =>
  typeof value === "string" && value.trim() !== "";

const NURSERY_AGE_RANGE = "Babies to 5 Years";

// Prefer a non-empty WordPress string; otherwise keep the static fallback.
const fromCms = (value, fallback) =>
  isNonEmptyString(value) ? value : fallback;

// Value-safe merge of one CMS nursery record onto its matching visual model.
// Known branches retain their approved visual defaults; newly created branches
// receive generic presentation defaults without requiring a code allow-list.
const mergeNursery = (stat, cms) => {
  if (!cms) return stat;
  const s = (val, fb) => (isNonEmptyString(val) ? val : fb);
  const gallery =
    Array.isArray(cms.gallery) && cms.gallery.length
      ? cms.gallery.filter(isNonEmptyString)
      : [];
  return {
    ...stat,
    name: s(cms.name, stat.name),
    area: s(cms.area, stat.area),
    heroTagline: s(cms.heroTagline, stat.heroTagline),
    subheading: s(cms.subheading, stat.subheading),
    address: s(cms.address, stat.address),
    postcode: s(cms.postcode, stat.postcode),
    phone: s(cms.phone, stat.phone),
    email: s(cms.email, stat.email),
    hours: s(cms.hours, stat.hours),
    ageRange: s(cms.ageRange, stat.ageRange),
    short: s(cms.short, stat.short),
    welcome: s(cms.welcome, stat.welcome),
    image: s(cms.image, stat.image),
    welcomeImage: s(cms.welcomeImage, stat.welcomeImage),
    gallery: gallery.length ? gallery : stat.gallery,
    feeSheetPdf: s(cms.feeSheetPdf, stat.feeSheetPdf || ""),
    // Ofsted (client-requested; footer + branch pages)
    ofstedRating: s(cms.ofstedRating, stat.ofstedRating),
    ofstedUrl: s(cms.ofstedUrl, stat.ofstedUrl),
    ofstedLabel: s(cms.ofstedLabel, stat.ofstedLabel),
    // Food Hygiene Rating (FSA; /food-hygiene-rating)
    hygieneRating: s(cms.hygieneRating, stat.hygieneRating),
    hygieneDate: s(cms.hygieneDate, stat.hygieneDate),
    hygieneAuthority: s(cms.hygieneAuthority, stat.hygieneAuthority),
    hygieneUrl: s(cms.hygieneUrl, stat.hygieneUrl),
    features: stat.features,
  };
};

const staticSocials = [
  {
    label: "Facebook",
    href: "https://www.facebook.com/alexandramontessoriltd/",
  },
  {
    label: "Instagram",
    href: "https://www.instagram.com/alexandra_montessori?igsh=MTltNW8waTQ3aWM0ZQ==",
  },
  { label: "X", href: "https://x.com/alexand96462858?s=21" },
  {
    label: "LinkedIn",
    href: "https://www.linkedin.com/in/alexandra-montessori-89b92b320?utm_source=share_via&utm_content=profile&utm_medium=member_ios",
  },
];

export const brand = {
  name: "Alexandra Montessori",
  short: "Alexandra",
  tagline: "Quality Montessori childcare in London",
  motto: "Learning for life", // real strapline from the brand logo
  ageRange: NURSERY_AGE_RANGE,
  email: fromCms(wpSettings.email, "info@alexandramontessori.co.uk"), // general enquiries inbox
  phonePrimary: fromCms(wpSettings.phone, "0204 618 3477"), // general enquiry line (Mon-Fri 8am-6pm)
  hours: fromCms(wpSettings.hours, "Mon-Fri, 8am-6pm"),
  address: fromCms(wpSettings.address, ""),
  applyUrl: fromCms(
    wpSettings.applyUrl,
    "https://applyalways.com/display/G6o5jN8mK1m1GuxmpRN2",
  ),
  // Use WordPress socials only when at least one link was entered; the getter
  // already drops fully-empty rows, so a non-empty array means real CMS data.
  socials:
    Array.isArray(wpSettings.socials) && wpSettings.socials.length
      ? wpSettings.socials
      : staticSocials,
};

// Featured quote used across the site (real - from the live homepage).
export const featuredQuote = {
  text: "Education is the most powerful weapon which you can use to change the world.",
  author: "Nelson Mandela",
};

// ---------------------------------------------------------------- Navigation
// Keep the primary navigation calm. Parent resources are grouped separately so
// required pages do not clutter the main menu.
export const nav = [
  { label: "Home", to: "/" },
  { label: "Our Nurseries", to: "/nurseries" },
  { label: "Our Curriculum", to: "/curriculum" },
  { label: "Careers", to: "/careers" },
  { label: "Events", to: "/events" },
  { label: "Contact Us", to: "/contact" },
];

export const parentInfoLinks = [
  { label: "Fees", to: "/fees" },
  { label: "Fee Calculator", to: "/fee-calculator" },
  { label: "Funded Childcare", to: "/funded-childcare" },
  { label: "Blog", to: "/blogs" },
  { label: "Food Hygiene Rating", to: "/food-hygiene-rating" },
];

// Shared feature set shown on each nursery page (Montessori-adapted).
const nurseryFeatures = [
  { icon: "GraduationCap", label: "School readiness" },
  { icon: "Sprout", label: "Montessori Inspired" },
  { icon: "HandHeart", label: "Practical life" },
  { icon: "Sparkles", label: "Sensory spaces" },
  { icon: "Utensils", label: "Home-cooked meals" },
  { icon: "Users", label: "Qualified practitioners" },
  { icon: "Trees", label: "Outdoor garden play" },
  { icon: "ShoppingBag", label: "Buggy store" },
  { icon: "Lock", label: "Secure entry" },
  { icon: "Clock", label: "Extended hours" },
  { icon: "ChefHat", label: "Onsite chef" },
  { icon: "Palette", label: "Creative atelier" },
];

// Heston is the only setting with dedicated parking, so it keeps "Ample
// parking" in place of the shared "Creative atelier" feature.
const hestonNurseryFeatures = nurseryFeatures.map((feature) =>
  feature.label === "Creative atelier"
    ? { icon: "CarFront", label: "Ample parking" }
    : feature,
);

const hammersmithNurseryFeatures = nurseryFeatures
  .filter(
    (feature) =>
      ![
        "Home-cooked meals",
        "Onsite chef",
        "Outdoor garden play",
      ].includes(feature.label),
  );

const staticLocations = [
  {
    id: "hounslow",
    name: "Hounslow",
    area: "Hounslow",
    heroTagline: "Quality childcare in Hounslow",
    subheading: "Montessori Childcare Hounslow",
    address: "Ved Court, Alexandra Road, Hounslow TW3 1LS",
    postcode: "TW3 1LS",
    phone: "0208 001 5165",
    email: "info@alexandramontessori.co.uk",
    ofstedUrl: "https://reports.ofsted.gov.uk/provider/16/2546985",
    ofstedLabel: "Official Ofsted report",
    ofstedRating: "Good",
    hours: "Mon-Fri, 8am-6pm",
    ageRange: "6 months to 5 years",
    feeSheetPdf:
      "https://alexandramontessori.co.uk/wp-content/uploads/2026/04/Alexandra_Montessori_Hounslow_Fees.pdf",
    hygieneRating: "4",
    hygieneDate: "6 October 2025",
    hygieneAuthority: "London Borough of Hounslow",
    hygieneUrl:
      "https://ratings.food.gov.uk/business/1250950/alexandra-montessori-hounslow",
    image: `/assets/organisation/classroom-main.webp`,
    cardImage: `/assets/organisation/portrait-girl.webp`,
    welcomeImage: `/assets/organisation/teacher-hug.webp`,
    // Keep this identical to the Nursery Manager card framing.
    welcomePosition: `50% 0%`,
    welcomeTransform: `scale(1.1) translateX(4%)`,
    gallery: [
      `/assets/organisation/painting-close.webp`,
      `/assets/organisation/water-pouring.webp`,
      `/assets/organisation/hounslow-hi-vis-restored.webp`,
    ],
    short:
      "Montessori-inspired practice with a caring, family feel at the heart of Hounslow.",
    welcome:
      "Our Hounslow nursery blends Montessori-inspired practice with a caring, family feel. Natural materials and consistent key-person care help children grow in a safe, well-prepared setting.",
    offerHeading:
      "Everything your child needs to achieve, thrive and belong in Hounslow",
    features: nurseryFeatures,
  },
  {
    id: "heston",
    name: "Heston",
    area: "Hounslow",
    heroTagline: "Quality childcare in Heston",
    subheading: "Montessori Childcare Heston",
    address: "36 Springwell Road, Hounslow TW5 9EJ",
    postcode: "TW5 9EJ",
    phone: "0203 627 6707",
    email: "heston@alexandramontessori.co.uk",
    ofstedUrl: "https://reports.ofsted.gov.uk/provider/16/2814844",
    ofstedLabel: "No published report yet",
    ofstedRating: "Pending",
    hours: "Mon-Fri, 8am-6pm",
    ageRange: "6 months to 5 years",
    feeSheetPdf:
      "https://alexandramontessori.co.uk/wp-content/uploads/2026/04/Alexandra_Montessori_Heston_Fees.pdf",
    hygieneRating: "5",
    hygieneDate: "20 January 2025",
    hygieneAuthority: "London Borough of Hounslow",
    hygieneUrl:
      "https://ratings.food.gov.uk/business/548392/alexandra-montessori-heston",
    image: `/assets/organisation/friends-two.webp`,
    cardImage: `/assets/organisation/toddler-smile.webp`,
    welcomeImage: `/assets/organisation/monkey-bars.jpg`,
    // Portrait playground photo in the landscape welcome frame - bias the crop
    // up so the climbing child is the subject, not the fence/grass below.
    welcomePosition: `center 18%`,
    gallery: [
      `/assets/organisation/sensory-box.webp`,
      `/assets/organisation/toddler-smile.webp`,
      `/assets/organisation/materials-shelf.webp`,
    ],
    short:
      "A warm, welcoming home for early learners, with spacious studios and a secure garden.",
    welcome:
      "Our Heston nursery is a warm, welcoming home for early learners. Thoughtfully prepared environments, home-cooked meals and a secure garden help every child build confidence and independence at their own pace.",
    offerHeading:
      "Everything your child needs to achieve, thrive and belong in Heston",
    features: hestonNurseryFeatures,
  },
  {
    id: "hammersmith",
    name: "Hammersmith",
    area: "Ravenscourt",
    heroTagline: "Quality childcare in Hammersmith",
    subheading: "Montessori Childcare Ravenscourt",
    address: "Dalling Road, London W6 0EU",
    postcode: "W6 0EU",
    phone: "0204 618 3477",
    email: "hammersmith@alexandramontessori.co.uk",
    ofstedUrl: "https://reports.ofsted.gov.uk/provider/16/2498843",
    ofstedLabel: "Official Ofsted report",
    ofstedRating: "Good",
    hours: "Mon-Fri, 8am-6pm",
    ageRange: "12 months to 5 years",
    feeSheetPdf:
      "https://alexandramontessori.co.uk/wp-content/uploads/2026/05/Hammersmith-AM-Fees-2025.pdf",
    hygieneRating: "Check current local authority record",
    hygieneDate: "Awaiting public FHRS listing",
    hygieneAuthority: "London Borough of Hammersmith & Fulham",
    hygieneUrl: "https://ratings.food.gov.uk/",
    image: `/assets/organisation/classroom-calm.webp`,
    cardImage: `/assets/organisation/portrait-boy.webp`,
    // This portrait sits higher in frame, so bias the circle crop upward a
    // little more than the shared default to keep the top of the head in view.
    cardPosition: `50% 16%`,
    welcomeImage: `/assets/organisation/shape-work.webp`,
    // The CMS hero is portrait; this keeps every child's head below the nav.
    heroPosition: `center 28%`,
    // Keep the child's full head visible in the landscape welcome frame.
    welcomePosition: `center 8%`,
    gallery: [
      `/assets/organisation/tree-work.webp`,
      `/assets/organisation/movement-play.webp`,
    ],
    short:
      "Montessori-inspired care moments from Ravenscourt Park, with bright, natural-light studios.",
    welcome:
      "Just a short walk from Ravenscourt Park, our Hammersmith nursery offers calm, prepared Montessori environments and natural resources that support independence, confidence and purposeful early learning.",
    showMealSection: false,
    features: hammersmithNurseryFeatures,
  },
];

const genericNurseryModel = (cms) => ({
  id: cms.id,
  name: cms.name,
  area: cms.area || cms.name,
  heroTagline: `Quality childcare in ${cms.name}`,
  subheading: `Montessori Childcare ${cms.name}`,
  address: cms.address || "",
  postcode: cms.postcode || "",
  phone: cms.phone || "",
  email: cms.email || "",
  hours: cms.hours || "",
  ageRange: cms.ageRange || NURSERY_AGE_RANGE,
  image: cms.image || "",
  welcomeImage: cms.welcomeImage || cms.image || "",
  gallery: Array.isArray(cms.gallery) ? cms.gallery : [],
  short: cms.short || "",
  welcome: cms.welcome || "",
  feeSheetPdf: cms.feeSheetPdf || "",
  ofstedRating: cms.ofstedRating || "Pending",
  ofstedUrl: cms.ofstedUrl || "",
  ofstedLabel: cms.ofstedLabel || "Ofsted information",
  hygieneRating: cms.hygieneRating || "",
  hygieneDate: cms.hygieneDate || "",
  hygieneAuthority: cms.hygieneAuthority || "",
  hygieneUrl: cms.hygieneUrl || "",
  offerHeading: `Everything your child needs to thrive in ${cms.name}`,
  showMealSection: true,
  features: nurseryFeatures,
});

const cmsNurseryModel = (cms) => {
  const approvedStatic = staticLocations.find(
    (location) => location.id === cms.id,
  );
  return mergeNursery(approvedStatic || genericNurseryModel(cms), cms);
};

// WordPress is authoritative whenever its versioned data contract is present.
// Static locations exist only for the standalone Vite/design preview.
export const locations = hasWordPressDataContract
  ? wpNurseries.map(cmsNurseryModel)
  : staticLocations;

export const locationBySlug = (slug) => locations.find((l) => l.id === slug);

// Team cards on each nursery page - role-based (no invented names; staff photos
// to be supplied + consented by the client before launch).
export const team = [
  {
    role: "Nursery Manager",
    note: "Leads the setting day to day, with years of early-years experience and a deep belief in the Montessori approach.",
  },
  {
    role: "Montessori Lead Practitioner",
    note: "Guides our prepared environments and supports each child to follow their own curiosity with confidence.",
  },
];

// Six "Partnership with parents" cards (mirrors MRN structure).
export const partnership = [
  {
    icon: "CalendarHeart",
    title: "Settling-in visits",
    text: "Gentle, gradual settling sessions so every child - and parent - feels ready.",
  },
  {
    icon: "Smartphone",
    title: "Daily updates",
    text: "Photos, observations and daily highlights from your child's day, shared regularly.",
  },
  {
    icon: "BookOpen",
    title: "Home learning",
    text: "Simple ideas to carry the Montessori approach into your home.",
  },
  {
    icon: "GraduationCap",
    title: "Parent workshops",
    text: "Friendly sessions to help you understand and support your child's learning.",
  },
  {
    icon: "Users",
    title: "Termly meetings",
    text: "Regular catch-ups with your child's key person to celebrate progress.",
  },
  {
    icon: "MessageCircle",
    title: "Continuous feedback",
    text: "An open door and an honest, two-way partnership, every step of the way.",
  },
];

// Homepage: three large feature blocks (Our Nurseries / Careers / Events).
export const homeBlocks = [
  {
    eyebrow: "Our Nurseries",
    title: "Three welcoming homes across London",
    text: "Hounslow, Heston and Hammersmith, each sharing the same safety standards and authentic Montessori practice.",
    cta: "View nurseries",
    to: "/nurseries",
    image: `/assets/organisation/classroom-main.webp`,
  },
  {
    eyebrow: "Careers",
    title: "Grow with a team that truly cares",
    text: "Join a stable early years team with clear training routes and day-to-day support.",
    cta: "View careers",
    to: "/careers",
    // Portrait crop sits cleanly inside the hexagon; the previous wide
    // two-child shot had heads clipped by the diagonal corners.
    image: `/assets/organisation/portrait-girl.webp`,
    imagePosition: "center top",
  },
  {
    eyebrow: "News & Events",
    title: "Life inside our nurseries",
    text: "Open mornings, nursery updates and seasonal events across our settings.",
    cta: "View events",
    to: "/events",
    image: `/assets/organisation/painting-close.webp`,
  },
];

// Homepage: three key-feature columns.
export const keyFeatures = [
  {
    icon: "ShieldCheck",
    title: "Secure & nurturing",
    text: "Rich experiences in a safe, calm and nurturing environment where every child feels valued.",
  },
  {
    icon: "Sprout",
    title: "Child-centred curriculum",
    text: "Montessori-inspired practice alongside the EYFS, led by each child's own curiosity.",
  },
  {
    icon: "Users",
    title: "Skilled, devoted team",
    text: "Qualified, passionate practitioners - many degree-holders - who know your child by name.",
  },
];

// The four pillars of the Montessori-based approach (from the About page).
export const montessoriApproach = [
  {
    icon: "Sprout",
    title: "Montessori Inspired",
    text: "Helping children build confidence, independence and practical skills.",
  },
  {
    icon: "Leaf",
    title: "Using natural resources",
    text: "Real, tactile materials that nurture happy, healthy, confidently independent learners.",
  },
  {
    icon: "HeartHandshake",
    title: "Parent support & partnership",
    text: "A highly experienced management team offering parents continued support.",
  },
  {
    icon: "Users",
    title: "Connected nursery community",
    text: "Shared standards, experienced leadership and a close team across our Montessori nurseries.",
  },
];

export const testimonials = [
  {
    title: "An outstanding nursery",
    quote:
      "My son has been here since last year. He was only going initially for 15 hours a week, but on all the other days he would be asking non-stop to go back. He was so eager that he would even ask me during the school holidays. This just goes to show how good the nursery is and how caring the staff are. The children are really well looked after, and the team is genuinely focused on child development and stimulation.",
    name: "Parent of a 3-year-old",
    location: "Hammersmith",
  },
  {
    title: "Highly recommend",
    quote:
      "I can only highly recommend Alexandra Montessori, because I can't be grateful enough for how well my son is being looked after. He has even learned some Mandarin now and can say a few words. The care and attention from the whole team have made such a difference to him.",
    name: "Parent of a 4-year-old",
    location: "Heston",
  },
  {
    title: "A calm, confident start",
    quote:
      "You can feel the calm the moment you step inside. The Montessori environment has done wonders for our son's confidence, and the practitioners always know where he is in his learning. He is happy, settled and growing more independent every week.",
    name: "Parent of a 2-year-old",
    location: "Hounslow",
  },
  {
    title: "The team feels like family",
    quote:
      "Our daughter goes in happily every morning and comes home talking about what she has done. The team knows her well, and you can tell how much they care about every child individually. We feel lucky to have found them.",
    name: "Parent of a 3-year-old",
    location: "Ravenscourt",
  },
];

export const accreditations = [
  "Official Ofsted Reports",
  "Montessori Approach",
  "EYFS Curriculum",
  "Food Hygiene Rated",
];

// ---------------------------------------------------------------- About
export const aboutStory = {
  intro:
    "Both the Montessori philosophy and the Early Years Foundation Stage place the unique child at the centre, building positive relationships within an enabling environment. We bring the two together to support confidence, independence and readiness for school.",
  staffIntro:
    "Our team is made up of dedicated Directors, Managers, Room Leaders, Nursery Practitioners and Nursery Assistants - all holding, or working towards, appropriate early-years qualifications, with many degree-holders among them.",
};

// "A Typical Day" - the real Alexandra schedule.
export const dayRhythm = [
  {
    time: "08:00",
    title: "Children arrive",
    text: "A calm, settled welcome as children greet their key person and ease into the day.",
  },
  {
    time: "09:00",
    title: "Circle time",
    text: "Coming together to sing, share and set the tone for the morning.",
  },
  {
    time: "09:15",
    title: "Work cycle - focused work",
    text: "Uninterrupted Montessori time to choose activities and follow their curiosity.",
  },
  {
    time: "11:00",
    title: "Outdoors",
    text: "Fresh air, garden play and exploring the natural world.",
  },
  {
    time: "12:15",
    title: "Lunch",
    text: "Nutritious, freshly prepared meals shared family-style.",
  },
  {
    time: "12:45",
    title: "Rest",
    text: "Quiet time to recharge, with naps for those who need them.",
  },
  {
    time: "13:15",
    title: "Work cycle - crafts, cooking & science",
    text: "Afternoon discovery through creative, extra-curricular and hands-on activities.",
  },
  {
    time: "15:30",
    title: "Tea",
    text: "A wholesome afternoon tea together before the day winds down.",
  },
  {
    time: "16:00",
    title: "Work cycle - independent work",
    text: "Gentle, independent play and learning until home time.",
  },
];

export const snackTimes = ["10:00", "14:00", "17:00"];

// ---------------------------------------------------------------- Curriculum
export const curriculumIntro = [
  {
    title: "The unique child",
    text: "Every child is capable, curious and at the very centre of all we do.",
  },
  {
    title: "The practitioner as guide",
    text: "Our role is to observe, prepare and gently guide - never to direct.",
  },
  {
    title: "The prepared environment",
    text: 'A calm, ordered space, often called the "third teacher", that invites independence.',
  },
  {
    title: "Natural materials",
    text: "Real, tactile, natural resources that make learning concrete and purposeful.",
  },
];

export const framework = [
  {
    icon: "Target",
    title: "Intent",
    text: "A broad, balanced curriculum with clear progression from the very start.",
  },
  {
    icon: "Settings",
    title: "Implementation",
    text: "Montessori practice woven with the EYFS, through child-led work cycles and play.",
  },
  {
    icon: "BarChart3",
    title: "Impact",
    text: "Confident, independent learners who are happy, healthy and school-ready.",
  },
];

export const curriculumAreas = [
  {
    icon: "HandHeart",
    title: "Practical life",
    text: "Everyday activities that build independence, focus and self-belief.",
    image: `/assets/organisation/water-pouring.webp`,
  },
  {
    icon: "Shapes",
    title: "Sensorial",
    text: "Hands-on materials that refine the senses and prepare the mind.",
    image: `/assets/organisation/materials-shelf.webp`,
  },
  {
    icon: "BookOpen",
    title: "Language",
    text: "A rich, talk-filled environment where vocabulary and early reading grow.",
    image: `/assets/organisation/teacher-hug.webp`,
  },
  {
    icon: "Calculator",
    title: "Mathematics",
    text: "Concrete materials make number and pattern tangible before they're abstract.",
    image: `/assets/organisation/xylophone-wide.webp`,
  },
  {
    icon: "Globe2",
    title: "Cultural & nature",
    text: "Geography, science and the natural world build knowledge and respect.",
    image: `/assets/organisation/shape-work.webp`,
  },
  {
    icon: "Palette",
    title: "Creative arts",
    text: "Music, movement and art give every child a creative outlet.",
    image: `/assets/organisation/painting-side.webp`,
  },
];

export const safeguarding = [
  "CCTV across all communal areas",
  "Secure, intercom-controlled entry",
  "No personal mobile phones in rooms",
  "Safer recruitment and full vetting",
  "Enhanced DBS checks for all staff",
  "Ongoing safeguarding training",
  "Strict visitor sign-in protocols",
];

// ---------------------------------------------------------------- Funding
export const funding = [
  {
    title: "Funded childcare from 9 months",
    text: "Eligible working parents of children from 9 months old can register to access 15 hours of government-funded childcare a week.",
    image: `/assets/organisation/funding-babies-outdoors.webp`,
  },
  {
    title: "15 hours free childcare",
    text: "All three and four-year-olds are entitled to 15 hours of free early education a week, over 38 weeks of the year.",
    image: `/assets/organisation/funding-sports-session.webp`,
  },
  {
    title: "30 hours free childcare",
    text: "Working parents of three and four-year-olds may be eligible for an additional 15 hours - up to 30 hours a week.",
    image: `/assets/organisation/friends-two.webp`,
  },
  {
    title: "Two-year-old funding",
    text: "Some two-year-olds also qualify for 15 hours a week, depending on the family's circumstances and eligibility.",
    image: `/assets/organisation/classroom-light.webp`,
  },
];

export const fundingSteps = [
  {
    icon: "ShieldCheck",
    title: "Check eligibility",
    text: "All three and four-year-olds qualify for 15 hours. Extended and younger-child funding depends on work, income and benefits - we'll help you check.",
  },
  {
    icon: "BookOpen",
    title: "Register with us",
    text: "Complete a simple registration form with proof of your child's date of birth and eligibility.",
  },
  {
    icon: "CalendarHeart",
    title: "Claim your hours",
    text: "Sign a short parental declaration each term to confirm your funded hours.",
  },
];

// Per-nursery fee sheets. Derived from `locations` so a single source (the
// nursery's "Fee sheet PDF" CMS field, or the static fallback) drives the /fees
// page. Only branches that actually have a fee sheet URL are listed.
export const feeSheets = locations
  .filter((l) => isNonEmptyString(l.feeSheetPdf))
  .map((l) => ({ name: l.name, href: l.feeSheetPdf }));

// Food Hygiene ratings. Derived from `locations` so the nursery CMS fields
// (rating / date / authority / FSA URL) drive the /food-hygiene-rating page.
export const foodHygieneRatings = locations.map((l) => ({
  name: l.name,
  rating: l.hygieneRating,
  ratingDate: l.hygieneDate,
  authority: l.hygieneAuthority,
  address: l.address,
  href: l.hygieneUrl,
}));

// Trusted external resources for parents researching funded childcare.
export const fundingResources = [
  {
    label: "Childcare Choices",
    href: "https://www.childcarechoices.gov.uk",
    note: "The government hub explaining every type of childcare support.",
  },
  {
    label: "GOV.UK - Help with childcare costs",
    href: "https://www.gov.uk/help-with-childcare-costs",
    note: "Eligibility and how to apply for funded childcare.",
  },
  {
    label: "Hammersmith & Fulham Council",
    href: "https://www.lbhf.gov.uk/children-and-young-people/family-hub/early-years-and-childcare/help-childcare-costs",
    note: "Local funding information for our Hammersmith families.",
  },
];

export const fundingFeatures = [
  {
    icon: "Sprout",
    title: "Montessori curriculum",
    text: "Funded hours, full Montessori experience - no compromise on quality.",
  },
  {
    icon: "Users",
    title: "Qualified staff",
    text: "Warm, highly trained practitioners caring for your child.",
  },
  {
    icon: "Clock",
    title: "Flexible hours",
    text: "Stretch your funded hours across the year to suit your family.",
  },
  {
    icon: "HeartHandshake",
    title: "Inclusive community",
    text: "A diverse, welcoming setting where every family belongs.",
  },
];

// ---------------------------------------------------------------- Careers
export const careersBenefits = [
  {
    icon: "HeartHandshake",
    title: "A friendly environment",
    text: "A supportive team culture built on respect, creativity and clear communication.",
  },
  {
    icon: "TrendingUp",
    title: "Career prospects",
    text: "We encourage NVQ Level 2 & 3, foundation degrees and teaching qualifications.",
  },
  {
    icon: "GraduationCap",
    title: "Continuous training",
    text: "Regular in-house training and genuine support to help you reach your potential.",
  },
];

export const careersLookingFor = [
  {
    icon: "Heart",
    title: "Passion for child development",
    text: "A genuine love for nurturing young children and their earliest years.",
  },
  {
    icon: "Users",
    title: "Teamwork & collaboration",
    text: "A warm, dependable colleague who thrives as part of a close team.",
  },
  {
    icon: "Sprout",
    title: "Continuous learning & growth",
    text: "An open, curious mind that's always keen to learn and improve.",
  },
];

export const trainingList = [
  "Paediatric first aid",
  "Safeguarding",
  "Food hygiene",
  "Fire drill & prevention",
  "Child development",
  "Curriculum training",
  "Planning & observations",
  "Customer care",
];

// Qualifications & pathways the team can grow through (from the Careers page).
export const qualificationsOffered = [
  "T-Levels",
  "Level 2 & 3 Diplomas in Early Years",
  "Foundation Degree in Early Years",
  "Early Childhood Studies degree",
  "Teaching qualifications",
];

export const careersGallery = [
  `/assets/organisation/teacher-hug.webp`,
  `/assets/organisation/friends-two.webp`,
  `/assets/organisation/painting-close.webp`,
  `/assets/organisation/water-pouring.webp`,
  `/assets/organisation/fine-motor.webp`,
];

// Full set of image assets for the "View more" gallery lightbox.
// Public assets are served from /assets/* in production.
export const galleryImages = [
  ...careersGallery,
  `/assets/organisation/practical-kitchen.webp`,
  `/assets/organisation/plant-corner.webp`,
  `/assets/organisation/materials-shelf.webp`,
  `/assets/organisation/portrait-girl.webp`,
  `/assets/organisation/toddler-smile.webp`,
  `/assets/organisation/sensory-box.webp`,
  `/assets/organisation/shape-work.webp`,
  `/assets/organisation/tree-work.webp`,
  `/assets/organisation/movement-play.webp`,
  `/assets/organisation/pencils-wide.webp`,
];

export const qualificationLevels = [
  "Unqualified / trainee",
  "NVQ Level 2",
  "NVQ Level 3",
  "Foundation degree",
  "BA / MA",
];

export const positions = [
  "Apprentice",
  "Nursery Practitioner",
  "Montessori Practitioner",
  "Room Leader",
  "Deputy Manager",
  "Nursery Manager",
];

// ---------------------------------------------------------------- Events
export const events = [
  {
    title: "Open morning at our Hammersmith nursery",
    date: "2026-07-05",
    time: "9:30am - 11:30am",
    location: "Hammersmith, Ravenscourt",
    image: `/assets/organisation/painting-close.webp`,
    excerpt:
      "Come and explore our Ravenscourt studios, meet the team and see the Montessori approach in action. Book your place to secure a spot.",
  },
  {
    title: "Stay & play taster session",
    date: "2026-07-12",
    time: "10:00am - 11:00am",
    location: "Heston",
    image: `/assets/organisation/materials-shelf.webp`,
    excerpt:
      "A relaxed morning for little ones and their grown-ups to play, explore and get a feel for nursery life.",
  },
  {
    title: "Summer celebration & garden party",
    date: "2026-07-19",
    time: "11:00am - 1:00pm",
    location: "All nurseries",
    image: `/assets/organisation/movement-play.webp`,
    excerpt:
      "Music, games and outdoor fun as we celebrate a wonderful year of growing, learning and discovery together.",
  },
  {
    title: "Settling-in week for new starters",
    date: "2026-09-01",
    time: "By appointment",
    location: "All nurseries",
    image: `/assets/organisation/friends-two.webp`,
    excerpt:
      "Gentle, gradual settling sessions to help new children - and their families - feel at home before they start.",
  },
];

// ---------------------------------------------------------------- Blogs & resources
export const blogs = [
  {
    title: "Settling your child into nursery: a gentle guide",
    date: "2026-06-02",
    category: "For parents",
    image: `/assets/organisation/teacher-hug.webp`,
    excerpt:
      "Practical, reassuring tips to make those first days calm and happy - for your child and for you.",
  },
  {
    title: "The Montessori work cycle, explained",
    date: "2026-05-18",
    category: "Montessori",
    image: `/assets/organisation/xylophone-floor.webp`,
    excerpt:
      'What the "uninterrupted work cycle" really means, and why it builds focus, independence and joy.',
  },
  {
    title: "Healthy lunchboxes & nursery nutrition",
    date: "2026-05-04",
    category: "Nutrition",
    image: `/assets/organisation/practical-kitchen.webp`,
    excerpt:
      "How we plan fresh, balanced meals - and simple ideas to carry good habits home.",
  },
  {
    title: "A simple guide to funded childcare",
    date: "2026-04-15",
    category: "Funding",
    image: `/assets/organisation/pencils-wide.webp`,
    excerpt:
      "From 15 hours at nine months to 30 hours for working parents - what's on offer and how to claim it.",
  },
];

export const faqs = [
  {
    q: "What ages do you care for?",
    a: "All three nurseries welcome children from Babies to 5 Years.",
  },
  {
    q: "Do you offer funded childcare?",
    a: "Yes - we accept 15-hour, 30-hour and eligible two-year-old funding, plus funded hours from nine months for eligible working parents. Our team will guide you through it.",
  },
  {
    q: "What are your opening hours?",
    a: "Our nurseries are open Monday to Friday, 8am to 6pm.",
  },
  {
    q: "What makes Alexandra Montessori different?",
    a: "We're a close Montessori nursery community with authentic practice, natural resources and a highly qualified team.",
  },
];
