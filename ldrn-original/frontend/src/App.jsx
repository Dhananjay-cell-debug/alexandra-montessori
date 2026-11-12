import { Outlet, useLocation } from "react-router-dom";
import Navbar from "./components/Navbar";
import Footer from "./components/Footer";
import ScrollManager from "./components/ScrollManager";
import NotFound from "./pages/NotFound";
import { useWorkspacePages } from "./lib/workspace";

/**
 * Site shell. The header is fixed, so `main` carries matching top padding at
 * each breakpoint to keep the first heading clear of the bar.
 */
export default function App() {
  const { pathname } = useLocation();
  const directory = useWorkspacePages();
  const page = directory?.find((item) => item.route === pathname);
  const hidden = page?.visible === false && !window.__LDRN_WORKSPACE__?.studio?.edit;
  return (
    <>
      <ScrollManager />
      <a href="#main" className="skip-link">
        Skip to content
      </a>
      <Navbar />
      <main id="main" className="pt-[79px] lg:pt-[83px]">
        {hidden ? <NotFound /> : <Outlet />}
      </main>
      <Footer />
    </>
  );
}
