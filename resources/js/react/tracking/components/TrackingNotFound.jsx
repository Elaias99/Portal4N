import React from "react";

export default function TrackingNotFound({ message }) {
  return (
    <div className="rounded-[1.75rem] border border-[#7A3030] bg-[#161B1E] p-6 text-white shadow-[0_18px_40px_rgba(0,0,0,0.25)]">
      <p className="text-xs font-semibold uppercase tracking-[0.22em] text-[#FF8E8E]">
        Seguimiento no disponible
      </p>

      <h2 className="mt-3 font-['Kanit'] text-2xl font-semibold text-white">
        No encontramos este envío
      </h2>

      <p className="mt-3 text-white/68">
        {message || "Verifica el número ingresado e inténtalo nuevamente."}
      </p>
    </div>
  );
}