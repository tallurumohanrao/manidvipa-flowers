import HomePage from "@/components/pages/HomePage";
import { cookies } from "next/headers";
import React from "react";
import {
  fetchListingData,
  fetchSiteSettingsData,
} from "../../../hook/userCookie";
import {
  buildFaqPageSchema,
  jsonLdScriptContent,
  unpackPaginatedProducts,
} from "@/lib/seo";
import { buildMetadataWithAdminSeo } from "@/lib/metadata";

const fallbackStorefrontFaqs = [
  {
    question: "Do you deliver fresh flowers in Hyderabad?",
    answer:
      "Yes. Manidvipa Flowers delivers fresh flowers, puja flowers, garlands and selected flower arrangements across serviceable Hyderabad locations.",
  },
  {
    question: "Can I order flowers for daily puja?",
    answer:
      "Yes. You can order daily puja flowers and choose available quantities from the product page or subscription options.",
  },
  {
    question: "How do I confirm flower availability?",
    answer:
      "Flower availability changes daily. Product pages show current options, and you can contact Manidvipa Flowers for special or bulk requirements.",
  },
  {
    question: "Can I request bulk flowers or decoration support?",
    answer:
      "Yes. For bulk flower orders, temple needs, events and decorations, contact Manidvipa Flowers with your date, quantity and delivery location.",
  },
];

export async function generateMetadata() {
  return buildMetadataWithAdminSeo({
    title: "Fresh Flowers Online in Hyderabad | Manidvipa Flowers",
    description:
      "Order fresh puja flowers, garlands, premium flowers, rare flowers and flower subscriptions in Hyderabad with Manidvipa Flowers.",
    path: "/",
    image: "/assets/images/home-v2/hero-flowers.jpg",
  });
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

async function fetchFaqsData(userToken) {
  for (let attempt = 0; attempt < 3; attempt += 1) {
    const faqsData = await fetchListingData("GET", "faqs", userToken);

    if (Array.isArray(faqsData?.data) && faqsData.data.length) {
      return faqsData;
    }

    if (attempt < 2) {
      await wait(150);
    }
  }

  try {
    const response = await fetch(`${getApiBaseUrl()}/faqs`, {
      method: "GET",
      headers: {
        "Content-Type": "application/json",
      },
      next: { revalidate: 60 },
    });

    if (response.ok) {
      return response.json();
    }
  } catch (error) {
    console.error("Error fetching FAQ fallback:", error);
  }

  return null;
}

export default async function Page() {
  const cookieStore = await cookies();
  const userSessionCookie = cookieStore.get("userSession");

  let userToken = null;
  if (userSessionCookie) {
    try {
      const userSession = JSON.parse(userSessionCookie.value);
      userToken = userSession?.token;
    } catch (error) {
      console.error("Failed to parse userSession cookie:", error);
    }
  }

  const [
    bannersData,
    categoriesData,
    homeProductsData,
    premiumProductsData,
    rareProductsData,
    subscriptionPlansData,
    faqsData,
    siteSettings,
  ] = await Promise.all([
      fetchListingData("GET", "banners?page=home", userToken),
      fetchListingData("GET", "categories?scope=home", userToken),
      fetchListingData("GET", "home-featured-products", userToken),
      fetchListingData(
        "GET",
        "products-by-category?category_slug=premium-flowers&per_page=5",
        userToken
      ),
      fetchListingData(
        "GET",
        "products-by-category?category_slug=rare-flowers&per_page=6",
        userToken
      ),
      fetchListingData("GET", "subscription-plans?featured=1", userToken),
      fetchFaqsData(userToken),
      fetchSiteSettingsData(userToken),
    ]);
  const faqs = Array.isArray(faqsData?.data)
    ? faqsData.data
    : fallbackStorefrontFaqs;
  const faqSchema = buildFaqPageSchema(faqs, "/");

  return (
    <>
      {faqSchema ? (
        <script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: jsonLdScriptContent(faqSchema) }}
        />
      ) : null}
      <HomePage
        userToken={userToken}
        homeBanners={bannersData?.data || []}
        categories={categoriesData?.data || []}
        initialHomeProducts={homeProductsData?.data || []}
        initialPremiumProducts={unpackPaginatedProducts(premiumProductsData)}
        initialRareProducts={unpackPaginatedProducts(rareProductsData)}
        initialSubscriptionPlans={subscriptionPlansData?.data || []}
        faqs={faqs}
        siteSettings={siteSettings}
      />
    </>
  );
}
