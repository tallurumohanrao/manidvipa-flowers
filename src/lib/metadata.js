import { fetchStaticMetadata } from "../../hook/metaData";
import { buildMetadata, normalizePath } from "@/lib/seo";

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
  };
}

export async function buildMetadataWithAdminSeo(options = {}, aliases = []) {
  const path = normalizePath(options.path || "/");
  const seoData = await fetchFirstSeoMetadata([path, ...aliases]);

  return buildMetadata(applySeoMetadata({ ...options, path }, seoData));
}
