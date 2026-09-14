import {
  canonicalUrl,
  isNoIndexPath,
  normalizePath,
  PUBLIC_SITEMAP_ROUTES,
  unpackPaginatedProducts,
} from "@/lib/seo";

export const revalidate = 3600;

const VALID_CHANGE_FREQUENCIES = new Set([
  "always",
  "hourly",
  "daily",
  "weekly",
  "monthly",
  "yearly",
  "never",
]);

function getApiBaseUrl() {
  return String(
    process.env.NEXT_PUBLIC_MANIDVIPA_URL || "https://admin.manidvipastore.com/api"
  ).replace(/\/+$/, "");
}

async function fetchApi(endpoint) {
  try {
    const response = await fetch(`${getApiBaseUrl()}/${endpoint.replace(/^\/+/, "")}`, {
      method: "GET",
      headers: {
        "Content-Type": "application/json",
      },
      next: { revalidate },
    });

    if (!response.ok) return null;
    return response.json();
  } catch (error) {
    console.error(`Error fetching sitemap data from ${endpoint}:`, error);
    return null;
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
  const date = value ? new Date(value) : new Date();
  return Number.isNaN(date.getTime()) ? new Date() : date;
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

function addEntry(entries, pathOrUrl, options = {}) {
  const path = normalizeSitemapPath(pathOrUrl);
  if (isNoIndexPath(path)) return;

  const url = canonicalUrl(path);
  if (entries.has(url)) return;

  entries.set(url, {
    url,
    lastModified: normalizeLastModified(options.lastModified),
    changeFrequency: normalizeChangeFrequency(options.changeFrequency),
    priority: normalizePriority(options.priority),
  });
}

function extractProducts(productsResponse) {
  return unpackPaginatedProducts(productsResponse?.data || productsResponse);
}

export default async function sitemap() {
  const entries = new Map();

  PUBLIC_SITEMAP_ROUTES.forEach((route) => {
    addEntry(entries, route.path, route);
  });

  const [apiSitemap, categoriesResponse, productsResponse] = await Promise.all([
    fetchApi("sitemap"),
    fetchApi("categories"),
    fetchApi("products-by-category?category_slug=all-flowers&per_page=200"),
  ]);

  apiSitemap?.data?.forEach((item) => {
    addEntry(entries, item.loc, {
      lastModified: item.lastmod,
      changeFrequency: item.changeFrequency || item.changefreq,
      priority: item.priority,
    });
  });

  const categories = Array.isArray(categoriesResponse?.data) ? categoriesResponse.data : [];

  categories.forEach((category) => {
    const slug = category.route_slug || category.slug;
    if (!slug) return;
    addEntry(entries, buildCategorySitemapPath(category, categories), {
      lastModified: category.updated_at || category.created_at,
      changeFrequency: "daily",
      priority: category.parent_id ? 0.75 : 0.8,
    });
  });

  extractProducts(productsResponse).forEach((product) => {
    if (!product?.slug) return;
    addEntry(entries, `/flowers/${product.slug}`, {
      lastModified: product.updated_at || product.created_at,
      changeFrequency: "daily",
      priority: 0.72,
    });
  });

  return Array.from(entries.values());
}

