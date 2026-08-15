// import { NextResponse } from "next/server";

// export async function middleware(request) {
//   const pathName = request.nextUrl.pathname;
//   const response = NextResponse.next();

//   const userSession = request.cookies.get("userSession")?.value;
//   console.log("userSession", userSession?.token);

//   if (pathName) {
//     response.headers.set("x-metadata-pathName", pathName || "Default Title");
//     response.headers.set(
//       "x-metadata-description",
//       pathName || "Default Description"
//     );
//   }

//   return response;
// }

import { NextResponse } from "next/server";

export async function middleware(request) {
  const pathName = request.nextUrl.pathname;
  const response = NextResponse.next();

  // Extract and parse the user session cookie
  const userSessionCookie = request.cookies.get("userSession")?.value;
  let userSession = null;

  try {
    if (userSessionCookie) {
      userSession = JSON.parse(userSessionCookie);
      if (userSession?.token) {
        response.headers.set("x-user-token", userSession.token); // Set token in header
      }
    }
  } catch (error) {
    console.error("Failed to parse userSession cookie:", error);
  }

  if (pathName) {
    response.headers.set("x-metadata-pathName", pathName || "Default Title");
    response.headers.set(
      "x-metadata-description",
      pathName || "Default Description"
    );
  }

  // Set the pathName as "/" explicitly in the header
  // response.headers.set("x-metadata-pathName", "/");

  // // Optionally, you can still add a description or modify it based on your requirements
  // response.headers.set(
  //   "x-metadata-description",
  //   "Description for the homepage"
  // );

  return response;
}

export const config = {
  matcher: ["/about", "/wishlist"], // Apply to specific routes that require authentication
};
