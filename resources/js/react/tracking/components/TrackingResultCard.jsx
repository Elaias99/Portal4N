import React from "react";
import { CalendarClock, ImageIcon, Package, UserRound } from "lucide-react";
import { Card, CardContent } from "@/components/ui/card";
import TrackingStatusBadge from "./TrackingStatusBadge.jsx";

function formatDate(value) {
  if (!value) return null;

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

function DetailItem({ icon: Icon, label, value }) {
  return (
    <div className="rounded-[1.2rem] border border-white/8 bg-[#0E1519] p-4">
      <div className="flex items-center gap-2 text-white/48">
        <Icon className="h-4 w-4 text-[#67C9CC]" />
        <span className="text-xs font-medium uppercase tracking-[0.18em]">
          {label}
        </span>
      </div>
      <p className="mt-3 text-lg font-semibold text-white">{value}</p>
    </div>
  );
}

export default function TrackingResultCard({ data }) {
  const deliveredAt = formatDate(data?.delivered_at);
  const photos = Array.isArray(data?.photos) ? data.photos : [];

  return (
    <Card className="overflow-hidden rounded-[1.9rem] border border-white/10 bg-[#111A1F] text-white shadow-[0_18px_40px_rgba(0,0,0,0.25)]">
      <CardContent className="p-0">
        <div className="border-b border-white/8 bg-[linear-gradient(180deg,rgba(103,201,204,0.12),rgba(17,26,31,0.25))] px-5 py-5 sm:px-6">
          <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
              <p className="text-xs font-semibold uppercase tracking-[0.22em] text-[#7EDBDA]">
                Resultado del bulto
              </p>

              <p className="mt-4 text-sm text-white/54">Número de envío</p>
              <h2 className="mt-2 break-all font-['Kanit'] text-3xl font-semibold tracking-tight text-white sm:text-4xl">
                {data?.tracking}
              </h2>
            </div>

            <div className="flex items-center gap-3">
              <TrackingStatusBadge status={data?.status} />
            </div>
          </div>
        </div>

        <div className="p-5 sm:p-6">
          <div className="grid gap-4 md:grid-cols-2">
            <DetailItem
              icon={Package}
              label="Estado"
              value={
                data?.status === "delivered"
                  ? "Entregado"
                  : data?.status || "Sin información"
              }
            />

            <DetailItem
              icon={CalendarClock}
              label="Entregado el"
              value={deliveredAt || "Sin registro disponible"}
            />

            <DetailItem
              icon={UserRound}
              label="Recibido por"
              value={data?.received_by || "Sin registro disponible"}
            />

            <DetailItem
              icon={ImageIcon}
              label="Fotos disponibles"
              value={`${photos.length} ${photos.length === 1 ? "imagen" : "imágenes"}`}
            />
          </div>

          <div className="mt-6 border-t border-white/8 pt-6">
            <div className="flex items-center justify-between gap-3">
              <div>
                <p className="text-xs font-semibold uppercase tracking-[0.22em] text-[#7EDBDA]">
                  Prueba de entrega
                </p>
                <h3 className="mt-2 font-['Kanit'] text-2xl font-semibold text-white">
                  Evidencia fotográfica
                </h3>
              </div>
            </div>

            {photos.length === 0 ? (
              <div className="mt-5 rounded-[1.2rem] border border-dashed border-white/10 bg-[#0E1519] p-5 text-sm text-white/55">
                Aún no hay evidencia fotográfica visible para este envío.
              </div>
            ) : (
              <div className="mt-5 grid gap-4 sm:grid-cols-2">
                {photos.map((photo, index) => (
                  <a
                    key={`${photo.url}-${index}`}
                    href={photo.url}
                    target="_blank"
                    rel="noreferrer"
                    className="group overflow-hidden rounded-[1.25rem] border border-white/10 bg-[#0E1519] transition hover:border-[#67C9CC]/40"
                  >
                    <img
                      src={photo.preview_url || photo.url}
                      alt={`Foto de entrega ${index + 1}`}
                      className="h-56 w-full object-cover transition duration-300 group-hover:scale-[1.02]"
                      loading="lazy"
                    />
                    <div className="flex items-center justify-between px-4 py-3">
                      <div>
                        <p className="text-sm font-semibold text-white">
                          Foto {index + 1}
                        </p>
                        <p className="text-xs text-white/48">
                          Abrir imagen completa
                        </p>
                      </div>
                    </div>
                  </a>
                ))}
              </div>
            )}
          </div>
        </div>
      </CardContent>
    </Card>
  );
}