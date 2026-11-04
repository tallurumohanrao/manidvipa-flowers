import { NextResponse } from "next/server";

import { getApiBaseUrl } from "@/lib/apiClient";

export const dynamic = "force-dynamic";
export const runtime = "nodejs";

const REQUEST_TIMEOUT_MS = 15000;
const FORWARDED_REQUEST_HEADERS = ["accept", "authorization", "content-type"];
const FORWARDED_RESPONSE_HEADERS = ["content-type", "retry-after"];

function buildUpstreamUrl(parts, search) {
  const safeParts = parts.filter(
    (part) => part && part !== "." && part !== ".." && !part.includes(":")
  );

  if (!safeParts.length || safeParts.length !== parts.length) return null;

  const path = safeParts.map((part) => encodeURIComponent(part)).join("/");
  return `${getApiBaseUrl()}/${path}${search}`;
}

function jsonError(message, status = 503, code = "STORE_API_UNAVAILABLE") {
  return NextResponse.json(
    { success: false, status: false, code, message },
    { status, headers: { "Cache-Control": "no-store" } }
  );
}

async function proxyStoreRequest(request, context) {
  const { endpoint = [] } = await context.params;
  const upstreamUrl = buildUpstreamUrl(endpoint, request.nextUrl.search);

  if (!upstreamUrl) {
    return jsonError("Invalid store API endpoint.", 400, "INVALID_ENDPOINT");
  }

  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), REQUEST_TIMEOUT_MS);
  const headers = new Headers();

  for (const name of FORWARDED_REQUEST_HEADERS) {
    const value = request.headers.get(name);
    if (value) headers.set(name, value);
  }

  headers.set("accept", "application/json");
  headers.set("x-storefront-request", "manidvipa-next");

  try {
    const hasBody = !["GET", "HEAD"].includes(request.method);
    const response = await fetch(upstreamUrl, {
      method: request.method,
      headers,
      body: hasBody ? await request.arrayBuffer() : undefined,
      cache: "no-store",
      redirect: "manual",
      signal: controller.signal,
    });
    const contentType = response.headers.get("content-type") || "";

    // Hostinger's browser-verification page is HTML. Never send that challenge
    // into storefront fetch calls, because fetch cannot execute it as a page.
    if (!/application\/(?:[\w.-]+\+)?json/i.test(contentType)) {
      return jsonError(
        response.status === 403
          ? "The store API is being blocked by hosting protection. Please try again shortly."
          : "The store API returned an invalid response. Please try again.",
        503,
        response.status === 403 ? "HOSTING_PROTECTION_BLOCK" : "INVALID_UPSTREAM_RESPONSE"
      );
    }

    const responseHeaders = new Headers({ "Cache-Control": "no-store" });
    for (const name of FORWARDED_RESPONSE_HEADERS) {
      const value = response.headers.get(name);
      if (value) responseHeaders.set(name, value);
    }

    return new Response(await response.arrayBuffer(), {
      status: response.status,
      headers: responseHeaders,
    });
  } catch {
    return jsonError(
      controller.signal.aborted
        ? "The store API timed out. Please try again."
        : "Unable to connect to the store API. Please try again.",
      503,
      controller.signal.aborted ? "UPSTREAM_TIMEOUT" : "UPSTREAM_NETWORK_ERROR"
    );
  } finally {
    clearTimeout(timeout);
  }
}

export const GET = proxyStoreRequest;
export const POST = proxyStoreRequest;
export const PUT = proxyStoreRequest;
export const PATCH = proxyStoreRequest;
export const DELETE = proxyStoreRequest;
