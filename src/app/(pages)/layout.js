import React from "react";
import Footer from "@/components/Footer/page";
import Navbar from "@/components/Navbar";
import { UserProvider } from "./context/page";
import StickyIcons from "@/components/StickyIcons/page";
import {
  fetchCartSessionData,
  fetchCategoryData,
  fetchListingData,
  fetchSiteSettingsData,
} from "../../../hook/userCookie";
import { cookies } from "next/headers";

const fetchAboutData = async (userToken) => {
  try {
    // Fetch wishlist data with token
    const data = await fetchCategoryData(userToken);

    // const data = await fetchListingData("GET", "wishlist", userToken);
    return data?.data;
  } catch (error) {
    console.error("Error fetching About Us page data:", error);
    return null;
  }
};

export default async function RootLayout({ children }) {
  const cookieStore = await cookies();
  const userSessionCookie = cookieStore.get("userSession");
  const guestSessionCookie = cookieStore.get("guestSession");
  const guestSession = guestSessionCookie?.value;

  let userToken = null;
  if (userSessionCookie) {
    try {
      const userSession = JSON.parse(userSessionCookie.value);
      userToken = userSession?.token; // Retrieve token from userSession
    } catch (error) {
      console.error("Failed to parse userSession cookie:", error);
    }
  }
  const categories = await fetchAboutData(userToken);
  const siteSettings = await fetchSiteSettingsData(userToken);
  const watchListData = await fetchListingData("GET", "wishlist", userToken);
  const cartSessionData = await fetchCartSessionData(
    `get-cart?cart_session=${guestSession}`,
    userToken ? userToken : undefined
  );

  // const categories = await fetchCategoryData();
  return (
    <UserProvider>
      <html lang="en">
        <body>
          <Navbar
            categories={categories}
            siteSettings={siteSettings}
            watchListData={watchListData?.data?.length}
            cartSessionData={cartSessionData?.data?.length}
            guestSession={guestSession}
            userToken={userToken}
          />
          {children}

          <Footer categories={categories} siteSettings={siteSettings} />
          <StickyIcons />
        </body>
      </html>
    </UserProvider>
  );
}
