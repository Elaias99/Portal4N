import { StrictMode } from "react";
import { createRoot } from "react-dom/client";
import Sidebar from "./Sidebar.jsx";
import "./sidebar.css";

const rootElement = document.getElementById("portal-sidebar-root");

if (rootElement) {
    const userName = rootElement.dataset.userName || "";
    const logoUrl = rootElement.dataset.logoUrl || "";
    const canOpenMenu = rootElement.dataset.canOpenMenu === "true";
    const canSeeAdminMenu = rootElement.dataset.canSeeAdminMenu === "true";
    const canSeeAdminPanel = rootElement.dataset.canSeeAdminPanel === "true";
    const canSeeAdminOnly = rootElement.dataset.canSeeAdminOnly === "true";
    const canSeeTrackingMenu = rootElement.dataset.canSeeTrackingMenu === "true";

    let routes = {};

    try {
        routes = JSON.parse(rootElement.dataset.routes || "{}");
    } catch (error) {
        console.error("No se pudieron leer las rutas del sidebar.", error);
    }

    createRoot(rootElement).render(
        <StrictMode>
            <Sidebar
                userName={userName}
                logoUrl={logoUrl}
                canOpenMenu={canOpenMenu}
                canSeeAdminMenu={canSeeAdminMenu}
                canSeeAdminOnly={canSeeAdminOnly}
                canSeeAdminPanel={canSeeAdminPanel}
                canSeeTrackingMenu={canSeeTrackingMenu}
                routes={routes}
            />
        </StrictMode>
    );
}