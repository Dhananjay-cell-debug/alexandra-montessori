import { lazy, Suspense } from "react";
import { Navigate, Route, Routes, useParams } from "react-router-dom";
import App from "./App";
import Home from "./pages/Home";

function LegacySettingRedirect() {
  const { slug } = useParams();
  return <Navigate to={`/our-nursery/${slug}`} replace />;
}

// Home ships in the entry bundle because it is the most requested route.
// Everything else is code-split so a first visit stays small.
const Settings = lazy(() => import("./pages/Settings"));
const SettingDetail = lazy(() => import("./pages/SettingDetail"));
const About = lazy(() => import("./pages/About"));
const Ofsted = lazy(() => import("./pages/Ofsted"));
const Curriculum = lazy(() => import("./pages/Curriculum"));
const Events = lazy(() => import("./pages/Events"));
const EventDetail = lazy(() => import("./pages/EventDetail"));
const Fees = lazy(() => import("./pages/Fees"));
const FeeCalculator = lazy(() => import("./pages/FeeCalculator"));
const FundedChildcare = lazy(() => import("./pages/FundedChildcare"));
const Blogs = lazy(() => import("./pages/Blogs"));
const BlogDetail = lazy(() => import("./pages/BlogDetail"));
const WorkspacePage = lazy(() => import("./pages/WorkspacePage"));
const FoodHygieneRating = lazy(() => import("./pages/FoodHygieneRating"));
const Admissions = lazy(() => import("./pages/Admissions"));
const BookAVisit = lazy(() => import("./pages/BookAVisit"));
const Careers = lazy(() => import("./pages/Careers"));
const Vacancies = lazy(() => import("./pages/Vacancies"));
const Apply = lazy(() => import("./pages/Apply"));
const Testimonials = lazy(() => import("./pages/Testimonials"));
const Contact = lazy(() => import("./pages/Contact"));
const Privacy = lazy(() => import("./pages/Privacy"));
const NotFound = lazy(() => import("./pages/NotFound"));

export default function AppRoutes() {
  return (
    <Suspense fallback={<div className="min-h-[60vh]" />}>
      <Routes>
        <Route path="/" element={<App />}>
          <Route index element={<Home />} />
          <Route path="our-nursery" element={<Settings />} />
          <Route path="our-nursery/:slug" element={<SettingDetail />} />
          {/* Former /settings addresses; the server also answers them with a 301. */}
          <Route path="settings" element={<Navigate to="/our-nursery" replace />} />
          <Route path="settings/:slug" element={<LegacySettingRedirect />} />
          <Route path="about" element={<About />} />
          <Route path="about/ofsted" element={<Ofsted />} />
          {/* Team content is blocked pending confirmed ownership details. */}
          <Route
            path="about/meet-the-team"
            element={<Navigate to="/about" replace />}
          />
          <Route path="curriculum" element={<Curriculum />} />
          <Route path="events" element={<Events />} />
          <Route path="events/:slug" element={<EventDetail />} />
          <Route path="fees" element={<Fees />} />
          <Route path="fee-calculator" element={<FeeCalculator />} />
          <Route path="funded-childcare" element={<FundedChildcare />} />
          <Route path="blogs" element={<Blogs />} />
          <Route path="blogs/:slug" element={<BlogDetail />} />
          <Route path="food-hygiene-rating" element={<FoodHygieneRating />} />
          {/* Legacy path kept so existing links and search results resolve. */}
          <Route
            path="fees-and-funding"
            element={<Navigate to="/funded-childcare" replace />}
          />
          <Route path="admissions" element={<Admissions />} />
          <Route path="book-a-visit" element={<BookAVisit />} />
          <Route path="check-availability" element={<BookAVisit />} />
          <Route path="careers" element={<Careers />} />
          <Route path="careers/vacancies" element={<Vacancies />} />
          <Route path="careers/apply" element={<Apply />} />
          <Route path="testimonials" element={<Testimonials />} />
          <Route path="contact" element={<Contact />} />
          <Route path="privacy" element={<Privacy />} />
          <Route path="*" element={<WorkspacePage fallback={<NotFound />} />} />
        </Route>
      </Routes>
    </Suspense>
  );
}
