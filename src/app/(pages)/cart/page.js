import CartDetails from "@/components/pages/CartDetails";
import React from "react";
import {
  fetchCartSessionData,
  fetchSiteSettingsData,
} from "../../../../hook/userCookie";
import { cookies } from "next/headers";

export const dynamic = "force-dynamic";
export const revalidate = 0;

const fetchAboutData = (guestSession, token) => fetchCartSessionData(
  `get-cart?cart_session=${encodeURIComponent(guestSession || "")}`, token);

export default async function Page() {
  const cookieStore = await cookies();
  const userSessionCookie = cookieStore.get("userSession");
  const guestSessionCookie = cookieStore.get("guestSession");

  let userToken = null;
  let guestSession = guestSessionCookie?.value || "";
  if (userSessionCookie) {
    try {
      const userSession = JSON.parse(userSessionCookie?.value);
      userToken = userSession?.token;
    } catch (error) {
      console.error("Failed to parse userSession cookie:", error);
    }
  }
  const [CartDetailsData, siteSettings] = await Promise.all([
    fetchAboutData(guestSession, userToken),
    fetchSiteSettingsData(userToken),
  ]);

  return (
    <CartDetails
      CartDetailsData={CartDetailsData}
      guestSession={guestSession}
      userToken={userToken}
      siteSettings={siteSettings}
    />
  );
}
