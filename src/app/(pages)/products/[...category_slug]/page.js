import React from "react";
import CategoryProducts from "@/components/pages/CategoryProducts";
import { cookies } from "next/headers";
import { fetchListingData } from "../../../../../hook/userCookie";

export default async function Page({ params }) {
  const { category_slug } = await params;
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
  const productByCategoryData = await fetchListingData(
    "GET",
    `products-by-category?category_slug=${category_slug}`,
    userToken
  );

  return (
    <CategoryProducts
      category_slug={category_slug}
      produtsCategory={productByCategoryData?.data}
      userToken={userToken}
    />
  );
}
