import MyAccount from "@/components/pages/MyAccount";
import React from "react";
import { fetchBlogData } from "../../../../hook/loginAuth";
import { cookies } from "next/headers";

export default async function Page() {
  const cookieStore = await cookies();
  const userSessionCookie = cookieStore.get("userSession");
  const guestSessionCookie = cookieStore.get("guestSession");
  const guestAddressIdCookie = cookieStore.get("guestAddressId");

  let userToken = null;
  let guestSession = guestSessionCookie?.value;
  let guestAddressId = guestAddressIdCookie?.value;

  if (userSessionCookie) {
    try {
      const userSession = JSON.parse(userSessionCookie.value);
      userToken = userSession?.token;
    } catch (error) {
      console.error("Failed to parse userSession cookie:", error);
    }
  }
  const userData = await fetchBlogData(userToken);

  return (
    <MyAccount
      userData={userData}
      userToken={userToken}
      guestSession={guestSession}
    />
  );
}
