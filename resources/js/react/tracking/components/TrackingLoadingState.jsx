import React from "react";

export default function TrackingLoadingState() {
  return (
    <div className="rounded-[1.75rem] border border-white/10 bg-[#111A1F] p-6 text-white shadow-[0_18px_40px_rgba(0,0,0,0.25)]">
      <p className="text-xs font-semibold uppercase tracking-[0.22em] text-[#7EDBDA]">
        Consultando
      </p>
      <p className="mt-3 text-base text-white/72">
        Buscando información del bulto...
      </p>
    </div>
  );
}