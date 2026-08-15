import ProductDetails from "@/components/pages/ProductDetails";
import { cookies } from "next/headers";
import React from "react";
import { fetchListingData } from "../../../../../hook/userCookie";

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

  const produtsDetails = await fetchAboutData(
    `product-details?product_slug=${id}`,
    userToken
  );
  const produtsReviews = await fetchAboutData(
    `reviews?product_id=${produtsDetails?.data?.id}`,
    userToken
  );

  return (
    <ProductDetails
      userToken={userToken}
      guestSession={guestSession}
      produtsDetails={produtsDetails}
      produtsReviews={produtsReviews}
    />
  );
}
