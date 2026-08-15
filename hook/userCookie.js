import Cookies from "js-cookie";
import { revalidatePath } from "next/cache";

export default function handler(req, res) {
  const token = req.headers["x-user-token"];

  if (!token) {
    return res.status(401).json({ error: "Unauthorized" });
  }

  res.status(200).json({ token });
}

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
        ...(userToken && { Authorization: `Bearer ${userToken}` }),
        "Content-Type": "application/json",
      },
      revalidate: 60,
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
        ...(userToken && { Authorization: `Bearer ${userToken}` }),
        "Content-Type": "application/json",
      },
      revalidate: 60,
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
      headers: {
        ...(userToken && { Authorization: `Bearer ${userToken}` }),
        "Content-Type": "application/json",
      },
      revalidate: 60,
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
