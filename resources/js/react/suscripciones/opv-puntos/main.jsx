import React from "react";
import { createRoot } from "react-dom/client";
import OpvPuntosMap from "./OpvPuntosMap.jsx";
import "./opv-puntos.css";

const container = document.getElementById("opv-puntos-map-root");
const propsNode = document.getElementById("opv-puntos-map-props");

if (container && propsNode) {
    let props = {
        asignacion: null,
        puntos: [],
    };

    try {
        props = JSON.parse(propsNode.textContent || "{}");
    } catch (error) {
        console.error("No se pudieron leer los puntos OPV.", error);
    }

    createRoot(container).render(<OpvPuntosMap {...props} />);
}