import { NextResponse } from "next/server";

// Environment variable for the base URL
const BASE_URL = process.env.NEXT_PUBLIC_MANIDVIPA_URL;
const ALLOWED_ENDPOINTS = {
  POST: new Set(["add-to-cart"]),
  GET: new Set([]),
};

const normalizeMethod = (method) => method?.toUpperCase();

const getEndpointPath = (endpoint) => endpoint?.split("?")[0];

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

// Utility function to handle API requests
export async function fetchListingData(
  req_method,
  endpoint,
  userToken = "",
  formData = null
) {
  try {
    const method = normalizeMethod(req_method);
    if (!isAllowedEndpoint(method, endpoint)) {
      return { error: true, message: "Endpoint is not allowed" };
    }

    const options = {
      method,
      headers: {
        ...(userToken && { Authorization: `Bearer ${userToken}` }),
        "Content-Type": "application/json",
      },
    };

    if (method !== "GET" && formData) {
      options.body = JSON.stringify(formData);
    }

    const fetchOptions =
      method === "GET"
        ? { ...options, next: { revalidate: 120 } }
        : options;

    const response = await fetch(`${BASE_URL}/${endpoint}`, fetchOptions);

    if (response.ok) {
      return await response.json();
    } else {
      const errorData = await response.json();
      return { error: true, message: errorData.message || "Request failed" };
    }
  } catch (error) {
    console.error("Error in fetchListingData:", error);
    return { error: true, message: "An unexpected error occurred" };
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
      return NextResponse.json(data, { status: 400 });
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
    return NextResponse.json(data, { status: 400 });
  }

  return NextResponse.json(data);
}
