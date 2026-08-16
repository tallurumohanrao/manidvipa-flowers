import ProductDetails from "@/components/pages/ProductDetails";
import { cookies } from "next/headers";
import React from "react";
import {
  fetchListingData,
  fetchSiteSettingsData,
} from "../../../../../hook/userCookie";

const fetchAboutData = async (query, userToken) => {
  try {
    const data = await fetchListingData(
      "GET",
      query,
      userToken ? userToken : undefined
    );
    return data;
  } catch (error) {
    console.error("Error fetching details page data:", error);
    return null;
  }
};

const fallbackFlowerDetails = [
  ["chamanthi-flowers", "Chamanthi Flowers", 120, "kg", "/assets/images/home-v2/fresh-arrivals/fresh-chamanthi.jpg"],
  ["red-roses", "Red Roses", 250, "kg", "/assets/images/home-v2/fresh-arrivals/fresh-red-roses.jpg"],
  ["kanakambaram", "Kanakambaram", 250, "kg", "/assets/images/home-v2/fresh-arrivals/fresh-kanakambaram.jpg"],
  ["banthi-flowers", "Banthi Flowers", 120, "kg", "/assets/images/home-v2/fresh-arrivals/fresh-banthi.jpg"],
  ["jasmine-flowers", "Jasmine Flowers", 500, "kg", "/assets/images/home-v2/rare-seasonal/rare-jasmine.jpg"],
  ["lotus-flowers", "Lotus Flowers", 60, "piece", "/assets/images/home-v2/fresh-arrivals/fresh-lotus.jpg"],
  ["white-roses", "White Roses", 300, "kg", "/assets/images/home-v2/premium-collection/premium-roses.jpg"],
  ["pink-roses", "Pink Roses", 300, "kg", "/assets/images/home-v2/premium-collection/premium-roses.jpg"],
  ["yellow-banthi", "Yellow Banthi", 110, "kg", "/assets/images/home-v2/fresh-arrivals/fresh-yellow-sevanthi.jpg"],
  ["orchids", "Orchids", 450, "bunch", "/assets/images/home-v2/premium-collection/premium-orchids.jpg"],
  ["lilies", "Lilies", 300, "bunch", "/assets/images/home-v2/premium-collection/premium-lilies.jpg"],
  ["mixed-flowers", "Mixed Flowers", 250, "bunch", "/assets/images/home-v2/premium-collection/premium-exotic.jpg"],
  ["sevanthi-garlands", "Sevanthi Garlands", 180, "piece", "/assets/images/home-v2/recent-decorations/recent-decoration-pooja.jpg"],
  ["temple-flower-mix", "Temple Flower Mix", 220, "box", "/assets/images/home-v2/custom-puja-flower-box.jpg"],
  ["imported-tulips", "Imported Tulips", 600, "bunch", "/assets/images/home-v2/premium-collection/premium-tulips.jpg"],
  ["puja-flower-basket", "Puja Flower Basket", 350, "basket", "/assets/images/home-v2/cta-basket-flowers.png"],
];

function titleFromSlug(slug) {
  return String(slug || "fresh-flowers")
    .split("-")
    .filter(Boolean)
    .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
    .join(" ");
}

function buildFallbackProductDetails(slug) {
  const fallback = fallbackFlowerDetails.find(([fallbackSlug]) => fallbackSlug === slug);
  if (!fallback) return null;

  const [, title, sellPrice, unit, image] = fallback;

  return {
    success: true,
    data: {
      id: null,
      title: title || titleFromSlug(slug),
      slug,
      description:
        "Fresh flower availability can change daily. Please contact Manidvipa Flowers to confirm this item and delivery timing.",
      sell_price: sellPrice,
      list_price: sellPrice,
      unit,
    },
    images: [{ name: image }],
    weights: [
      {
        id: null,
        name: `1 ${unit}`,
        sell_price: sellPrice,
        list_price: sellPrice,
      },
    ],
  };
}

export default async function Page({ params }) {
  const { id } = await params;

  const cookieStore = await cookies();
  const userSessionCookie = cookieStore.get("userSession");
  const guestSessionCookie = cookieStore.get("guestSession");
  const guestSession = guestSessionCookie?.value;

  let userToken = null;
  if (userSessionCookie) {
    try {
      const userSession = JSON.parse(userSessionCookie.value);
      userToken = userSession?.token;
    } catch (error) {
      console.error("Failed to parse userSession cookie:", error);
    }
  }

  let produtsDetails = await fetchAboutData(
    `product-details?product_slug=${id}`,
    userToken
  );

  if (!produtsDetails?.data) {
    produtsDetails = buildFallbackProductDetails(id);
  }

  const produtsReviews = produtsDetails?.data?.id
    ? await fetchAboutData(`reviews?product_id=${produtsDetails.data.id}`, userToken)
    : { success: true, data: [] };
  const siteSettings = await fetchSiteSettingsData(userToken);

  return (
    <ProductDetails
      userToken={userToken}
      guestSession={guestSession}
      produtsDetails={produtsDetails}
      produtsReviews={produtsReviews}
      siteSettings={siteSettings}
    />
  );
}
