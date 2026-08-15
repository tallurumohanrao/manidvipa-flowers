"use server";
import { revalidatePath, revalidateTag } from "next/cache";
import { NextResponse } from "next/server";

// Environment variable for the base URL
const BASE_URL = process.env.NEXT_PUBLIC_MANIDVIPA_URL;

// Utility function to handle API requests
export async function fetchListingData(
  req_method,
  endpoint,
  userToken = "",
  formData = null
) {
  try {
    const options = {
      method: req_method,
      headers: {
        Authorization: userToken ? `Bearer ${userToken}` : undefined,
        "Content-Type": "application/json",
      },
    };

    if (req_method !== "GET" && formData) {
      options.body = JSON.stringify(formData);
    }

    const fetchOptions =
      req_method === "GET"
        ? { ...options, next: { revalidate: 120 } } // Cache for 60 seconds
        : options;

    // const res = await fetch("https://admin.manidvipastore.com/api/settings", {
    //   next: { revalidate: 60 }, // Cache for 60 seconds
    // });
    const response = await fetch(`${BASE_URL}/${endpoint}`, fetchOptions);

    if (response.ok) {
      revalidateTag(endpoint);

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

    if (!req_method || !endpoint) {
      return NextResponse.json(
        { error: true, message: "req_method and endpoint are required" },
        { status: 400 }
      );
    }

    const data = await fetchListingData(
      req_method,
      endpoint,
      userToken,
      formData
    );

    if (data.error) {
      return NextResponse.json(data, { status: 400 });
    }
    revalidatePath("/");
    return NextResponse.json(data);
  } catch (error) {
    console.error("Error in POST /api:", error);
    return NextResponse.json(
      { error: true, message: "Internal server error" },
      { status: 500 }
    );
  }
}

// export async function GET(req) {
//   const { searchParams } = new URL(req.url);
//   console.log("reqss", searchParams);

//   const req_method = "GET";
//   const endpoint = searchParams.get("endpoint");
//   const userToken = searchParams.get("userToken");

//   if (!endpoint) {
//     return NextResponse.json(
//       { error: true, message: "endpoint is required" },
//       { status: 400 }
//     );
//   }

//   const data = await fetchListingData(req_method, endpoint, userToken);

//   if (data.error) {
//     return NextResponse.json(data, { status: 400 });
//   }

//   return NextResponse.json(data);
// }

export async function GET(req) {
  // Extract query parameters from the request URL
  const { searchParams } = new URL(req.url);

  // Get individual query parameters
  const endpoint = searchParams.get("endpoint");
  const category_slug = searchParams.get("category_slug");
  const userToken = searchParams.get("userToken");

  // Log the query parameters for debugging

  // Check if the required 'endpoint' parameter is present
  if (!endpoint) {
    return NextResponse.json(
      { error: true, message: "Endpoint is required" },
      { status: 400 }
    );
  }

  // Fetch data based on the endpoint and other parameters
  const data = await fetchListingData(
    "GET",
    endpoint,
    userToken,
    category_slug
  );

  // Handle errors in the fetched data
  if (data.error) {
    return NextResponse.json(data, { status: 400 });
  }

  // Return the fetched data as a JSON response
  return NextResponse.json(data);
}
