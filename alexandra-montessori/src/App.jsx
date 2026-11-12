import { Outlet, useLocation } from "react-router-dom";
import Navbar from "./components/Navbar";
import Footer from "./components/Footer";
import SocialSidebar from "./components/SocialSidebar";
import CookieBar from "./components/CookieBar";
import NotFound from "./pages/NotFound";
import { useWorkspacePages } from "./lib/workspace";

export default function App() {
  const { pathname } = useLocation();
  const directory = useWorkspacePages();
  const page = directory?.find((item) => item.route === pathname);
  const hidden = page?.visible === false && !window.__AM_WORKSPACE__?.studio?.edit;
  return (
    <div className="flex min-h-screen flex-col bg-cream">
      <a href="#main" className="skip-link">
        Skip to Main Content
      </a>
      <Navbar />
      <SocialSidebar />
      <main id="main" className="bg-cream">
        {hidden ? <NotFound /> : <Outlet />}
      </main>
      {/* mt-auto keeps the footer at the bottom on short pages; the fill above it
 is the footer's own green, so there is no empty cream band. */}
      <Footer />
      <CookieBar />
    </div>
  );
}
