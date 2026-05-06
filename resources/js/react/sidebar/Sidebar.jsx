import { useEffect, useMemo, useState } from "react";

function SidebarLink({ href, icon, children, onNavigate }) {
    if (!href) return null;

    return (
        <a className="portal-sidebar-link" href={href} onClick={onNavigate}>
            <i className={icon} aria-hidden="true"></i>
            <span>{children}</span>
        </a>
    );
}

function SidebarSection({ id, title, icon, items, openSection, setOpenSection, onNavigate }) {
    const isOpen = openSection === id;

    if (!items.length) return null;

    return (
        <div className="portal-sidebar-section">
            <button
                type="button"
                className="portal-sidebar-section__button"
                onClick={() => setOpenSection(isOpen ? "" : id)}
                aria-expanded={isOpen}
            >
                <span>
                    <i className={icon} aria-hidden="true"></i>
                    {title}
                </span>

                <i
                    className={`fas fa-chevron-${isOpen ? "up" : "down"}`}
                    aria-hidden="true"
                ></i>
            </button>

            {isOpen && (
                <div className="portal-sidebar-section__content">
                    {items.map((item) => (
                        <SidebarLink
                            key={item.label}
                            href={item.href}
                            icon={item.icon}
                            onNavigate={onNavigate}
                        >
                            {item.label}
                        </SidebarLink>
                    ))}
                </div>
            )}
        </div>
    );
}

export default function Sidebar({
    userName,
    canOpenMenu,
    canSeeAdminMenu = false,
    canSeeAdminOnly = false,
    canSeeAdminPanel = false,
    canSeeTrackingMenu = false,
    routes = {},
}) {
    const [open, setOpen] = useState(false);
    const [openSection, setOpenSection] = useState("");

    useEffect(() => {
        if (!open) return;

        const handleKeyDown = (event) => {
            if (event.key === "Escape") {
                setOpen(false);
            }
        };

        document.body.classList.add("portal-sidebar-is-open");
        document.addEventListener("keydown", handleKeyDown);

        return () => {
            document.body.classList.remove("portal-sidebar-is-open");
            document.removeEventListener("keydown", handleKeyDown);
        };
    }, [open]);

    const sections = useMemo(() => {
        const builtSections = [];

        if (canSeeAdminMenu) {
            builtSections.push({
                id: "employees",
                title: "Información de Empleados",
                icon: "fas fa-address-book",
                items: [
                    {
                        label: "Trabajadores",
                        href: routes.empleadosIndex,
                        icon: "fas fa-user-friends",
                    },
                    {
                        label: "Zonas de Residencia",
                        href: routes.empleadosLocalidades,
                        icon: "fas fa-map-marker-alt",
                    },
                    {
                        label: "Hijos de Empleados",
                        href: routes.hijosIndex,
                        icon: "fas fa-child",
                    },
                    {
                        label: "Tallas de Uniformes",
                        href: routes.tallasIndex,
                        icon: "fas fa-tshirt",
                    },
                    {
                        label: "Áreas de Trabajo",
                        href: routes.areasIndex,
                        icon: "fa-solid fa-tags",
                    },
                ],
            });

            builtSections.push({
                id: "requests",
                title: "Solicitudes y Permisos",
                icon: "fas fa-file-signature",
                items: [
                    {
                        label: "Solicitudes de Modificación",
                        href: routes.solicitudesIndex,
                        icon: "fas fa-edit",
                    },
                    {
                        label: "Solicitudes de Días",
                        href: routes.solicitudesVacaciones,
                        icon: "fas fa-calendar-day",
                    },
                ],
            });
        }

        if (canSeeTrackingMenu) {
            builtSections.push({
                id: "tracking",
                title: "Tracking",
                icon: "fas fa-truck-moving",
                items: [
                    {
                        label: "Seguimiento Tracking",
                        href: routes.trackingDeliveryLinks,
                        icon: "fas fa-search-location",
                    },
                    {
                        label: "Etiquetas Zebra",
                        href: routes.labels,
                        icon: "fas fa-barcode",
                    },
                ],
            });
        }

        return builtSections;
    }, [canSeeAdminMenu, canSeeTrackingMenu, routes]);

    const singleLinks = useMemo(() => {
        const links = [];

        if (canSeeAdminMenu) {
            links.push({
                label: "Archivos Adjuntos",
                href: routes.archivosRespaldo,
                icon: "fas fa-folder-open",
            });
        }

        if (canSeeAdminOnly) {
            links.push({
                label: "Centro de Gestión",
                href: routes.adminIndex,
                icon: "fas fa-cogs",
            });

            links.push({
                label: "Historial de Vacaciones",
                href: routes.historialVacacion,
                icon: "fas fa-history",
            });
        }

        if (canSeeAdminPanel) {
            links.push({
                label: "Panel Administrativo",
                href: routes.adminControlPanel,
                icon: "fas fa-tools",
            });
        }

        return links;
    }, [canSeeAdminMenu, canSeeAdminOnly, canSeeAdminPanel, routes]);

    const closeSidebar = () => {
        setOpen(false);
    };

    if (!canOpenMenu) {
        return null;
    }

    return (
        <>
            <button
                type="button"
                className="portal-sidebar-trigger"
                onClick={() => setOpen(true)}
                aria-expanded={open}
                aria-controls="portal-react-sidebar"
            >
                <i className="fas fa-bars" aria-hidden="true"></i>
                <span>Menú</span>
            </button>

            {open && (
                <div className="portal-sidebar-layer">
                    <button
                        type="button"
                        className="portal-sidebar-backdrop"
                        onClick={closeSidebar}
                        aria-label="Cerrar menú"
                    ></button>

                    <aside
                        id="portal-react-sidebar"
                        className="portal-sidebar-panel"
                        aria-label="Navegación rápida"
                    >
                        <div className="portal-sidebar-header">
                            <div>
                                <h2>Navegación Rápida</h2>
                                <p>{userName}</p>
                            </div>

                            <button
                                type="button"
                                className="portal-sidebar-close"
                                onClick={closeSidebar}
                                aria-label="Cerrar menú"
                            >
                                ×
                            </button>
                        </div>

                        <div className="portal-sidebar-logo">
                            <img src="/images/logo.png" alt="4Nortes Logística" />
                        </div>

                        <nav className="portal-sidebar-nav" aria-label="Menú principal">
                            {sections.map((section) => (
                                <SidebarSection
                                    key={section.id}
                                    id={section.id}
                                    title={section.title}
                                    icon={section.icon}
                                    items={section.items}
                                    openSection={openSection}
                                    setOpenSection={setOpenSection}
                                    onNavigate={closeSidebar}
                                />
                            ))}

                            {singleLinks.length > 0 && (
                                <div className="portal-sidebar-single-links">
                                    {singleLinks.map((item) => (
                                        <SidebarLink
                                            key={item.label}
                                            href={item.href}
                                            icon={item.icon}
                                            onNavigate={closeSidebar}
                                        >
                                            {item.label}
                                        </SidebarLink>
                                    ))}
                                </div>
                            )}
                        </nav>
                    </aside>
                </div>
            )}
        </>
    );
}