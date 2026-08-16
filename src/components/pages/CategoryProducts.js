"use client";

import React, { useCallback, useEffect, useMemo, useRef, useState } from "react";
import Image from "next/image";
import Link from "next/link";
import { usePathname, useRouter, useSearchParams } from "next/navigation";
import { FaShoppingBasket, FaSlidersH, FaStar, FaTimes } from "react-icons/fa";
import Toast from "@/components/Toast";
import { useCartCount, useToast, useUser } from "@/context/UserContext";
import styles from "@/scss/pages/listingPage.module.scss";
import {
  fetchCartBySession,
  fetchListingData,
  getCartCount,
} from "../../../hook/userCookie";

const IMG_URL = process.env.NEXT_PUBLIC_IMG_URL;
const DEFAULT_CATEGORY = "all-flowers";
const DEFAULT_SORT = "popular";
const DEFAULT_PAGE_SIZE = 12;
const REQUEST_FLOWER_MESSAGE =
  "Hello Manidvipa Flowers, I am looking for a flower that I could not find on your website. Can you help me source it?";

const fallbackCategoryOptions = [
  { value: "all-flowers", label: "All Flowers", keywords: [] },
  {
    value: "daily-puja-flowers",
    label: "Daily Puja Flowers",
    keywords: ["daily puja", "puja", "pooja", "ritual", "temple"],
    collections: ["puja"],
    flags: ["isPujaFlower", "is_puja_flower", "puja_flower"],
  },
  {
    value: "premium-flowers",
    label: "Premium Flowers",
    keywords: ["premium", "imported", "exotic"],
    collections: ["premium"],
    flags: ["isPremium", "is_premium"],
  },
  {
    value: "rare-flowers",
    label: "Rare Flowers",
    keywords: ["rare", "limited", "special"],
    collections: ["rare"],
    flags: ["isRare", "is_rare"],
  },
  {
    value: "seasonal-flowers",
    label: "Seasonal Flowers",
    keywords: ["seasonal", "season"],
    collections: ["seasonal"],
    flags: ["isSeasonal", "is_seasonal"],
  },
  { value: "chamanthi", label: "Chamanthi", keywords: ["chamanthi", "sevanthi", "chrysanthemum"] },
  { value: "roses", label: "Roses", keywords: ["rose", "roses"] },
  { value: "banthi", label: "Banthi", keywords: ["banthi", "marigold"] },
  { value: "kanakambaram", label: "Kanakambaram", keywords: ["kanakambaram", "crossandra"] },
  { value: "jasmine-malli", label: "Jasmine / Malli", keywords: ["jasmine", "malli", "mogra"] },
  { value: "lotus", label: "Lotus", keywords: ["lotus"] },
  { value: "lilies", label: "Lilies", keywords: ["lily", "lilies"] },
  { value: "orchids", label: "Orchids", keywords: ["orchid", "orchids"] },
  { value: "gerbera", label: "Gerbera", keywords: ["gerbera"] },
  { value: "other-flowers", label: "Other Flowers", keywords: ["mixed", "assorted", "other"] },
  { value: "patri-leaves", label: "Patri & Leaves", keywords: ["patri", "leaves", "leaf", "tulasi", "bilva", "mango leaves", "betel"] },
  { value: "garlands", label: "Garlands", keywords: ["garland", "garlands", "mala"] },
  { value: "bouquets-gifting", label: "Bouquets & Gifting", keywords: ["bouquet", "bouquets", "gift", "gifting"] },
  { value: "temple-pooja", label: "Temple & Pooja", keywords: ["temple", "pooja", "puja", "ritual"] },
];

const priceOptions = [
  { value: "0-100", label: "₹0 - ₹100", min: 0, max: 100 },
  { value: "100-250", label: "₹100 - ₹250", min: 100, max: 250 },
  { value: "250-500", label: "₹250 - ₹500", min: 250, max: 500 },
  { value: "500-1000", label: "₹500 - ₹1000", min: 500, max: 1000 },
  { value: "1000-plus", label: "₹1000+", min: 1000, max: Infinity },
];

const collectionOptions = [
  { value: "fresh-today", label: "Fresh Today", keywords: ["fresh today", "fresh", "today"], flags: ["isFreshToday", "is_fresh_today"] },
  { value: "premium", label: "Premium", keywords: ["premium", "imported", "exotic"], flags: ["isPremium", "is_premium"] },
  { value: "rare", label: "Rare", keywords: ["rare", "limited"], flags: ["isRare", "is_rare"] },
  { value: "seasonal", label: "Seasonal", keywords: ["seasonal"], flags: ["isSeasonal", "is_seasonal"] },
  { value: "best-seller", label: "Best Seller", keywords: ["best seller", "bestseller"], flags: ["isBestSeller", "is_best_seller", "is_bestseller"] },
  { value: "new-arrival", label: "New Arrival", keywords: ["new arrival", "new"], flags: ["isNewArrival", "is_new_arrival", "isNew", "is_new"] },
  { value: "puja", label: "Puja Flowers", keywords: ["puja", "pooja", "temple", "ritual"], flags: ["isPujaFlower", "is_puja_flower"] },
];

const colorOptions = [
  { label: "White", value: "white", color: "#f8f4e8" },
  { label: "Red", value: "red", color: "#c20d25" },
  { label: "Yellow", value: "yellow", color: "#f4c20d" },
  { label: "Orange", value: "orange", color: "#ff8a00" },
  { label: "Pink", value: "pink", color: "#ef4aa7" },
  { label: "Purple", value: "purple", color: "#7b45b5" },
  { label: "Green", value: "green", color: "#2ab060" },
  { label: "Mixed", value: "mixed", color: "linear-gradient(135deg, #c20d25 0 25%, #f4c20d 25% 50%, #2ab060 50% 75%, #ef4aa7 75% 100%)" },
];

const flowerTypeOptions = [
  { value: "loose-flowers", label: "Loose Flowers", keywords: ["loose", "loose flowers"] },
  { value: "garlands", label: "Garlands", keywords: ["garland", "garlands", "mala"] },
  { value: "bouquets", label: "Bouquets", keywords: ["bouquet", "bouquets"] },
  { value: "bunches", label: "Bunches", keywords: ["bunch", "bunches"] },
  { value: "leaves-patri", label: "Leaves / Patri", keywords: ["leaf", "leaves", "patri", "tulasi", "bilva"] },
  { value: "puja-sets", label: "Puja Sets", keywords: ["puja set", "pooja set", "ritual set"] },
  { value: "decoration-flowers", label: "Decoration Flowers", keywords: ["decoration", "decor", "wedding", "event"] },
];

const availabilityOptions = [
  { value: "in-stock", label: "In Stock", keywords: ["in stock", "available"], flags: ["isAvailable", "is_available"] },
  { value: "available-today", label: "Available Today", keywords: ["available today", "today"], flags: ["availableToday", "available_today"] },
  { value: "pre-order", label: "Pre-Order", keywords: ["pre-order", "preorder", "pre order"], flags: ["isPreOrder", "is_pre_order", "pre_order"] },
];

const sortOptions = [
  { value: "popular", label: "Popular" },
  { value: "newest", label: "Newest" },
  { value: "price-low", label: "Price: Low to High" },
  { value: "price-high", label: "Price: High to Low" },
  { value: "best-selling", label: "Best Selling" },
  { value: "recently-added", label: "Recently Added" },
];

const localImageRules = [
  { keywords: ["kanakambaram", "crossandra"], image: "/assets/images/home-v2/fresh-arrivals/fresh-kanakambaram.jpg" },
  { keywords: ["lotus"], image: "/assets/images/home-v2/fresh-arrivals/fresh-lotus.jpg" },
  { keywords: ["jasmine", "malli", "mogra"], image: "/assets/images/home-v2/rare-seasonal/rare-jasmine.jpg" },
  { keywords: ["orchid"], image: "/assets/images/home-v2/premium-collection/premium-orchids.jpg" },
  { keywords: ["lily", "lilies"], image: "/assets/images/home-v2/premium-collection/premium-lilies.jpg" },
  { keywords: ["tulip"], image: "/assets/images/home-v2/premium-collection/premium-tulips.jpg" },
  { keywords: ["rose", "roses"], image: "/assets/images/home-v2/fresh-arrivals/fresh-red-roses.jpg" },
  { keywords: ["banthi", "marigold"], image: "/assets/images/home-v2/fresh-arrivals/fresh-banthi.jpg" },
  { keywords: ["chamanthi", "sevanthi", "chrysanthemum"], image: "/assets/images/home-v2/fresh-arrivals/fresh-chamanthi.jpg" },
  { keywords: ["mixed", "assorted", "basket"], image: "/assets/images/home-v2/cta-basket-flowers.png" },
];

function unpackProductList(source) {
  if (Array.isArray(source)) return source;
  if (Array.isArray(source?.data?.data)) return source.data.data;
  if (Array.isArray(source?.data)) return source.data;
  if (Array.isArray(source?.products)) return source.products;
  return [];
}

function normalizeText(value) {
  return String(value || "").toLowerCase().trim();
}

function normalizeParamValue(value) {
  return normalizeText(value).replace(/&/g, "and").replace(/[^a-z0-9]+/g, "-").replace(/^-|-$/g, "");
}

function parseListParam(searchParams, key) {
  const value = searchParams.get(key);
  return value ? value.split(",").map(normalizeParamValue).filter(Boolean) : [];
}

function buildCategoryOptions(categories = []) {
  const seenValues = new Set([DEFAULT_CATEGORY]);
  const dynamicCategories = Array.isArray(categories) ? categories : [];
  const options = [
    { value: DEFAULT_CATEGORY, label: "All Flowers", keywords: [] },
    ...dynamicCategories
      .map((category) => {
        const value = normalizeParamValue(category?.slug || category?.title);
        if (!value || seenValues.has(value)) return null;
        seenValues.add(value);

        return {
          value,
          label: category?.title || formatCategoryTitle(value),
          keywords: [category?.slug, category?.title].filter(Boolean),
        };
      })
      .filter(Boolean),
  ];

  return options.length > 1 ? options : fallbackCategoryOptions;
}

function formatCategoryTitle(categorySlug = DEFAULT_CATEGORY, options = fallbackCategoryOptions) {
  const cleanSlug = String(categorySlug).split("/").pop() || DEFAULT_CATEGORY;
  const matchedCategory = options.find((category) => category.value === normalizeParamValue(cleanSlug));

  if (matchedCategory) return matchedCategory.label;

  return cleanSlug
    .split("-")
    .filter(Boolean)
    .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
    .join(" ");
}

function parsePriceValue(value) {
  const numericValue = Number(String(value || "").replace(/[^\d.]/g, ""));
  return Number.isFinite(numericValue) ? numericValue : 0;
}

function formatRupees(value) {
  const price = parsePriceValue(value);
  return `₹${new Intl.NumberFormat("en-IN", {
    maximumFractionDigits: price % 1 === 0 ? 0 : 2,
  }).format(price)}`;
}

function asArray(value) {
  if (Array.isArray(value)) return value;
  if (typeof value === "string") return value.split(",").map((item) => item.trim()).filter(Boolean);
  if (value === null || value === undefined) return [];
  return [value];
}

function getProductTags(product) {
  return [
    ...asArray(product?.tags),
    ...asArray(product?.tag),
    ...asArray(product?.labels),
    ...asArray(product?.badges),
    ...asArray(product?.collections),
    ...asArray(product?.collection),
  ];
}

function getSearchableProductText(product) {
  return [
    product?.title,
    product?.name,
    product?.slug,
    product?.category,
    product?.category_name,
    product?.category_slug,
    product?.category_title,
    product?.subcategory,
    product?.sub_category,
    product?.collection,
    product?.color,
    product?.flower_type,
    product?.flowerType,
    product?.type,
    product?.availability,
    product?.stock_status,
    product?.status,
    ...getProductTags(product),
  ]
    .filter(Boolean)
    .join(" ")
    .toLowerCase();
}

function isTruthyField(value) {
  return value === true || value === 1 || value === "1" || normalizeText(value) === "true" || normalizeText(value) === "yes";
}

function hasAnyFlag(product, flags = []) {
  return flags.some((flag) => isTruthyField(product?.[flag]) || isTruthyField(product?.meta?.[flag]));
}

function matchesKeywords(product, keywords = []) {
  if (!keywords.length) return true;
  const searchableText = getSearchableProductText(product);
  return keywords.some((keyword) => searchableText.includes(normalizeText(keyword)));
}

function matchesLogicalOption(product, option) {
  if (!option) return true;
  if (!option.keywords?.length && !option.flags?.length && !option.collections?.length) return true;

  const keywordMatch = option.keywords?.length ? matchesKeywords(product, option.keywords) : false;
  const flagMatch = option.flags?.length ? hasAnyFlag(product, option.flags) : false;
  const collectionMatch = option.collections?.length
    ? option.collections.some((collection) => matchesKeywords(product, [collection]))
    : false;

  return keywordMatch || flagMatch || collectionMatch;
}

function getProductId(product) {
  return product?.product_id || product?.id || product?.data?.id || null;
}

function getWeightName(weight, fallback) {
  return weight?.name || weight?.weight || weight?.title || weight?.label || weight?.value || fallback;
}

function getWeightId(weight, product) {
  return weight?.id || weight?.weight_id || product?.weight_id || product?.default_weight_id || null;
}

function buildWeightOptions(product) {
  const weights = Array.isArray(product?.weights) ? product.weights : [];

  if (weights.length) {
    return weights.map((weight, index) => ({
      key: String(getWeightId(weight, product) || index),
      label: getWeightName(weight, `Option ${index + 1}`),
      sellPrice: parsePriceValue(weight?.sell_price || weight?.price || product?.sell_price),
      weightId: getWeightId(weight, product),
    }));
  }

  const fallbackWeightId = getWeightId(null, product);
  if (!fallbackWeightId) return [];

  return [
    {
      key: String(fallbackWeightId),
      label: product?.unit ? `1 ${product.unit}` : "1",
      sellPrice: parsePriceValue(product?.sell_price || product?.price),
      weightId: fallbackWeightId,
    },
  ];
}

function getProductTitle(product) {
  return product?.title || product?.name || "Fresh Flowers";
}

function getProductPrice(product) {
  return parsePriceValue(product?.sell_price || product?.offer_price || product?.price || product?.starting_price);
}

function getProductOriginalPrice(product) {
  return parsePriceValue(product?.list_price || product?.original_price || product?.mrp || product?.regular_price);
}

function getProductUnit(product) {
  return product?.unit || product?.units || product?.measurement || product?.weight_unit || "kg";
}

function getProductImage(product) {
  if (product?.localImage) return product.localImage;
  if (product?.image_name && IMG_URL) return `${IMG_URL}/${product.image_name}`;
  if (product?.image && String(product.image).startsWith("http")) return product.image;

  const searchableText = getSearchableProductText(product);
  const localRule = localImageRules.find((rule) =>
    rule.keywords.some((keyword) => searchableText.includes(keyword))
  );

  if (localRule) return localRule.image;
  return "/assets/images/no-image.png";
}

function getRatingValue(product) {
  const rawRating =
    product?.rating ||
    product?.average_rating ||
    product?.avg_rating ||
    product?.ratings_avg_rating ||
    product?.review_rating;
  const rating = Number(rawRating);
  return Number.isFinite(rating) && rating > 0 ? Math.min(5, rating) : 0;
}

function getDateValue(product) {
  const rawDate = product?.created_at || product?.createdAt || product?.updated_at || product?.updatedAt;
  const timestamp = rawDate ? new Date(rawDate).getTime() : 0;
  return Number.isFinite(timestamp) ? timestamp : 0;
}

function getSalesValue(product) {
  return parsePriceValue(product?.sales_count || product?.sold_count || product?.orders_count || product?.purchase_count);
}

function getAvailabilityValue(product) {
  const text = getSearchableProductText(product);

  if (hasAnyFlag(product, ["isPreOrder", "is_pre_order", "pre_order"]) || text.includes("pre-order") || text.includes("preorder")) {
    return "pre-order";
  }

  if (hasAnyFlag(product, ["availableToday", "available_today"]) || text.includes("available today")) {
    return "available-today";
  }

  if (hasAnyFlag(product, ["isAvailable", "is_available"]) || text.includes("in stock") || text.includes("available")) {
    return "in-stock";
  }

  return "";
}

function getProductBadges(product) {
  const text = getSearchableProductText(product);
  const candidates = [
    {
      value: "RARE",
      className: styles.badgeRare,
      match: hasAnyFlag(product, ["isRare", "is_rare"]) || text.includes("rare") || text.includes("limited"),
    },
    {
      value: "SEASONAL",
      className: styles.badgeSeasonal,
      match: hasAnyFlag(product, ["isSeasonal", "is_seasonal"]) || text.includes("seasonal"),
    },
    {
      value: "PREMIUM",
      className: styles.badgePremium,
      match: hasAnyFlag(product, ["isPremium", "is_premium"]) || text.includes("premium") || text.includes("imported"),
    },
    {
      value: "FRESH TODAY",
      className: styles.badgeFresh,
      match: hasAnyFlag(product, ["isFreshToday", "is_fresh_today"]) || text.includes("fresh today"),
    },
    {
      value: "BEST SELLER",
      className: styles.badgeBestSeller,
      match: hasAnyFlag(product, ["isBestSeller", "is_best_seller", "is_bestseller"]) || text.includes("best seller"),
    },
    {
      value: "NEW",
      className: styles.badgeNew,
      match: hasAnyFlag(product, ["isNewArrival", "is_new_arrival", "isNew", "is_new"]) || text.includes("new arrival"),
    },
    {
      value: "PRE-ORDER",
      className: styles.badgePreOrder,
      match: getAvailabilityValue(product) === "pre-order",
    },
  ];

  return candidates.filter((badge) => badge.match).slice(0, 2);
}

function normalizeWhatsApp(value) {
  const phone = String(value || "").replace(/\D/g, "");
  return phone ? `https://wa.me/${phone}` : "";
}

function appendWhatsAppMessage(href, message) {
  if (!href) return "/contact-us";
  const separator = href.includes("?") ? "&" : "?";
  return `${href}${separator}text=${encodeURIComponent(message)}`;
}

async function fetchCategoryProducts(categorySlug, userToken) {
  const data = await fetchListingData(
    "GET",
    `products-by-category?category_slug=${encodeURIComponent(categorySlug)}`,
    userToken ? userToken : undefined
  );
  return unpackProductList(data);
}

function FilterCheckboxGroup({ options, selectedValues, onToggle, className = "" }) {
  return (
    <div className={className}>
      {options.map((option) => (
        <label key={option.value} className={styles.checkboxLabel}>
          <input
            type="checkbox"
            checked={selectedValues.includes(option.value)}
            onChange={() => onToggle(option.value)}
          />
          <span>{option.label}</span>
        </label>
      ))}
    </div>
  );
}

function SkeletonGrid({ count = DEFAULT_PAGE_SIZE }) {
  return (
    <div className={styles.productGrid} aria-label="Loading flowers">
      {Array.from({ length: count }, (_, index) => (
        <article className={`${styles.productCard} ${styles.skeletonCard}`} key={index}>
          <div className={styles.skeletonImage} />
          <div className={styles.productCardBody}>
            <div className={styles.skeletonLine} />
            <div className={styles.skeletonLineShort} />
            <div className={styles.skeletonButton} />
          </div>
        </article>
      ))}
    </div>
  );
}

function CategoryProductCard({ product, userToken }) {
  const router = useRouter();
  const { guestSession } = useUser();
  const { showToast } = useToast();
  const { setCartCount } = useCartCount();
  const [quantity, setQuantity] = useState(1);
  const [isAdding, setIsAdding] = useState(false);

  const productId = getProductId(product);
  const productTitle = getProductTitle(product);
  const productPrice = getProductPrice(product);
  const productOriginalPrice = getProductOriginalPrice(product);
  const productUnit = getProductUnit(product);
  const productHref = product?.slug ? `/product-details/${product.slug}` : "#";
  const canAttemptCart = Boolean(productId || product?.slug);
  const rating = getRatingValue(product);
  const badges = getProductBadges(product);
  const totalProductPrice = productPrice * quantity;
  const totalOriginalPrice = productOriginalPrice * quantity;
  const hasDiscount = totalOriginalPrice > totalProductPrice && totalProductPrice > 0;
  const priceMetaText =
    quantity > 1
      ? `total - ${formatRupees(productPrice)} / ${productUnit}`
      : `/ ${productUnit}`;

  const updateQuantity = (nextQuantity) => {
    setQuantity(Math.min(99, Math.max(1, nextQuantity)));
  };

  const resolveDefaultCartSelection = async () => {
    const existingWeight = buildWeightOptions(product)[0];
    if (productId && existingWeight?.weightId) {
      return { productId, weightId: existingWeight.weightId };
    }

    if (!product?.slug) return null;

    const details = await fetchListingData(
      "GET",
      `product-details?product_slug=${encodeURIComponent(product.slug)}`,
      userToken ? userToken : undefined
    );

    const hydratedProduct = {
      ...product,
      product_id: details?.data?.id || productId,
      weight_id: details?.data?.weight_id || product?.weight_id,
      default_weight_id: details?.data?.default_weight_id || product?.default_weight_id,
      weights:
        details?.weights ||
        details?.data?.weights ||
        details?.data?.product_weights ||
        details?.product_weights ||
        [],
    };
    const hydratedWeight = buildWeightOptions(hydratedProduct)[0];
    const hydratedProductId = getProductId(hydratedProduct);

    if (!hydratedProductId || !hydratedWeight?.weightId) return null;
    return { productId: hydratedProductId, weightId: hydratedWeight.weightId };
  };

  const handleAddToCart = async (event) => {
    event.preventDefault();

    if (!guestSession) {
      showToast("Please wait while your cart is getting ready.", "error");
      return;
    }

    setIsAdding(true);

    try {
      const cartSelection = await resolveDefaultCartSelection();

      if (!cartSelection?.productId || !cartSelection?.weightId) {
        if (productHref !== "#") {
          showToast("Please choose product options on the details page.", "error");
          router.push(productHref);
          return;
        }

        showToast("This item is not ready for cart yet.", "error");
        return;
      }

      const cartData = await fetchListingData(
        "POST",
        "add-to-cart",
        userToken ? userToken : undefined,
        {
          cart_session: guestSession,
          product_id: cartSelection.productId,
          quantity,
          weight_id: cartSelection.weightId,
        }
      );

      if (!cartData?.success) {
        showToast(cartData?.message || "Failed to add to cart", "error");
        return;
      }

      showToast(cartData.message || "Added to cart successfully", "success");

      const refreshedCart = await fetchCartBySession(
        guestSession,
        userToken ? userToken : undefined
      );

      if (refreshedCart?.success) {
        setCartCount(getCartCount(refreshedCart));
      }
    } catch (error) {
      console.error("Category add-to-cart failed:", error);
      showToast("Unable to add this item now. Please try again.", "error");
    } finally {
      setIsAdding(false);
    }
  };

  return (
    <article className={styles.productCard}>
      <Link href={productHref} className={styles.productImageWrap}>
        {badges.length > 0 && (
          <div className={styles.productBadges}>
            {badges.map((badge) => (
              <span className={`${styles.productBadge} ${badge.className}`} key={badge.value}>
                {badge.value}
              </span>
            ))}
          </div>
        )}
        <Image
          src={getProductImage(product)}
          alt={productTitle}
          width={360}
          height={300}
          sizes="(max-width: 575px) 50vw, (max-width: 991px) 33vw, 25vw"
          className={styles.productImage}
        />
      </Link>

      <div className={styles.productCardBody}>
        <Link href={productHref} className={styles.productTitle}>
          {productTitle}
        </Link>

        {rating > 0 && (
          <div className={styles.rating} aria-label={`${rating.toFixed(1)} star rating`}>
            {Array.from({ length: Math.round(rating) }, (_, star) => (
              <FaStar key={star} />
            ))}
          </div>
        )}

        <p className={styles.productPrice}>
          {hasDiscount && <del>{formatRupees(totalOriginalPrice)}</del>}
          <strong>{formatRupees(totalProductPrice)}</strong>
          <span className={styles.priceMeta}>{priceMetaText}</span>
          <span>
            {quantity > 1 ? ` total • ${formatRupees(productPrice)} / ${productUnit}` : `/ ${productUnit}`}
          </span>
        </p>

        <div className={styles.quantityRow}>
          <button
            type="button"
            onClick={() => updateQuantity(quantity - 1)}
            disabled={quantity <= 1 || isAdding}
            aria-label={`Decrease quantity for ${productTitle}`}
          >
            −
          </button>
          <span>{quantity}</span>
          <button
            type="button"
            onClick={() => updateQuantity(quantity + 1)}
            disabled={isAdding}
            aria-label={`Increase quantity for ${productTitle}`}
          >
            +
          </button>
        </div>

        <div className={styles.cardActions}>
          <button
            type="button"
            className={styles.addToCartButton}
            onClick={handleAddToCart}
            disabled={isAdding || !canAttemptCart}
            title={!canAttemptCart ? "Product details are required for cart" : undefined}
          >
            <FaShoppingBasket />
            {isAdding ? "ADDING..." : "ADD"}
          </button>
          <Link href={productHref} className={styles.viewDetailsButton}>
            VIEW DETAILS
          </Link>
        </div>
      </div>
    </article>
  );
}

export default function CategoryProducts({
  category_slug,
  userToken,
  produtsCategory,
  categories = [],
  siteSettings,
  pageHeading,
  pageDescription,
  breadcrumbLabel,
}) {
  const router = useRouter();
  const pathname = usePathname();
  const searchParams = useSearchParams();
  const initialProducts = unpackProductList(produtsCategory);
  const initialCategoryValue = normalizeParamValue(category_slug || DEFAULT_CATEGORY);

  const [productCategory, setProductCategory] = useState(initialProducts);
  const [isLoading, setIsLoading] = useState(!initialProducts.length);
  const [loadError, setLoadError] = useState("");
  const [sortOption, setSortOption] = useState(
    sortOptions.some((option) => option.value === searchParams.get("sort"))
      ? searchParams.get("sort")
      : DEFAULT_SORT
  );
  const [pageSize, setPageSize] = useState(
    [12, 24, 36, 48].includes(Number(searchParams.get("show")))
      ? Number(searchParams.get("show"))
      : DEFAULT_PAGE_SIZE
  );
  const [currentPage, setCurrentPage] = useState(Math.max(1, Number(searchParams.get("page")) || 1));
  const [activeCategory, setActiveCategory] = useState(
    normalizeParamValue(searchParams.get("category") || initialCategoryValue || DEFAULT_CATEGORY)
  );
  const [selectedPrices, setSelectedPrices] = useState(() => parseListParam(searchParams, "price"));
  const [selectedCollections, setSelectedCollections] = useState(() => parseListParam(searchParams, "collection"));
  const [selectedColor, setSelectedColor] = useState(normalizeParamValue(searchParams.get("color") || ""));
  const [selectedTypes, setSelectedTypes] = useState(() => parseListParam(searchParams, "type"));
  const [selectedAvailability, setSelectedAvailability] = useState(() => parseListParam(searchParams, "availability"));
  const [isFilterOpen, setIsFilterOpen] = useState(false);
  const hasMountedFiltersRef = useRef(false);

  const categoryFilterOptions = useMemo(
    () => buildCategoryOptions(categories),
    [categories]
  );
  const computedCategoryTitle = formatCategoryTitle(
    activeCategory || category_slug || DEFAULT_CATEGORY,
    categoryFilterOptions
  );
  const isInitialCategoryView = activeCategory === initialCategoryValue;
  const pageTitle = pageHeading && isInitialCategoryView ? pageHeading : computedCategoryTitle;
  const pageCopy =
    pageDescription && isInitialCategoryView
      ? pageDescription
      : "Explore our wide range of fresh flowers for every occasion and ritual.";
  const finalBreadcrumbLabel =
    breadcrumbLabel && isInitialCategoryView ? breadcrumbLabel : pageTitle || "All Flowers";
  const whatsappHref = useMemo(() => {
    const settings = siteSettings?.data || siteSettings || {};
    const baseHref = normalizeWhatsApp(settings?.SITE_WHATSAPP || settings?.SITE_PHONE);
    return appendWhatsAppMessage(baseHref, REQUEST_FLOWER_MESSAGE);
  }, [siteSettings]);

  const fetchData = useCallback(async () => {
    setIsLoading(true);
    setLoadError("");

    try {
      const products = await fetchCategoryProducts(category_slug || DEFAULT_CATEGORY, userToken);

      if (products.length) {
        setProductCategory(products);
      } else {
        setProductCategory((currentProducts) => (currentProducts.length ? currentProducts : []));
      }
    } catch (error) {
      console.error("Unable to fetch category products:", error);
      setLoadError("Unable to load flowers right now.");
    } finally {
      setIsLoading(false);
    }
  }, [category_slug, setIsLoading, setLoadError, setProductCategory, userToken]);

  useEffect(() => {
    fetchData();
  }, [fetchData]);

  useEffect(() => {
    if (!hasMountedFiltersRef.current) {
      hasMountedFiltersRef.current = true;
      return;
    }

    setCurrentPage(1);
  }, [activeCategory, selectedPrices, selectedCollections, selectedColor, selectedTypes, selectedAvailability, sortOption, pageSize]);

  useEffect(() => {
    const nextParams = new URLSearchParams();

    if (activeCategory && activeCategory !== DEFAULT_CATEGORY && activeCategory !== initialCategoryValue) {
      nextParams.set("category", activeCategory);
    }

    if (selectedCollections.length) nextParams.set("collection", selectedCollections.join(","));
    if (selectedPrices.length) nextParams.set("price", selectedPrices.join(","));
    if (selectedColor) nextParams.set("color", selectedColor);
    if (selectedTypes.length) nextParams.set("type", selectedTypes.join(","));
    if (selectedAvailability.length) nextParams.set("availability", selectedAvailability.join(","));
    if (sortOption !== DEFAULT_SORT) nextParams.set("sort", sortOption);
    if (pageSize !== DEFAULT_PAGE_SIZE) nextParams.set("show", String(pageSize));
    if (currentPage > 1) nextParams.set("page", String(currentPage));

    const nextQuery = nextParams.toString();
    const nextUrl = nextQuery ? `${pathname}?${nextQuery}` : pathname;
    const currentQuery = searchParams.toString();
    const currentUrl = currentQuery ? `${pathname}?${currentQuery}` : pathname;

    if (nextUrl !== currentUrl) {
      router.replace(nextUrl, { scroll: false });
    }
  }, [
    activeCategory,
    currentPage,
    initialCategoryValue,
    pageSize,
    pathname,
    router,
    searchParams,
    selectedAvailability,
    selectedCollections,
    selectedColor,
    selectedPrices,
    selectedTypes,
    sortOption,
  ]);

  const toggleListValue = (setter) => (value) => {
    setter((currentValues) =>
      currentValues.includes(value)
        ? currentValues.filter((currentValue) => currentValue !== value)
        : [...currentValues, value]
    );
  };

  const clearFilters = () => {
    setActiveCategory(DEFAULT_CATEGORY);
    setSelectedPrices([]);
    setSelectedCollections([]);
    setSelectedColor("");
    setSelectedTypes([]);
    setSelectedAvailability([]);
    setSortOption(DEFAULT_SORT);
    setPageSize(DEFAULT_PAGE_SIZE);
    setCurrentPage(1);
  };

  const filteredProducts = useMemo(() => {
    const activeCategoryOption = categoryFilterOptions.find((category) => category.value === activeCategory);
    const selectedPriceOptions = priceOptions.filter((price) => selectedPrices.includes(price.value));
    const selectedCollectionOptions = collectionOptions.filter((collection) => selectedCollections.includes(collection.value));
    const selectedTypeOptions = flowerTypeOptions.filter((type) => selectedTypes.includes(type.value));
    const selectedAvailabilityOptions = availabilityOptions.filter((availability) =>
      selectedAvailability.includes(availability.value)
    );

    return productCategory.filter((product) => {
      const searchableText = getSearchableProductText(product);
      const price = getProductPrice(product);

      const matchesCategory =
        activeCategory === DEFAULT_CATEGORY
          ? true
          : activeCategoryOption
          ? matchesLogicalOption(product, activeCategoryOption)
          : false;
      const matchesPrice =
        !selectedPriceOptions.length ||
        selectedPriceOptions.some((priceOption) => price >= priceOption.min && price <= priceOption.max);
      const matchesCollection =
        !selectedCollectionOptions.length ||
        selectedCollectionOptions.some((collection) => matchesLogicalOption(product, collection));
      const matchesColor =
        !selectedColor ||
        searchableText.includes(selectedColor) ||
        normalizeParamValue(product?.color) === selectedColor;
      const matchesType =
        !selectedTypeOptions.length ||
        selectedTypeOptions.some((type) => matchesKeywords(product, type.keywords));
      const productAvailability = getAvailabilityValue(product);
      const matchesAvailability =
        !selectedAvailabilityOptions.length ||
        selectedAvailabilityOptions.some(
          (availability) =>
            productAvailability === availability.value ||
            matchesLogicalOption(product, availability)
        );

      return (
        matchesCategory &&
        matchesPrice &&
        matchesCollection &&
        matchesColor &&
        matchesType &&
        matchesAvailability
      );
    });
  }, [
    activeCategory,
    categoryFilterOptions,
    productCategory,
    selectedAvailability,
    selectedCollections,
    selectedColor,
    selectedPrices,
    selectedTypes,
  ]);

  const sortedProducts = useMemo(() => {
    const products = [...filteredProducts];

    if (sortOption === "newest" || sortOption === "recently-added") {
      products.sort((a, b) => getDateValue(b) - getDateValue(a));
    } else if (sortOption === "price-low") {
      products.sort((a, b) => getProductPrice(a) - getProductPrice(b));
    } else if (sortOption === "price-high") {
      products.sort((a, b) => getProductPrice(b) - getProductPrice(a));
    } else if (sortOption === "best-selling") {
      products.sort((a, b) => getSalesValue(b) - getSalesValue(a));
    }

    return products;
  }, [filteredProducts, sortOption]);

  const totalPages = Math.max(1, Math.ceil(sortedProducts.length / pageSize));
  const safeCurrentPage = Math.min(currentPage, totalPages);
  const paginatedProducts = sortedProducts.slice(
    (safeCurrentPage - 1) * pageSize,
    safeCurrentPage * pageSize
  );

  const renderFilters = (
    <>
      <div className={styles.filterBlock}>
        <h2>Categories</h2>
        <div className={styles.categoryList}>
          {categoryFilterOptions.map((category) => (
            <button
              type="button"
              key={category.value}
              className={activeCategory === category.value ? styles.activeCategory : ""}
              onClick={() => setActiveCategory(category.value)}
            >
              {category.label}
            </button>
          ))}
        </div>
      </div>

      <div className={styles.filterBlock}>
        <h2>Filter By</h2>

        <div className={styles.filterGroup}>
          <h3>Price</h3>
          <FilterCheckboxGroup
            options={priceOptions}
            selectedValues={selectedPrices}
            onToggle={toggleListValue(setSelectedPrices)}
          />
        </div>

        <div className={styles.filterGroup}>
          <h3>Collection</h3>
          <FilterCheckboxGroup
            options={collectionOptions}
            selectedValues={selectedCollections}
            onToggle={toggleListValue(setSelectedCollections)}
          />
        </div>

        <div className={styles.filterGroup}>
          <h3>Color</h3>
          <div className={styles.colorSwatches}>
            {colorOptions.map((color) => (
              <button
                type="button"
                key={color.value}
                aria-label={color.label}
                title={color.label}
                className={selectedColor === color.value ? styles.activeColor : ""}
                style={{ "--swatch-color": color.color }}
                onClick={() =>
                  setSelectedColor((currentColor) =>
                    currentColor === color.value ? "" : color.value
                  )
                }
              />
            ))}
          </div>
        </div>

        <div className={styles.filterGroup}>
          <h3>Flower Type</h3>
          <FilterCheckboxGroup
            options={flowerTypeOptions}
            selectedValues={selectedTypes}
            onToggle={toggleListValue(setSelectedTypes)}
          />
        </div>

        <div className={styles.filterGroup}>
          <h3>Availability</h3>
          <FilterCheckboxGroup
            options={availabilityOptions}
            selectedValues={selectedAvailability}
            onToggle={toggleListValue(setSelectedAvailability)}
          />
        </div>

        <button type="button" className={styles.clearButton} onClick={clearFilters}>
          CLEAR FILTERS
        </button>
      </div>
    </>
  );

  return (
    <>
      <section className={styles.categoryPage}>
        <div className="container">
          <div className={styles.breadcrumbs}>
            <Link href="/">Home</Link>
            <span>›</span>
            <Link href="/flowers">Flowers</Link>
            <span>›</span>
            <strong>{finalBreadcrumbLabel}</strong>
          </div>

          <header className={styles.pageHeader}>
            <div>
              <h1>{pageTitle || "All Flowers"}</h1>
              <p>{pageCopy}</p>
            </div>
          </header>

          <div className={styles.mobileToolbar}>
            <button type="button" className={styles.mobileFilterButton} onClick={() => setIsFilterOpen(true)}>
              <FaSlidersH />
              Filters
            </button>
            <label className={styles.mobileSortSelect}>
              <span>Sort</span>
              <select value={sortOption} onChange={(event) => setSortOption(event.target.value)}>
                {sortOptions.map((option) => (
                  <option value={option.value} key={option.value}>
                    {option.label}
                  </option>
                ))}
              </select>
            </label>
          </div>

          {isFilterOpen && (
            <button
              type="button"
              className={styles.filterOverlay}
              aria-label="Close filters"
              onClick={() => setIsFilterOpen(false)}
            />
          )}

          <div className={styles.categoryLayout}>
            <aside
              className={`${styles.filterSidebar} ${isFilterOpen ? styles.filterSidebarOpen : ""}`}
              aria-label="Flower filters"
            >
              <div className={styles.mobileFilterHeader}>
                <span>Filters</span>
                <button type="button" aria-label="Close filters" onClick={() => setIsFilterOpen(false)}>
                  <FaTimes />
                </button>
              </div>

              {renderFilters}

              <div className={styles.mobileFilterActions}>
                <button type="button" onClick={clearFilters}>
                  Clear
                </button>
                <button type="button" onClick={() => setIsFilterOpen(false)}>
                  Apply Filters
                </button>
              </div>
            </aside>

            <main className={styles.productsPanel}>
              <div className={styles.toolbar}>
                <span>{sortedProducts.length} flowers found</span>
                <div className={styles.toolbarControls}>
                  <label>
                    <span>Sort By</span>
                    <select value={sortOption} onChange={(event) => setSortOption(event.target.value)}>
                      {sortOptions.map((option) => (
                        <option value={option.value} key={option.value}>
                          {option.label}
                        </option>
                      ))}
                    </select>
                  </label>

                  <label>
                    <span>Show</span>
                    <select value={pageSize} onChange={(event) => setPageSize(Number(event.target.value))}>
                      {[12, 24, 36, 48].map((size) => (
                        <option value={size} key={size}>
                          {size}
                        </option>
                      ))}
                    </select>
                  </label>
                </div>
              </div>

              {isLoading && !productCategory.length ? (
                <SkeletonGrid count={pageSize} />
              ) : loadError && !productCategory.length ? (
                <div className={styles.errorState}>
                  <h2>Unable to load flowers right now.</h2>
                  <button type="button" onClick={fetchData}>
                    TRY AGAIN
                  </button>
                </div>
              ) : paginatedProducts.length > 0 ? (
                <div className={styles.productGrid}>
                  {paginatedProducts.map((product, index) => (
                    <CategoryProductCard
                      key={product?.id || product?.product_id || product?.slug || index}
                      product={product}
                      userToken={userToken}
                    />
                  ))}
                </div>
              ) : (
                <div className={styles.emptyState}>
                  <h2>No flowers found</h2>
                  <p>Try changing your filters or request the flower you&apos;re looking for.</p>
                  <div className={styles.emptyActions}>
                    <button type="button" onClick={clearFilters}>
                      CLEAR FILTERS
                    </button>
                    <Link
                      href={whatsappHref}
                      target={whatsappHref.startsWith("http") ? "_blank" : undefined}
                      rel={whatsappHref.startsWith("http") ? "noopener noreferrer" : undefined}
                    >
                      REQUEST A FLOWER
                    </Link>
                  </div>
                </div>
              )}

              <div className={styles.pagination} aria-label="Product pagination">
                <button
                  type="button"
                  disabled={safeCurrentPage === 1}
                  onClick={() => setCurrentPage((page) => Math.max(1, page - 1))}
                >
                  ←
                </button>
                {Array.from({ length: totalPages }, (_, index) => index + 1)
                  .slice(Math.max(0, safeCurrentPage - 3), Math.max(4, safeCurrentPage + 1))
                  .map((page) => (
                    <button
                      type="button"
                      key={page}
                      className={page === safeCurrentPage ? styles.activePage : ""}
                      onClick={() => setCurrentPage(page)}
                    >
                      {page}
                    </button>
                  ))}
                <button
                  type="button"
                  disabled={safeCurrentPage === totalPages}
                  onClick={() => setCurrentPage((page) => Math.min(totalPages, page + 1))}
                >
                  Next
                </button>
                <button
                  type="button"
                  disabled={safeCurrentPage === totalPages}
                  onClick={() => setCurrentPage((page) => Math.min(totalPages, page + 1))}
                >
                  →
                </button>
              </div>
            </main>
          </div>

          <section className={styles.requestCta}>
            <div>
              <p>Can&apos;t find the flower you&apos;re looking for?</p>
              <h2>Request a Flower</h2>
              <span>Share the flower name or photo. We&apos;ll try to source it for you.</span>
            </div>
            <Link
              href={whatsappHref}
              target={whatsappHref.startsWith("http") ? "_blank" : undefined}
              rel={whatsappHref.startsWith("http") ? "noopener noreferrer" : undefined}
              className={styles.requestButton}
            >
              REQUEST NOW
            </Link>
            <Image
              src="/assets/images/home-v2/cta-basket-flowers.png"
              alt="Fresh flower basket"
              width={260}
              height={140}
              className={styles.requestImage}
            />
          </section>
        </div>
      </section>
      <Toast />
    </>
  );
}
