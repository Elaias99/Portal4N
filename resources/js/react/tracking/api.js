export async function fetchPublicTracking(tracking) {
  const cleanTracking = String(tracking ?? "").trim();

  if (!cleanTracking) {
    const error = new Error("Debes ingresar un número de seguimiento.");
    error.status = 422;
    throw error;
  }

  const response = await fetch(`/tracking-data/${encodeURIComponent(cleanTracking)}`, {
    method: "GET",
    headers: {
      Accept: "application/json",
    },
  });

  let json = null;

  try {
    json = await response.json();
  } catch {
    json = null;
  }

  if (!response.ok || !json?.success) {
    const error = new Error(
      json?.message || "No fue posible consultar el tracking."
    );
    error.status = response.status;
    throw error;
  }

  return json.data;
}