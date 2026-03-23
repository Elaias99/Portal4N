import React, { useEffect, useMemo, useRef, useState } from "react";
import { motion } from "framer-motion";
import {
  ArrowRight,
  BadgeCheck,
  Building2,
  ChevronRight,
  ChevronDown,
  Clock3,
  Globe2,
  HeartHandshake,
  Layers3,
  MapPinned,
  Menu,
  MessageSquareMore,
  Package,
  Search,
  Send,
  ShieldCheck,
  Truck,
  Warehouse,
  X,
  Route,
  Users,
  Boxes,
  Phone,
  Mail,
  MapPin,
  ExternalLink,
  ImageIcon,
  UserRound,
  Box,
} from "lucide-react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Badge } from "@/components/ui/badge";
import { fetchPublicTracking } from "../tracking/api.js";
import TrackingStatusBadge from "../tracking/components/TrackingStatusBadge.jsx";
import chileMapRaw from "./assets/chile.svg?raw";

const palette = {
  dark: "#231F21",
  gray: "#AFAFAF",
  light: "#B0FFF8",
  teal: "#5CBABC",
};

const navItems = [
  { label: "Inicio", href: "#inicio" },
  { label: "Empresa", href: "#empresa" },
  { label: "Servicios", href: "#servicios" },
  { label: "Cobertura", href: "#cobertura" },
  { label: "Tracking", href: "#inicio" },
  { label: "Contacto", href: "#contacto" },
];

const reasons = [
  {
    icon: Globe2,
    title: "Cobertura nacional real",
    text: "Operación de norte a sur con conocimiento del territorio y rutas definidas.",
  },
  {
    icon: Layers3,
    title: "Soluciones a medida",
    text: "Servicios adaptados según volumen, industria y urgencia.",
  },
  {
    icon: ShieldCheck,
    title: "Operación confiable",
    text: "Cumplimiento de plazos exigentes, incluyendo entregas de 24 a 48 horas.",
  },
  {
    icon: Users,
    title: "Escala humana",
    text: "Trato directo, respuestas claras y responsables reales a cargo de cada operación.",
  },
  {
    icon: HeartHandshake,
    title: "Equipo Postventa",
    text: "Atención directa, sin bots ni respuestas impersonales.",
  },
  {
    icon: Search,
    title: "Trackeo permanente",
    text: "Seguimiento continuo y trazabilidad de cada envío desde origen a destino.",
  },
  {
    icon: Truck,
    title: "Flota propia",
    text: "Flota propia desde furgones de 7 m³ hasta ramplas de 100 m³.",
  },
];

const services = [
  {
    icon: Truck,
    title: "Transporte",
    description:
      "Transporte troncal y distribución nacional con control de rutas, tiempos y trazabilidad.",
  },
  {
    icon: Warehouse,
    title: "Almacenaje",
    description:
      "Bodegas estratégicas, control de inventario, trazabilidad y escalabilidad.",
  },
  {
    icon: Boxes,
    title: "Fulfillment",
    description:
      "Preparación de pedidos, integración con transporte y operación sin intermediarios.",
  },
  {
    icon: Route,
    title: "Última milla",
    description:
      "Entrega final con seguimiento, control del último tramo y postventa humana.",
  },
  {
    icon: MapPinned,
    title: " Servicio Punto a punto",
    description:
      "Traslados dedicados entre ubicaciones específicas con coordinación operativa directa.",
  },
  {
    icon: BadgeCheck,
    title: "Valijas y encomiendas",
    description:
      "Valijas o encomiendas entre puntos fijos con rutas y frecuencia definidas.",
  },
];

const regions = [
  "Arica y Parinacota",
  "Tarapacá",
  "Antofagasta",
  "Atacama",
  "Coquimbo",
  "Valparaíso",
  "Metropolitana",
  "O’Higgins",
  "Maule",
  "Ñuble",
  "Biobío",
  "Araucanía",
  "Los Ríos",
  "Los Lagos",
  "Aysén",
  "Magallanes",
];

const coverageMarkers = [
  { label: "Arica", x: 578, y: 18 },
  { label: "Antofagasta", x: 586, y: 92 },
  { label: "Atacama", x: 575, y: 152 },
  { label: "Valparaíso", x: 560, y: 238 },
  { label: "RM", x: 560, y: 254 },
  { label: "Maule", x: 548, y: 289 },
  { label: "Concepción", x: 536, y: 322 },
  { label: "Araucanía", x: 534, y: 343 },
  { label: "Los Lagos", x: 525, y: 405 },
  { label: "Magallanes", x: 540, y: 626 },
];

const chileMapInner = chileMapRaw
  .replace(/<\?xml[\s\S]*?\?>/g, "")
  .replace(/<svg[^>]*>/i, "")
  .replace(/<\/svg>\s*$/i, "");

const fadeUp = {
  hidden: { opacity: 0, y: 24 },
  visible: { opacity: 1, y: 0 },
};






function formatTrackingDate(value) {
  if (!value) return "Sin registro disponible";

  const date = new Date(value);

  if (Number.isNaN(date.getTime())) {
    return value;
  }

  return new Intl.DateTimeFormat("es-CL", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
    hour: "numeric",
    minute: "2-digit",
    second: "2-digit",
  }).format(date);
}

function SectionHeader({ eyebrow, title, description, light = false }) {
  return (
    <div className="max-w-3xl space-y-4">
      <Badge
        variant="outline"
        className={`rounded-full border px-4 py-1 text-xs uppercase tracking-[0.22em] ${
          light ? "border-white/20 text-[#B0FFF8]" : "border-[#5CBABC]/30 text-[#231F21]"
        }`}
      >
        {eyebrow}
      </Badge>
      <h2
        className={`font-['Kanit'] text-3xl font-semibold tracking-tight sm:text-4xl ${
          light ? "text-white" : "text-[#231F21]"
        }`}
      >
        {title}
      </h2>
      <p className={`max-w-2xl text-base leading-7 ${light ? "text-white/75" : "text-[#231F21]/70"}`}>
        {description}
      </p>
    </div>
  );
}

function Logo() {
  return (
    <a href="#inicio" className="flex items-center shrink-0">
      <img
        src="/images/logo.png"
        alt="4N Logística"
        className="h-9 w-auto sm:h-10 lg:h-12 object-contain"
        loading="eager"
      />
    </a>
  );
}

function ChileRouteGraphic() {
  const points = useMemo(
    () => [
      { top: "5%", left: "50%", label: "Arica" },
      { top: "14%", left: "48%", label: "Antofagasta" },
      { top: "22%", left: "49%", label: "Atacama" },
      { top: "33%", left: "50%", label: "Valparaíso" },
      { top: "38%", left: "53%", label: "RM" },
      { top: "49%", left: "51%", label: "Maule" },
      { top: "58%", left: "52%", label: "Concepción" },
      { top: "67%", left: "50%", label: "Araucanía" },
      { top: "79%", left: "47%", label: "Los Lagos" },
      { top: "92%", left: "43%", label: "Magallanes" },
    ],
    []
  );

  return (
    <div className="relative mx-auto h-[560px] w-full max-w-[260px] rounded-[2rem] border border-white/10 bg-white/5 p-6 backdrop-blur">
      <div className="absolute inset-0 rounded-[2rem] bg-[radial-gradient(circle_at_top,_rgba(92,186,188,0.18),_transparent_55%)]" />
      <svg viewBox="0 0 120 520" className="relative z-10 h-full w-full">
        <defs>
          <linearGradient id="chileStroke" x1="0" x2="0" y1="0" y2="1">
            <stop offset="0%" stopColor="#B0FFF8" />
            <stop offset="55%" stopColor="#5CBABC" />
            <stop offset="100%" stopColor="#AFAFAF" />
          </linearGradient>
        </defs>
        <path
          d="M61 9c-6 14-14 18-11 34 2 12 1 24-6 35-6 10-9 20-5 31 5 15 5 24-2 38-6 12-6 22 0 33 7 13 7 27 0 40-8 16-7 29-1 43 7 16 6 32-1 48-9 18-8 34 0 51 8 17 9 31 2 46-6 12-7 24 1 37 6 11 6 21-2 30-8 10-10 19-5 31 4 10 2 20-5 31-8 13-9 26-4 40 4 11 3 20-2 29-6 12-8 21-2 32"
          fill="none"
          stroke="url(#chileStroke)"
          strokeWidth="10"
          strokeLinecap="round"
        />
        <path
          d="M78 363c8 12 11 28 3 42-6 11-7 24-1 36 5 11 3 20-5 30-7 9-9 18-5 30 3 8 1 14-3 19"
          fill="none"
          stroke="url(#chileStroke)"
          strokeWidth="8"
          strokeLinecap="round"
        />
      </svg>
      {points.map((point) => (
        <div
          key={point.label}
          className="absolute z-20 flex items-center gap-2"
          style={{ top: point.top, left: point.left }}
        >
          <span className="h-3 w-3 rounded-full bg-[#B0FFF8] shadow-[0_0_0_6px_rgba(176,255,248,0.12)]" />
          <span className="whitespace-nowrap text-xs font-medium text-white/80">{point.label}</span>
        </div>
      ))}
    </div>
  );
}

export default function FourNLogisticaWebsite() {
  const [mobileOpen, setMobileOpen] = useState(false);
  const [tracking, setTracking] = useState("");
  const [trackingResult, setTrackingResult] = useState(null);
  const quoteFormRef = useRef(null);
  const [trackingLoading, setTrackingLoading] = useState(false);
  const [trackingError, setTrackingError] = useState("");
  const [trackingSearched, setTrackingSearched] = useState(false);
  const [showProof, setShowProof] = useState(false);

  const [contactForm, setContactForm] = useState({
    nombre: "",
    apellido: "",
    correo: "",
    celular: "",
    empresa: "",
    mensaje: "",
  });

  const [accessOpen, setAccessOpen] = useState(false);
  const accessMenuRef = useRef(null);

  useEffect(() => {
  function handleClickOutside(event) {
      if (accessMenuRef.current && !accessMenuRef.current.contains(event.target)) {
        setAccessOpen(false);
      }
    }

    document.addEventListener("mousedown", handleClickOutside);
    return () => {
      document.removeEventListener("mousedown", handleClickOutside);
    };
  }, []);



  function scrollToQuoteForm() {
    setMobileOpen(false);
    setAccessOpen(false);

    requestAnimationFrame(() => {
      quoteFormRef.current?.scrollIntoView({
        behavior: "smooth",
        block: "center",
      });
    });
  }





  function handleContactChange(event) {
    const { name, value } = event.target;

    setContactForm((prev) => ({
      ...prev,
      [name]: value,
    }));
  }

  function openWhatsAppContact(type = "cotizacion") {
    const phone = "56926826733";

    const intro =
      type === "ejecutivo"
        ? "Hola, quiero hablar con un ejecutivo."
        : "Hola, quiero solicitar una cotización.";

    const details = [
      intro,
      contactForm.nombre ? `Nombre: ${contactForm.nombre}` : null,
      contactForm.apellido ? `Apellido: ${contactForm.apellido}` : null,
      contactForm.correo ? `Correo: ${contactForm.correo}` : null,
      contactForm.celular ? `Celular: ${contactForm.celular}` : null,
      contactForm.empresa ? `Empresa: ${contactForm.empresa}` : null,
      contactForm.mensaje ? `Mensaje: ${contactForm.mensaje}` : null,
    ]
      .filter(Boolean)
      .join("\n");

    const url = `https://wa.me/${phone}?text=${encodeURIComponent(details)}`;

    window.open(url, "_blank", "noopener,noreferrer");
  }




  async function handleTrackingSubmit(event) {
    event.preventDefault();

    const cleanTracking = tracking.trim();

    setTrackingSearched(true);
    setTrackingError("");
    setTrackingResult(null);
    setShowProof(false);

    if (!cleanTracking) {
      setTrackingError("Debes ingresar un número de seguimiento.");
      return;
    }

    setTrackingLoading(true);

    try {
      const data = await fetchPublicTracking(cleanTracking);
      setTrackingResult(data);
    } catch (error) {
      setTrackingError(error.message || "No fue posible consultar el tracking.");
    } finally {
      setTrackingLoading(false);
    }
  }

  return (
    <div
      className="min-h-screen bg-[#F7F9F9] text-[#231F21]"
      style={{ fontFamily: "Montserrat, system-ui, sans-serif" }}
    >
      <style>{`
        @import url('https://fonts.googleapis.com/css2?family=Kanit:wght@400;500;600;700&family=Montserrat:wght@400;500;600;700;800&display=swap');
        html { scroll-behavior: smooth; }
        body { background: #F7F9F9; }
      `}</style>

      <style>{`
        .coverage-map-shapes path {
          transition:
            transform 180ms ease,
            fill 180ms ease,
            opacity 180ms ease,
            filter 180ms ease;
          transform-box: fill-box;
          transform-origin: center;
          cursor: pointer;
        }

        .coverage-map-shapes:hover path {
          opacity: 0.72;
        }

        .coverage-map-shapes path:hover {
          opacity: 1;
          transform: translateY(-4px) scale(1.03);
          fill: #B0FFF8;
          filter: drop-shadow(0 0 10px rgba(176,255,248,0.35));
        }
      `}</style>

      <header className="sticky top-0 z-50 border-b border-white/10 bg-[#F7F9F9]/88 backdrop-blur-xl">
        <div className="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
          <Logo />

          <nav className="hidden items-center gap-7 lg:flex">
            {navItems.map((item) => (
              <a
                key={item.label}
                href={item.href}
                className="text-sm font-medium text-[#231F21]/72 transition hover:text-[#231F21]"
              >
                {item.label}
              </a>
            ))}
          </nav>

          <div className="hidden items-center gap-3 lg:flex">
            <div ref={accessMenuRef} className="relative">
              <button
                type="button"
                onClick={() => setAccessOpen((value) => !value)}
                className="inline-flex h-11 items-center gap-2 rounded-full border border-[#231F21]/10 bg-white px-5 text-sm font-medium text-[#231F21] transition hover:bg-[#231F21]/5"
              >
                Acceso
                <ChevronDown
                  className={`h-4 w-4 transition-transform ${accessOpen ? "rotate-180" : ""}`}
                />
              </button>

              {accessOpen && (
                <div className="absolute right-0 top-full z-50 mt-3 w-64 overflow-hidden rounded-2xl border border-[#231F21]/10 bg-white shadow-[0_20px_50px_rgba(35,31,33,0.12)]">
                  <a
                    href="/acceso-trabajadores"
                    onClick={() => setAccessOpen(false)}
                    className="block px-4 py-3 text-sm text-[#231F21] transition hover:bg-[#5CBABC]/10"
                  >
                    Acceso trabajadores
                  </a>

                  <a
                    href="https://cliente.4nortes.app/login"
                    onClick={() => setAccessOpen(false)}
                    className="block border-t border-[#231F21]/8 px-4 py-3 text-sm text-[#231F21] transition hover:bg-[#5CBABC]/10"
                  >
                    Acceso clientes
                  </a>

                  <a
                    href="https://admin.4nortes.app/login"
                    onClick={() => setAccessOpen(false)}
                    className="block border-t border-[#231F21]/8 px-4 py-3 text-sm text-[#231F21] transition hover:bg-[#5CBABC]/10"
                  >
                    Acceso proveedores
                  </a>
                </div>
              )}
            </div>



              <Button
                type="button"
                onClick={scrollToQuoteForm}
                className="rounded-full bg-[#231F21] px-5 text-white hover:bg-[#231F21]/90"
              >
                Cotizar servicio
              </Button>




          </div>

          <button
            className="inline-flex h-11 w-11 items-center justify-center rounded-full border border-[#231F21]/10 bg-white text-[#231F21] lg:hidden"
            onClick={() => setMobileOpen((v) => !v)}
            aria-label="Abrir menú"
          >
            {mobileOpen ? <X className="h-5 w-5" /> : <Menu className="h-5 w-5" />}
          </button>
        </div>

        {mobileOpen && (
          <div className="border-t border-[#231F21]/8 bg-white lg:hidden">
            <div className="mx-auto flex max-w-7xl flex-col gap-2 px-4 py-4 sm:px-6 lg:px-8">
              {navItems.map((item) => (
                <a
                  key={item.label}
                  href={item.href}
                  onClick={() => setMobileOpen(false)}
                  className="rounded-xl px-3 py-3 text-sm font-medium text-[#231F21]/75 transition hover:bg-[#5CBABC]/10 hover:text-[#231F21]"
                >
                  {item.label}
                </a>
              ))}

              <div className="mt-2 rounded-2xl border border-[#231F21]/8 bg-[#F7F9F9] p-2">
                <div className="px-3 py-2 text-xs font-semibold uppercase tracking-[0.18em] text-[#231F21]/45">
                  Acceso
                </div>

                <a
                  href="/acceso-trabajadores"
                  onClick={() => setMobileOpen(false)}
                  className="block rounded-xl px-3 py-3 text-sm font-medium text-[#231F21]/75 transition hover:bg-[#5CBABC]/10 hover:text-[#231F21]"
                >
                  Acceso trabajadores
                </a>

                <a
                  href="https://cliente.4nortes.app/login"
                  onClick={() => setMobileOpen(false)}
                  className="block rounded-xl px-3 py-3 text-sm font-medium text-[#231F21]/75 transition hover:bg-[#5CBABC]/10 hover:text-[#231F21]"
                >
                  Acceso clientes
                </a>

                <a
                  href="https://admin.4nortes.app/login"
                  onClick={() => setMobileOpen(false)}
                  className="block rounded-xl px-3 py-3 text-sm font-medium text-[#231F21]/75 transition hover:bg-[#5CBABC]/10 hover:text-[#231F21]"
                >
                  Acceso proveedores
                </a>
              </div>

              <Button
                type="button"
                onClick={scrollToQuoteForm}
                className="mt-2 rounded-full bg-[#231F21] text-white hover:bg-[#231F21]/90"
              >
                Cotizar servicio
              </Button>
            </div>
          </div>
        )}
      </header>

      <main>
        {/* Hero + Tracking integrado */}
        <section id="inicio" className="bg-[#F7F9F9] text-[#231F21]">
          <div className="mx-auto max-w-7xl px-4 pt-8 sm:px-6 sm:pt-10 lg:px-8 lg:pt-12">
            <div className="relative z-20 overflow-hidden rounded-[2.2rem] border border-[#231F21]/8 bg-white p-6 shadow-[0_20px_60px_rgba(35,31,33,0.05)] sm:p-8 lg:p-10">
              <div className="grid gap-8 lg:grid-cols-[1.15fr_0.85fr] lg:items-start">
                <div>
                  <div className="inline-flex rounded-full bg-[#5CBABC]/12 px-4 py-2 text-xs uppercase tracking-[0.22em] text-[#231F21]">
                    Seguimiento
                  </div>

                  <h2 className="mt-5 font-['Kanit'] text-3xl font-semibold tracking-tight text-[#231F21] sm:text-4xl">
                    Seguimiento de envíos
                  </h2>

                  <p className="mt-4 max-w-2xl text-base leading-8 text-[#231F21]/72">
                    Revisa el estado de tu envío en un solo lugar. Ingresa tu número de seguimiento para
                    consultar la información disponible de tu despacho de manera rápida.
                  </p>

                  <div className="mt-8 rounded-[1.8rem] border border-[#231F21]/10 bg-[#F7F9F9] p-4 sm:p-5">
                    <form onSubmit={handleTrackingSubmit} className="flex flex-col gap-3">
                      <div className="flex flex-col gap-3 md:flex-row">
                        <div className="relative flex-1">
                          <Search className="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-[#231F21]/35" />
                          <Input
                            value={tracking}
                            onChange={(e) => setTracking(e.target.value)}
                            placeholder="Ej: 4N202601123267"
                            disabled={trackingLoading}
                            className="h-12 rounded-2xl border-[#231F21]/10 bg-white pl-11 text-[#231F21] placeholder:text-[#231F21]/35"
                          />
                        </div>

                        <Button
                          type="submit"
                          disabled={trackingLoading}
                          className="h-12 rounded-full bg-[#5CBABC] px-8 text-[#231F21] hover:bg-[#B0FFF8] md:min-w-[160px]"
                        >
                          {trackingLoading ? "Consultando..." : "Consultar"}
                        </Button>
                      </div>
                    </form>

                    {trackingLoading && (
                      <div className="mt-4 rounded-[1.2rem] border border-[#231F21]/8 bg-white px-4 py-4 text-sm text-[#231F21]/70">
                        Consultando información del envío...
                      </div>
                    )}

                    {!trackingLoading && trackingSearched && trackingError && (
                      <div className="mt-4 rounded-[1.2rem] border border-[#E7B4B4] bg-[#FFF4F4] px-4 py-4">
                        <p className="text-sm font-semibold text-[#9F2D2D]">No pudimos encontrar el envío</p>
                        <p className="mt-1 text-sm text-[#231F21]/70">{trackingError}</p>
                      </div>
                    )}

                    {!trackingLoading && trackingResult && (
                      <div className="mt-4 rounded-[1.35rem] border border-[#231F21]/8 bg-white p-4 shadow-[0_10px_30px_rgba(35,31,33,0.05)]">
                        <div className="mt-3 flex flex-col gap-4">
                          <div className="min-w-0">
                            <p className="text-xs uppercase tracking-[0.18em] text-[#231F21]/38">
                              Número de seguimiento
                            </p>

                            <p
                              title={trackingResult.tracking}
                              className="mt-2 whitespace-nowrap font-['Kanit'] text-3xl font-semibold leading-[1.05] tracking-tight text-[#231F21] sm:text-4xl"
                            >
                              {trackingResult.tracking}
                            </p>
                          </div>

                          <div className="flex flex-wrap items-center gap-3">
                            <div className="rounded-[1rem] bg-[#F7F9F9] px-4 py-3 text-sm text-[#231F21]">
                              <p className="text-xs uppercase tracking-[0.16em] text-[#231F21]/45">
                                Entregado el
                              </p>
                              <p className="mt-1 font-medium">{formatTrackingDate(trackingResult.delivered_at)}</p>
                            </div>

                            <Button
                              type="button"
                              variant="outline"
                              onClick={() => setShowProof((value) => !value)}
                              className="h-11 rounded-[1rem] border-[#231F21]/12 bg-white px-5 text-[#231F21] hover:bg-[#231F21]/5"
                            >
                              Ver evidencia
                            </Button>
                          </div>
                        </div>

                        <div className="mt-4 grid gap-3 md:grid-cols-3">
                          <div className="rounded-[1rem] bg-[#F7F9F9] px-4 py-3">
                            <div className="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.16em] text-[#231F21]/45">
                              <Box className="h-4 w-4 text-[#5CBABC]" />
                              Estado
                            </div>
                            <p className="mt-2 text-sm font-semibold text-[#231F21]">
                              {trackingResult.status === "delivered"
                                ? "Entregado"
                                : trackingResult.status || "Sin información"}
                            </p>
                          </div>

                          <div className="rounded-[1rem] bg-[#F7F9F9] px-4 py-3">
                            <div className="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.16em] text-[#231F21]/45">
                              <UserRound className="h-4 w-4 text-[#5CBABC]" />
                              Recibido por
                            </div>
                            <p className="mt-2 text-sm font-semibold text-[#231F21]">
                              {trackingResult.received_by || "Sin registro disponible"}
                            </p>
                          </div>

                          <div className="rounded-[1rem] bg-[#F7F9F9] px-4 py-3">
                            <div className="flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.16em] text-[#231F21]/45">
                              <ImageIcon className="h-4 w-4 text-[#5CBABC]" />
                              Evidencia
                            </div>
                            <p className="mt-2 text-sm font-semibold text-[#231F21]">
                              {Array.isArray(trackingResult.photos) && trackingResult.photos.length > 0
                                ? `${trackingResult.photos.length} ${trackingResult.photos.length === 1 ? "imagen disponible" : "imágenes disponibles"}`
                                : "Sin evidencia fotográfica"}
                            </p>
                          </div>
                        </div>

                        {showProof && (
                          <div className="mt-5 border-t border-[#231F21]/8 pt-5">
                            {Array.isArray(trackingResult.photos) && trackingResult.photos.length > 0 ? (
                              <div className="grid gap-4 sm:grid-cols-2">
                                {trackingResult.photos.map((photo, index) => (
                                  <a
                                    key={`${photo.url}-${index}`}
                                    href={photo.url}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="group overflow-hidden rounded-[1.25rem] border border-[#231F21]/10 bg-[#F7F9F9] transition hover:border-[#5CBABC]/50"
                                  >
                                    <img
                                      src={photo.preview_url || photo.url}
                                      alt={`Foto de entrega ${index + 1}`}
                                      className="h-52 w-full object-cover transition duration-300 group-hover:scale-[1.02]"
                                      loading="lazy"
                                    />
                                    <div className="px-4 py-3">
                                      <p className="text-sm font-semibold text-[#231F21]">
                                        Evidencia {index + 1}
                                      </p>
                                      <p className="text-xs text-[#231F21]/50">
                                        Abrir imagen completa
                                      </p>
                                    </div>
                                  </a>
                                ))}
                              </div>
                            ) : (
                              <div className="rounded-[1rem] border border-dashed border-[#231F21]/12 bg-[#F7F9F9] px-4 py-4 text-sm text-[#231F21]/60">
                                Este envío aún no tiene evidencia fotográfica disponible.
                              </div>
                            )}
                          </div>
                        )}
                      </div>
                    )}
                  </div>
                </div>

                <div className="rounded-[2rem] border border-[#231F21]/8 bg-[#F7F9F9] p-6">
                  <div className="mx-auto flex justify-center">
                    <img
                      src="/images/caja.webp"
                      alt="Caja 4N Logística"
                      width="230"
                      height="153"
                      loading="eager"
                      fetchPriority="high"
                      decoding="async"
                      className="h-auto w-full max-w-[230px] object-contain"
                    />
                  </div>

                  <h3 className="mt-6 font-['Kanit'] text-3xl font-semibold tracking-tight text-[#231F21]">
                    ¿Qué verás al consultar?
                  </h3>

                  <div className="mt-6 space-y-4">
                    {[
                      "Estado actual del envío",
                      "Fecha y hora de entrega",
                      "Nombre de quien recibe",
                      "Evidencia fotográfica disponible",
                    ].map((item) => (
                      <div key={item} className="flex items-start gap-3">
                        <div className="mt-0.5 inline-flex h-6 w-6 items-center justify-center rounded-full bg-[#5CBABC]/18 text-[#231F21]">
                          <BadgeCheck className="h-4 w-4" />
                        </div>
                        <p className="text-base leading-7 text-[#231F21]/75">{item}</p>
                      </div>
                    ))}
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div className="-mt-10 sm:-mt-12 lg:-mt-16">
            <section className="relative overflow-hidden bg-[#231F21] text-white">
              <div className="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(176,255,248,0.16),_transparent_38%),radial-gradient(circle_at_90%_20%,_rgba(92,186,188,0.22),_transparent_26%),linear-gradient(180deg,rgba(35,31,33,0.92),rgba(35,31,33,1))]" />
              <div className="absolute -left-20 top-24 h-64 w-64 rounded-full bg-[#B0FFF8]/10 blur-3xl" />
              <div className="absolute bottom-0 right-0 h-72 w-72 rounded-full bg-[#5CBABC]/10 blur-3xl" />

              <div className="relative mx-auto grid max-w-7xl items-center gap-12 px-4 pb-20 pt-24 sm:px-6 md:pb-24 md:pt-28 lg:grid-cols-[1.15fr_0.85fr] lg:px-8 lg:pb-28 lg:pt-32">
                <motion.div
                  id="cotizacion-formulario"
                  className="scroll-mt-28"
                  initial="hidden"
                  whileInView="visible"
                  viewport={{ once: true, amount: 0.2 }}
                  variants={fadeUp}
                  transition={{ duration: 0.6, delay: 0.05 }}
                >
                  <div className="space-y-5">
                    <Badge className="rounded-full border-0 bg-white/10 px-4 py-2 text-[#B0FFF8] shadow-none hover:bg-white/10">
                      Logística en Chile · Distribución nacional · Última milla
                    </Badge>
                    <h1 className="max-w-4xl font-['Kanit'] text-4xl font-semibold leading-[1.02] tracking-tight sm:text-5xl lg:text-7xl">
                      Logística y distribución confiable en todo Chile
                    </h1>
                    <p className="max-w-2xl text-lg leading-8 text-white/78 sm:text-xl">
                      Transporte, almacenaje, fulfillment y última milla con flota propia, trackeo permanente y atención humana de verdad.
                    </p>
                    <p className="text-base font-medium text-[#B0FFF8]">
                      Confianza, eficiencia y transparencia en cada gestión.
                    </p>
                  </div>
                </motion.div>

                <motion.div
                  initial={{ opacity: 0, y: 24, scale: 0.98 }}
                  animate={{ opacity: 1, y: 0, scale: 1 }}
                  transition={{ duration: 0.7, delay: 0.1 }}
                  className="relative"
                >
                  <div className="absolute -left-6 -top-6 h-28 w-28 rounded-full border border-[#B0FFF8]/30" />
                  <div className="absolute -bottom-8 -right-8 h-44 w-44 rounded-full bg-[#5CBABC]/15 blur-2xl" />
                  <div className="relative overflow-hidden rounded-[2rem] border border-white/10 bg-white/5 p-3 shadow-[0_30px_80px_rgba(0,0,0,0.32)] backdrop-blur-sm">
                    <div className="relative overflow-hidden rounded-[1.6rem] bg-[#161416]">
                      <div className="absolute inset-0 bg-[linear-gradient(145deg,rgba(176,255,248,0.08),transparent_36%),linear-gradient(180deg,transparent,rgba(35,31,33,0.16))]" />
                      <img
                        src="https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?auto=format&fit=crop&w=1200&q=80"
                        alt="Operación logística y reparto"
                        className="h-[520px] w-full object-cover"
                      />
                      <div className="absolute inset-0 bg-[linear-gradient(180deg,rgba(35,31,33,0.08),rgba(35,31,33,0.64))]" />
                      <div className="absolute bottom-0 left-0 right-0 p-6 sm:p-8" />
                    </div>
                  </div>
                </motion.div>
              </div>
            </section>
          </div>
        </section>
        {/* Hero + Tracking integrado */}

        {/* Servicios */}
        <section id="servicios" className="bg-white">
          <div className="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
            <div className="flex flex-col gap-8 lg:flex-row lg:items-end lg:justify-between">
              <motion.div initial="hidden" whileInView="visible" viewport={{ once: true, amount: 0.2 }} variants={fadeUp} transition={{ duration: 0.55 }}>
                <SectionHeader
                  eyebrow="Servicios"
                  title="Soluciones logísticas pensadas para operar con continuidad"
                  description="Transporte de carga, almacenaje logístico, fulfillment, última milla y servicios complementarios para operaciones B2B y B2C con enfoque práctico y comercial."
                />
              </motion.div>
              <motion.div initial="hidden" whileInView="visible" viewport={{ once: true, amount: 0.2 }} variants={fadeUp} transition={{ duration: 0.55, delay: 0.05 }}>
              </motion.div>
            </div>

            <div className="mt-10 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
              {services.map((service, index) => {
                const Icon = service.icon;
                return (
                  <motion.div
                    key={service.title}
                    initial="hidden"
                    whileInView="visible"
                    viewport={{ once: true, amount: 0.15 }}
                    variants={fadeUp}
                    transition={{ duration: 0.45, delay: index * 0.03 }}
                  >
                    <Card className="group h-full rounded-[1.75rem] border border-[#231F21]/8 bg-white shadow-[0_18px_50px_rgba(35,31,33,0.05)] transition hover:-translate-y-1 hover:shadow-[0_28px_65px_rgba(35,31,33,0.08)]">
                      <CardHeader className="pb-4">
                        <div className="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-[#231F21] text-[#B0FFF8]">
                          <Icon className="h-6 w-6" />
                        </div>
                        <CardTitle className="font-['Kanit'] text-2xl text-[#231F21]">{service.title}</CardTitle>
                        <CardDescription className="text-sm leading-7 text-[#231F21]/68">{service.description}</CardDescription>
                      </CardHeader>
                    </Card>
                  </motion.div>
                );
              })}
            </div>
          </div>
        </section>
        {/* Servicios */}

        {/* MAPA DE CHILE */}
        <section id="cobertura" className="bg-[#231F21] text-white">
          <div className="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
            <div className="grid gap-12 lg:grid-cols-[1fr_0.95fr] lg:items-center">
              <motion.div initial="hidden" whileInView="visible" viewport={{ once: true, amount: 0.2 }} variants={fadeUp} transition={{ duration: 0.55 }}>
                <SectionHeader
                  eyebrow="Cobertura nacional"
                  title="Cobertura desde Arica a Punta Arenas"
                  description="4N Logística opera de norte a sur con una presencia pensada para responder con orden, trazabilidad y continuidad. con su casa matriz en La Región Metropolitana."
                  light
                />

                <div className="mt-8 grid gap-3 sm:grid-cols-2">
                  {regions.map((region) => (
                    <div key={region} className="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-white/82">
                      {region}
                    </div>
                  ))}
                </div>
              </motion.div>

              <motion.div
                initial={{ opacity: 0, y: 24 }}
                whileInView={{ opacity: 1, y: 0 }}
                viewport={{ once: true, amount: 0.2 }}
                transition={{ duration: 0.6, delay: 0.05 }}
                className="grid gap-6"
              >
                <div className="relative mx-auto h-[560px] w-full overflow-hidden rounded-[2rem] border border-white/10 bg-white/5 backdrop-blur">
                  <div className="absolute inset-0 rounded-[2rem] bg-[radial-gradient(circle_at_top,_rgba(92,186,188,0.18),_transparent_55%)]" />
                  <div className="absolute right-10 top-16 h-40 w-40 rounded-full bg-[#5CBABC]/10 blur-3xl" />
                  <div className="absolute bottom-16 right-16 h-32 w-32 rounded-full bg-[#B0FFF8]/10 blur-3xl" />

                  <svg
                    viewBox="470 0 145 709"
                    className="relative z-10 h-full w-full"
                    preserveAspectRatio="xMidYMid meet"
                  >
                    <g
                      className="coverage-map-shapes"
                      dangerouslySetInnerHTML={{ __html: chileMapInner }}
                    />

                    {coverageMarkers.map((point) => (
                      <g key={point.label}>
                        <circle
                          cx={point.x}
                          cy={point.y}
                          r="9"
                          fill="rgba(176,255,248,0.16)"
                        />
                        <circle
                          cx={point.x}
                          cy={point.y}
                          r="4.5"
                          fill="#B0FFF8"
                        />
                        <text
                          x={point.x + 8}
                          y={point.y + 4}
                          fill="rgba(255,255,255,0.85)"
                          fontSize="11"
                          fontWeight="500"
                        >
                          {point.label}
                        </text>
                      </g>
                    ))}
                  </svg>
                </div>
              </motion.div>
            </div>
          </div>
        </section>
        {/* MAPA DE CHILE */}

        {/* Empresa */}
        <section id="empresa" className="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
          <div className="grid gap-10 lg:grid-cols-[0.9fr_1.1fr] lg:gap-14">
            <motion.div initial="hidden" whileInView="visible" viewport={{ once: true, amount: 0.2 }} variants={fadeUp} transition={{ duration: 0.5 }}>
              <SectionHeader
                eyebrow="Empresa"
                title="Una operación que crece sin perder el trato directo"
                description="4N Logística nace en marzo de 2021, impulsada por la oportunidad de continuar y proyectar la experiencia de Distribución Nacional META S.A., consolidando un servicio logístico basado en la confianza, la eficiencia y la transparencia."
              />
            </motion.div>

            <motion.div
              initial="hidden"
              whileInView="visible"
              viewport={{ once: true, amount: 0.2 }}
              variants={fadeUp}
              transition={{ duration: 0.55, delay: 0.05 }}
              className="grid gap-5 sm:grid-cols-2"
            >
              {[
                "Hoy contamos con un equipo de cerca de 80 personas y más de 100 colaboradores externos, distribuidos a lo largo de todo el país.",
                "Su crecimiento ha sido impulsado por el compromiso del equipo humano y una forma responsable de operar.",
              ].map((item, index) => (
                <Card key={index} className="rounded-[1.75rem] border border-[#231F21]/8 bg-white shadow-[0_20px_50px_rgba(35,31,33,0.05)]">
                  <CardContent className="p-6">
                    <div className="mb-4 inline-flex h-10 w-10 items-center justify-center rounded-full bg-[#5CBABC]/12 text-[#231F21]">
                      <ChevronRight className="h-5 w-5" />
                    </div>
                    <p className="text-sm leading-7 text-[#231F21]/75">{item}</p>
                  </CardContent>
                </Card>
              ))}
            </motion.div>
          </div>
        </section>
        {/* Empresa */}

        {/* Visión de marca */}
        <section id="visión-de-marca" className="bg-white">
          <div className="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
            <div className="grid gap-10 lg:grid-cols-[0.92fr_1.08fr] lg:gap-14">
              <motion.div
                initial="hidden"
                whileInView="visible"
                viewport={{ once: true, amount: 0.2 }}
                variants={fadeUp}
                transition={{ duration: 0.6, delay: 0.05 }}
                className="grid gap-4 sm:grid-cols-2"
              >
                {[
                  ["Sustentabilidad", "Decisiones que buscan continuidad operativa y bienestar del equipo."],
                  ["Crecimiento responsable", "Expandirse sin perder control, calidad y compromiso real."],
                  ["Bienestar del equipo", "El desarrollo de la empresa también debe impactar a quienes la hacen posible."],
                  ["Transparencia", "Información clara, trazabilidad y comunicación oportuna en cada gestión."],
                  ["Cumplimiento real", "Prometer lo que se puede cumplir y ejecutar con seriedad."],
                ].map(([title, text]) => (
                  <div key={title} className="rounded-[1.75rem] border border-[#231F21]/8 bg-[#F7F9F9] p-6">
                    <div className="mb-3 inline-flex rounded-full bg-[#5CBABC]/12 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-[#231F21]">
                      {title}
                    </div>
                    <p className="text-sm leading-7 text-[#231F21]/72">{text}</p>
                  </div>
                ))}
              </motion.div>

              <motion.div initial="hidden" whileInView="visible" viewport={{ once: true, amount: 0.2 }} variants={fadeUp} transition={{ duration: 0.55 }}>
                <SectionHeader
                  eyebrow="Nuestra visión"
                  title="Consolidarse como un partner logístico confiable en Chile"
                  description="La visión de 4N Logística pone el foco en una operación transparente, cercana y sostenible, capaz de crecer con responsabilidad sin perder la escala humana que define su forma de trabajar."
                />
              </motion.div>
            </div>
          </div>
        </section>
        {/* Visión de marca */}

        {/* Por qué elegir 4N Logística */}
        <section id="por-qué-elegir-4n-logística" className="bg-[#F7F9F9]">
          <div className="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
            <motion.div initial="hidden" whileInView="visible" viewport={{ once: true, amount: 0.2 }} variants={fadeUp} transition={{ duration: 0.55 }}>
              <SectionHeader
                eyebrow="¿Por qué elegir 4N Logística?"
                title="Diferenciales que se notan en la operación y también en la atención"
                description="La propuesta de 4N Logística combina cobertura, control, trazabilidad y personas reales a cargo. Esa mezcla permite resolver operaciones con más claridad, menos fricción y mejor respuesta."
              />
            </motion.div>

            <div className="mt-10 grid gap-5 md:grid-cols-2 xl:grid-cols-4">
              {reasons.map((reason, index) => {
                const Icon = reason.icon;
                return (
                  <motion.div
                    key={reason.title}
                    initial="hidden"
                    whileInView="visible"
                    viewport={{ once: true, amount: 0.12 }}
                    variants={fadeUp}
                    transition={{ duration: 0.45, delay: index * 0.04 }}
                  >
                    <Card className="h-full rounded-[1.75rem] border border-[#231F21]/8 bg-white shadow-[0_20px_55px_rgba(35,31,33,0.05)] transition hover:-translate-y-1">
                      <CardContent className="p-6">
                        <div className="mb-5 inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-[#5CBABC]/12 text-[#231F21]">
                          <Icon className="h-6 w-6" />
                        </div>
                        <h3 className="font-['Kanit'] text-xl font-semibold text-[#231F21]">{reason.title}</h3>
                        <p className="mt-3 text-sm leading-7 text-[#231F21]/72">{reason.text}</p>
                      </CardContent>
                    </Card>
                  </motion.div>
                );
              })}
            </div>
          </div>
        </section>
        {/* Por qué elegir 4N Logística */}




        <section id="contacto" className="bg-white">
          <div className="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
            <div className="grid gap-10 lg:grid-cols-[0.9fr_1.1fr] lg:gap-14">
              <motion.div initial="hidden" whileInView="visible" viewport={{ once: true, amount: 0.2 }} variants={fadeUp} transition={{ duration: 0.55 }}>
                <SectionHeader
                  eyebrow="Contacto"
                  title="Conversemos sobre tu operación"
                  description="Una sección visible, simple y comercial para recibir cotizaciones, consultas y oportunidades de trabajo conjunto sin fricción innecesaria."
                />

                <div className="mt-8 grid gap-4">
                  <div className="rounded-[1.6rem] border border-[#231F21]/8 bg-[#F7F9F9] p-5">
                    <div className="flex items-start gap-3">
                      <Phone className="mt-1 h-5 w-5 text-[#5CBABC]" />
                      <div>
                        <div className="font-semibold text-[#231F21]">Teléfono</div>
                        <div className="text-sm text-[#231F21]/68">+56 9 2682 6733</div>
                      </div>
                    </div>
                  </div>
                  <div className="rounded-[1.6rem] border border-[#231F21]/8 bg-[#F7F9F9] p-5">
                    <div className="flex items-start gap-3">
                      <MapPin className="mt-1 h-5 w-5 text-[#5CBABC]" />
                      <div>
                        <div className="font-semibold text-[#231F21]">Dirección</div>
                        <div className="text-sm text-[#231F21]/68">Galvarino 9215-A Quilicura, Santiago</div>
                      </div>
                    </div>
                  </div>
                  <div className="rounded-[1.6rem] border border-[#231F21]/8 bg-[#F7F9F9] p-5">
                    <div className="flex items-start gap-3">
                      <Mail className="mt-1 h-5 w-5 text-[#5CBABC]" />
                      <div>
                        <div className="font-semibold text-[#231F21]">Sitio web</div>
                        <div className="text-sm text-[#231F21]/68">https:4n.pmcb.cl</div>
                      </div>
                    </div>
                  </div>
                </div>
              </motion.div>

              <div ref={quoteFormRef} className="scroll-mt-28">
                <motion.div
                  initial="hidden"
                  whileInView="visible"
                  viewport={{ once: true, amount: 0.2 }}
                  variants={fadeUp}
                  transition={{ duration: 0.6, delay: 0.05 }}
                >
                  <Card className="rounded-[2rem] border border-[#231F21]/8 bg-[#231F21] text-white shadow-[0_28px_70px_rgba(35,31,33,0.12)]">
                    <CardContent className="p-6 sm:p-8">
                      <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                          <label className="text-sm text-white/70">Nombre</label>
                          <Input
                            name="nombre"
                            value={contactForm.nombre}
                            onChange={handleContactChange}
                            className="h-12 rounded-2xl border-white/10 bg-white/8 text-white placeholder:text-white/30"
                            placeholder="Tu nombre"
                          />
                        </div>

                        <div className="space-y-2">
                          <label className="text-sm text-white/70">Apellido</label>
                          <Input
                            name="apellido"
                            value={contactForm.apellido}
                            onChange={handleContactChange}
                            className="h-12 rounded-2xl border-white/10 bg-white/8 text-white placeholder:text-white/30"
                            placeholder="Tu apellido"
                          />
                        </div>

                        <div className="space-y-2">
                          <label className="text-sm text-white/70">Correo</label>
                          <Input
                            type="email"
                            name="correo"
                            value={contactForm.correo}
                            onChange={handleContactChange}
                            className="h-12 rounded-2xl border-white/10 bg-white/8 text-white placeholder:text-white/30"
                            placeholder="nombre@empresa.cl"
                          />
                        </div>

                        <div className="space-y-2">
                          <label className="text-sm text-white/70">Celular</label>
                          <Input
                            name="celular"
                            value={contactForm.celular}
                            onChange={handleContactChange}
                            className="h-12 rounded-2xl border-white/10 bg-white/8 text-white placeholder:text-white/30"
                            placeholder="+56 9"
                          />
                        </div>

                        <div className="space-y-2 sm:col-span-2">
                          <label className="text-sm text-white/70">Empresa</label>
                          <Input
                            name="empresa"
                            value={contactForm.empresa}
                            onChange={handleContactChange}
                            className="h-12 rounded-2xl border-white/10 bg-white/8 text-white placeholder:text-white/30"
                            placeholder="Nombre de tu empresa"
                          />
                        </div>

                        <div className="space-y-2 sm:col-span-2">
                          <label className="text-sm text-white/70">Mensaje</label>
                          <Textarea
                            name="mensaje"
                            value={contactForm.mensaje}
                            onChange={handleContactChange}
                            className="min-h-[140px] rounded-[1.4rem] border-white/10 bg-white/8 text-white placeholder:text-white/30"
                            placeholder="Cuéntanos qué tipo de operación necesitas resolver"
                          />
                        </div>
                      </div>



                        <div className="mt-6 flex flex-col gap-3 sm:flex-row">
                          <Button
                            type="button"
                            onClick={() => openWhatsAppContact("cotizacion")}
                            className="h-12 rounded-full bg-[#5CBABC] px-6 text-[#231F21] hover:bg-[#B0FFF8]"
                          >
                            Solicitar cotización
                          </Button>

                          <Button
                            type="button"
                            variant="outline"
                            onClick={() => openWhatsAppContact("ejecutivo")}
                            className="h-12 rounded-full border-white/12 bg-white/5 px-6 text-white hover:bg-white/10"
                          >
                            Hablar con un ejecutivo
                          </Button>
                        </div>



                    </CardContent>
                  </Card>
                </motion.div>
              </div>



              
            </div>
          </div>
        </section>







      </main>

      <footer className="border-t border-white/5 bg-[#231F21] text-white">
        <div className="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:px-6 lg:grid-cols-[1.1fr_0.9fr_0.9fr] lg:px-8">
          <div>
            <Logo />
            <p className="mt-5 max-w-md text-sm leading-7 text-white/68">
              Operador logístico en Chile enfocado en transporte, almacenaje, fulfillment, paquetería y última milla con atención cercana y cumplimiento real.
            </p>
            <p className="mt-6 font-['Kanit'] text-xl leading-tight text-[#B0FFF8]">
              Logística hecha por personas, para operaciones que no pueden fallar.
            </p>
          </div>

          <div>
            <div className="text-sm font-semibold uppercase tracking-[0.22em] text-[#B0FFF8]">Menú</div>
            <div className="mt-5 grid grid-cols-2 gap-3 text-sm text-white/70">
              {navItems.map((item) => (
                <a key={item.label} href={item.href} className="transition hover:text-white">
                  {item.label}
                </a>
              ))}
            </div>
          </div>

          <div>
            <div className="text-sm font-semibold uppercase tracking-[0.22em] text-[#B0FFF8]">Contacto</div>
            <div className="mt-5 space-y-4 text-sm text-white/70">
              <div className="flex items-start gap-3">
                <MapPin className="mt-1 h-4 w-4 text-[#B0FFF8]" />
                <span>Galvarino 9215-A Quilicura, Santiago</span>
              </div>
              <div className="flex items-start gap-3">
                <Phone className="mt-1 h-4 w-4 text-[#B0FFF8]" />
                <span>+56 9 2682 6733</span>
              </div>
              <div className="flex items-start gap-3">
                <ExternalLink className="mt-1 h-4 w-4 text-[#B0FFF8]" />
                <span>Tracking y acceso de clientes</span>
              </div>
              <div className="flex items-start gap-3">
                <MessageSquareMore className="mt-1 h-4 w-4 text-[#B0FFF8]" />
                <span>Espacio preparado para integrar redes sociales oficiales</span>
              </div>
            </div>
          </div>
        </div>
      </footer>
    </div>
  );
}