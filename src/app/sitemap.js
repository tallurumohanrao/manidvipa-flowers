import {
  canonicalUrl,
  isNoIndexPath,
  normalizePath,
  PUBLIC_SITEMAP_ROUTES,
  unpackPaginatedProducts,
} from "@/lib/seo";

export const revalidate = 60;

const VALID_CHANGE_FREQUENCIES = new Set([
  "always",
  "hourly",
  "daily",
  "weekly",
  "monthly",
  "yearly",
  "never",
]);
const IS_PRODUCTION_BUILD = process.env.NEXT_PHASE === "phase-production-build";

function getApiBaseUrl() {
  return String(
    process.env.NEXT_PUBLIC_MANIDVIPA_URL || "https://admin.manidvipaflowers.com/api"
  ).replace(/\/+$/, "");
}

async function fetchApi(endpoint) {
  const url = `${getApiBaseUrl()}/${endpoint.replace(/^\/+/, "")}`;

  try {
    const response = await fetch(url, {
      method: "GET",
      headers: {
        "Content-Type": "application/json",
      },
      next: { revalidate },
    });

    if (!response.ok) {
      throw new Error(`Sitemap API request failed with ${response.status}: ${url}`);
    }

    return response.json();
  } catch (error) {
    console.error(`Error fetching sitemap data from ${endpoint}:`, error);
    if (IS_PRODUCTION_BUILD) {
      // Hostinger may build while the separate Admin API is briefly
      // unavailable. Let the build finish with the stable public routes; ISR
      // will populate categories and products as soon as the API recovers.
      return null;
    }

    // Throwing during ISR keeps the last complete sitemap available. Returning
    // partial data here would replace it with a sitemap missing categories and
    // products whenever the Admin API is briefly unavailable.
    throw error;
  }
}

function normalizeChangeFrequency(value, fallback = "weekly") {
  const frequency = String(value || fallback).toLowerCase();
  return VALID_CHANGE_FREQUENCIES.has(frequency) ? frequency : fallback;
}

function normalizePriority(value, fallback = 0.5) {
  const priority = Number(value);
  if (!Number.isFinite(priority)) return fallback;
  return Math.min(1, Math.max(0, priority));
}

function normalizeLastModified(value) {
  if (!value) return undefined;

  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? undefined : date;
}

function normalizeSitemapPath(value) {
  if (!value) return "/";

  let path = String(value);
  if (/^https?:\/\//i.test(path)) {
    try {
      path = new URL(path).pathname;
    } catch {
      path = "/";
    }
  }

  path = normalizePath(path);

  if (path.startsWith("/categories/")) {
    return normalizePath(path.replace(/^\/categories\//, "/"));
  }

  if (path.startsWith("/products/")) {
    return normalizePath(path.replace(/^\/products\//, "/"));
  }

  if (path.startsWith("/flower-category/")) {
    return normalizePath(path.replace(/^\/flower-category\//, "/"));
  }

  if (path.startsWith("/product-details/")) {
    return normalizePath(path.replace(/^\/product-details\//, "/flowers/"));
  }

  return path;
}

function buildCategorySitemapPath(category, categories = []) {
  const slug = normalizePath(`/${category.route_slug || category.slug || ""}`).replace(/^\//, "");
  if (!slug || slug === "all-flowers") return "/flowers";

  const parent = categories.find((candidate) => String(candidate?.id) === String(category?.parent_id));
  const parentSlug = normalizePath(
    `/${category.parent_route_slug || category.parent_slug || parent?.route_slug || parent?.slug || ""}`
  ).replace(/^\//, "");

  return parentSlug && parentSlug !== slug ? `/${parentSlug}/${slug}` : `/${slug}`;
}

function resolveSitemapPath(
  pathOrUrl,
  editableRoutes = new Map(),
  redirects = new Map()
) {
  let path = normalizeSitemapPath(pathOrUrl);
  const visited = new Set();

  while (!visited.has(path)) {
    visited.add(path);
    const nextPath = editableRoutes.get(path) || redirects.get(path);
    if (!nextPath) break;
    path = normalizeSitemapPath(nextPath);
  }

  return path;
}

function addEntry(
  entries,
  pathOrUrl,
  options = {},
  editableRoutes = new Map(),
  redirects = new Map()
) {
  const requestedPath = normalizeSitemapPath(pathOrUrl);
  const path = resolveSitemapPath(pathOrUrl, editableRoutes, redirects);
  const noIndexPaths = options.noIndexPaths || new Set();
  if (
    isNoIndexPath(path) ||
    noIndexPaths.has(requestedPath) ||
    noIndexPaths.has(path)
  ) return;

  const url = canonicalUrl(path);
  if (entries.has(url)) return;

  const entry = {
    url,
    changeFrequency: normalizeChangeFrequency(options.changeFrequency),
    priority: normalizePriority(options.priority),
  };
  const lastModified = normalizeLastModified(options.lastModified);

  if (lastModified) entry.lastModified = lastModified;
  entries.set(url, entry);
}

function extractProducts(productsResponse) {
  return unpackPaginatedProducts(productsResponse?.data || productsResponse);
}

async function fetchAllProducts() {
  const endpoint = "products-by-category?category_slug=all-flowers&per_page=200";
  const firstPage = await fetchApi(`${endpoint}&page=1`);
  const lastPage = Math.max(1, Number(firstPage?.data?.last_page) || 1);

  if (lastPage === 1) return extractProducts(firstPage);

  const remainingPages = await Promise.all(
    Array.from({ length: lastPage - 1 }, (_, index) =>
      fetchApi(`${endpoint}&page=${index + 2}`)
    )
  );

  return [firstPage, ...remainingPages].flatMap(extractProducts);
}

export default async function sitemap() {
  const entries = new Map();
  const [routesResponse, categoriesResponse, products] = await Promise.all([
    fetchApi("seo-routes"),
    fetchApi("categories"),
    fetchAllProducts(),
  ]);
  const editableRouteRows = Array.isArray(routesResponse?.data?.routes)
    ? routesResponse.data.routes
    : [];
  const editableRoutes = new Map(
    editableRouteRows.map((route) => [
      normalizeSitemapPath(route.alias),
      normalizeSitemapPath(route.url),
    ])
  );
  const redirectRows = Array.isArray(routesResponse?.data?.redirects)
    ? routesResponse.data.redirects
    : [];
  const redirects = new Map(
    redirectRows.map((redirect) => [
      normalizeSitemapPath(redirect.from_url),
      normalizeSitemapPath(redirect.to_url),
    ])
  );
  const noIndexPaths = new Set(
    editableRouteRows
      .filter((route) => /(^|,)\s*noindex\b/i.test(String(route?.robots || "")))
      .flatMap((route) => [
        normalizeSitemapPath(route.alias),
        normalizeSitemapPath(route.url),
      ])
  );

  PUBLIC_SITEMAP_ROUTES.forEach((route) => {
    addEntry(
      entries,
      route.path,
      { ...route, noIndexPaths },
      editableRoutes,
      redirects
    );
  });

  const categories = Array.isArray(categoriesResponse?.data) ? categoriesResponse.data : [];

  categories.forEach((category) => {
    const slug = category.route_slug || category.slug;
    if (!slug) return;
    addEntry(entries, buildCategorySitemapPath(category, categories), {
      lastModified: category.updated_at || category.created_at,
      changeFrequency: "daily",
      priority: category.parent_id ? 0.75 : 0.8,
      noIndexPaths,
    }, editableRoutes, redirects);
  });

  products.forEach((product) => {
    if (!product?.slug) return;
    addEntry(entries, `/flowers/${product.slug}`, {
      lastModified: product.updated_at || product.created_at,
      changeFrequency: "daily",
      priority: 0.72,
      noIndexPaths,
    });
  });

  return Array.from(entries.values());
}

