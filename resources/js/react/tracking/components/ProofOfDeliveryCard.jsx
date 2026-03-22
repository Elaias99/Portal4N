import React from "react";
import { Camera, ExternalLink, ImageIcon } from "lucide-react";
import { Card, CardContent } from "@/components/ui/card";

export default function ProofOfDeliveryCard({ photos = [] }) {
  return (
    <Card className="overflow-hidden rounded-[2rem] border border-white/10 bg-[linear-gradient(180deg,rgba(255,255,255,0.08),rgba(255,255,255,0.04))] text-white shadow-[0_30px_80px_rgba(0,0,0,0.22)] backdrop-blur-xl">
      <CardContent className="p-0">
        <div className="border-b border-white/8 bg-[linear-gradient(180deg,rgba(22,52,59,0.9),rgba(22,52,59,0.38))] px-6 py-6 sm:px-8">
          <div className="flex items-start justify-between gap-4">
            <div>
              <div className="inline-flex rounded-full border border-[#5CBABC]/20 bg-[#5CBABC]/10 px-4 py-2 text-xs font-medium uppercase tracking-[0.22em] text-[#B0FFF8]">
                Prueba de entrega
              </div>

              <h3 className="mt-5 font-['Kanit'] text-3xl font-semibold tracking-tight text-white">
                Fotos de entrega
              </h3>

              <p className="mt-3 max-w-2xl text-sm leading-7 text-white/68">
                Evidencia visual asociada al envío. Selecciona una imagen para
                verla en tamaño completo.
              </p>
            </div>

            <div className="hidden rounded-full border border-white/10 bg-white/6 p-3 text-[#B0FFF8] sm:flex">
              <Camera className="h-5 w-5" />
            </div>
          </div>
        </div>

        <div className="p-6 sm:p-8">
          {photos.length === 0 ? (
            <div className="rounded-[1.5rem] border border-dashed border-white/12 bg-[#0F171B]/60 p-8 text-center">
              <div className="mx-auto flex h-14 w-14 items-center justify-center rounded-full border border-white/10 bg-white/5 text-[#B0FFF8]">
                <ImageIcon className="h-6 w-6" />
              </div>

              <h4 className="mt-5 font-['Kanit'] text-2xl font-semibold text-white">
                Sin fotos disponibles
              </h4>

              <p className="mx-auto mt-3 max-w-md text-sm leading-7 text-white/65">
                Aún no existe evidencia fotográfica visible para este envío.
              </p>
            </div>
          ) : (
            <>
              <div className="mb-5 flex items-center justify-between gap-3">
                <p className="text-sm text-white/58">
                  {photos.length} {photos.length === 1 ? "imagen disponible" : "imágenes disponibles"}
                </p>

                <div className="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs uppercase tracking-[0.18em] text-[#B0FFF8]">
                  Evidencia registrada
                </div>
              </div>

              <div className="grid gap-4 sm:grid-cols-2">
                {photos.map((photo, index) => (
                  <a
                    key={`${photo.url}-${index}`}
                    href={photo.url}
                    target="_blank"
                    rel="noreferrer"
                    className="group block overflow-hidden rounded-[1.5rem] border border-white/8 bg-[#0F171B]/70 transition duration-200 hover:-translate-y-1 hover:border-[#5CBABC]/30 hover:shadow-[0_20px_40px_rgba(0,0,0,0.22)]"
                  >
                    <div className="relative">
                      <img
                        src={photo.preview_url || photo.url}
                        alt={`Foto de entrega ${index + 1}`}
                        className="h-64 w-full object-cover transition duration-300 group-hover:scale-[1.02]"
                        loading="lazy"
                      />

                      <div className="absolute inset-0 bg-[linear-gradient(180deg,transparent_35%,rgba(8,11,13,0.78)_100%)]" />

                      <div className="absolute bottom-0 left-0 right-0 flex items-center justify-between px-4 py-4">
                        <div>
                          <p className="text-sm font-semibold text-white">
                            Foto {index + 1}
                          </p>
                          <p className="text-xs text-white/65">
                            Abrir imagen completa
                          </p>
                        </div>

                        <div className="flex h-10 w-10 items-center justify-center rounded-full border border-white/15 bg-white/10 text-white backdrop-blur">
                          <ExternalLink className="h-4 w-4" />
                        </div>
                      </div>
                    </div>
                  </a>
                ))}
              </div>
            </>
          )}
        </div>
      </CardContent>
    </Card>
  );
}