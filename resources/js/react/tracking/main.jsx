import { StrictMode } from "react";
import { createRoot } from "react-dom/client";
import "../landing/index.css";
import PublicTrackingPage from "./PublicTrackingPage.jsx";

const rootElement = document.getElementById("react-root");

if (rootElement) {
  const initialTracking = rootElement.dataset.initialTracking ?? "";

  createRoot(rootElement).render(
    <StrictMode>
      <PublicTrackingPage initialTracking={initialTracking} />
    </StrictMode>
  );
}