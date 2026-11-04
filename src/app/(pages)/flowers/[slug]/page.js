import { ApiError, isApiNotFound } from "@/lib/apiClient";
import ProductDetails from "@/components/pages/ProductDetails";
import { cookies } from "next/headers";
import { notFound } from "next/navigation";
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
} from "@/lib/seo";
import {
  buildPublicSeoRouteMap,
  fetchEditableSeoRoutes,
  fetchFirstSeoMetadata,
  resolvePublicSeoPath,
} from "@/lib/metadata";
import { resolvePrimaryProductCategory } from "@/lib/productCategory";

const fetchAboutData = (query, token) => fetchListingData("GET", query, token);

function titleFromSlug(slug) {
  return String(slug).split("-").map((word) => word.charAt(0).toUpperCase() + word.slice(1)).join(" ");
}

const resolveProductDetails = cache(async (slug, token = "") => {
  try {
    const result = await fetchAboutData(`product-details?product_slug=${encodeURIComponent(slug)}`, token);
    if (!result?.data?.id || !Array.isArray(result.weights) || !Array.isArray(result.images)) {
      throw new ApiError("The product could not be loaded completely.", { code: "INVALID_RESPONSE" });
    }
    return result;
  } catch (error) {
    if (isApiNotFound(error)) return null;
    throw error;
  }
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

  // A product URL must represent a real active catalog record. Returning a
  // fallback product for an unknown slug creates thin, indexable duplicate
  // pages and makes stale URLs look valid to search engines.
  if (!produtsDetails?.data?.id) {
    notFound();
  }

  const editableRoutes = await fetchEditableSeoRoutes();
  const publicPath = `/flowers/${slug}`;
  const publicRouteMap = buildPublicSeoRouteMap(editableRoutes);

  const produtsReviews = produtsDetails?.data?.id
    ? await fetchAboutData(`reviews?product_id=${produtsDetails.data.id}`, userToken)
    : { success: true, data: [] };
  const siteSettings = await fetchSiteSettingsData(userToken);
  const productTitle = produtsDetails?.data?.title || titleFromSlug(slug);
  const primaryCategory = resolvePrimaryProductCategory(produtsDetails);
  const productSchema = buildProductSchema(produtsDetails, slug, publicPath);
  const breadcrumbSchema = buildBreadcrumbSchema([
    { name: "Home", path: resolvePublicSeoPath("/", publicRouteMap) },
    {
      name: primaryCategory.title,
      path: resolvePublicSeoPath(primaryCategory.href, publicRouteMap),
    },
    { name: productTitle, path: publicPath },
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

