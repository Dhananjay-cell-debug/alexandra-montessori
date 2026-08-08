import { Outlet } from "react-router-dom";
import Navbar from "./components/Navbar";
import Footer from "./components/Footer";
import SocialSidebar from "./components/SocialSidebar";
import CookieBar from "./components/CookieBar";

export default function App() {
  return (
    <div className="flex min-h-screen flex-col bg-cream">
      <a href="#main" className="skip-link">
        Skip to Main Content
      </a>
      <Navbar />
      <SocialSidebar />
      <main id="main" className="bg-cream">
        <Outlet />
      </main>
      {/* mt-auto keeps the footer at the bottom on short pages; the fill above it
 is the footer's own green, so there is no empty cream band. */}
      <Footer />
      <CookieBar />
    </div>
  );
}
