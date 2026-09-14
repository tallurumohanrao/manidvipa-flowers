import React from "react";
import CategoryProducts from "@/components/pages/CategoryProducts";
import { cookies } from "next/headers";
import { notFound, permanentRedirect } from "next/navigation";
import {
  fetchListingData,
  fetchSiteSettingsData,
} from "../../../../hook/userCookie";
import {
  buildBreadcrumbSchema,
  buildCategoryMetadata,
  buildItemListSchema,
  findCategoryBySlug,
  jsonLdScriptContent,
  normalizeCategorySlug,
  titleFromSlug,
  unpackPaginatedProducts,
} from "@/lib/seo";
import { fetchFirstSeoMetadata } from "@/lib/metadata";

const reservedTopLevelRoutes = new Set([
  "about",
  "address",
  "admin",
  "api",
  "cart",
  "checkout",
  "contact-us",
  "decorations",
  "flowers",
  "forgot-password",
  "garlands",
  "gifts",
  "login",
  "my-account",
  "offers",
  "order-details",
  "orderdetails",
  "password-reset",
  "premium-flowers",
  "privacy-policy",
  "product-details",
  "products",
  "puja-flowers",
  "rare-flowers",
  "refund-cancellation",
  "register",
  "search",
  "subscriptions",
  "terms-conditions",
  "testimonials",
  "thank-you",
  "watchlist",
]);

function normalizePathSegments(categoryPath) {
  return (Array.isArray(categoryPath) ? categoryPath : [categoryPath])
    .map((segment) => normalizeCategorySlug(segment))
    .filter(Boolean);
}

function getCategorySlugFromSegments(segments) {
  return segments[segments.length - 1] || "all-flowers";
}

function buildCategoryUrl(category, categories = []) {
  const slug = normalizeCategorySlug(category?.route_slug || category?.slug || category?.title);
  if (!slug || slug === "all-flowers") return "/flowers";

  const parentSlug = normalizeCategorySlug(
    category?.parent_route_slug ||
      category?.parent_slug ||
      categories.find((candidate) => String(candidate?.id) === String(category?.parent_id))?.route_slug ||
      categories.find((candidate) => String(candidate?.id) === String(category?.parent_id))?.slug
  );

  return parentSlug && parentSlug !== slug ? `/${parentSlug}/${slug}` : `/${slug}`;
}

async function getUserToken() {
  const cookieStore = await cookies();
  const userSessionCookie = cookieStore.get("userSession");

  if (!userSessionCookie) return null;

  try {
    const userSession = JSON.parse(userSessionCookie.value);
    return userSession?.token || null;
  } catch (error) {
    console.error("Failed to parse userSession cookie:", error);
    return null;
  }
}

async function getCategoryContext(params) {
  const { category_path } = await params;
  const segments = normalizePathSegments(category_path);

  if (!segments.length) {
    return null;
  }

  if (segments.length === 1 && reservedTopLevelRoutes.has(segments[0])) {
    notFound();
  }

  const categorySlug = getCategorySlugFromSegments(segments);
  const categoriesData = await fetchListingData("GET", "categories");
  const categories = categoriesData?.data || [];
  const category = findCategoryBySlug(categories, categorySlug);

  if (!category && categorySlug !== "all-flowers") {
    notFound();
  }

  const canonicalPath = category ? buildCategoryUrl(category, categories) : `/${segments.join("/")}`;
  const currentPath = `/${segments.join("/")}`;

  if (category && currentPath !== canonicalPath) {
    permanentRedirect(canonicalPath);
  }

  return { categorySlug, category, categories, canonicalPath };
}

export async function generateMetadata({ params }) {
  const context = await getCategoryContext(params);
  if (!context) notFound();

  const { categorySlug, category, canonicalPath } = context;
  const seoData = await fetchFirstSeoMetadata([
    canonicalPath,
    `/${categorySlug}`,
    `/flower-category/${categorySlug}`,
    `/products/${categorySlug}`,
  ]);

  return buildCategoryMetadata(categorySlug, category, seoData, canonicalPath);
}

export default async function Page({ params }) {
  const context = await getCategoryContext(params);
  if (!context) notFound();

  const { categorySlug, category, categories, canonicalPath } = context;
  const userToken = await getUserToken();

  const [productByCategoryData, siteSettings] = await Promise.all([
    fetchListingData(
      "GET",
      `products-by-category?category_slug=${encodeURIComponent(categorySlug)}`,
      userToken
    ),
    fetchSiteSettingsData(userToken),
  ]);

  const categoryTitle = category?.title || titleFromSlug(categorySlug, "All Flowers");
  const products = unpackPaginatedProducts(productByCategoryData);
  const parentCategory = category?.parent_slug
    ? findCategoryBySlug(categories, category.parent_slug)
    : null;
  const breadcrumbItems = [
    { name: "Home", path: "/" },
    { name: "Flowers", path: "/flowers" },
  ];

  if (parentCategory) {
    breadcrumbItems.push({
      name: parentCategory.title || titleFromSlug(parentCategory.slug),
      path: buildCategoryUrl(parentCategory, categories),
    });
  }

  breadcrumbItems.push({ name: categoryTitle, path: canonicalPath });

  const breadcrumbSchema = buildBreadcrumbSchema(breadcrumbItems);
  const itemListSchema = buildItemListSchema(products, canonicalPath);

  return (
    <>
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: jsonLdScriptContent(breadcrumbSchema) }}
      />
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: jsonLdScriptContent(itemListSchema) }}
      />
      <CategoryProducts
        category_slug={categorySlug}
        produtsCategory={productByCategoryData?.data}
        categories={categories}
        siteSettings={siteSettings}
        userToken={userToken}
      />
    </>
  );
}
