import React from "react";

const STATUS_MAP = {
  delivered: {
    label: "Entregado",
    className: "bg-[#0B8F43] text-white border-[#0B8F43]",
  },
  in_transit: {
    label: "En tránsito",
    className: "bg-[#B8871A] text-[#0B1114] border-[#B8871A]",
  },
  out_for_delivery: {
    label: "En reparto",
    className: "bg-[#1B6E8C] text-white border-[#1B6E8C]",
  },
  pending: {
    label: "Pendiente",
    className: "bg-[#5F6B73] text-white border-[#5F6B73]",
  },
  not_found: {
    label: "No encontrado",
    className: "bg-[#AA3A3A] text-white border-[#AA3A3A]",
  },
};

function normalizeStatus(status) {
  return String(status ?? "").trim().toLowerCase();
}

export default function TrackingStatusBadge({ status }) {
  const normalized = normalizeStatus(status);
  const config = STATUS_MAP[normalized] ?? {
    label: normalized ? normalized.replaceAll("_", " ") : "Sin estado",
    className: "bg-[#1C2A31] text-white border-white/12",
  };

  return (
    <span
      className={`inline-flex rounded-full border px-4 py-2 text-sm font-semibold ${config.className}`}
    >
      {config.label}
    </span>
  );
}