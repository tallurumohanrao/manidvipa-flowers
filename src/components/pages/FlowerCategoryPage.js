import React from "react";
import { cookies } from "next/headers";
import CategoryProducts from "@/components/pages/CategoryProducts";
import {
  fetchListingData,
  fetchSiteSettingsData,
} from "../../../hook/userCookie";
import {
  buildBreadcrumbSchema,
  buildItemListSchema,
  jsonLdScriptContent,
  unpackPaginatedProducts,
} from "@/lib/seo";

async function getUserTokenFromCookies() {
  const cookieStore = await cookies();
  const userSessionCookie = cookieStore.get("userSession");

  if (!userSessionCookie) return null;

  try {
    const userSession = JSON.parse(userSessionCookie.value);
    return userSession?.token || null;
  } catch (error) {
    console.error("Failed to parse userSession cookie:", error);
    return null;
  }
}

export default async function FlowerCategoryPage({ config }) {
  const userToken = await getUserTokenFromCookies();
  const categorySlug = config.categorySlug || "all-flowers";

  const [productByCategoryData, categoriesData, siteSettings] = await Promise.all([
    fetchListingData(
      "GET",
      `products-by-category?category_slug=${encodeURIComponent(categorySlug)}`,
      userToken
    ),
    fetchListingData("GET", "categories", userToken),
    fetchSiteSettingsData(userToken),
  ]);
  const products = unpackPaginatedProducts(productByCategoryData);
  const breadcrumbSchema = buildBreadcrumbSchema([
    { name: "Home", path: "/" },
    { name: config.label || config.title, path: config.href },
  ]);
  const itemListSchema = buildItemListSchema(products, config.href);

  return (
    <>
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: jsonLdScriptContent(breadcrumbSchema) }}
      />
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: jsonLdScriptContent(itemListSchema) }}
      />
      <CategoryProducts
        category_slug={categorySlug}
        produtsCategory={productByCategoryData?.data}
        categories={categoriesData?.data || []}
        siteSettings={siteSettings}
        userToken={userToken}
        pageHeading={config.title}
        pageDescription={config.description}
        breadcrumbLabel={config.label}
      />
    </>
  );
}
