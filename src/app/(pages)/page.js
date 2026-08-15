import HomePage from "@/components/pages/HomePage";
import { cookies } from "next/headers";
import React from "react";
import {
  fetchListingData,
  fetchSiteSettingsData,
} from "../../../hook/userCookie";

export default async function Page() {
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

  const [bannersData, categoriesData, homeProductsData, siteSettings] =
    await Promise.all([
      fetchListingData("GET", "banners", userToken),
      fetchListingData("GET", "categories", userToken),
      fetchListingData("GET", "home-featured-products", userToken),
      fetchSiteSettingsData(userToken),
    ]);

  return (
    <HomePage
      userToken={userToken}
      homeBanners={bannersData?.data || []}
      categories={categoriesData?.data || []}
      initialHomeProducts={homeProductsData?.data || []}
      siteSettings={siteSettings}
    />
  );
}
