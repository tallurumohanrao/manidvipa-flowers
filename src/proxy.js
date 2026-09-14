import { NextResponse } from "next/server";

function cleanSlug(value) {
  return String(value || "")
    .toLowerCase()
    .trim()
    .replace(/&/g, " and ")
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-+|-+$/g, "");
}

const childCategoryParents = {
  banthi: "puja-flowers",
  chamanthi: "puja-flowers",
  roses: "puja-flowers",
  kanakambaram: "puja-flowers",
  lotus: "puja-flowers",
  "jasmine-malli": "rare-flowers",
  "seasonal-flowers": "rare-flowers",
  tuberose: "rare-flowers",
  sampangi: "rare-flowers",
  tulips: "premium-flowers",
  orchids: "premium-flowers",
  lilies: "premium-flowers",
  "imported-exotic-flowers": "premium-flowers",
};

function buildLegacyCategoryRedirectPath(categorySlug) {
  if (!categorySlug || categorySlug === "all-flowers") return "/flowers";
  const parentSlug = childCategoryParents[categorySlug];
  return parentSlug ? `/${parentSlug}/${categorySlug}` : `/${categorySlug}`;
}

export function proxy(request) {
  const pathName = request.nextUrl.pathname;
  const legacyCategory = request.nextUrl.searchParams.get("category");

  if (pathName === "/flowers" && legacyCategory) {
    const categorySlug = cleanSlug(legacyCategory);
    const redirectUrl = request.nextUrl.clone();

    redirectUrl.pathname = buildLegacyCategoryRedirectPath(categorySlug);
    redirectUrl.searchParams.delete("category");

    return NextResponse.redirect(redirectUrl, 308);
  }

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
