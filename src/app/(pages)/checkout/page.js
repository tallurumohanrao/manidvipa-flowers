import OrderCheckout from "@/components/pages/OrderCheckout";
import { cookies } from "next/headers";
import { fetchCartSessionData } from "../../../../hook/userCookie";
import { fetchBlogData } from "../../../../hook/loginAuth";

const fetchAboutData = (query, token) => fetchCartSessionData(query, token);

export default async function Checkout() {
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

  const cartParams = new URLSearchParams({
    cart_session: guestSession || "",
  });

  if (guestAddressId) {
    cartParams.set("address_id", guestAddressId);
  }

  const CartDetailsData = await fetchAboutData(
    `get-cart?${cartParams.toString()}`,
    userToken
  );
  const PaymentMethods = await fetchAboutData(`payment-methods`, userToken);
  const userData = await fetchBlogData(userToken);

  return (
    <OrderCheckout
      CartDetailsData={CartDetailsData}
      PaymentMethods={PaymentMethods}
      userData={userData}
      userToken={userToken}
      guestSession={guestSession}
    />
  );
}
