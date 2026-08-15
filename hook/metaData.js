export async function fetchStaticMetadata(url) {
  if (url) {
    try {
      const res = await fetch(
        `https://admin.manidvipastore.com/api/seo-meta-data?url=${url}`,
        { next: { revalidate: 10 } }
      );
      if (!res.ok) {
        throw new Error("Failed to fetch metadata");
      }
      const metaData = await res.json();
      return {
        title: metaData?.data?.page_title || "Manidvipa",
        description: metaData?.data?.meta_description || "Default Description",
        keywords: metaData?.data?.meta_keywords || "Default Keywords",
        robots: metaData?.data?.robots || "index, follow",
      };
    } catch (error) {
      return {
        title: "Default Title",
        description: "Default Description",
      };
    }
  } else {
    return;
  }
}
