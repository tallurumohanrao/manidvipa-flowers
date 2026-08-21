export async function fetchStaticMetadata(url) {
  if (url) {
    try {
      const apiUrl = String(
        process.env.NEXT_PUBLIC_MANIDVIPA_URL ||
          "https://admin.manidvipastore.com/api"
      ).replace(/\/+$/, "");
      const res = await fetch(
        `${apiUrl}/seo-meta-data?url=${encodeURIComponent(url)}`,
        { next: { revalidate: 10 } }
      );
      if (!res.ok) {
        throw new Error("Failed to fetch metadata");
      }
      const metaData = await res.json();
      return metaData?.data || null;
    } catch (error) {
      return null;
    }
  } else {
    return null;
  }
}
