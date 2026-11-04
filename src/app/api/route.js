import { NextResponse } from "next/server";

import { requestApi } from "@/lib/apiClient";
const ALLOWED_ENDPOINTS = {
  POST: new Set(["add-to-cart"]),
  GET: new Set([]),
};

const normalizeMethod = (method) => typeof method === "string" ? method.toUpperCase() : "";

const getEndpointPath = (endpoint) => typeof endpoint === "string" ? endpoint.split("?")[0] : "";

const isAllowedEndpoint = (method, endpoint) => {
  const endpointPath = getEndpointPath(endpoint);
  return Boolean(
    endpointPath &&
      !endpointPath.includes("..") &&
      !endpointPath.includes("://") &&
      !endpointPath.startsWith("/") &&
      ALLOWED_ENDPOINTS[method]?.has(endpointPath)
  );
};

async function fetchListingData(method, endpoint, userToken, formData) {
  try {
    return await requestApi(endpoint, { method, token: userToken, body: method === "GET" ? undefined : formData });
  } catch (error) {
    return { success: false, error: true, message: error.message,
      httpStatus: error.status >= 400 ? error.status : error.code === "API_REJECTED" ? 422 : 503 };
  }
}

// API route handler
export async function POST(req) {
  try {
    const requestBody = await req.json();

    const { req_method, endpoint, userToken, formData } = requestBody;
    const method = normalizeMethod(req_method);

    if (!method || !endpoint) {
      return NextResponse.json(
        { error: true, message: "req_method and endpoint are required" },
        { status: 400 }
      );
    }

    if (!isAllowedEndpoint(method, endpoint)) {
      return NextResponse.json(
        { error: true, message: "Endpoint is not allowed" },
        { status: 403 }
      );
    }

    const data = await fetchListingData(
      method,
      endpoint,
      userToken,
      formData
    );

    if (data.error) {
      return NextResponse.json(data, { status: data.httpStatus || 400 });
    }

    return NextResponse.json(data);
  } catch (error) {
    console.error("Error in POST /api:", error);
    return NextResponse.json(
      { error: true, message: "Internal server error" },
      { status: 500 }
    );
  }
}

export async function GET(req) {
  const { searchParams } = new URL(req.url);
  const endpoint = searchParams.get("endpoint");
  const userToken = searchParams.get("userToken");

  if (!endpoint) {
    return NextResponse.json(
      { error: true, message: "Endpoint is required" },
      { status: 400 }
    );
  }

  if (!isAllowedEndpoint("GET", endpoint)) {
    return NextResponse.json(
      { error: true, message: "Endpoint is not allowed" },
      { status: 403 }
    );
  }

  const data = await fetchListingData("GET", endpoint, userToken);

  if (data.error) {
    return NextResponse.json(data, { status: data.httpStatus || 400 });
  }

  return NextResponse.json(data);
}
