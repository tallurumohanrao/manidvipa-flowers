import HomePage from "@/components/pages/HomePage";
import { cookies } from "next/headers";
import React from "react";
import {
  fetchListingData,
  fetchSiteSettingsData,
} from "../../../hook/userCookie";
import { buildMetadata, unpackPaginatedProducts } from "@/lib/seo";

export const metadata = buildMetadata({
  title: "Fresh Flowers Online in Hyderabad | Manidvipa Flowers",
  description:
    "Order fresh puja flowers, garlands, premium flowers, rare flowers and flower subscriptions in Hyderabad with Manidvipa Flowers.",
  path: "/",
  image: "/assets/images/home-v2/hero-flowers.jpg",
});

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

  const [
    bannersData,
    categoriesData,
    homeProductsData,
    premiumProductsData,
    rareProductsData,
    subscriptionPlansData,
    siteSettings,
  ] = await Promise.all([
      fetchListingData("GET", "banners?page=home", userToken),
      fetchListingData("GET", "categories?scope=home", userToken),
      fetchListingData("GET", "home-featured-products", userToken),
      fetchListingData(
        "GET",
        "products-by-category?category_slug=premium-flowers&per_page=5",
        userToken
      ),
      fetchListingData(
        "GET",
        "products-by-category?category_slug=rare-flowers&per_page=6",
        userToken
      ),
      fetchListingData("GET", "subscription-plans?featured=1", userToken),
      fetchSiteSettingsData(userToken),
    ]);

  return (
    <HomePage
      userToken={userToken}
      homeBanners={bannersData?.data || []}
      categories={categoriesData?.data || []}
      initialHomeProducts={homeProductsData?.data || []}
      initialPremiumProducts={unpackPaginatedProducts(premiumProductsData)}
      initialRareProducts={unpackPaginatedProducts(rareProductsData)}
      initialSubscriptionPlans={subscriptionPlansData?.data || []}
      siteSettings={siteSettings}
    />
  );
}
