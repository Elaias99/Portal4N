  import React, { useEffect, useState } from "react";
  import TrackingLookupForm from "./components/TrackingLookupForm.jsx";
  import TrackingLoadingState from "./components/TrackingLoadingState.jsx";
  import TrackingNotFound from "./components/TrackingNotFound.jsx";
  import TrackingResultCard from "./components/TrackingResultCard.jsx";
  import { fetchPublicTracking } from "./api.js";

  export default function PublicTrackingPage({ initialTracking = "" }) {
    const [tracking, setTracking] = useState(initialTracking);
    const [result, setResult] = useState(null);
    const [loading, setLoading] = useState(false);
    const [errorMessage, setErrorMessage] = useState("");
    const [hasSearched, setHasSearched] = useState(false);

    async function runSearch(trackingNumber) {
      const cleanTracking = String(trackingNumber ?? "").trim();

      if (!cleanTracking) {
        setResult(null);
        setHasSearched(true);
        setErrorMessage("Debes ingresar un número de seguimiento.");
        return;
      }

      setLoading(true);
      setErrorMessage("");
      setHasSearched(true);

      try {
        const data = await fetchPublicTracking(cleanTracking);
        setResult(data);
      } catch (error) {
        setResult(null);
        setErrorMessage(error.message || "No fue posible consultar el tracking.");
      } finally {
        setLoading(false);
      }
    }

    function handleSubmit(event) {
      event.preventDefault();
      runSearch(tracking);
    }

    useEffect(() => {
      if (initialTracking) {
        runSearch(initialTracking);
      }
    }, [initialTracking]);

    return (
      <div className="min-h-screen bg-[#0B1114] text-white">
        <div className="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8 lg:py-12">
          <div className="mb-6">
            <p className="text-xs font-semibold uppercase tracking-[0.24em] text-[#7EDBDA]">
              4N Logística / Seguimiento
            </p>
            <h1 className="mt-3 font-['Kanit'] text-4xl font-semibold tracking-tight text-white sm:text-5xl">
              Seguimiento de envío
            </h1>
            <p className="mt-4 max-w-2xl text-base leading-7 text-white/68">
              Consulta el estado de tu bulto y revisa su información pública de
              entrega en una sola vista.
            </p>
          </div>

          <div className="space-y-5">
            <TrackingLookupForm
              tracking={tracking}
              onTrackingChange={setTracking}
              onSubmit={handleSubmit}
              loading={loading}
            />

            {loading && <TrackingLoadingState />}

            {!loading && hasSearched && errorMessage && (
              <TrackingNotFound message={errorMessage} />
            )}

            {!loading && result && <TrackingResultCard data={result} />}
          </div>
        </div>
      </div>
    );
  }