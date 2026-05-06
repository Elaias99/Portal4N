import { StrictMode } from "react";
import { createRoot } from "react-dom/client";
import Sidebar from "./Sidebar.jsx";
import "./sidebar.css";

const rootElement = document.getElementById("portal-sidebar-root");

if (rootElement) {
    const userName = rootElement.dataset.userName || "";
    const canOpenMenu = rootElement.dataset.canOpenMenu === "true";

    createRoot(rootElement).render(
        <StrictMode>
            <Sidebar userName={userName} canOpenMenu={canOpenMenu} />
        </StrictMode>
    );
}