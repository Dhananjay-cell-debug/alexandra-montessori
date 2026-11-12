import { StrictMode } from "react";
import { createRoot } from "react-dom/client";
import { BrowserRouter } from "react-router-dom";
import AppRoutes from "./routes";
import "./index.css";

// WordPress serves the app from the site root, so no router basename is
// needed; only the asset base changes between the static and theme builds.
createRoot(document.getElementById("root")).render(
  <StrictMode>
    <BrowserRouter>
      <AppRoutes />
    </BrowserRouter>
  </StrictMode>,
);
