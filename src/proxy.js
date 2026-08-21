import { NextResponse } from "next/server";

export function proxy(request) {
  const pathName = request.nextUrl.pathname;
  const response = NextResponse.next();

  if (pathName) {
    response.headers.set("x-metadata-pathName", pathName || "Default Title");
    response.headers.set(
      "x-metadata-description",
      pathName || "Default Description"
    );
  }

  return response;
}

export const config = {
  matcher: ["/((?!api|_next/static|_next/image|favicon.ico|sitemap.xml|robots.txt|assets).*)"],
};
