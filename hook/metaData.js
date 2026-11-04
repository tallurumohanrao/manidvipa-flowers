import { requestApi } from "../src/lib/apiClient";

export async function fetchStaticMetadata(url) {
  if (!url) return null;
  try {
    const result = await requestApi(`seo-meta-data?url=${encodeURIComponent(url)}`, {
      cache: "force-cache", next: { revalidate: 60 },
    });
    return result.data || null;
  } catch {
    return null; // Existing page-specific SEO remains the fallback.
  }
}
