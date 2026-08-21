import React from "react";
import CategoryProducts from "@/components/pages/CategoryProducts";
import { cookies } from "next/headers";
import {
  fetchListingData,
  fetchSiteSettingsData,
} from "../../../../../hook/userCookie";
import {
  buildBreadcrumbSchema,
  buildCategoryMetadata,
  buildItemListSchema,
  findCategoryBySlug,
  jsonLdScriptContent,
  titleFromSlug,
  unpackPaginatedProducts,
} from "@/lib/seo";

function resolveCategorySlug(category_slug) {
  return Array.isArray(category_slug)
    ? category_slug.join("/")
    : category_slug || "all-flowers";
}

export async function generateMetadata({ params }) {
  const { category_slug } = await params;
  const categorySlug = resolveCategorySlug(category_slug);
  const categoriesData = await fetchListingData("GET", "categories");
  const category = findCategoryBySlug(categoriesData?.data || [], categorySlug);

  return buildCategoryMetadata(categorySlug, category);
}

export default async function Page({ params }) {
  const { category_slug } = await params;
  const categorySlug = resolveCategorySlug(category_slug);
  const cookieStore = await cookies();
  const userSessionCookie = cookieStore.get("userSession");

  let userToken = null;
  if (userSessionCookie) {
    try {
      const userSession = JSON.parse(userSessionCookie.value);
      userToken = userSession?.token;
    } catch (error) {
      console.error("Failed to parse userSession cookie:", error);
    }
  }
  const [productByCategoryData, categoriesData, siteSettings] = await Promise.all([
    fetchListingData(
      "GET",
      `products-by-category?category_slug=${encodeURIComponent(categorySlug)}`,
      userToken
    ),
    fetchListingData("GET", "categories", userToken),
    fetchSiteSettingsData(userToken),
  ]);
  const category = findCategoryBySlug(categoriesData?.data || [], categorySlug);
  const categoryTitle = category?.title || titleFromSlug(categorySlug, "All Flowers");
  const categoryPath = `/products/${categorySlug}`;
  const products = unpackPaginatedProducts(productByCategoryData);
  const breadcrumbSchema = buildBreadcrumbSchema([
    { name: "Home", path: "/" },
    { name: "Flowers", path: "/flowers" },
    { name: categoryTitle, path: categoryPath },
  ]);
  const itemListSchema = buildItemListSchema(products, categoryPath);

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
      />
    </>
  );
}
