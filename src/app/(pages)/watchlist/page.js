// import Watchlist from "@/components/pages/Watchlist";
import React from "react";
import { cookies } from "next/headers";
import { fetchListingData } from "../../../../hook/userCookie";
import dynamic from "next/dynamic";
const Watchlist = dynamic(() => import("@/components/pages/Watchlist"), {
  ssr: true,
});

const fetchAboutData = async (userToken) => {
  try {
    // Fetch wishlist data with token
    const data = await fetchListingData("GET", "wishlist", userToken);
    return data?.data;
  } catch (error) {
    console.error("Error fetching About Us page data:", error);
    return null;
  }
};

export default async function About() {
  const cookieStore = await cookies();
  const userSessionCookie = cookieStore.get("userSession");
  const guestSessionCookie = cookieStore.get("guestSession");

  let guestSession = guestSessionCookie?.value;

  let userToken = null;
  if (userSessionCookie) {
    try {
      const userSession = JSON.parse(userSessionCookie.value);
      userToken = userSession?.token;
    } catch (error) {
      console.error("Failed to parse userSession cookie:", error);
    }
  }
  const aboutUs = await fetchAboutData(userToken);

  return (
    <Watchlist
      watchlist={aboutUs}
      userToken={userToken}
      guestSession={guestSession}
    />
  );
}
