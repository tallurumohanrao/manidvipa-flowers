import React from "react";
import ThankYou from "@/components/pages/ThankYou";
import { fetchListingData } from "../../../../../hook/userCookie";

const fetchAboutData = async (id) => {
  try {
    const data = await fetchListingData("GET", `order-summary/${id}`);
    return data;
  } catch (error) {
    console.error("Error fetching About Us page data:", error);
    return null;
  }
};

export default async function OrderSuccess({ params }) {
  const { id } = await params;

  const orderData = await fetchAboutData(id);

  return <ThankYou orderData={orderData} />;
}
