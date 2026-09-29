import { NextResponse } from "next/server";

function getApiBaseUrl() {
  return String(
    process.env.NEXT_PUBLIC_MANIDVIPA_URL ||
      "https://admin.manidvipastore.com/api"
  ).replace(/\/+$/, "");
}

function normalizeRoutePath(value) {
  const path = String(value || "/")
    .split("?")[0]
    .split("#")[0]
    .trim();
  const withSlash = path.startsWith("/") ? path : `/${path}`;
  const normalized = withSlash.replace(/\/{2,}/g, "/").replace(/\/$/, "");
  return normalized || "/";
}

async function fetchEditableRoutes() {
  try {
    const response = await fetch(`${getApiBaseUrl()}/seo-routes`, {
      cache: "no-store",
      headers: {
        Accept: "application/json",
        "Cache-Control": "no-cache",
      },
    });

    if (!response.ok) return { routes: [], redirects: [] };

    const result = await response.json();
    return {
      routes: Array.isArray(result?.data?.routes) ? result.data.routes : [],
      redirects: Array.isArray(result?.data?.redirects)
        ? result.data.redirects
        : [],
    };
  } catch {
    return { routes: [], redirects: [] };
  }
}

function metadataRequestHeaders(request, pathName) {
  const requestHeaders = new Headers(request.headers);
  requestHeaders.set("x-metadata-pathName", normalizeRoutePath(pathName));
  requestHeaders.set("x-metadata-description", normalizeRoutePath(pathName));
  return requestHeaders;
}

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
  "jasmine-malli": "fragrance-flowers",
  "jasmine-flowers": "fragrance-flowers",
  "seasonal-flowers": "rare-flowers",
  tuberose: "rare-flowers",
  sampangi: "fragrance-flowers",
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

export async function proxy(request) {
  const pathName = request.nextUrl.pathname;
  const legacyCategory = request.nextUrl.searchParams.get("category");

  if (pathName === "/flowers" && legacyCategory) {
    const categorySlug = cleanSlug(legacyCategory);
    const redirectUrl = request.nextUrl.clone();

    redirectUrl.pathname = buildLegacyCategoryRedirectPath(categorySlug);
    redirectUrl.searchParams.delete("category");

    return NextResponse.redirect(redirectUrl, 308);
  }

  const currentPath = normalizeRoutePath(pathName);
  const routeConfig = await fetchEditableRoutes();
  const historicalRedirect = routeConfig.redirects.find(
    (redirect) => normalizeRoutePath(redirect?.from_url) === currentPath
  );

  if (historicalRedirect?.to_url) {
    const redirectUrl = request.nextUrl.clone();
    redirectUrl.pathname = normalizeRoutePath(historicalRedirect.to_url);
    return NextResponse.redirect(redirectUrl, 308);
  }

  const publicRoute = routeConfig.routes.find(
    (route) => normalizeRoutePath(route?.url) === currentPath
  );

  if (publicRoute?.alias) {
    const systemPath = normalizeRoutePath(publicRoute.alias);

    if (systemPath !== currentPath) {
      const rewriteUrl = request.nextUrl.clone();
      rewriteUrl.pathname = systemPath;
      return NextResponse.rewrite(rewriteUrl, {
        request: {
          headers: metadataRequestHeaders(request, currentPath),
        },
      });
    }
  }

  const systemRoute = routeConfig.routes.find(
    (route) =>
      normalizeRoutePath(route?.alias) === currentPath &&
      normalizeRoutePath(route?.url) !== currentPath
  );

  if (systemRoute?.url) {
    const redirectUrl = request.nextUrl.clone();
    redirectUrl.pathname = normalizeRoutePath(systemRoute.url);
    return NextResponse.redirect(redirectUrl, 308);
  }

  return NextResponse.next({
    request: {
      headers: metadataRequestHeaders(request, currentPath),
    },
  });
}

export const config = {
  matcher: ["/((?!api|_next/static|_next/image|favicon.ico|sitemap.xml|robots.txt|assets).*)"],
};
