import React from "react";
import { Search } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";

export default function TrackingLookupForm({
  tracking,
  onTrackingChange,
  onSubmit,
  loading = false,
}) {
  return (
    <form
      onSubmit={onSubmit}
      className="rounded-[1.75rem] border border-white/10 bg-[#111A1F] p-5 shadow-[0_18px_40px_rgba(0,0,0,0.25)] sm:p-6"
    >
      <div className="flex flex-col gap-4 sm:flex-row sm:items-end">
        <div className="flex-1">
          <label className="mb-3 block text-sm font-medium text-white/78">
            Número de seguimiento
          </label>

          <div className="relative">
            <Search className="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-white/35" />
            <Input
              value={tracking}
              onChange={(e) => onTrackingChange(e.target.value)}
              placeholder="Ej: 4N202601123267"
              className="h-12 rounded-full border-white/10 bg-[#0D1418] pl-11 text-white placeholder:text-white/28"
              disabled={loading}
            />
          </div>
        </div>

        <Button
          type="submit"
          disabled={loading}
          className="h-12 rounded-full bg-[#67C9CC] px-6 text-[#0B1114] hover:bg-[#7EDBDA] sm:min-w-[220px]"
        >
          {loading ? "Consultando..." : "Consultar seguimiento"}
        </Button>
      </div>

      <p className="mt-4 text-sm text-white/48">
        Verás estado, fecha de entrega, receptor y evidencia disponible.
      </p>
    </form>
  );
}