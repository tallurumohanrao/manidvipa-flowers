import {
  buildCategoryNavigationHref,
  normalizeNavigationHref,
} from "@/lib/navigation";

const CATEGORY_TITLES = {
  "fragrant-flowers": "Fragrant Flowers",
};

function titleFromSlug(slug) {
  return String(slug || "")
    .split("-")
    .filter(Boolean)
    .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
    .join(" ");
}

export function resolvePrimaryProductCategory(productDetails) {
  const product = productDetails?.data || productDetails || {};
  const categories = Array.isArray(product?.categories) ? product.categories : [];
  const category =
    product?.primary_category ||
    categories.find((item) => Boolean(item?.is_primary)) ||
    categories.find((item) => item?.slug === product?.category_slug) ||
    (product?.category_slug
      ? {
          slug: product.category_slug,
          title: product.category_title,
          path: product.category_path,
        }
      : null);

  if (!category) {
    return {
      id: null,
      slug: "flowers",
      title: "Flowers",
      href: "/flowers",
    };
  }

  const slug = String(category.slug || "").trim();
  const title =
    CATEGORY_TITLES[slug] ||
    String(category.title || "").trim() ||
    titleFromSlug(slug) ||
    "Flowers";
  const href = normalizeNavigationHref(
    category.path ||
      category.public_path ||
      product.category_path ||
      buildCategoryNavigationHref(category)
  );

  return {
    ...category,
    slug,
    title,
    href: href || "/flowers",
  };
}
