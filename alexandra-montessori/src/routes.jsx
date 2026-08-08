import { Suspense, lazy } from "react";
import { Routes, Route } from "react-router-dom";
import App from "./App.jsx";
import Home from "./pages/Home.jsx";

const Nurseries = lazy(() => import("./pages/Nurseries.jsx"));
const NurseryDetail = lazy(() => import("./pages/NurseryDetail.jsx"));
const About = lazy(() => import("./pages/About.jsx"));
const Curriculum = lazy(() => import("./pages/Curriculum.jsx"));
const Fees = lazy(() => import("./pages/Fees.jsx"));
const FeeCalculator = lazy(() => import("./pages/FeeCalculator.jsx"));
const FundedChildcare = lazy(() => import("./pages/FundedChildcare.jsx"));
const Blogs = lazy(() => import("./pages/Blogs.jsx"));
const BlogArticle = lazy(() => import("./pages/BlogArticle.jsx"));
const FoodHygieneRating = lazy(() => import("./pages/FoodHygieneRating.jsx"));
const Events = lazy(() => import("./pages/Events.jsx"));
const EventDetail = lazy(() => import("./pages/EventDetail.jsx"));
const Testimonials = lazy(() => import("./pages/Testimonials.jsx"));
const Careers = lazy(() => import("./pages/Careers.jsx"));
const Vacancies = lazy(() => import("./pages/Vacancies.jsx"));
const VacancyDetail = lazy(() => import("./pages/VacancyDetail.jsx"));
const Apply = lazy(() => import("./pages/Apply.jsx"));
const Availability = lazy(() => import("./pages/Availability.jsx"));
const Contact = lazy(() => import("./pages/Contact.jsx"));
const ContactLocation = lazy(() => import("./pages/ContactLocation.jsx"));
const Privacy = lazy(() => import("./pages/Privacy.jsx"));
const NotFound = lazy(() => import("./pages/NotFound.jsx"));

export default function AppRoutes() {
  return (
    <Suspense fallback={null}>
      <Routes>
        <Route path="/" element={<App />}>
          <Route index element={<Home />} />
          <Route path="nurseries" element={<Nurseries />} />
          <Route path="nurseries/:slug" element={<NurseryDetail />} />
          <Route path="about" element={<About />} />
          <Route path="curriculum" element={<Curriculum />} />
          <Route path="fees" element={<Fees />} />
          <Route path="fee-calculator" element={<FeeCalculator />} />
          <Route path="funded-childcare" element={<FundedChildcare />} />
          <Route path="blogs" element={<Blogs />} />
          <Route path="blogs/:slug" element={<BlogArticle />} />
          <Route path="food-hygiene-rating" element={<FoodHygieneRating />} />
          <Route path="events" element={<Events />} />
          <Route path="events/:slug" element={<EventDetail />} />
          <Route path="testimonials" element={<Testimonials />} />
          <Route path="careers" element={<Careers />} />
          <Route path="careers/vacancies" element={<Vacancies />} />
          <Route path="careers/vacancies/:slug" element={<VacancyDetail />} />
          <Route path="careers/apply" element={<Apply />} />
          <Route path="check-availability" element={<Availability />} />
          <Route path="contact" element={<Contact />} />
          <Route path="contact/:slug" element={<ContactLocation />} />
          <Route path="privacy" element={<Privacy />} />
          <Route path="*" element={<NotFound />} />
        </Route>
      </Routes>
    </Suspense>
  );
}
