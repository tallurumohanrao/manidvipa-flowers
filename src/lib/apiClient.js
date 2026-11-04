// One transport for browser and server requests. API failures are not empty data.
export class ApiError extends Error {
  constructor(message, { status = 0, code = "API_ERROR", payload = null } = {}) {
    super(message);
    this.name = "ApiError";
    this.status = status;
    this.code = code;
    this.payload = payload;
  }
}

export function isApiNotFound(error) {
  return error instanceof ApiError && error.status === 404 &&
    error.code === "HTTP_ERROR" && error.payload?.success === false;
}

export function getApiBaseUrl() {
  if (typeof window !== "undefined") return "/api/store";

  return String(
    process.env.MANIDVIPA_SERVER_API_URL ||
      process.env.NEXT_PUBLIC_MANIDVIPA_URL ||
      "https://admin.manidvipaflowers.com/api"
  ).replace(/\/+$/, "");
}

export async function requestApi(endpoint, {
  token, method = "GET", body, signal, timeoutMs = 10000,
  retries = 1, allowFailure = false, ...fetchOptions
} = {}) {
  if (typeof endpoint !== "string" || !endpoint.trim() ||
      /^(null|undefined)(\?|$)/.test(endpoint) || /^(https?:|\/)/i.test(endpoint)) {
    throw new ApiError("Invalid API endpoint.", { code: "INVALID_ENDPOINT" });
  }
  method = method.toUpperCase();
  const attempts = method === "GET" ? Math.min(1, Math.max(0, retries)) + 1 : 1;
  for (let attempt = 0; attempt < attempts; attempt += 1) {
    if (signal?.aborted) throw signal.reason || new DOMException("Aborted", "AbortError");
    const controller = new AbortController();
    const abort = () => controller.abort(signal.reason);
    signal?.addEventListener("abort", abort, { once: true });
    const timer = setTimeout(() => controller.abort(), timeoutMs);
    let retryDelay = 250;
    try {
      const response = await fetch(`${getApiBaseUrl()}/${endpoint}`, {
        cache: "no-store", ...fetchOptions, method,
        headers: {
          Accept: "application/json",
          ...(token ? { Authorization: `Bearer ${token}` } : {}),
          ...(body != null ? { "Content-Type": "application/json" } : {}),
          ...fetchOptions.headers,
        },
        ...(body != null ? { body: JSON.stringify(body) } : {}),
        signal: controller.signal,
      });
      const retryAfter = response.headers.get("retry-after");
      if (retryAfter) {
        retryDelay = /^\d+$/.test(retryAfter) ? Number(retryAfter) * 1000 :
          Math.max(0, Date.parse(retryAfter) - Date.now());
      }
      let payload;
      try {
        if (!/application\/(?:[\w.-]+\+)?json/i.test(response.headers.get("content-type") || "")) {
          throw new Error("Not JSON");
        }
        payload = await response.json();
      } catch {
        if (controller.signal.aborted) throw new DOMException("Aborted", "AbortError");
        throw new ApiError("The store service is temporarily unavailable. Please try again.", {
          status: response.status, code: "INVALID_RESPONSE",
        });
      }
      if (!response.ok) {
        throw new ApiError(response.status < 500 && typeof payload?.message === "string"
          ? payload.message : "The store service is temporarily unavailable. Please try again.",
        { status: response.status, code: "HTTP_ERROR", payload });
      }
      if (!payload || typeof payload !== "object" || Array.isArray(payload)) {
        throw new ApiError("The store returned an invalid response. Please try again.", { code: "INVALID_RESPONSE" });
      }
      if (!allowFailure && (payload.success === false || payload.status === false)) {
        throw new ApiError(payload.message || "The request could not be completed.", {
          status: response.status, code: "API_REJECTED", payload,
        });
      }
      return payload;
    } catch (cause) {
      if (signal?.aborted) throw signal.reason || new DOMException("Aborted", "AbortError");
      const error = cause instanceof ApiError ? cause : new ApiError(
        controller.signal.aborted ? "The request timed out. Please try again." :
          "Unable to connect to the store. Please try again.",
        { code: controller.signal.aborted ? "TIMEOUT" : "NETWORK_ERROR" });
      const retryable = ["NETWORK_ERROR", "TIMEOUT"].includes(error.code) ||
        [429, 500, 502, 503, 504].includes(error.status);
      if (!retryable || attempt + 1 >= attempts || !Number.isFinite(retryDelay) || retryDelay > 1000) throw error;
    } finally {
      clearTimeout(timer);
      signal?.removeEventListener("abort", abort);
    }
    await new Promise((resolve) => setTimeout(resolve, retryDelay));
  }
}

export function requireDataArray(result) {
  if (!Array.isArray(result?.data)) {
    throw new ApiError("The store returned incomplete data. Please try again.", { code: "INVALID_RESPONSE" });
  }
  return result.data;
}

// Client-side sorting/filtering needs the full result set, not just page one.
// Publish nothing until every page succeeds so outages cannot look like a short catalog.
export async function fetchAllProducts(endpoint, options = {}) {
  const [path, query = ""] = endpoint.split("?");
  const params = new URLSearchParams(query);
  params.set("per_page", "200");
  const products = new Map();
  let lastPage = 1;
  for (let page = 1; page <= lastPage; page += 1) {
    params.set("page", String(page));
    const result = await requestApi(`${path}?${params}`, options);
    const pagination = result.data;
    if (!Array.isArray(pagination?.data) || !Number.isInteger(pagination.last_page) ||
        pagination.last_page < 1 || pagination.current_page !== page) {
      throw new ApiError("The product list could not be loaded completely. Please try again.", { code: "INVALID_RESPONSE" });
    }
    lastPage = pagination.last_page;
    for (const product of pagination.data) {
      const id = product.product_id ?? product.id;
      if (id == null) throw new ApiError("Invalid product response.", { code: "INVALID_RESPONSE" });
      products.set(String(id), product);
    }
  }
  return [...products.values()];
}
