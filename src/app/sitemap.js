export default async function sitemap() {
  const API_URL = "https://admin.manidvipastore.com/api/sitemap";

  try {
    const response = await fetch(API_URL, {
      method: "GET",
      headers: {
        "Content-Type": "application/json",
      },
    });

    if (!response.ok) {
      throw new Error(`Failed to fetch sitemap data: ${response.status}`);
    }

    const data = await response.json();

    const sitemapEntries = data?.data?.map((item) => ({
      url: item.loc,
      lastModified: item.lastmod,
      changefreq: item.changefreq,
      priority: item.priority || "2.0",
    }));
    return sitemapEntries;
  } catch (error) {
    console.error("Error generating sitemap:", error);

    return [
      {
        url: "https://example.com",
        lastModified: new Date(),
        changefreq: "daily",
        priority: "1.0",
      },
    ];
  }
}
