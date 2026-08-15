import OrderCheckout from "@/components/pages/OrderCheckout";
import { cookies } from "next/headers";
import { fetchCartSessionData } from "../../../../hook/userCookie";
import { fetchBlogData } from "../../../../hook/loginAuth";

const fetchAboutData = async (query, userToken) => {
  try {
    const result = await fetchCartSessionData(
      query,
      userToken ? userToken : undefined
    );
    return result;
  } catch (error) {
    console.error("Error fetching About Us page data:", error);
    return null;
  }
};

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

  let endpoint = null;

  if (userSessionCookie) {
    endpoint = "addresses";
  } else if (guestAddressId) {
    endpoint = `checkout-get-address-by-id?address_id=${guestAddressId}`;
  }

  // const endpoint = userToken
  //   ? "addresses"
  //   : guestAddressId
  //   ? `checkout-get-address-by-id?address_id=${guestAddressId}`
  //   : null;

  const CartDetailsData = await fetchAboutData(
    `get-cart?cart_session=${guestSession}`,
    userToken
  );
  const PaymentMethods = await fetchAboutData(`payment-methods`, userToken);
  const userAddress = await fetchAboutData(endpoint, userToken);
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
