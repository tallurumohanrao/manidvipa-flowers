import { fetchStaticMetadata } from "../../hook/metaData";
import { buildMetadata, normalizePath } from "@/lib/seo";

function getApiBaseUrl() {
  return String(
    process.env.NEXT_PUBLIC_MANIDVIPA_URL ||
      "https://admin.manidvipastore.com/api"
  ).replace(/\/+$/, "");
}

export async function fetchEditableSeoRoutes() {
  try {
    const response = await fetch(`${getApiBaseUrl()}/seo-routes`, {
      next: { revalidate: 10 },
    });

    if (!response.ok) return [];

    const result = await response.json();
    return Array.isArray(result?.data?.routes) ? result.data.routes : [];
  } catch {
    return [];
  }
}

export function buildPublicSeoRouteMap(routes = []) {
  return new Map(
    routes
      .filter((route) => route?.alias && route?.url)
      .map((route) => [normalizePath(route.alias), normalizePath(route.url)])
  );
}

export function resolvePublicSeoPath(path, routeMap = new Map()) {
  const normalizedPath = normalizePath(path);
  return routeMap.get(normalizedPath) || normalizedPath;
}

export async function fetchFirstSeoMetadata(paths = []) {
  const uniquePaths = [...new Set(paths.filter(Boolean).map(normalizePath))];

  for (const path of uniquePaths) {
    const data = await fetchStaticMetadata(path);

    if (data) {
      return data;
    }
  }

  return null;
}

export function applySeoMetadata(options, seoData) {
  if (!seoData) return options;

  return {
    ...options,
    title: seoData.page_title || options.title,
    description: seoData.meta_description || options.description,
    keywords: seoData.meta_keywords || options.keywords,
    robots: seoData.robots || options.robots,
    path: seoData.url || options.path,
  };
}

export async function buildMetadataWithAdminSeo(options = {}, aliases = []) {
  const path = normalizePath(options.path || "/");
  const seoData = await fetchFirstSeoMetadata([path, ...aliases]);

  return buildMetadata(applySeoMetadata({ ...options, path }, seoData));
}
