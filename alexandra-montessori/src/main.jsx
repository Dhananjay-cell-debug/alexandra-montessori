import { StrictMode } from "react";
import { createRoot } from "react-dom/client";
import { BrowserRouter } from "react-router-dom";
import "./index.css";
import ScrollManager from "./components/ScrollManager.jsx";
import Analytics from "./components/Analytics.jsx";
import AppRoutes from "./routes.jsx";
import VisualBuilderPreview from "./components/VisualBuilderPreview.jsx";

createRoot(document.getElementById("root")).render(
  <StrictMode>
    {window.amVisualBuilderPreview ? (
      <VisualBuilderPreview />
    ) : (
      <BrowserRouter>
        <ScrollManager />
        <Analytics />
        <AppRoutes />
      </BrowserRouter>
    )}
  </StrictMode>,
);
