import React from "react";
import ContactUs from "@/components/pages/ContactUs";
import {
  fetchCategoryData,
  fetchListingData,
} from "../../../../hook/userCookie";
import { cookies } from "next/headers";

const fetchAboutData = async (query) => {
  try {
    const data = await fetchListingData("GET", query);
    return data;
  } catch (error) {
    console.error("Error fetching details page data:", error);
    return null;
  }
};

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
  const contactDetails = await fetchAboutData(
    `static-page?page_name=contact-us`
  );
  const produtTitles = await fetchCategoryData(`categories`);
  return (
    <ContactUs
      contactDetails={contactDetails}
      produtTitles={produtTitles}
      userToken={userToken}
    />
  );
}
