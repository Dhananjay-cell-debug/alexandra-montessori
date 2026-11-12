import { useLocation } from "react-router-dom";
import Seo from "../components/Seo";
import { useWorkspacePages } from "../lib/workspace";

export default function WorkspacePage({ fallback }) {
  const { pathname } = useLocation();
  useWorkspacePages();
  const page = window.amWorkspace?.customPages?.find((item) => item.route === pathname);
  if (!page) return fallback;
  // The workspace runtime places the saved section trees inside the shared main.
  return <Seo path={pathname} title={page.label} />;
}
