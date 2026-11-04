import { requestApi, ApiError } from "@/lib/apiClient";
import { NextResponse } from "next/server";

const SEO_ROUTE_CACHE_TTL_MS = 60_000;
const FIXED_STOREFRONT_PATHS = new Set(["/flowers"]);
let seoRouteCache = {
  expiresAt: 0,
  available: false,
  routes: [],
  redirects: [],
};
let seoRouteRequest = null;

function normalizeRoutePath(value) {
  const path = String(value || "/")
    .split("?")[0]
    .split("#")[0]
    .trim();
  const withSlash = path.startsWith("/") ? path : `/${path}`;
  const normalized = withSlash.replace(/\/{2,}/g, "/").replace(/\/$/, "");
  return normalized || "/";
}

function isCanonicalProductPath(value) {
  return /^\/flowers\/[^/]+$/.test(normalizeRoutePath(value));
}

async function fetchEditableRoutes() {
  if (seoRouteCache.expiresAt > Date.now()) {
    return seoRouteCache;
  }

  if (seoRouteRequest) return seoRouteRequest;

  seoRouteRequest = (async () => {
    try {
      const result = await requestApi("seo-routes", { retries: 0 });
      if (!Array.isArray(result?.data?.routes) || !Array.isArray(result?.data?.redirects)) {
        throw new ApiError("Invalid route response.");
      }
      const nextValue = { available: true, routes: result.data.routes, redirects: result.data.redirects };
      seoRouteCache = {
        ...nextValue,
        expiresAt: Date.now() + SEO_ROUTE_CACHE_TTL_MS,
      };
      return nextValue;
    } catch {
      // Keep the last valid routes and avoid retrying on every incoming request.
      seoRouteCache.expiresAt = Date.now() + 10000;
      return seoRouteCache;
    } finally {
      seoRouteRequest = null;
    }
  })();

  return seoRouteRequest;
}

function metadataRequestHeaders(request, pathName, routesAvailable = true) {
  const requestHeaders = new Headers(request.headers);
  requestHeaders.set("x-metadata-pathName", normalizeRoutePath(pathName));
  requestHeaders.set("x-metadata-description", normalizeRoutePath(pathName));
  requestHeaders.delete("x-seo-routes-unavailable");
  if (!routesAvailable) requestHeaders.set("x-seo-routes-unavailable", "1");
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

  // `/flowers/{slug}` is the permanent product URL. Some older database SEO
  // rows point that canonical route back to category-nested URLs; ignore those
  // rows and let the real product page render.
  if (
    historicalRedirect?.to_url &&
    !isCanonicalProductPath(currentPath) &&
    !FIXED_STOREFRONT_PATHS.has(currentPath)
  ) {
    const historicalTarget = normalizeRoutePath(historicalRedirect.to_url);
    const targetRoute = routeConfig.routes.find(
      (route) => normalizeRoutePath(route?.url) === historicalTarget
    );
    const redirectPath = isCanonicalProductPath(targetRoute?.alias)
      ? normalizeRoutePath(targetRoute.alias)
      : historicalTarget;
    const redirectUrl = request.nextUrl.clone();
    redirectUrl.pathname = redirectPath;
    return NextResponse.redirect(redirectUrl, 308);
  }

  const publicRoute = routeConfig.routes.find(
    (route) => normalizeRoutePath(route?.url) === currentPath
  );

  if (publicRoute?.alias) {
    const systemPath = normalizeRoutePath(publicRoute.alias);

    if (systemPath !== currentPath) {
      if (isCanonicalProductPath(systemPath)) {
        const redirectUrl = request.nextUrl.clone();
        redirectUrl.pathname = systemPath;
        return NextResponse.redirect(redirectUrl, 308);
      }

      const rewriteUrl = request.nextUrl.clone();
      rewriteUrl.pathname = systemPath;
      return NextResponse.rewrite(rewriteUrl, {
        request: {
          headers: metadataRequestHeaders(request, currentPath, routeConfig.available),
        },
      });
    }
  }

  const systemRoute = routeConfig.routes.find(
    (route) =>
      normalizeRoutePath(route?.alias) === currentPath &&
      normalizeRoutePath(route?.url) !== currentPath
  );

  if (systemRoute?.url && !isCanonicalProductPath(currentPath)) {
    const redirectUrl = request.nextUrl.clone();
    redirectUrl.pathname = normalizeRoutePath(systemRoute.url);
    return NextResponse.redirect(redirectUrl, 308);
  }

  return NextResponse.next({
    request: {
      headers: metadataRequestHeaders(request, currentPath, routeConfig.available),
    },
  });
}

export const config = {
  matcher: ["/((?!api|_next/static|_next/image|favicon.ico|sitemap.xml|robots.txt|llms.txt|assets).*)"],
};
