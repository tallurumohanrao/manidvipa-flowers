import Cookies from "js-cookie";
import { requestApi, requireDataArray } from "../src/lib/apiClient";

const PUBLIC_GET_REVALIDATE_SECONDS = new Map([
  ["banners", 60],
  ["categories", 60],
  ["faqs", 300],
  ["home-categories", 60],
  ["navigation", 60],
  ["seo-meta-data", 60],
  ["seo-routes", 60],
  ["settings", 60],
  ["static-page", 300],
  ["subscription-plans", 60],
]);

export function fetchUser() {
  const userSession = Cookies.get("userSession");
  if (userSession) {
    try {
      const user = JSON.parse(userSession);
      return user.token;
    } catch (error) {
      return null;
    }
  } else {
    return null;
  }
}


export async function fetchListingData(method, endpoint, userToken = "", formData = null) {
  const ttl = method === "GET" ? PUBLIC_GET_REVALIDATE_SECONDS.get(endpoint?.split("?")[0]) : undefined;
  try {
    return await requestApi(endpoint, {
      method, token: ttl === undefined ? userToken : undefined,
      body: method !== "GET" ? formData : undefined,
      ...(ttl === undefined ? {} : { cache: "force-cache", next: { revalidate: ttl } }),
    });
  } catch (error) {
    // Forms already consume success/message validation responses. Never retry writes.
    if (method === "GET") throw error;
    return {
      success: false, status: false,
      message: error.message,
      ...(error.status < 500 && error.payload?.data ? { data: error.payload.data } : {}),
      httpStatus: error.status || 503,
    };
  }
}

export function getCartItems(cartResponse) {
  if (Array.isArray(cartResponse?.data?.data)) return cartResponse.data.data;
  if (Array.isArray(cartResponse?.data?.cart)) return cartResponse.data.cart;
  if (Array.isArray(cartResponse?.data?.items)) return cartResponse.data.items;
  if (Array.isArray(cartResponse?.data)) return cartResponse.data;
  if (Array.isArray(cartResponse?.cart)) return cartResponse.cart;
  if (Array.isArray(cartResponse?.items)) return cartResponse.items;
  return [];
}

export function getCartCount(cartResponse) {
  const explicitCount =
    cartResponse?.totals?.items_count ??
    cartResponse?.totals?.item_count ??
    cartResponse?.cart_count ??
    cartResponse?.count ??
    cartResponse?.data?.count;
  const numericCount = Number(explicitCount);

  if (Number.isFinite(numericCount)) return numericCount;

  return getCartItems(cartResponse).length;
}


export async function fetchCartBySession(guestSession, userToken) {
  if (!guestSession && !userToken) return { success: true, data: [], cart_count: 0 };
  return fetchCartSessionData(
    `get-cart?cart_session=${encodeURIComponent(guestSession || "")}`, userToken);
}

export function fetchPageData(method, endpoint, userToken = "", formData = null) {
  return fetchListingData(method, endpoint, userToken, formData);
}

export async function fetchCategoryData(userToken) {
  const result = await fetchListingData("GET", "categories", userToken);
  requireDataArray(result);
  return result;
}

export function fetchSiteSettingsData(userToken) {
  return fetchListingData("GET", "settings", userToken);
}

export function fetchCartSessionData(query, userToken, options = {}) {
  return requestApi(query, { ...options, token: userToken });
}

export function fetchHomeData(endpoint) {
  return fetchListingData("GET", endpoint);
}

export function formatPrice(price) {
  const formattedPrice = new Intl.NumberFormat("en-IN", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(price);

  return `Rs ${formattedPrice}`;
}
