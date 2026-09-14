export const SITE_NAME = "Manidvipa Flowers";
export const SITE_URL = cleanUrl(
  process.env.NEXT_PUBLIC_SITE_URL ||
    process.env.FRONTEND_URL ||
    "https://manidvipaflowers.com"
);
export const DEFAULT_SEO_DESCRIPTION =
  "Order fresh flowers, puja flowers, garlands, premium blooms and flower subscriptions in Hyderabad from Manidvipa Flowers.";
export const DEFAULT_OG_IMAGE = "/assets/images/home-v2/hero-flowers.jpg";
export const PRODUCT_IMAGE_BASE_URL = cleanUrl(
  process.env.NEXT_PUBLIC_IMG_URL ||
    `${cleanUrl(process.env.NEXT_PUBLIC_ADMIN_URL || "https://admin.manidvipastore.com")}/storage/products`
);

export const PUBLIC_SITEMAP_ROUTES = [
  { path: "/", changeFrequency: "daily", priority: 1 },
  { path: "/flowers", changeFrequency: "daily", priority: 0.95 },
  { path: "/puja-flowers", changeFrequency: "daily", priority: 0.95 },
  { path: "/subscriptions", changeFrequency: "weekly", priority: 0.9 },
  { path: "/premium-flowers", changeFrequency: "daily", priority: 0.9 },
  { path: "/rare-flowers", changeFrequency: "daily", priority: 0.9 },
  { path: "/garlands", changeFrequency: "daily", priority: 0.85 },
  { path: "/decorations", changeFrequency: "weekly", priority: 0.85 },
  { path: "/gifts", changeFrequency: "daily", priority: 0.85 },
  { path: "/offers", changeFrequency: "daily", priority: 0.8 },
  { path: "/about", changeFrequency: "monthly", priority: 0.6 },
  { path: "/contact-us", changeFrequency: "monthly", priority: 0.7 },
  { path: "/testimonials", changeFrequency: "monthly", priority: 0.5 },
  { path: "/privacy-policy", changeFrequency: "yearly", priority: 0.3 },
  { path: "/terms-conditions", changeFrequency: "yearly", priority: 0.3 },
  { path: "/refund-cancellation", changeFrequency: "yearly", priority: 0.3 },
];

const NOINDEX_PREFIXES = [
  "/admin",
  "/cart",
  "/checkout",
  "/login",
  "/register",
  "/my-account",
  "/address",
  "/orderDetails",
  "/order-details",
  "/forgot-password",
  "/password-reset",
  "/thank-you",
  "/watchlist",
  "/search",
];

const indexRobots = {
  index: true,
  follow: true,
  googleBot: {
    index: true,
    follow: true,
    "max-image-preview": "large",
    "max-snippet": -1,
    "max-video-preview": -1,
  },
};

export const noIndexRobots = {
  index: false,
  follow: false,
  googleBot: {
    index: false,
    follow: false,
  },
};

function cleanUrl(value) {
  return String(value || "")
    .trim()
    .replace(/\/+$/, "");
}

export function normalizePath(path) {
  const rawPath = String(path || "/")
    .split("?")[0]
    .split("#")[0]
    .trim();
  const withSlash = rawPath.startsWith("/") ? rawPath : `/${rawPath}`;
  const normalized = withSlash.replace(/\/{2,}/g, "/").replace(/\/$/, "");
  return normalized || "/";
}

export function canonicalUrl(path = "/") {
  return new URL(normalizePath(path), `${SITE_URL}/`).toString();
}

export function absoluteUrl(value, fallback = SITE_URL) {
  if (!value) return fallback;
  const src = String(value).trim();
  if (/^https?:\/\//i.test(src)) return src;
  if (src.startsWith("//")) return `https:${src}`;
  return new URL(src, `${SITE_URL}/`).toString();
}

export function isNoIndexPath(path) {
  const normalized = normalizePath(path);
  return NOINDEX_PREFIXES.some(
    (prefix) => normalized === prefix || normalized.startsWith(`${prefix}/`)
  );
}

export function titleFromSlug(value, fallback = "Fresh Flowers") {
  return String(value || fallback)
    .split("/")
    .pop()
    .split("-")
    .filter(Boolean)
    .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
    .join(" ");
}

export function stripHtml(value) {
  return String(value || "")
    .replace(/<script[\s\S]*?<\/script>/gi, " ")
    .replace(/<style[\s\S]*?<\/style>/gi, " ")
    .replace(/<[^>]*>/g, " ")
    .replace(/&nbsp;/gi, " ")
    .replace(/&amp;/gi, "&")
    .replace(/\s+/g, " ")
    .trim();
}

export function truncateText(value, maxLength = 155) {
  const text = stripHtml(value);
  if (text.length <= maxLength) return text;
  return `${text.slice(0, maxLength - 1).trim()}…`;
}

export function parsePrice(value) {
  const price = Number(String(value || "").replace(/[^\d.]/g, ""));
  return Number.isFinite(price) ? price : 0;
}

export function robotsFromValue(value) {
  if (!value) return indexRobots;
  if (typeof value === "object") return value;

  const robots = String(value).toLowerCase();
  if (robots.includes("noindex")) return noIndexRobots;

  return {
    ...indexRobots,
    follow: !robots.includes("nofollow"),
    googleBot: {
      ...indexRobots.googleBot,
      follow: !robots.includes("nofollow"),
    },
  };
}

export function buildMetadata({
  title,
  description,
  path = "/",
  image = DEFAULT_OG_IMAGE,
  type = "website",
  robots,
  keywords,
} = {}) {
  const finalTitle = title || `${SITE_NAME} | Fresh Flowers Online in Hyderabad`;
  const finalDescription = truncateText(description || DEFAULT_SEO_DESCRIPTION);
  const finalPath = normalizePath(path);
  const imageUrl = absoluteUrl(image || DEFAULT_OG_IMAGE);

  return {
    metadataBase: new URL(SITE_URL),
    applicationName: SITE_NAME,
    title: finalTitle,
    description: finalDescription,
    keywords,
    authors: [{ name: SITE_NAME }],
    creator: SITE_NAME,
    publisher: SITE_NAME,
    robots: robotsFromValue(robots),
    alternates: {
      canonical: finalPath,
    },
    icons: {
      icon: "/favicon.ico",
      shortcut: "/favicon.ico",
      apple: "/favicon.ico",
    },
    openGraph: {
      title: finalTitle,
      description: finalDescription,
      url: canonicalUrl(finalPath),
      siteName: SITE_NAME,
      locale: "en_IN",
      type,
      images: [
        {
          url: imageUrl,
          width: 1200,
          height: 630,
          alt: finalTitle,
        },
      ],
    },
    twitter: {
      card: "summary_large_image",
      title: finalTitle,
      description: finalDescription,
      images: [imageUrl],
    },
  };
}

export function buildNoIndexMetadata(path = "/", title = SITE_NAME) {
  return buildMetadata({
    title,
    description: "This page is not intended for search indexing.",
    path,
    robots: noIndexRobots,
  });
}

export function resolveProductImageUrl(imageName) {
  if (!imageName) return absoluteUrl("/assets/images/no-image.png");
  const src = String(imageName).trim();
  if (/^https?:\/\//i.test(src) || src.startsWith("/")) return absoluteUrl(src);
  return `${PRODUCT_IMAGE_BASE_URL}/${src.replace(/^\/+/, "")}`;
}

function getLowestWeight(weights = []) {
  if (!Array.isArray(weights)) return null;
  return weights
    .map((weight) => ({
      ...weight,
      sellPrice: parsePrice(weight?.sell_price),
      listPrice: parsePrice(weight?.list_price),
    }))
    .filter((weight) => weight.sellPrice > 0)
    .sort((a, b) => a.sellPrice - b.sellPrice)[0] || null;
}

export function buildProductMetadata(productDetails, slug, seoData = null) {
  const product = productDetails?.data;
  const path = `/flowers/${slug}`;

  if (!product) {
    return buildMetadata(applyDirectSeoMetadata({
      title: `${titleFromSlug(slug)} | ${SITE_NAME}`,
      description: "This product is not currently available.",
      path,
      robots: noIndexRobots,
    }, seoData, { preserveRobots: true }));
  }

  const fallbackTitle = `${product.title || titleFromSlug(slug)} Online in Hyderabad | ${SITE_NAME}`;
  const fallbackDescription =
    truncateText(product.description) ||
    `Order ${product.title || titleFromSlug(slug)} fresh flowers online in Hyderabad from ${SITE_NAME}.`;
  const image = resolveProductImageUrl(productDetails?.images?.[0]?.name);

  return buildMetadata(applyDirectSeoMetadata({
    title: fallbackTitle,
    description: fallbackDescription,
    path,
    image,
    type: "website",
    keywords: [
      product.title,
      "fresh flowers Hyderabad",
      "puja flowers Hyderabad",
      "Manidvipa Flowers",
    ]
      .filter(Boolean)
      .join(", "),
  }, seoData));
}

export function findCategoryBySlug(categories = [], slug) {
  const normalizedSlug = normalizeCategorySlug(slug);
  return categories.find((category) => {
    const values = [
      category?.slug,
      category?.route_slug,
      category?.title,
      category?.name,
    ].map(normalizeCategorySlug);
    return values.includes(normalizedSlug);
  });
}

export function normalizeCategorySlug(value) {
  return String(value || "")
    .toLowerCase()
    .trim()
    .replace(/&/g, " and ")
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-+|-+$/g, "");
}

export function buildCategoryMetadata(slug, category, seoData = null, categoryPath = null) {
  const normalizedSlug = normalizeCategorySlug(slug || "all-flowers");
  const title =
    category?.title ||
    (["all", "all-flowers", "flowers"].includes(normalizedSlug)
      ? "All Flowers"
      : titleFromSlug(normalizedSlug));
  const description =
    category?.short_description ||
    `Shop ${title.toLowerCase()} online in Hyderabad for puja, home, temple, decorations and gifting.`;
  const image = category?.image_url || DEFAULT_OG_IMAGE;

  return buildMetadata(applyDirectSeoMetadata({
    title: `${title} Online in Hyderabad | ${SITE_NAME}`,
    description,
    path: categoryPath || `/${normalizedSlug || "all-flowers"}`,
    image,
    keywords: `${title}, fresh flowers Hyderabad, puja flowers, ${SITE_NAME}`,
  }, seoData));
}

function applyDirectSeoMetadata(options, seoData, { preserveRobots = false } = {}) {
  if (!seoData) return options;

  return {
    ...options,
    title: seoData.page_title || options.title,
    description: seoData.meta_description || options.description,
    keywords: seoData.meta_keywords || options.keywords,
    robots: preserveRobots ? options.robots : seoData.robots || options.robots,
  };
}

export function jsonLdScriptContent(schema) {
  return JSON.stringify(schema).replace(/</g, "\\u003c");
}

export function parseAdminSchemaMarkup(schemaMarkup) {
  if (!schemaMarkup || typeof schemaMarkup !== "string") return [];

  let markup = schemaMarkup.trim();
  const scriptMatch = markup.match(/<script\b[^>]*>([\s\S]*?)<\/script>/i);

  if (scriptMatch?.[1]) {
    markup = scriptMatch[1].trim();
  }

  if (!markup) return [];

  try {
    const parsed = JSON.parse(markup);
    const schemas = Array.isArray(parsed) ? parsed : [parsed];

    return schemas.filter(
      (schema) =>
        schema &&
        typeof schema === "object" &&
        !Array.isArray(schema) &&
        (schema["@context"] || schema["@type"] || schema["@graph"])
    );
  } catch (error) {
    console.error("Invalid admin schema markup:", error);
    return [];
  }
}

export function buildLocalBusinessSchema() {
  return {
    "@context": "https://schema.org",
    "@type": "Florist",
    "@id": `${SITE_URL}/#business`,
    name: SITE_NAME,
    url: SITE_URL,
    image: absoluteUrl(DEFAULT_OG_IMAGE),
    logo: absoluteUrl("/assets/images/manidvpa-flowers-2.png"),
    telephone: "+91 94917 47624",
    email: "support@manidvipaflowers.com",
    priceRange: "₹₹",
    address: {
      "@type": "PostalAddress",
      streetAddress: "Vengal Rao Nagar, SR Nagar",
      addressLocality: "Hyderabad",
      addressRegion: "Telangana",
      postalCode: "500038",
      addressCountry: "IN",
    },
    areaServed: [
      {
        "@type": "City",
        name: "Hyderabad",
      },
    ],
    sameAs: [
      "https://www.instagram.com/manidvipa_store/",
      "https://www.facebook.com/manidvipastore",
      "https://www.youtube.com/@ManidvipaStore",
    ],
  };
}

export function buildWebSiteSchema() {
  return {
    "@context": "https://schema.org",
    "@type": "WebSite",
    "@id": `${SITE_URL}/#website`,
    name: SITE_NAME,
    url: SITE_URL,
    potentialAction: {
      "@type": "SearchAction",
      target: `${SITE_URL}/search/{search_term_string}`,
      "query-input": "required name=search_term_string",
    },
  };
}

export function buildBreadcrumbSchema(items = []) {
  return {
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    itemListElement: items.map((item, index) => ({
      "@type": "ListItem",
      position: index + 1,
      name: item.name,
      item: absoluteUrl(item.url || item.path || "/"),
    })),
  };
}

export function buildProductSchema(productDetails, slug) {
  const product = productDetails?.data;
  if (!product) return null;

  const lowestWeight = getLowestWeight(productDetails?.weights);
  const price = lowestWeight?.sellPrice || parsePrice(product.sell_price);
  const image = resolveProductImageUrl(productDetails?.images?.[0]?.name);
  const weights = Array.isArray(productDetails?.weights) ? productDetails.weights : [];
  const hasTrackedWeights = weights.some((weight) => Number(weight?.stock) === 1);
  const hasAvailableWeight = weights.some((weight) => {
    if (Number(weight?.stock) !== 1) return false;
    return parsePrice(weight?.qty) > 0;
  });
  const availability = hasTrackedWeights && !hasAvailableWeight
    ? "https://schema.org/OutOfStock"
    : "https://schema.org/InStock";

  return {
    "@context": "https://schema.org",
    "@type": "Product",
    name: product.title || titleFromSlug(slug),
    description:
      truncateText(product.description, 300) ||
      `Fresh ${product.title || titleFromSlug(slug)} from ${SITE_NAME}.`,
    sku: product.sku || slug,
    image: [image],
    brand: {
      "@type": "Brand",
      name: SITE_NAME,
    },
    offers: price
      ? {
          "@type": "Offer",
          url: canonicalUrl(`/flowers/${slug}`),
          priceCurrency: "INR",
          price,
          availability,
          itemCondition: "https://schema.org/NewCondition",
        }
      : undefined,
  };
}

export function buildFaqPageSchema(faqs = [], path = "/") {
  const items = Array.isArray(faqs)
    ? faqs
        .map((faq) => ({
          question: stripHtml(faq?.question),
          answer: stripHtml(faq?.answer),
        }))
        .filter((faq) => faq.question && faq.answer)
        .slice(0, 12)
    : [];

  if (!items.length) return null;

  return {
    "@context": "https://schema.org",
    "@type": "FAQPage",
    "@id": `${canonicalUrl(path)}#faqs`,
    mainEntity: items.map((faq) => ({
      "@type": "Question",
      name: faq.question,
      acceptedAnswer: {
        "@type": "Answer",
        text: faq.answer,
      },
    })),
  };
}

export function buildItemListSchema(products = [], path = "/flowers") {
  const list = Array.isArray(products) ? products : [];
  return {
    "@context": "https://schema.org",
    "@type": "ItemList",
    itemListElement: list
      .filter((product) => product?.slug && product?.title)
      .slice(0, 24)
      .map((product, index) => ({
        "@type": "ListItem",
        position: index + 1,
        url: canonicalUrl(`/flowers/${product.slug}`),
        name: product.title,
      })),
    url: canonicalUrl(path),
  };
}

export function unpackPaginatedProducts(source) {
  if (Array.isArray(source?.data?.data)) return source.data.data;
  if (Array.isArray(source?.data)) return source.data;
  if (Array.isArray(source)) return source;
  return [];
}

