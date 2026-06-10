import React, { useEffect, useMemo, useRef } from "react";
import L from "leaflet";
import "leaflet/dist/leaflet.css";

export default function OpvPuntosMap({ asignacion, puntos = [] }) {
    const mapRef = useRef(null);
    const mapInstanceRef = useRef(null);
    const markersLayerRef = useRef(null);

    const cantidadPuntos = puntos.length;

    const puntosConCoordenadas = useMemo(() => {
        return puntos
            .map((punto) => {
                const lat = Number(punto.lat ?? punto.latitud);
                const lng = Number(punto.lng ?? punto.longitud);

                return {
                    ...punto,
                    lat,
                    lng,
                    tieneCoordenadas: Number.isFinite(lat) && Number.isFinite(lng),
                };
            })
            .filter((punto) => punto.tieneCoordenadas);
    }, [puntos]);

    const cantidadConCoordenadas = puntosConCoordenadas.length;

    const buscarEnMapaUrl = (punto) => {
        const partes = [
            punto.direccion,
            punto.comuna,
            "Chile",
        ].filter(Boolean);

        return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(partes.join(", "))}`;
    };

    useEffect(() => {
        if (!mapRef.current || cantidadPuntos === 0) {
            return;
        }

        if (!mapInstanceRef.current) {
            mapInstanceRef.current = L.map(mapRef.current, {
                scrollWheelZoom: false,
            }).setView([-33.4489, -70.6693], 11);

            L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
                maxZoom: 19,
                attribution:
                    '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            }).addTo(mapInstanceRef.current);

            markersLayerRef.current = L.layerGroup().addTo(mapInstanceRef.current);
        }

        const map = mapInstanceRef.current;
        const markersLayer = markersLayerRef.current;

        markersLayer.clearLayers();

        if (puntosConCoordenadas.length === 0) {
            map.setView([-33.4489, -70.6693], 11);
            return;
        }

        const bounds = [];

        puntosConCoordenadas.forEach((punto, index) => {
            const markerIcon = L.divIcon({
                className: "opv-leaflet-marker",
                html: `<span>${index + 1}</span>`,
                iconSize: [32, 32],
                iconAnchor: [16, 32],
                popupAnchor: [0, -28],
            });

            const marker = L.marker([punto.lat, punto.lng], {
                icon: markerIcon,
            });

            marker.bindPopup(`
                <strong>${escapeHtml(punto.nombre_corto || punto.nombre || "Local OPV")}</strong><br>
                ${escapeHtml(punto.nombre || "—")}<br>
                <small>${escapeHtml(punto.direccion || "Sin dirección")}${punto.comuna ? ", " + escapeHtml(punto.comuna) : ""}</small>
            `);

            marker.addTo(markersLayer);
            bounds.push([punto.lat, punto.lng]);
        });

        if (bounds.length === 1) {
            map.setView(bounds[0], 15);
        }

        if (bounds.length > 1) {
            map.fitBounds(bounds, {
                padding: [32, 32],
            });
        }

        setTimeout(() => {
            map.invalidateSize();
        }, 150);
    }, [cantidadPuntos, puntosConCoordenadas]);

    return (
        <section className="opv-map-card">
            <div className="opv-map-card__header">
                <div>
                    <p className="opv-map-card__eyebrow">Vista OPV</p>
                    <h2 className="opv-map-card__title">
                        {asignacion?.punto || "Ruta OPV"}
                    </h2>
                </div>

                <span className="opv-map-card__badge">
                    {cantidadPuntos} punto{cantidadPuntos === 1 ? "" : "s"}
                </span>
            </div>

            {cantidadPuntos === 0 ? (
                <div className="opv-map-empty">
                    Esta asignación OPV no tiene locales asociados.
                </div>
            ) : (
                <>
                    <div className="opv-map-status">
                        {cantidadConCoordenadas > 0 ? (
                            <>
                                Mostrando {cantidadConCoordenadas} punto
                                {cantidadConCoordenadas === 1 ? "" : "s"} con coordenadas en el mapa.
                            </>
                        ) : (
                            <>
                                Los locales OPV todavía no tienen coordenadas guardadas. Por ahora se muestra el mapa base y la lista de direcciones.
                            </>
                        )}
                    </div>

                    <div className="opv-map-layout">
                        <div className="opv-leaflet-map-wrap">
                            <div ref={mapRef} className="opv-leaflet-map"></div>
                        </div>

                        <div className="opv-point-list">
                            {puntos.map((punto, index) => {
                                const lat = Number(punto.lat ?? punto.latitud);
                                const lng = Number(punto.lng ?? punto.longitud);
                                const tieneCoordenadas = Number.isFinite(lat) && Number.isFinite(lng);

                                return (
                                    <article className="opv-point-card" key={punto.id ?? index}>
                                        <div className="opv-point-card__number">
                                            {index + 1}
                                        </div>

                                        <div className="opv-point-card__body">
                                            <div className="opv-point-card__top">
                                                <h3>
                                                    {punto.nombre_corto || punto.nombre || "Local OPV"}
                                                </h3>

                                                {tieneCoordenadas ? (
                                                    <span className="opv-point-card__coord opv-point-card__coord--ok">
                                                        Con coordenadas
                                                    </span>
                                                ) : (
                                                    <span className="opv-point-card__coord">
                                                        Sin coordenadas
                                                    </span>
                                                )}
                                            </div>

                                            <p>
                                                {punto.nombre || "—"}
                                            </p>

                                            <span>
                                                {punto.direccion || "Sin dirección"}
                                                {punto.comuna ? `, ${punto.comuna}` : ""}
                                            </span>

                                            <a
                                                href={buscarEnMapaUrl(punto)}
                                                target="_blank"
                                                rel="noreferrer"
                                                className="opv-point-card__link"
                                            >
                                                Buscar dirección en Google Maps
                                            </a>
                                        </div>
                                    </article>
                                );
                            })}
                        </div>
                    </div>
                </>
            )}
        </section>
    );
}

function escapeHtml(value) {
    return String(value ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}