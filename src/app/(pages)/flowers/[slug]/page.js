import ProductDetails from "@/components/pages/ProductDetails";
import { cookies } from "next/headers";
import React, { cache } from "react";
import {
  fetchListingData,
  fetchSiteSettingsData,
} from "../../../../../hook/userCookie";
import {
  buildBreadcrumbSchema,
  buildProductMetadata,
  buildProductSchema,
  jsonLdScriptContent,
  unpackPaginatedProducts,
} from "@/lib/seo";
import { fetchFirstSeoMetadata } from "@/lib/metadata";

const fetchAboutData = async (query, userToken) => {
  try {
    const data = await fetchListingData(
      "GET",
      query,
      userToken ? userToken : undefined
    );
    return data;
  } catch (error) {
    console.error("Error fetching details page data:", error);
    return null;
  }
};

const fallbackFlowerDetails = [
  ["chamanthi-flowers", "Chamanthi Flowers", 120, "kg", "/assets/images/home-v2/fresh-arrivals/fresh-chamanthi.jpg"],
  ["red-roses", "Red Roses", 250, "kg", "/assets/images/home-v2/fresh-arrivals/fresh-red-roses.jpg"],
  ["kanakambaram", "Kanakambaram", 250, "kg", "/assets/images/home-v2/fresh-arrivals/fresh-kanakambaram.jpg"],
  ["banthi-flowers", "Banthi Flowers", 120, "kg", "/assets/images/home-v2/fresh-arrivals/fresh-banthi.jpg"],
  ["jasmine-flowers", "Jasmine Flowers", 500, "kg", "/assets/images/home-v2/rare-seasonal/rare-jasmine.jpg"],
  ["lotus-flowers", "Lotus Flowers", 60, "piece", "/assets/images/home-v2/fresh-arrivals/fresh-lotus.jpg"],
  ["white-roses", "White Roses", 300, "kg", "/assets/images/home-v2/premium-collection/premium-roses.jpg"],
  ["pink-roses", "Pink Roses", 300, "kg", "/assets/images/home-v2/premium-collection/premium-roses.jpg"],
  ["yellow-banthi", "Yellow Banthi", 110, "kg", "/assets/images/home-v2/fresh-arrivals/fresh-yellow-sevanthi.jpg"],
  ["orchids", "Orchids", 450, "bunch", "/assets/images/home-v2/premium-collection/premium-orchids.jpg"],
  ["lilies", "Lilies", 300, "bunch", "/assets/images/home-v2/premium-collection/premium-lilies.jpg"],
  ["mixed-flowers", "Mixed Flowers", 250, "bunch", "/assets/images/home-v2/premium-collection/premium-exotic.jpg"],
  ["sevanthi-garlands", "Sevanthi Garlands", 180, "piece", "/assets/images/home-v2/recent-decorations/recent-decoration-pooja.jpg"],
  ["temple-flower-mix", "Temple Flower Mix", 220, "box", "/assets/images/home-v2/custom-puja-flower-box.jpg"],
  ["imported-tulips", "Imported Tulips", 600, "bunch", "/assets/images/home-v2/premium-collection/premium-tulips.jpg"],
  ["puja-flower-basket", "Puja Flower Basket", 350, "basket", "/assets/images/home-v2/cta-basket-flowers.png"],
];

function titleFromSlug(slug) {
  return String(slug || "fresh-flowers")
    .split("-")
    .filter(Boolean)
    .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
    .join(" ");
}

function buildFallbackProductDetails(slug) {
  const fallback = fallbackFlowerDetails.find(([fallbackSlug]) => fallbackSlug === slug);
  if (!fallback) return null;

  const [, title, sellPrice, unit, image] = fallback;

  return {
    success: true,
    data: {
      id: null,
      title: title || titleFromSlug(slug),
      slug,
      description:
        "Fresh flower availability can change daily. Please contact Manidvipa Flowers to confirm this item and delivery timing.",
      sell_price: sellPrice,
      list_price: sellPrice,
      unit,
    },
    images: [{ name: image }],
    weights: [
      {
        id: null,
        name: `1 ${unit}`,
        sell_price: sellPrice,
        list_price: sellPrice,
      },
    ],
  };
}

function wait(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

function getApiBaseUrl() {
  return String(
    process.env.NEXT_PUBLIC_MANIDVIPA_URL ||
      "https://admin.manidvipastore.com/api"
  ).replace(/\/+$/, "");
}

function buildProductDetailsFromListingProduct(product, slug) {
  if (!product) return null;

  const productTitle = product.title || titleFromSlug(slug);
  const images = Array.isArray(product.images) && product.images.length
    ? product.images
    : product.image_name
      ? [{ name: product.image_name }]
      : [];
  const weights = Array.isArray(product.weights) && product.weights.length
    ? product.weights
    : product.weight_id
      ? [
          {
            id: product.weight_id,
            name: product.weight_name,
            sell_price: product.sell_price,
            list_price: product.list_price,
            stock: product.stock,
            qty: product.qty,
          },
        ]
      : [];

  return {
    success: true,
    data: {
      id: product.product_id || product.id,
      title: productTitle,
      slug: product.slug || slug,
      sku: product.sku,
      description:
        product.description ||
        `Order ${productTitle} fresh flowers online in Hyderabad from Manidvipa Flowers.`,
      sell_price: product.sell_price,
      list_price: product.list_price,
      unit: product.weight_name,
    },
    images,
    weights,
  };
}

async function fetchSeoProductListing() {
  for (let attempt = 0; attempt < 3; attempt += 1) {
    try {
      const response = await fetch(
        `${getApiBaseUrl()}/products-by-category?category_slug=all-flowers&per_page=200`,
        {
          method: "GET",
          headers: {
            "Content-Type": "application/json",
          },
          next: { revalidate: 60 },
        }
      );

      if (response.ok) {
        return unpackPaginatedProducts(await response.json());
      }
    } catch (error) {
      console.error("Error fetching product listing fallback:", error);
    }

    if (attempt < 2) {
      await wait(250);
    }
  }

  return [];
}

async function fetchProductDetailsFromListing(slug) {
  let product = null;

  for (let attempt = 0; attempt < 3; attempt += 1) {
    const products = await fetchSeoProductListing();
    product = products.find((item) => item?.slug === slug);

    if (product) {
      break;
    }

    if (attempt < 2) {
      await wait(150);
    }
  }

  return buildProductDetailsFromListingProduct(product, slug);
}

const resolveProductDetails = cache(async (slug, userToken = "") => {
  for (let attempt = 0; attempt < 4; attempt += 1) {
    const productDetails = await fetchAboutData(
      `product-details?product_slug=${encodeURIComponent(slug)}`,
      userToken
    );

    if (productDetails?.data) {
      return productDetails;
    }

    if (attempt < 3) {
      await wait(150);
    }
  }

  return (
    (await fetchProductDetailsFromListing(slug)) ||
    buildFallbackProductDetails(slug)
  );
});

export async function generateMetadata({ params }) {
  const { slug } = await params;
  const productDetails = await resolveProductDetails(slug, "");
  const seoData = await fetchFirstSeoMetadata([
    `/flowers/${slug}`,
    `/product-details/${slug}`,
    `/productDetails/${slug}`,
    `/${slug}`,
  ]);

  return buildProductMetadata(productDetails, slug, seoData);
}

export default async function Page({ params }) {
  const { slug } = await params;

  const cookieStore = await cookies();
  const userSessionCookie = cookieStore.get("userSession");
  const guestSessionCookie = cookieStore.get("guestSession");
  const guestSession = guestSessionCookie?.value;

  let userToken = null;
  if (userSessionCookie) {
    try {
      const userSession = JSON.parse(userSessionCookie.value);
      userToken = userSession?.token;
    } catch (error) {
      console.error("Failed to parse userSession cookie:", error);
    }
  }

  const produtsDetails = await resolveProductDetails(slug, userToken || "");

  const produtsReviews = produtsDetails?.data?.id
    ? await fetchAboutData(`reviews?product_id=${produtsDetails.data.id}`, userToken)
    : { success: true, data: [] };
  const siteSettings = await fetchSiteSettingsData(userToken);
  const productTitle = produtsDetails?.data?.title || titleFromSlug(slug);
  const productSchema = buildProductSchema(produtsDetails, slug);
  const breadcrumbSchema = buildBreadcrumbSchema([
    { name: "Home", path: "/" },
    { name: "Flowers", path: "/flowers" },
    { name: productTitle, path: `/flowers/${slug}` },
  ]);

  return (
    <>
      {productSchema ? (
        <script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: jsonLdScriptContent(productSchema) }}
        />
      ) : null}
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: jsonLdScriptContent(breadcrumbSchema) }}
      />
      <ProductDetails
        userToken={userToken}
        guestSession={guestSession}
        produtsDetails={produtsDetails}
        produtsReviews={produtsReviews}
        siteSettings={siteSettings}
      />
    </>
  );
}

