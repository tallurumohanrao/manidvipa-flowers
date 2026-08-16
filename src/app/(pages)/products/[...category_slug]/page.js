import React from "react";
import CategoryProducts from "@/components/pages/CategoryProducts";
import { cookies } from "next/headers";
import {
  fetchListingData,
  fetchSiteSettingsData,
} from "../../../../../hook/userCookie";

export default async function Page({ params }) {
  const { category_slug } = await params;
  const categorySlug = Array.isArray(category_slug)
    ? category_slug.join("/")
    : category_slug;
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

  return (
    <CategoryProducts
      category_slug={categorySlug}
      produtsCategory={productByCategoryData?.data}
      categories={categoriesData?.data || []}
      siteSettings={siteSettings}
      userToken={userToken}
    />
  );
}
