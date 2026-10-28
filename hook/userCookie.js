import Cookies from "js-cookie";

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

// Product information changes frequently in Admin. These endpoints must all
// read the same current image, selling option, price, and stock instead of
// keeping independent Next.js cache snapshots for each storefront surface.
const LIVE_PRODUCT_GET_ENDPOINTS = new Set([
  "home-featured-products",
  "home-sections",
  "product-details",
  "products-by-category",
  "search",
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

export async function fetchListingData(
  req_method,
  endpoint,
  userToken = "",
  formData = null
) {
  const url = process.env.NEXT_PUBLIC_MANIDVIPA_URL;
  try {
    const endpointPath =
      typeof endpoint === "string" ? endpoint.split("?")[0] : "";
    const isLiveProductRequest =
      req_method === "GET" && LIVE_PRODUCT_GET_ENDPOINTS.has(endpointPath);
    const publicRevalidateSeconds =
      req_method === "GET"
        ? PUBLIC_GET_REVALIDATE_SECONDS.get(endpointPath)
        : undefined;
    const options = {
      method: req_method,
      headers: {
        ...(userToken && publicRevalidateSeconds === undefined
          ? { Authorization: `Bearer ${userToken}` }
          : {}),
        "Content-Type": "application/json",
      },
      // headers: {
      //   Authorization: userToken ? `Bearer ${userToken}` : undefined,
      //   "Content-Type": "application/json",
      // },
    };

    if (req_method !== "GET" && formData) {
      options.body = JSON.stringify(formData);
    }

    const fetchOptions =
      req_method === "GET"
        ? isLiveProductRequest
          ? { ...options, cache: "no-store" }
          : publicRevalidateSeconds !== undefined
          ? {
              ...options,
              cache: "force-cache",
              next: { revalidate: publicRevalidateSeconds },
            }
          : { ...options, next: { revalidate: 60 } }
        : options;

    const response = await fetch(`${url}/${endpoint}`, fetchOptions);

    // console.clear();
    // console.log("res", response);
    if (response.ok) {
      const data = await response.json();
      // console.log("data", data, data.message);
      // alert("data", data.message);

      return data;
    } else {
      const data = await response.json();
      if (data.message) {
        return data;
      }
    }
  } catch (error) {
    console.log("Error fetching data:", error);
    return null;
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
  if (!guestSession) return { data: [] };

  return fetchCartSessionData(
    `get-cart?cart_session=${encodeURIComponent(guestSession)}`,
    userToken
  );
}

export async function fetchPageData(
  req_method,
  endpoint,
  userToken = "",
  formData = null
) {
  const url = process.env.NEXT_PUBLIC_MANIDVIPA_URL;
  try {
    const options = {
      method: req_method,
      headers: {
        ...(userToken && { Authorization: `Bearer ${userToken}` }),
        "Content-Type": "application/json",
      },
      // headers: {
      //   Authorization: userToken ? `Bearer ${userToken}` : undefined,
      //   "Content-Type": "application/json",
      // },
    };

    if (req_method !== "GET" && formData) {
      options.body = JSON.stringify(formData);
    }

    const fetchOptions =
      req_method === "GET"
        ? { ...options, next: { cache: "no-store" } }
        : options;

    const response = await fetch(`${url}/${endpoint}`, fetchOptions);

    // console.clear();
    // console.log("res", response);
    if (response.ok) {
      const data = await response.json();
      // console.log("data", data, data.message);
      // alert("data", data.message);

      return data;
    } else {
      const data = await response.json();
      if (data.message) {
        return data;
      }
    }
  } catch (error) {
    console.log("Error fetching data:", error);
    return null;
  }
}

// fetch categories

export async function fetchCategoryData(userToken) {
  const PUBLIC_URL = process.env.NEXT_PUBLIC_MANIDVIPA_URL;

  try {
    const response = await fetch(`${PUBLIC_URL}/categories`, {
      method: "GET",
      headers: {
        "Content-Type": "application/json",
      },
      cache: "force-cache",
      next: { revalidate: 60 },
    });
    const resJson = await response.json();
    return resJson;
  } catch (error) {
    console.error("Error fetching posts:", error);
    return [];
  }
}

// fetch site settings
export async function fetchSiteSettingsData(userToken) {
  const PUBLIC_URL = process.env.NEXT_PUBLIC_MANIDVIPA_URL;

  try {
    const response = await fetch(`${PUBLIC_URL}/settings`, {
      method: "GET",
      headers: {
        "Content-Type": "application/json",
      },
      cache: "force-cache",
      next: { revalidate: 60 },
    });
    const resJson = await response.json();
    return resJson;
  } catch (error) {
    console.error("Error fetching posts:", error);
    return [];
  }
}

// get cart session
export async function fetchCartSessionData(query, userToken) {
  const PUBLIC_URL = process.env.NEXT_PUBLIC_MANIDVIPA_URL;

  try {
    const response = await fetch(`${PUBLIC_URL}/${query}`, {
      method: "GET",
      cache: "no-store",
      headers: {
        ...(userToken && { Authorization: `Bearer ${userToken}` }),
        "Content-Type": "application/json",
      },
    });
    const resJson = await response.json();
    return resJson;
  } catch (error) {
    console.error("Error fetching posts:", error);
    return [];
  }
}

export async function fetchHomeData(endpoint) {
  const url = process.env.NEXT_PUBLIC_MANIDVIPA_URL;
  try {
    const options = {
      method: "GET",
      headers: {
        "Content-Type": "application/json",
      },
    };

    const response = await fetch(`${url}/${endpoint}`, options);

    if (response.ok) {
      const data = await response.json();
      return data;
    } else {
      const data = await response.json();
      if (data.message) {
        alert(data.message);
      }
    }
  } catch (error) {
    console.log("Error fetching data:", error);
    return null;
  }
}

export function formatPrice(price) {
  const formattedPrice = new Intl.NumberFormat("en-IN", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(price);

  return `Rs ${formattedPrice}`;
}
