"use client";

import React, { useCallback, useEffect, useMemo, useState } from "react";
import Image from "next/image";
import Link from "next/link";
import { FaInstagram, FaShoppingBasket, FaWhatsapp } from "react-icons/fa";
import Slick from "@/components/Slick";
import styles from "@/scss/pages/home.module.scss";
import { useCartCount, useToast, useUser } from "@/context/UserContext";
import {
  fetchCartBySession,
  fetchListingData,
  getCartCount,
} from "../../../hook/userCookie";

const IMG_URL = process.env.NEXT_PUBLIC_IMG_URL;

const categoryCards = [
  {
    title: "Puja Flowers",
    description: "Chamanthi, Banthi, Kanakambaram & more",
    image: "/assets/images/home-v2/fresh-arrivals/fresh-chamanthi.jpg",
    href: "/puja-flowers",
    terms: ["chamanthi", "banthi", "kanakambaram"],
  },
  {
    title: "Premium Flowers",
    description: "Roses, lilies, orchids & more",
    image: "/assets/images/home-v2/premium-collection/premium-lilies.jpg",
    href: "/premium-flowers",
    terms: ["rose", "premium"],
  },
  {
    title: "Rare Flowers",
    description: "Seasonal & exotic varieties",
    image: "/assets/images/home-v2/rare-seasonal/rare-lotus.jpg",
    href: "/rare-flowers",
    terms: ["other", "rare"],
  },
  {
    title: "Patri & Leaves",
    description: "Tulasi, Bilva, Mango leaves & more",
    image: "/assets/images/home-v2/custom-puja-flower-box.jpg",
    href: "/patri-leaves",
    terms: ["patri", "leaves", "leaf"],
  },
  {
    title: "Bouquets & Gifting",
    description: "Perfect for every occasion",
    image: "/assets/images/home-v2/cta-basket-flowers.png",
    href: "/gifts",
    terms: ["rose", "flower"],
  },
  {
    title: "Temple & Pooja",
    description: "Garlands, pooja kits & more",
    image: "/assets/images/home-v2/recent-decorations/recent-decoration-temple.jpg",
    href: "/garlands",
    terms: ["pooja", "puja", "garland"],
  },
];

const legacyCategoryBadgeImages = new Set([
  "category-daily-puja.jpg",
  "category-gifting.jpg",
  "category-patri.jpg",
  "category-premium.jpg",
  "category-rare.jpg",
  "category-temple.jpg",
]);

const homeCategoryImageBySlug = {
  "puja-flowers": "/assets/images/home-v2/fresh-arrivals/fresh-chamanthi.jpg",
  "daily-puja-flowers": "/assets/images/home-v2/fresh-arrivals/fresh-chamanthi.jpg",
  "premium-flowers": "/assets/images/home-v2/premium-collection/premium-lilies.jpg",
  "primimum-flowers": "/assets/images/home-v2/premium-collection/premium-lilies.jpg",
  "rare-flowers": "/assets/images/home-v2/rare-seasonal/rare-lotus.jpg",
  "patri-leaves": "/assets/images/home-v2/custom-puja-flower-box.jpg",
  "patri-and-leaves": "/assets/images/home-v2/custom-puja-flower-box.jpg",
  "bouquets-gifting": "/assets/images/home-v2/cta-basket-flowers.png",
  gifts: "/assets/images/home-v2/cta-basket-flowers.png",
  garlands: "/assets/images/home-v2/recent-decorations/recent-decoration-temple.jpg",
  "temple-pooja": "/assets/images/home-v2/recent-decorations/recent-decoration-temple.jpg",
  "temple-pooja-flowers": "/assets/images/home-v2/recent-decorations/recent-decoration-temple.jpg",
};

const benefits = [
  ["/assets/images/free-shipping.png", "Fresh Every Morning", "Sourced and prepared daily"],
  ["/assets/images/free-shipping.png", "Same-Day Delivery", "Fast delivery across Hyderabad"],
  ["/assets/images/kumbham-img-1.png", "Puja Ready", "Flowers and leaves ready to use"],
  ["/assets/images/online-support.png", "Easy Subscriptions", "Daily, weekly or monthly plans"],
  ["/assets/icons/whatsapp.png", "WhatsApp Ordering", "Quick support and easy ordering"],
  ["/assets/images/security.png", "Secure Payments", "Safe and secure payments"],
];

const fallbackSubscriptionPlans = [
  {
    title: "Corporate Premium Arrangement",
    description: "1 reception + 2 desk/lounge arrangements.",
    price: "\u20B914,999",
    cadence: "/ month",
    image: "/assets/images/home-v2/premium-collection/premium-lilies.jpg",
  },
  {
    title: "Elite Imported Arrangement",
    description: "Lobby, boardroom and cabin arrangements.",
    price: "\u20B925,000",
    cadence: "/ month",
    image: "/assets/images/home-v2/premium-collection/premium-orchids.jpg",
  },
  {
    title: "Hospital Floral Service",
    description: "Reception arrangement + optional puja flowers.",
    price: "\u20B99,999",
    cadence: "/ month",
    image: "/assets/images/home-v2/premium-collection/premium-roses.jpg",
  },
  {
    title: "Temple Loose Flower Plan",
    description: "Starts with 500g loose flowers per delivery.",
    price: "\u20B91,199",
    cadence: "/ month",
    image: "/assets/images/home-v2/category-temple.jpg",
  },
  {
    title: "Custom Enterprise Plan",
    description: "Premium arrangements, loose flowers or bulk supply.",
    price: "Custom Quote",
    cadence: "",
    image: "/assets/images/home-v2/puja-box.jpg",
  },
];

const subscriptionPlanFallbackImages = {
  "corporate-office-flower-subscription": "/assets/images/home-v2/premium-collection/premium-roses.jpg",
  "corporate-premium-flower-arrangement-plan": "/assets/images/home-v2/premium-collection/premium-lilies.jpg",
  "elite-imported-flower-arrangement-plan": "/assets/images/home-v2/premium-collection/premium-exotic.jpg",
  "hospital-fresh-flowers-supply": "/assets/images/home-v2/premium-collection/premium-orchids.jpg",
  "hotel-lobby-flower-plan": "/assets/images/home-v2/premium-collection/premium-exotic.jpg",
  "temple-daily-flower-subscription": "/assets/images/home-v2/custom-puja-flower-box.jpg",
  "business-bulk-flower-plan": "/assets/images/home-v2/premium-collection/premium-tulips.jpg",
};

function getSubscriptionPlanFallbackImage(plan, index) {
  const slug = String(plan?.slug || "").toLowerCase();
  const businessType = String(plan?.business_type || "").toLowerCase();
  const subscriptionType = String(plan?.subscription_type || "").toLowerCase();
  const flowerGrade = String(plan?.flower_grade || "").toLowerCase();

  if (subscriptionPlanFallbackImages[slug]) {
    return subscriptionPlanFallbackImages[slug];
  }

  if (subscriptionType.includes("loose") || businessType.includes("temple")) {
    return "/assets/images/home-v2/custom-puja-flower-box.jpg";
  }

  if (businessType.includes("hotel") || flowerGrade.includes("exotic") || flowerGrade.includes("imported")) {
    return "/assets/images/home-v2/premium-collection/premium-exotic.jpg";
  }

  if (businessType.includes("hospital")) {
    return "/assets/images/home-v2/premium-collection/premium-orchids.jpg";
  }

  if (subscriptionType.includes("premium")) {
    return "/assets/images/home-v2/premium-collection/premium-lilies.jpg";
  }

  return fallbackSubscriptionPlans[index % fallbackSubscriptionPlans.length].image;
}

function normalizeHomeSubscriptionPlans(plans = []) {
  const source = Array.isArray(plans) && plans.length ? plans : fallbackSubscriptionPlans;

  return source.slice(0, 5).map((plan, index) => ({
    title: plan?.title || "Flower Subscription",
    description:
      plan?.included_arrangement_count ||
      plan?.included_quantity_text ||
      plan?.short_description ||
      plan?.description ||
      plan?.description_text ||
      "Fresh flowers delivered on a regular schedule.",
    price: plan?.price_label || plan?.price || "Custom Quote",
    cadence: plan?.price_suffix || plan?.cadence || "",
    image:
      plan?.image_url ||
      plan?.image ||
      getSubscriptionPlanFallbackImage(plan, index),
  }));
}

const defaultHeroSlides = [
  {
    titleLines: ["Fresh Flowers.", "Delivered With Devotion."],
    description:
      "From Daily Puja Flowers to Premium & Rare Blooms — Freshly Sourced and Delivered to Your Doorstep.",
    benefits: [
      ["/assets/images/free-shipping.png", "Fresh Every Morning", "Sourced Daily"],
      ["/assets/images/free-shipping.png", "Same-Day Delivery", "Across Hyderabad"],
      ["/assets/images/kumbham-img-1.png", "Puja Ready", "Flowers & Leaves"],
    ],
    primary: { label: "SHOP FRESH FLOWERS", href: "/flowers" },
    secondary: { label: "START A SUBSCRIPTION", href: "/subscriptions" },
    image: "/assets/images/home-v2/hero-flowers.jpg",
    alt: "Fresh puja flowers arranged in a traditional tray",
  },
  {
    titleLines: ["Daily Puja Flowers.", "Ready Every Morning."],
    description:
      "Marigold, jasmine, lotus, tulasi and leaves packed fresh for your morning rituals.",
    benefits: [
      ["/assets/images/kumbham-img-1.png", "Puja Essentials", "Flowers & Leaves"],
      ["/assets/images/free-shipping.png", "Morning Freshness", "Prepared Daily"],
      ["/assets/icons/whatsapp.png", "Quick Ordering", "WhatsApp Support"],
    ],
    primary: { label: "SHOP PUJA FLOWERS", href: "/puja-flowers" },
    secondary: { label: "ORDER ON WHATSAPP", href: "whatsapp" },
    image: "/assets/images/home-v2/hero-flowers.jpg",
    alt: "Fresh flowers and lotus for daily puja",
  },
  {
    titleLines: ["Flowers For Every", "Occasion & Ritual."],
    description:
      "Fresh flowers, garlands and decoration support for homes, temples, weddings and events.",
    benefits: [
      ["/assets/images/online-support.png", "Easy Planning", "Support Available"],
      ["/assets/images/free-shipping.png", "Local Delivery", "Across Hyderabad"],
      ["/assets/images/security.png", "Trusted Quality", "Fresh Selection"],
    ],
    primary: { label: "VIEW DECORATIONS", href: "/decorations" },
    secondary: { label: "CONTACT US", href: "/contact-us" },
    image: "/assets/images/home-v2/hero-flowers.jpg",
    alt: "Premium fresh flowers for rituals and decorations",
  },
];

const heroSliderSettings = {
  dots: true,
  arrows: false,
  infinite: true,
  autoplay: true,
  autoplaySpeed: 4500,
  speed: 650,
  slidesToShow: 1,
  slidesToScroll: 1,
  pauseOnHover: true,
  responsive: [
    {
      breakpoint: 1200,
      settings: {
        slidesToShow: 1,
        slidesToScroll: 1,
      },
    },
    {
      breakpoint: 900,
      settings: {
        slidesToShow: 1,
        slidesToScroll: 1,
      },
    },
    {
      breakpoint: 600,
      settings: {
        slidesToShow: 1,
        slidesToScroll: 1,
      },
    },
  ],
};

function cleanHeroText(value) {
  return String(value || "")
    .replace(/<[^>]*>/g, " ")
    .replace(/\s+/g, " ")
    .trim();
}

function splitHeroTitleLines(value) {
  const rawTitle = String(value || "").trim();
  if (!rawTitle) return [];

  const explicitLines = rawTitle
    .split(/\s*(?:\||<br\s*\/?>|\r?\n)\s*/i)
    .map(cleanHeroText)
    .filter(Boolean);

  if (explicitLines.length > 1) return explicitLines;

  const cleanTitle = cleanHeroText(rawTitle);
  const sentenceLines = cleanTitle
    .split(/(?<=\.)\s+/)
    .map((line) => line.trim())
    .filter(Boolean);

  return sentenceLines.length > 1 && sentenceLines.length <= 3
    ? sentenceLines
    : [cleanTitle];
}

function normalizeHeroUrl(value, fallbackHref) {
  const url = String(value || "").trim();
  if (!url) return fallbackHref;
  if (url === "whatsapp") return url;
  if (/^(https?:)?\/\//i.test(url) || /^(tel|mailto):/i.test(url)) return url;
  return url.startsWith("/") ? url : `/${url.replace(/^\/+/, "")}`;
}

function buildHeroSlides(homeBanners = []) {
  const banners = Array.isArray(homeBanners)
    ? homeBanners.filter((banner) => banner?.image_url || banner?.image || banner?.title)
    : [];

  if (!banners.length) return defaultHeroSlides;

  return banners.map((banner, index) => {
    const fallbackSlide = defaultHeroSlides[index % defaultHeroSlides.length];
    const titleLines = splitHeroTitleLines(banner.title);
    const description = cleanHeroText(banner.banner_text);
    const image = banner.image_url || banner.image || fallbackSlide.image;

    return {
      ...fallbackSlide,
      id: banner.id || `${image}-${index}`,
      titleLines: titleLines.length ? titleLines : fallbackSlide.titleLines,
      description: description || fallbackSlide.description,
      primary: {
        label: cleanHeroText(banner.button_text) || fallbackSlide.primary.label,
        href: normalizeHeroUrl(banner.url, fallbackSlide.primary.href),
      },
      secondary: fallbackSlide.secondary,
      image,
      alt: cleanHeroText(banner.alt) || cleanHeroText(banner.title) || fallbackSlide.alt,
    };
  });
}

function resolveHeroHref(href, whatsappHref) {
  return href === "whatsapp" ? whatsappHref : href || "/flowers";
}

function opensInNewTab(href) {
  return /^(https?:)?\/\//i.test(String(href || ""));
}

const occasions = [
  ["Daily Puja", "/assets/images/home-v2/fresh-arrivals/fresh-chamanthi.jpg"],
  ["Temple Offering", "/assets/images/home-v2/recent-decorations/recent-decoration-temple.jpg"],
  ["Wedding", "/assets/images/home-v2/recent-decorations/recent-decoration-wedding.jpg"],
  ["Housewarming", "/assets/images/home-v2/custom-puja-flower-box.jpg"],
  ["Birthday", "/assets/images/home-v2/cta-basket-flowers.png"],
  ["Anniversary", "/assets/images/home-v2/premium-collection/premium-roses.jpg"],
];

const decorationGallery = [
  ["Wedding Decorations", "/assets/images/home-v2/recent-decorations/recent-decoration-wedding.jpg"],
  ["Pooja Decorations", "/assets/images/home-v2/recent-decorations/recent-decoration-pooja.jpg"],
  ["Temple Decorations", "/assets/images/home-v2/recent-decorations/recent-decoration-temple.jpg"],
  ["Event Decorations", "/assets/images/home-v2/recent-decorations/recent-decoration-events.jpg"],
];

const instagramImages = [
  ...Array.from(
    { length: 9 },
    (_, index) => `/assets/images/home-v2/instagram-gallery/instagram-gallery-${index + 1}.jpg`
  ),
];

const fallbackProducts = [
  {
    title: "Red Roses",
    sell_price: "250",
    list_price: "300",
    slug: "red-roses",
    localImage: "/assets/images/home-v2/category-premium.jpg",
  },
  {
    title: "Chamanthi Flowers",
    sell_price: "120",
    list_price: "150",
    slug: "chamanthi-flowers",
    localImage: "/assets/images/home-v2/category-daily-puja.jpg",
  },
  {
    title: "Kanakambaram",
    sell_price: "250",
    list_price: "300",
    slug: "kanakambaram",
    localImage: "/assets/images/home-v2/category-temple.jpg",
  },
  {
    title: "Lotus Flowers",
    sell_price: "60",
    list_price: "80",
    slug: "lotus-flowers",
    localImage: "/assets/images/home-v2/category-rare.jpg",
    unit: "piece",
  },
  {
    title: "Banthi Flowers",
    sell_price: "120",
    list_price: "150",
    slug: "banthi-flowers",
    localImage: "/assets/images/home-v2/category-daily-puja.jpg",
  },
  {
    title: "Premium Roses",
    sell_price: "150",
    list_price: "200",
    slug: "premium-roses",
    localImage: "/assets/images/home-v2/category-premium.jpg",
    unit: "bunch",
  },
  {
    title: "Lilies",
    sell_price: "300",
    list_price: "380",
    slug: "lilies",
    localImage: "/assets/images/home-v2/category-gifting.jpg",
    unit: "bunch",
  },
  {
    title: "Orchid Flowers",
    sell_price: "450",
    list_price: "550",
    slug: "orchid-flowers",
    localImage: "/assets/images/home-v2/category-rare.jpg",
    unit: "bunch",
  },
  {
    title: "Lotus",
    sell_price: "180",
    list_price: "220",
    slug: "lotus",
    localImage: "/assets/images/home-v2/category-rare.jpg",
    unit: "bunch",
  },
  {
    title: "Dreshta Flowers",
    sell_price: "750",
    list_price: "900",
    slug: "dreshta-flowers",
    localImage: "/assets/images/home-v2/category-temple.jpg",
    unit: "bunch",
  },
  {
    title: "Imported Tulips",
    sell_price: "600",
    list_price: "750",
    slug: "imported-tulips",
    localImage: "/assets/images/home-v2/category-gifting.jpg",
    unit: "bunch",
  },
  {
    title: "Rare Jasmine",
    sell_price: "320",
    list_price: "420",
    slug: "rare-jasmine",
    localImage: "/assets/images/home-v2/category-patri.jpg",
    unit: "bunch",
  },
  {
    title: "Seasonal Marigold",
    sell_price: "140",
    list_price: "180",
    slug: "seasonal-marigold",
    localImage: "/assets/images/home-v2/category-temple.jpg",
    unit: "kg",
  },
  {
    title: "White Tuberose",
    sell_price: "280",
    list_price: "350",
    slug: "white-tuberose",
    localImage: "/assets/images/home-v2/category-patri.jpg",
    unit: "bunch",
  },
  {
    title: "Mixed Ritual Flowers",
    sell_price: "220",
    list_price: "280",
    slug: "mixed-ritual-flowers",
    localImage: "/assets/images/home-v2/hero-flowers.jpg",
    unit: "kg",
  },
  {
    title: "Decor Flower Mix",
    sell_price: "500",
    list_price: "650",
    slug: "decor-flower-mix",
    localImage: "/assets/images/home-v2/cta-flowers.jpg",
    unit: "bunch",
  },
  {
    title: "Temple Garland Flowers",
    sell_price: "350",
    list_price: "450",
    slug: "temple-garland-flowers",
    localImage: "/assets/images/home-v2/puja-box.jpg",
    unit: "bunch",
  },
];

const freshArrivalSeeds = [
  {
    title: "Red Roses",
    keywords: ["red rose", "rose", "roses"],
    slug: "button-roses",
    startingPrice: "250",
    listPrice: "300",
    image: "/assets/images/home-v2/fresh-arrivals/fresh-red-roses.jpg",
    href: "/search/rose",
    unit: "kg",
  },
  {
    title: "Chamanthi Flowers",
    keywords: ["chamanthi", "chrysanthemum"],
    slug: "yellow-chamanthi",
    startingPrice: "120",
    listPrice: "150",
    image: "/assets/images/home-v2/fresh-arrivals/fresh-chamanthi.jpg",
    href: "/search/chamanthi",
    unit: "kg",
  },
  {
    title: "Kanakambaram",
    keywords: ["kanakambaram", "crossandra"],
    slug: "kanakambaram-flowers",
    startingPrice: "250",
    listPrice: "300",
    image: "/assets/images/home-v2/fresh-arrivals/fresh-kanakambaram.jpg",
    href: "/search/kanakambaram",
    unit: "kg",
  },
  {
    title: "Lotus Flowers",
    keywords: ["lotus"],
    slug: "pink-lotas",
    startingPrice: "60",
    listPrice: "80",
    image: "/assets/images/home-v2/fresh-arrivals/fresh-lotus.jpg",
    href: "/search/lotus",
    unit: "piece",
  },
  {
    title: "Banthi Flowers",
    keywords: ["banthi", "marigold"],
    slug: "yellow-banthi",
    startingPrice: "120",
    listPrice: "150",
    image: "/assets/images/home-v2/fresh-arrivals/fresh-banthi.jpg",
    href: "/search/banthi",
    unit: "kg",
  },
  {
    title: "Yellow Sevanthi",
    keywords: ["yellow sevanthi", "sevanthi"],
    slug: "pink-chamanthi",
    startingPrice: "60",
    listPrice: "80",
    image: "/assets/images/home-v2/fresh-arrivals/fresh-yellow-sevanthi.jpg",
    href: "/search/sevanthi",
    unit: "kg",
  },
];

const premiumCollectionSeeds = [
  {
    title: "Premium Roses",
    keywords: ["premium rose", "rose", "roses"],
    startingPrice: "150",
    image: "/assets/images/home-v2/premium-collection/premium-roses.jpg",
    href: "/search/rose",
  },
  {
    title: "Tulips",
    keywords: ["tulip", "tulips", "imported tulip"],
    startingPrice: "600",
    image: "/assets/images/home-v2/premium-collection/premium-tulips.jpg",
    href: "/search/tulip",
  },
  {
    title: "Orchids",
    keywords: ["orchid", "orchids"],
    startingPrice: "450",
    image: "/assets/images/home-v2/premium-collection/premium-orchids.jpg",
    href: "/search/orchid",
  },
  {
    title: "Lilies",
    keywords: ["lily", "lilies"],
    startingPrice: "300",
    image: "/assets/images/home-v2/premium-collection/premium-lilies.jpg",
    href: "/search/lilies",
  },
  {
    title: "Imported / Exotic Flowers",
    keywords: ["imported", "exotic", "rare"],
    startingPrice: "600",
    image: "/assets/images/home-v2/premium-collection/premium-exotic.jpg",
    href: "/search/imported",
  },
];

const rareSeasonalCollectionSeeds = [
  {
    title: "Lotus Flowers",
    keywords: ["lotus"],
    startingPrice: "60",
    image: "/assets/images/home-v2/rare-seasonal/rare-lotus.jpg",
    href: "/search/lotus",
    unit: "piece",
    tag: "Puja Special",
  },
  {
    title: "Rare Jasmine",
    keywords: ["jasmine", "malli", "malle"],
    startingPrice: "320",
    image: "/assets/images/home-v2/rare-seasonal/rare-jasmine.jpg",
    href: "/search/jasmine",
    unit: "bunch",
    tag: "Limited Stock",
  },
  {
    title: "Kanakambaram",
    keywords: ["kanakambaram", "crossandra"],
    startingPrice: "250",
    image: "/assets/images/home-v2/rare-seasonal/rare-kanakambaram.jpg",
    href: "/search/kanakambaram",
    unit: "kg",
    tag: "Seasonal",
  },
  {
    title: "Seasonal Marigold",
    keywords: ["marigold", "banthi", "seasonal"],
    startingPrice: "140",
    image: "/assets/images/home-v2/rare-seasonal/rare-marigold.jpg",
    href: "/search/marigold",
    unit: "kg",
    tag: "Seasonal",
  },
  {
    title: "White Tuberose",
    keywords: ["tuberose", "rajanigandha"],
    startingPrice: "280",
    image: "/assets/images/home-v2/rare-seasonal/rare-tuberose.jpg",
    href: "/search/tuberose",
    unit: "bunch",
    tag: "Fragrant",
  },
  {
    title: "Sampangi Flowers",
    keywords: ["sampangi", "champaca", "champak"],
    startingPrice: "300",
    image: "/assets/images/home-v2/rare-seasonal/rare-sampangi.jpg",
    href: "/search/sampangi",
    unit: "bunch",
    tag: "Rare",
  },
];

const pujaBoxOptions = {
  flowers: [
    "Daily Puja Mix",
    "Chamanthi + Banthi",
    "Lotus + Jasmine",
    "Custom Flower Mix",
  ],
  leaves: [
    "Tulasi + Bilva",
    "Mango Leaves",
    "Patri Combo",
    "No Leaves",
  ],
  quantity: ["250g", "500g", "1kg", "Custom quantity"],
  delivery: ["Tomorrow Morning", "Today Evening", "Daily Subscription", "Pick a Date"],
};

function normalizeWhatsApp(value) {
  if (!value) return "/contact-us";
  if (value.startsWith("http")) return value;
  const phone = value.replace(/\D/g, "");
  return phone ? `https://wa.me/${phone}` : "/contact-us";
}

function appendWhatsAppMessage(href, message) {
  if (!href || !href.startsWith("http")) return href || "/contact-us";
  const separator = href.includes("?") ? "&" : "?";
  return `${href}${separator}text=${encodeURIComponent(message)}`;
}

function getInstagramHandle(value) {
  if (!value || value === "#") return "manidvipaflowers";

  try {
    const pathname = new URL(value).pathname;
    return pathname.split("/").filter(Boolean)[0] || "manidvipaflowers";
  } catch {
    return value.replace(/^@/, "") || "manidvipaflowers";
  }
}

function cleanPlainText(value) {
  return String(value || "")
    .replace(/<[^>]*>/g, " ")
    .replace(/&nbsp;/gi, " ")
    .replace(/&amp;/gi, "&")
    .replace(/\s+/g, " ")
    .trim();
}

function normalizeHomeFaqs(faqs = []) {
  const source = Array.isArray(faqs) ? faqs : [];

  return source
    .map((faq) => ({
      question: cleanPlainText(faq?.question),
      answer: cleanPlainText(faq?.answer),
    }))
    .filter((faq) => faq.question && faq.answer)
    .slice(0, 8);
}

function getImageFileName(value) {
  return String(value || "")
    .split("?")[0]
    .split("/")
    .filter(Boolean)
    .pop();
}

function getHomepageCategoryImage(category, index) {
  const slug = String(category?.route_slug || category?.slug || "")
    .trim()
    .toLowerCase();
  const mappedImage = homeCategoryImageBySlug[slug];
  const uploadedImage =
    category?.image_url ||
    (category?.image && String(category.image).startsWith("http") ? category.image : null);
  const uploadedFileName = getImageFileName(uploadedImage);

  if (uploadedImage && !legacyCategoryBadgeImages.has(uploadedFileName)) {
    return uploadedImage;
  }

  if (mappedImage) return mappedImage;

  return categoryCards[index % categoryCards.length]?.image || "/assets/images/no-image.png";
}

function getHomepageCategoryHref(category) {
  const slug = String(category?.route_slug || category?.slug || "").trim();
  const parentSlug = String(
    category?.parent_route_slug || category?.parent_slug || category?.parent_title || ""
  )
    .trim()
    .toLowerCase()
    .replace(/&/g, " and ")
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-+|-+$/g, "");
  const routeBySlug = {
    "puja-flowers": "/puja-flowers",
    "daily-puja-flowers": "/puja-flowers",
    "premium-flowers": "/premium-flowers",
    "primimum-flowers": "/premium-flowers",
    "rare-flowers": "/rare-flowers",
    garlands: "/garlands",
    gifts: "/gifts",
    "bouquets-gifting": "/gifts",
  };

  if (parentSlug && parentSlug !== slug) {
    return `/${parentSlug}/${slug}`;
  }

  return routeBySlug[slug] || (slug ? `/${slug}` : "/flowers");
}

function buildHomepageCategoryCards(categories = []) {
  const dynamicCategories = Array.isArray(categories)
    ? categories.filter((category) => category?.slug || category?.title)
    : [];

  if (!dynamicCategories.length) return categoryCards;

  return dynamicCategories.map((category, index) => ({
    title: category?.title || "Fresh Flowers",
    description:
      cleanPlainText(category?.short_description) ||
      "Fresh flowers selected and packed for your needs.",
    image: getHomepageCategoryImage(category, index),
    href: getHomepageCategoryHref(category),
    terms: [category?.title, category?.slug, category?.route_slug].filter(Boolean),
  }));
}

function SectionTitle({ title, subtitle, actionText, actionHref = "/flowers" }) {
  return (
    <div className={styles.sectionTitleRow}>
      <div>
        <h2>{title}</h2>
        {subtitle ? <p>{subtitle}</p> : null}
      </div>
      {actionText ? (
        <Link href={actionHref} className={styles.textLink}>
          {actionText} <span aria-hidden="true">&rarr;</span>
        </Link>
      ) : null}
    </div>
  );
}

function getProductImage(product) {
  if (product?.localImage) return product.localImage;
  if (product?.image_url) return product.image_url;

  if (product?.image && /^(https?:)?\/\//i.test(String(product.image))) {
    return product.image;
  }

  if (product?.image_name) return `${IMG_URL}/${product.image_name}`;
  if (product?.image && !String(product.image).startsWith("/")) return `${IMG_URL}/${product.image}`;
  if (product?.image) return product.image;

  return "/assets/images/no-image.png";
}

function getProductHref(product) {
  if (product?.href) return product.href;
  if (product?.localImage && !product?.product_id) return "/flowers";
  return product?.slug ? `/flowers/${product.slug}` : "/flowers";
}

function formatPrice(value) {
  if (!value) return "--";
  const price = String(value);
  return price.includes("₹") || price.toLowerCase().includes("rs") ? price : `₹${price}`;
}

function getProductUnit(product, fallback = "kg") {
  return product?.unit || product?.units || product?.measurement || product?.weight_name || fallback;
}

function getSearchableProductText(product) {
  const categoryTitles = Array.isArray(product?.categories)
    ? product.categories.flatMap((category) => [category?.title, category?.slug])
    : [];

  return [
    product?.title,
    product?.slug,
    product?.sku,
    product?.category_name,
    product?.category,
    product?.category_title,
    product?.category_slug,
    ...categoryTitles,
  ]
    .filter(Boolean)
    .join(" ")
    .toLowerCase();
}

function normalizeProductCollection(products = []) {
  if (Array.isArray(products?.data?.data)) return products.data.data;
  if (Array.isArray(products?.data)) return products.data;
  return Array.isArray(products) ? products : [];
}

function normalizeHomeProduct(product, fallback = {}, fallbackUnit = "kg") {
  const productSlug = product?.slug || fallback.slug;

  return {
    ...fallback,
    ...product,
    title: product?.title || fallback.title || "Fresh Flowers",
    slug: productSlug,
    sell_price:
      product?.sell_price ||
      product?.starting_price ||
      product?.price ||
      fallback.startingPrice ||
      fallback.sell_price,
    list_price: product?.list_price || fallback.listPrice || fallback.list_price,
    localImage: product?.localImage || fallback.localImage,
    image_url: product?.image_url || fallback.image_url,
    image_name: product?.image_name || fallback.image_name,
    image: product?.image || fallback.image,
    href: productSlug ? `/flowers/${productSlug}` : fallback.href || "/flowers",
    unit: getProductUnit(product, fallback.unit || fallbackUnit),
    tag: product?.tag || fallback.tag,
  };
}

function buildSeedProducts(seeds = [], fallbackUnit = "kg") {
  return seeds.map((seed) =>
    normalizeHomeProduct(
      {
        title: seed.title,
        slug: seed.slug,
        sell_price: seed.startingPrice,
        list_price: seed.listPrice,
        localImage: seed.image,
        href: seed.href,
        unit: seed.unit || fallbackUnit,
        tag: seed.tag,
      },
      seed,
      fallbackUnit
    )
  );
}

function getProductIdentity(product) {
  return String(product?.slug || product?.title || product?.href || "")
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-+|-+$/g, "");
}

function fillProductSlots(products = [], seeds = [], limit = 6, fallbackUnit = "kg") {
  const normalizedProducts = products
    .slice(0, limit)
    .map((product, index) => normalizeHomeProduct(product, seeds[index], fallbackUnit));

  if (normalizedProducts.length >= limit) return normalizedProducts;

  const usedProductKeys = new Set(normalizedProducts.map(getProductIdentity).filter(Boolean));
  const fallbackSeedProducts = buildSeedProducts(seeds, fallbackUnit).filter((seedProduct) => {
    const seedKey = getProductIdentity(seedProduct);
    return seedKey ? !usedProductKeys.has(seedKey) : true;
  });

  return [...normalizedProducts, ...fallbackSeedProducts].slice(0, limit);
}

function buildSeedMatchedProducts(products = [], seeds = [], fallbackUnit = "kg") {
  const usedProductKeys = new Set();

  return seeds.map((seed) => {
    const matchedProduct = products.find((product) => {
      const productKey = product?.product_id || product?.id || product?.slug || product?.title;

      if (productKey && usedProductKeys.has(productKey)) return false;

      const searchableText = getSearchableProductText(product);
      return seed.keywords?.some((keyword) => searchableText.includes(keyword));
    });

    if (matchedProduct) {
      const productKey =
        matchedProduct?.product_id || matchedProduct?.id || matchedProduct?.slug || matchedProduct?.title;
      if (productKey) usedProductKeys.add(productKey);
    }

    return matchedProduct
      ? normalizeHomeProduct(matchedProduct, seed, seed.unit || fallbackUnit)
      : normalizeHomeProduct(
          {
            title: seed.title,
            slug: seed.slug,
            sell_price: seed.startingPrice,
            list_price: seed.listPrice,
            localImage: seed.image,
            href: seed.href,
            unit: seed.unit || fallbackUnit,
            tag: seed.tag,
          },
          seed,
          fallbackUnit
        );
  });
}

function buildFreshArrivalProducts(products = []) {
  const dynamicProducts = normalizeProductCollection(products);

  if (dynamicProducts.length) {
    return fillProductSlots(dynamicProducts, freshArrivalSeeds, 6, "kg");
  }

  return buildSeedProducts(freshArrivalSeeds, "kg");
}

function buildPremiumCollectionProducts(products = []) {
  const dynamicProducts = normalizeProductCollection(products);

  if (dynamicProducts.length) {
    return fillProductSlots(dynamicProducts, premiumCollectionSeeds, 5, "bunch");
  }

  return buildSeedMatchedProducts(fallbackProducts, premiumCollectionSeeds, "bunch");
}

function buildRareSeasonalCollectionProducts(products = []) {
  const dynamicProducts = normalizeProductCollection(products);

  if (dynamicProducts.length) {
    return fillProductSlots(dynamicProducts, rareSeasonalCollectionSeeds, 6, "bunch");
  }

  return buildSeedMatchedProducts(fallbackProducts, rareSeasonalCollectionSeeds, "bunch");
}

function parsePriceValue(value) {
  const numericValue = Number(String(value || "").replace(/[^\d.]/g, ""));
  return Number.isFinite(numericValue) ? numericValue : 0;
}

function getProductId(product) {
  return product?.product_id || product?.id || product?.data?.id || null;
}

function getWeightName(weight, fallback) {
  return (
    weight?.name ||
    weight?.weight ||
    weight?.title ||
    weight?.label ||
    weight?.value ||
    fallback
  );
}

function getWeightId(weight, product) {
  return weight?.id || weight?.weight_id || product?.weight_id || product?.default_weight_id || null;
}

function buildFreshWeightOptions(product) {
  const weights = Array.isArray(product?.weights) ? product.weights : [];

  if (weights.length) {
    const normalizedWeights = weights.map((weight, index) => {
      const sellPrice = parsePriceValue(weight?.sell_price || weight?.price || product?.sell_price);
      const listPrice = parsePriceValue(weight?.list_price || product?.list_price);

      return {
        key: String(getWeightId(weight, product) || index),
        label: getWeightName(weight, `Option ${index + 1}`),
        sellPrice,
        listPrice,
        weightId: getWeightId(weight, product),
        isOutOfStock: Number(weight?.stock) > 0 && Number(weight?.qty) <= 0,
      };
    });

    return normalizedWeights.sort((left, right) => Number(left.isOutOfStock) - Number(right.isOutOfStock));
  }

  const baseSellPrice = parsePriceValue(product?.sell_price || product?.list_price);
  const baseListPrice = parsePriceValue(product?.list_price || product?.sell_price);
  const fallbackWeightId = getWeightId(null, product);
  const fallbackWeights = [
    ["100 Grams", 0.1],
    ["250 Grams", 0.25],
    ["500 Grams", 0.5],
    ["1 Kg", 1],
  ];

  const normalizedFallbackWeights = fallbackWeights.map(([label, multiplier]) => ({
    key: label,
    label,
    sellPrice: Math.max(1, Math.round(baseSellPrice * multiplier)),
    listPrice: Math.max(1, Math.round(baseListPrice * multiplier)),
    weightId: fallbackWeightId,
  }));

  return normalizedFallbackWeights;
}

function FreshArrivalCard({ product, userToken }) {
  const { guestSession } = useUser();
  const { showToast } = useToast();
  const { setCartCount } = useCartCount();
  const [selectedWeightIndex, setSelectedWeightIndex] = useState(0);
  const [quantity, setQuantity] = useState(0);
  const [isAdding, setIsAdding] = useState(false);
  const [wasAdded, setWasAdded] = useState(false);
  const [resolvedProductDetails, setResolvedProductDetails] = useState(null);
  const [isResolvingWeights, setIsResolvingWeights] = useState(false);
  const imageSrc = getProductImage(product);
  const href = getProductHref(product);
  const cartProduct = useMemo(() => {
    if (!resolvedProductDetails?.weights?.length) return product;

    return {
      ...product,
      product_id: resolvedProductDetails?.data?.id || getProductId(product),
      weight_id: resolvedProductDetails?.data?.weight_id || product?.weight_id,
      default_weight_id: resolvedProductDetails?.data?.default_weight_id || product?.default_weight_id,
      weights: resolvedProductDetails.weights,
    };
  }, [product, resolvedProductDetails]);
  const weightOptions = useMemo(() => buildFreshWeightOptions(cartProduct), [cartProduct]);
  const selectedWeight = weightOptions[selectedWeightIndex] || weightOptions[0];
  const isOutOfStock = selectedWeight?.isOutOfStock === true;
  const displayQuantity = quantity > 0 ? quantity : 1;
  const totalSellPrice = (selectedWeight?.sellPrice || 0) * displayQuantity;
  const totalListPrice = (selectedWeight?.listPrice || 0) * displayQuantity;
  const productId = getProductId(cartProduct);

  useEffect(() => {
    setSelectedWeightIndex(0);
    setQuantity(0);
    setWasAdded(false);
    setResolvedProductDetails(null);
    setIsResolvingWeights(false);
  }, [product?.id, product?.product_id, product?.slug]);

  const updateQuantity = (nextQuantity) => {
    const parsedQuantity = Number.parseInt(nextQuantity, 10);
    setQuantity(Math.min(99, Math.max(0, Number.isNaN(parsedQuantity) ? 0 : parsedQuantity)));
    setWasAdded(false);
  };

  const handleWeightChange = (event) => {
    setSelectedWeightIndex(Number(event.target.value));
    setWasAdded(false);
  };

  useEffect(() => {
    let isMounted = true;
    const hasWeights = Array.isArray(product?.weights) && product.weights.length > 0;

    const isLocalOnlyProduct = product?.localImage && !getProductId(product);

    if (hasWeights || isLocalOnlyProduct || !product?.slug) {
      setResolvedProductDetails(null);
      return () => {
        isMounted = false;
      };
    }

    const fetchProductWeights = async () => {
      setIsResolvingWeights(true);

      try {
        const details = await fetchListingData(
          "GET",
          `product-details?product_slug=${product.slug}`,
          userToken ? userToken : undefined
        );

        const resolvedWeights =
          details?.weights ||
          details?.data?.weights ||
          details?.data?.product_weights ||
          details?.product_weights ||
          [];

        if (isMounted && resolvedWeights.length) {
          setResolvedProductDetails({
            ...details,
            weights: resolvedWeights,
          });
        }
      } catch (error) {
        console.error("Unable to load fresh arrival weights:", error);
      } finally {
        if (isMounted) setIsResolvingWeights(false);
      }
    };

    fetchProductWeights();

    return () => {
      isMounted = false;
    };
  }, [product, userToken]);

  const resolveCartSelection = async () => {
    const existingWeight = selectedWeight || buildFreshWeightOptions(cartProduct)[0];

    if (productId && existingWeight?.weightId) {
      return { productId, weightId: existingWeight.weightId };
    }

    if (!product?.slug) return null;

    setIsResolvingWeights(true);

    let details = null;
    try {
      details = await fetchListingData(
        "GET",
        `product-details?product_slug=${encodeURIComponent(product.slug)}`,
        userToken ? userToken : undefined
      );
    } finally {
      setIsResolvingWeights(false);
    }

    const resolvedWeights =
      details?.weights ||
      details?.data?.weights ||
      details?.data?.product_weights ||
      details?.product_weights ||
      [];

    const hydratedProduct = {
      ...product,
      product_id: details?.data?.id || productId,
      weight_id: details?.data?.weight_id || product?.weight_id,
      default_weight_id: details?.data?.default_weight_id || product?.default_weight_id,
      weights: resolvedWeights,
    };
    const hydratedWeights = buildFreshWeightOptions(hydratedProduct);
    const hydratedWeight = hydratedWeights[selectedWeightIndex] || hydratedWeights[0];
    const hydratedProductId = getProductId(hydratedProduct);

    if (resolvedWeights.length) {
      setResolvedProductDetails({
        ...details,
        weights: resolvedWeights,
      });
    }

    if (!hydratedProductId || !hydratedWeight?.weightId) return null;
    return { productId: hydratedProductId, weightId: hydratedWeight.weightId };
  };

  const handleAddToCart = async (event) => {
    event.preventDefault();

    if (isOutOfStock) {
      showToast("This weight is out of stock. Please choose another option.", "error");
      return;
    }

    if (!guestSession) {
      showToast("Please wait while your cart is getting ready.", "error");
      return;
    }

    if (isResolvingWeights) {
      showToast("Please wait while product weights are loading.", "error");
      return;
    }

    setIsAdding(true);

    try {
      const cartQuantity = quantity > 0 ? quantity : 1;
      const cartSelection = await resolveCartSelection();

      if (!cartSelection?.productId || !cartSelection?.weightId) {
        showToast("Please choose product options on the details page.", "error");
        return;
      }

      const cartData = await fetchListingData(
        "POST",
        "add-to-cart",
        userToken ? userToken : undefined,
        {
          cart_session: guestSession,
          product_id: cartSelection.productId,
          quantity: cartQuantity,
          weight_id: cartSelection.weightId,
        }
      );

      if (!cartData?.success) {
        showToast(cartData?.message || "Failed to add to cart", "error");
        return;
      }

      setQuantity(cartQuantity);
      setWasAdded(true);
      showToast(cartData.message || "Added to cart successfully", "success");

      const refreshedCart = await fetchCartBySession(
        guestSession,
        userToken ? userToken : undefined
      );

      if (refreshedCart?.success) {
        setCartCount(getCartCount(refreshedCart));
      }
    } catch (error) {
      console.error("Error during ADD cart:", error);
      showToast("An unexpected error occurred. Please try again later.", "error");
    } finally {
      setIsAdding(false);
    }
  };

  return (
    <article className={styles.freshArrivalCard}>
      <Link href={href} className={styles.freshArrivalImage}>
        <span className={styles.freshBadge}>Fresh Today</span>
        <Image
          src={imageSrc}
          alt={product?.title || "Fresh flower"}
          width={300}
          height={230}
          sizes="(max-width: 700px) 62vw, 180px"
        />
      </Link>
      <div className={styles.freshArrivalBody}>
        <h3>{product?.title || "Fresh Flowers"}</h3>
        <div className={styles.freshStars}>★★★★★</div>
        <div className={styles.freshPrice}>
          {totalListPrice && totalListPrice > totalSellPrice ? (
            <span>{formatPrice(totalListPrice)}</span>
          ) : null}
          <strong>{formatPrice(totalSellPrice)}</strong>
        </div>
        <select
          className={styles.freshWeightSelect}
          value={selectedWeightIndex}
          onChange={handleWeightChange}
          aria-label={`Select weight for ${product?.title || "fresh flowers"}`}
          disabled={isResolvingWeights}
        >
          {weightOptions.map((weight, index) => (
            <option key={weight.key} value={index}>
              {weight.label}{weight.isOutOfStock ? " — Out of stock" : ""}
            </option>
          ))}
        </select>
        <div className={styles.freshQuantity}>
          <span>−</span>
          <strong>{quantity}</strong>
          <span>{getProductUnit(product)}</span>
          <span>+</span>
        </div>
        <div className={styles.freshQuantityStepper}>
          <button
            type="button"
            onClick={() => updateQuantity(quantity - 1)}
            disabled={quantity <= 0 || isAdding || isResolvingWeights}
            aria-label={`Decrease quantity for ${product?.title || "fresh flowers"}`}
          >
            −
          </button>
          <input
            type="number"
            min="0"
            max="99"
            value={quantity}
            onChange={(event) => updateQuantity(event.target.value)}
            disabled={isAdding || isResolvingWeights}
            aria-label={`Quantity for ${product?.title || "fresh flowers"}`}
          />
          <button
            type="button"
            onClick={() => updateQuantity(quantity + 1)}
            disabled={isAdding || isResolvingWeights}
            aria-label={`Increase quantity for ${product?.title || "fresh flowers"}`}
          >
            +
          </button>
        </div>
        <button
          type="button"
          className={`${styles.freshAddButton} ${wasAdded ? styles.freshAddedButton : ""}`}
          onClick={handleAddToCart}
          disabled={isAdding || isResolvingWeights || isOutOfStock}
        >
          <FaShoppingBasket aria-hidden="true" />
          {isAdding ? "Adding..." : isResolvingWeights ? "Loading..." : isOutOfStock ? "Out of stock" : wasAdded ? "Added" : "Add"}
        </button>
      </div>
    </article>
  );
}

function PremiumProductCard({ product }) {
  const imageSrc = getProductImage(product);
  const href = getProductHref(product);

  return (
    <article className={styles.premiumProductCard}>
      <Link href={href} className={styles.premiumProductImage}>
        <Image
          src={imageSrc}
          alt={product?.title || "Premium flower"}
          width={520}
          height={390}
          sizes="(max-width: 700px) 92vw, (max-width: 1200px) 42vw, 260px"
        />
      </Link>
      <div className={styles.premiumProductInfo}>
        <span className={styles.premiumProductTag}>Premium Blooms</span>
        <h3>{product?.title || "Premium Flowers"}</h3>
        <p>
          Starting <strong>{formatPrice(product?.sell_price || product?.list_price)}</strong>
        </p>
        <Link href={href} className={styles.premiumExploreLink}>
          <span className={styles.premiumExploreFullText}>Explore Premium Flowers</span>
          <span className={styles.premiumExploreShortText}>Explore</span>
          <span aria-hidden="true">&rarr;</span>
        </Link>
      </div>
    </article>
  );
}

function RareProductCard({ product }) {
  const imageSrc = getProductImage(product);
  const href = getProductHref(product);

  return (
    <article className={styles.rareProductCard}>
      <Link href={href} className={styles.rareProductImage}>
        <Image
          src={imageSrc}
          alt={product?.title || "Rare flower"}
          width={420}
          height={420}
          sizes="(max-width: 700px) 44vw, (max-width: 1200px) 30vw, 220px"
        />
      </Link>
      <div className={styles.rareProductInfo}>
        {product?.tag ? <span className={styles.rareProductTag}>{product.tag}</span> : null}
        <h3>{product?.title || "Rare Flowers"}</h3>
        <p>
          Starting <strong>{formatPrice(product?.sell_price || product?.list_price)}</strong> /{" "}
          {getProductUnit(product, "bunch")}
        </p>
        <Link href={href} className={styles.rareExploreLink}>
          Explore Flowers <span aria-hidden="true">&rarr;</span>
        </Link>
      </div>
    </article>
  );
}

export default function HomePage({
  userToken,
  homeBanners = [],
  categories = [],
  initialHomeProducts = [],
  initialPremiumProducts = [],
  initialRareProducts = [],
  initialSubscriptionPlans = [],
  faqs = [],
  siteSettings,
}) {
  const [homeProducts, setHomeProducts] = useState(initialHomeProducts || []);
  const visibleSubscriptionPlans = useMemo(
    () => normalizeHomeSubscriptionPlans(initialSubscriptionPlans),
    [initialSubscriptionPlans]
  );
  const visibleFaqs = useMemo(() => normalizeHomeFaqs(faqs), [faqs]);
  const [pujaBoxSelection, setPujaBoxSelection] = useState({
    flowers: pujaBoxOptions.flowers[0],
    leaves: pujaBoxOptions.leaves[0],
    quantity: pujaBoxOptions.quantity[1],
    delivery: pujaBoxOptions.delivery[0],
  });

  const whatsappHref = normalizeWhatsApp(siteSettings?.data?.SITE_WHATSAPP);
  const instagramHref = siteSettings?.data?.INSTAGRAM_LINK || "#";
  const instagramHandle = getInstagramHandle(instagramHref);
  const customPujaBoxWhatsAppHref = useMemo(() => {
    const message = [
      "Hi Manidvipa Flowers, I want to build a custom puja flower box.",
      `Flowers: ${pujaBoxSelection.flowers}`,
      `Leaves: ${pujaBoxSelection.leaves}`,
      `Quantity: ${pujaBoxSelection.quantity}`,
      `Delivery: ${pujaBoxSelection.delivery}`,
    ].join("\n");

    return appendWhatsAppMessage(whatsappHref, message);
  }, [pujaBoxSelection, whatsappHref]);

  const updatePujaBoxSelection = useCallback((type, value) => {
    setPujaBoxSelection((currentSelection) => ({
      ...currentSelection,
      [type]: value,
    }));
  }, []);

  const fetchData = useCallback(async () => {
    try {
      const response = await fetchListingData(
        "GET",
        "home-featured-products",
        userToken || undefined
      );
      if (response?.data?.length) setHomeProducts(response.data);
    } catch (error) {
      console.error("Unable to refresh homepage products:", error);
    }
  }, [userToken]);

  useEffect(() => {
    if (!initialHomeProducts?.length) fetchData();
  }, [fetchData, initialHomeProducts]);

  const categoryHref = useCallback(
    (terms, fallbackIndex = 0) => {
      const normalizedTerms = terms.map((term) => String(term || "").toLowerCase());
      const directRoute = [
        { terms: ["daily puja", "puja", "temple"], href: "/puja-flowers" },
        { terms: ["premium", "primimum", "imported", "exotic"], href: "/premium-flowers" },
        { terms: ["birthday", "anniversary", "gift", "bouquet"], href: "/gifts" },
        { terms: ["rare", "seasonal"], href: "/rare-flowers" },
        { terms: ["garland", "mala"], href: "/garlands" },
        { terms: ["wedding", "housewarming", "event", "decoration"], href: "/decorations" },
      ].find((route) =>
        route.terms.some((term) =>
          normalizedTerms.some((normalizedTerm) => normalizedTerm.includes(term))
        )
      );

      if (directRoute) return directRoute.href;

      const match = categories.find((category) => {
        const title = String(category?.title || "").toLowerCase();
        return normalizedTerms.some((term) => title.includes(term));
      });
      const fallback = categories[fallbackIndex];
      const target = match || fallback;
      const targetSlug = target?.route_slug || target?.slug;
      return targetSlug ? `/${targetSlug}` : "/flowers";
    },
    [categories]
  );

  const productSections = useMemo(() => {
    const featuredProducts = normalizeProductCollection(homeProducts);
    const premiumProducts = normalizeProductCollection(initialPremiumProducts);
    const rareProducts = normalizeProductCollection(initialRareProducts);
    const fresh = buildFreshArrivalProducts(featuredProducts);
    const premium = buildPremiumCollectionProducts(premiumProducts);
    const rare = buildRareSeasonalCollectionProducts(rareProducts);
    return { fresh, premium, rare };
  }, [homeProducts, initialPremiumProducts, initialRareProducts]);

  const homepageCategoryCards = useMemo(
    () => buildHomepageCategoryCards(categories),
    [categories]
  );

  const activeHeroSlides = useMemo(
    () => buildHeroSlides(homeBanners),
    [homeBanners]
  );

  return (
    <main className={styles.homePage}>
      <section className={styles.heroSection} id="home">
        <div className={styles.heroCarousel}>
          <Slick
            slickCustomSettings={heroSliderSettings}
            slides={activeHeroSlides.map((slide, index) => (
              <div className={styles.heroSlide} key={slide.id || slide.titleLines.join(" ")}>
                <div className={`${styles.homeContainer} ${styles.heroContainer}`}>
                  <div className={styles.heroGrid}>
                    <div className={styles.heroContent}>
                      <h1>
                        {slide.titleLines.map((line, lineIndex) => (
                          <React.Fragment key={line}>
                            {line}
                            {lineIndex < slide.titleLines.length - 1 ? <br /> : null}
                          </React.Fragment>
                        ))}
                      </h1>
                      <p className={styles.heroDescription}>{slide.description}</p>

                      <div className={styles.heroBenefits}>
                        {slide.benefits.map(([icon, title, subtitle]) => (
                          <div className={styles.heroBenefit} key={title}>
                            <span className={styles.heroBenefitIcon}>
                              <Image src={icon} alt="" width={34} height={34} />
                            </span>
                            <span className={styles.heroBenefitCopy}>
                              <strong>{title}</strong>
                              <span>{subtitle}</span>
                            </span>
                          </div>
                        ))}
                      </div>

                      <div className={styles.heroActions}>
                        <Link
                          href={resolveHeroHref(slide.primary.href, whatsappHref)}
                          target={
                            opensInNewTab(resolveHeroHref(slide.primary.href, whatsappHref))
                              ? "_blank"
                              : undefined
                          }
                          rel={
                            opensInNewTab(resolveHeroHref(slide.primary.href, whatsappHref))
                              ? "noopener noreferrer"
                              : undefined
                          }
                          className="primary-but"
                        >
                          {slide.primary.label}
                        </Link>
                        <Link
                          href={resolveHeroHref(slide.secondary.href, whatsappHref)}
                          target={
                            opensInNewTab(resolveHeroHref(slide.secondary.href, whatsappHref))
                              ? "_blank"
                              : undefined
                          }
                          rel={
                            opensInNewTab(resolveHeroHref(slide.secondary.href, whatsappHref))
                              ? "noopener noreferrer"
                              : undefined
                          }
                          className="green-but"
                        >
                          {slide.secondary.label}
                        </Link>
                      </div>
                      <Link
                        href={whatsappHref}
                        target="_blank"
                        rel="noopener noreferrer"
                        className={styles.whatsappTextLink}
                      >
                        Order on WhatsApp →
                      </Link>
                    </div>

                    <div className={styles.heroImageWrap}>
                      <Image
                        src={slide.image}
                        alt={slide.alt}
                        width={1000}
                        height={600}
                        priority={index === 0}
                        sizes="(max-width: 900px) 100vw, 56vw"
                      />
                    </div>
                  </div>
                </div>
              </div>
            ))}
          />
        </div>
      </section>

      <section className={styles.sectionWhite} id="shop-by-category">
        <div className={styles.homeContainer}>
          <div className={styles.centerSectionTitle}>
            <Image src="/assets/icons/head-left.png" alt="" width={48} height={16} />
            <h2>Shop by Category</h2>
            <Image src="/assets/icons/head-right.png" alt="" width={48} height={16} />
          </div>
          <div className={styles.categoryGrid}>
            {homepageCategoryCards.map((card, index) => (
              <Link
                key={card.title}
                href={card.href || categoryHref(card.terms, index)}
                className={styles.categoryCardV2}
              >
                <Image src={card.image} alt={card.title} width={220} height={180} />
                <h3>{card.title}</h3>
                <p>{card.description}</p>
              </Link>
            ))}
          </div>
        </div>
      </section>

      <section className={styles.sectionSoft} id="subscriptions">
        <div className={styles.homeContainer}>
          <div className={styles.subscriptionLayout}>
            <div className={styles.subscriptionArea}>
              <SectionTitle
                title="Business Flower Subscriptions"
                subtitle="Premium arrangements for offices, hospitals and hotels; loose flowers for temples and puja."
                actionText="View All Plans"
                actionHref="/subscriptions"
              />
              <div className={styles.subscriptionGrid}>
                {visibleSubscriptionPlans.map((plan) => (
                  <div className={styles.subscriptionCard} key={plan.title}>
                    <div className={styles.subscriptionIcon}>
                      <Image
                        src={plan.image}
                        alt={plan.title}
                        width={420}
                        height={280}
                        sizes="(max-width: 700px) 44vw, (max-width: 1100px) 30vw, 210px"
                        quality={90}
                      />
                    </div>
                    <h3>{plan.title}</h3>
                    <p>{plan.description}</p>
                    <div className={styles.subscriptionPrice}>
                      <strong>{plan.price}</strong> <span>{plan.cadence}</span>
                    </div>
                    <Link href="/subscriptions" className={styles.smallPrimaryButton}>
                      VIEW PLAN
                    </Link>
                  </div>
                ))}
              </div>
              <div className={styles.subscriptionMeta}>
                <span>Daily</span>
                <span>Alternate Days</span>
                <span>Weekly</span>
                <span>Monthly</span>
                <strong>Easy • Flexible • Hassle Free</strong>
              </div>
            </div>

            <div className={styles.pujaBoxCard}>
              <div className={styles.pujaBoxContent}>
                <h2>Build Your Puja Box</h2>
                <p>Create your own puja flower box</p>
                <ul>
                  <li>✓ Choose Flowers</li>
                  <li>✓ Choose Leaves</li>
                  <li>✓ Select Quantity</li>
                  <li>✓ Select Delivery</li>
                </ul>
                <Link href="/subscriptions" className={styles.smallPrimaryButton}>
                  BUILD NOW →
                </Link>
              </div>
              <div className={styles.pujaBoxImage}>
                <Image
                  src="/assets/images/home-v2/puja-box.jpg"
                  alt="Custom puja flower box"
                  width={500}
                  height={430}
                />
              </div>
            </div>
          </div>
        </div>
      </section>

      <section className={styles.sectionWhite} id="fresh-arrivals">
        <div className={styles.homeContainer}>
          <div className={styles.productShowcaseStack}>
            <div className={styles.freshProductSection}>
              <SectionTitle
                title="Today's Fresh Arrivals"
                subtitle="Prices updated today at 9:00 AM"
                actionText="View All"
              />
              <div className={styles.freshArrivalGrid}>
                {productSections.fresh.slice(0, 6).map((product, index) => (
                  <FreshArrivalCard
                    key={product?.product_id || product?.slug || `fresh-${index}`}
                    product={product}
                    userToken={userToken}
                  />
                ))}
              </div>
            </div>

            <div className={styles.collectionShowcaseRow}>
              <div className={`${styles.collectionPanel} ${styles.premiumCollectionPanel}`} id="premium">
                <SectionTitle
                  title="The Premium Collection"
                  subtitle="Handpicked blooms for unforgettable moments."
                  actionText="Explore Premium Flowers"
                  actionHref="/premium-flowers"
                />
                <div className={styles.premiumProductGrid}>
                  {productSections.premium.slice(0, 5).map((product, index) => (
                    <PremiumProductCard
                      key={product?.product_id || product?.slug || `premium-${index}`}
                      product={product}
                    />
                  ))}
                </div>
              </div>

              <div className={styles.rareCollectionPanel} id="rare-flowers">
                <SectionTitle
                  title="Rare & Seasonal Flowers"
                  subtitle="Limited seasonal blooms for rituals and special occasions."
                  actionText="Explore Rare Flowers"
                  actionHref="/rare-flowers"
                />
                <div className={styles.rareProductGrid}>
                  {productSections.rare.slice(0, 6).map((product, index) => (
                    <RareProductCard
                      key={product?.product_id || product?.slug || `rare-${index}`}
                      product={product}
                    />
                  ))}
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section className={`${styles.sectionSoft} ${styles.pujaBuilderSection}`} id="puja-box">
        <div className={styles.homeContainer}>
          <div className={styles.pujaBuilderCard}>
            <div className={styles.pujaBuilderContent}>
              <span className={styles.eyebrowText}>Custom ritual flowers</span>
              <h2>Build Your Own Puja Flower Box</h2>
              <p>
                Choose flowers, leaves, quantity and delivery timing for daily puja,
                temple offering, or tomorrow morning rituals.
              </p>

              <div className={styles.pujaBuilderHighlights}>
                <span>Fresh packed</span>
                <span>Puja ready</span>
                <span>Morning delivery</span>
              </div>

              <div className={styles.pujaBuilderOptions}>
                {Object.entries(pujaBoxOptions).map(([type, options]) => (
                  <div className={styles.pujaOptionGroup} key={type}>
                    <h3>{type}</h3>
                    <div className={styles.pujaOptionButtons}>
                      {options.map((option) => {
                        const isSelected = pujaBoxSelection[type] === option;
                        return (
                          <button
                            type="button"
                            key={option}
                            className={isSelected ? styles.pujaOptionSelected : undefined}
                            onClick={() => updatePujaBoxSelection(type, option)}
                          >
                            {option}
                          </button>
                        );
                      })}
                    </div>
                  </div>
                ))}
              </div>

              <div className={styles.pujaBuilderSummary}>
                <div>
                  <span>Your box</span>
                  <strong>
                    {pujaBoxSelection.flowers} • {pujaBoxSelection.leaves} •{" "}
                    {pujaBoxSelection.quantity} • {pujaBoxSelection.delivery}
                  </strong>
                </div>
                <Link
                  href={customPujaBoxWhatsAppHref}
                  target="_blank"
                  rel="noopener noreferrer"
                  className={styles.smallPrimaryButton}
                >
                  BUILD ON WHATSAPP <span aria-hidden="true">&rarr;</span>
                </Link>
              </div>

              <ul className={styles.pujaBuilderNotes}>
                <li>For more than 1kg, choose Custom quantity and send your requirement.</li>
                <li>Tomorrow morning orders can be confirmed directly on WhatsApp.</li>
              </ul>
            </div>
            <div className={styles.pujaBuilderVisual}>
              <Image
                src="/assets/images/home-v2/custom-puja-flower-box.jpg"
                alt="Custom puja flower box with flowers and leaves"
                width={760}
                height={520}
                sizes="(max-width: 900px) 100vw, 48vw"
              />
              <div className={styles.pujaVisualCard}>
                <span>Best for</span>
                <strong>Daily puja, temple visits, vratham and special rituals</strong>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section className={styles.sectionSoft} id="shop-by-occasion">
        <div className={styles.homeContainer}>
          <div className={styles.occasionDecorationGrid}>
            <div className={styles.occasionArea}>
              <SectionTitle
                title="Shop by Occasion"
                actionText="View All Occasions"
                actionHref="/gifts"
              />
              <div className={styles.occasionGrid}>
                {occasions.map(([title, image], index) => (
                  <Link
                    href={categoryHref([title.toLowerCase()], index)}
                    className={styles.occasionCard}
                    key={title}
                  >
                    <Image src={image} alt={title} width={120} height={120} />
                    <span>{title}</span>
                  </Link>
                ))}
              </div>
            </div>

            <div className={styles.decorationService}>
              <div className={styles.decorationContent}>
                <h2>Flower Decoration Services</h2>
                <p>Make every moment beautiful with flowers.</p>
                <ul>
                  <li>✓ Wedding Decorations</li>
                  <li>✓ Pooja & Temple Decorations</li>
                  <li>✓ House Warming</li>
                  <li>✓ Birthday & Events</li>
                </ul>
                <div className={styles.inlineActions}>
                  <Link href="/decorations" className={styles.smallPrimaryButton}>
                    VIEW GALLERY
                  </Link>
                  <Link href={whatsappHref} target="_blank" className={styles.smallGreenButton}>
                    GET A QUOTE
                  </Link>
                </div>
              </div>
              <div className={styles.decorationImage}>
                <Image
                  src="/assets/images/home-v2/decoration-service.jpg"
                  alt="Traditional flower decoration service"
                  width={560}
                  height={370}
                />
              </div>
            </div>
          </div>
        </div>
      </section>

      <section className={styles.sectionWhite} id="decorations">
        <div className={styles.homeContainer}>
          <SectionTitle
            title="Flowers for Every Celebration"
            subtitle="Premium flower decoration support for homes, temples, weddings and events."
            actionText="View Decoration Gallery"
            actionHref="/contact-us"
          />
          <div className={styles.decorationService}>
            <div className={styles.decorationContent}>
              <span className={styles.eyebrowText}>Decoration services</span>
              <h2>Flower Decoration Services</h2>
              <p>Make every moment beautiful with fresh flowers and traditional styling.</p>
              <ul>
                <li>Wedding Decorations</li>
                <li>Pooja & Temple Decorations</li>
                <li>House Warming / Gruhapravesam</li>
                <li>Birthday & Event Decorations</li>
              </ul>
              <div className={styles.inlineActions}>
                <Link href="/contact-us" className={styles.smallPrimaryButton}>
                  VIEW GALLERY
                </Link>
                <Link href={whatsappHref} target="_blank" className={styles.smallGreenButton}>
                  GET A QUOTE
                </Link>
              </div>
            </div>
            <div className={styles.decorationTiles}>
              {decorationGallery.map(([title, image]) => (
                <div className={styles.decorationTile} key={title}>
                  <Image src={image} alt={title} width={360} height={250} />
                  <span>{title}</span>
                </div>
              ))}
            </div>
          </div>
        </div>
      </section>

      <section className={styles.benefitStrip} id="why-manidvipa">
        <div className={styles.homeContainer}>
          <SectionTitle
            title="Why Manidvipa Flowers?"
            subtitle="Fresh, puja-ready flowers with ordering support for Hyderabad customers."
          />
          <div className={styles.benefitGrid}>
            {benefits.map(([image, title, description]) => (
              <div className={styles.benefitItem} key={title}>
                <Image src={image} alt="" width={52} height={52} />
                <div>
                  <strong>{title}</strong>
                  <span>{description}</span>
                </div>
              </div>
            ))}
          </div>
        </div>
      </section>

      <section className={styles.sectionWhite} id="recent-decorations">
        <div className={styles.homeContainer}>
          <div className={styles.galleryTestimonialsGrid}>
            <div>
              <SectionTitle
                title="Our Recent Decorations"
                subtitle="See the beauty we create with flowers."
                actionText="View Gallery"
                actionHref="/contact-us"
              />
              <div className={styles.decorationGallery}>
                {decorationGallery.map(([title, image]) => (
                  <div key={title} className={styles.galleryCard}>
                    <Image src={image} alt={title} width={360} height={260} />
                    <span>{title}</span>
                  </div>
                ))}
              </div>
            </div>

            <div>
              <SectionTitle title="What Our Customers Say" />
              <div className={styles.testimonialGrid}>
                <div className={styles.testimonialCard}>
                  <div className={styles.stars}>★★★★★</div>
                  <p>Fresh flowers, neatly packed and delivered on time for our daily puja.</p>
                  <span>— Customer, Hyderabad</span>
                </div>
                <div className={styles.testimonialCard}>
                  <div className={styles.stars}>★★★★★</div>
                  <p>Good flower quality and convenient ordering support.</p>
                  <span>— Customer, Hyderabad</span>
                </div>
                <div className={styles.testimonialCard}>
                  <div className={styles.stars}>★★★★★</div>
                  <p>The flower selection was fresh and suitable for our function.</p>
                  <span>— Customer, Hyderabad</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section className={styles.testimonialSection} id="testimonials">
        <div className={styles.homeContainer}>
          <SectionTitle
            title="What Our Customers Say"
            subtitle="Real feedback from customers ordering fresh flowers and decorations."
          />
          <div className={styles.testimonialGrid}>
            <div className={styles.testimonialCard}>
              <div className={styles.stars}>{"\u2605\u2605\u2605\u2605\u2605"}</div>
              <p>Fresh flowers reached us early in the morning and the quality was excellent.</p>
              <span>&mdash; Customer, Hyderabad</span>
            </div>
            <div className={styles.testimonialCard}>
              <div className={styles.stars}>{"\u2605\u2605\u2605\u2605\u2605"}</div>
              <p>Fresh flowers, neatly packed and delivered on time for our daily puja.</p>
              <span>&mdash; Customer, Hyderabad</span>
            </div>
            <div className={styles.testimonialCard}>
              <div className={styles.stars}>{"\u2605\u2605\u2605\u2605\u2605"}</div>
              <p>The flower selection was fresh and suitable for our function.</p>
              <span>&mdash; Customer, Hyderabad</span>
            </div>
          </div>
        </div>
      </section>

      <section className={styles.instagramSection} id="instagram">
        <div className={styles.instagramInner}>
          <div className={styles.instagramHeader}>
            <div className={styles.instagramTitle}>
              <h2>Fresh From Manidvipa</h2>
              <p>
                Follow us on Instagram{" "}
                <Link href={instagramHref} target="_blank" rel="noopener noreferrer">
                  @{instagramHandle}
                </Link>
              </p>
            </div>
            <Link
              href={instagramHref}
              target="_blank"
              rel="noopener noreferrer"
              className={styles.instagramButton}
            >
              <FaInstagram aria-hidden="true" />
              Follow Us on Instagram
            </Link>
          </div>
          <div className={styles.instagramGrid}>
            {instagramImages.map((image, index) => (
              <Link
                href={instagramHref}
                target="_blank"
                rel="noopener noreferrer"
                className={styles.instagramCard}
                key={image}
              >
                <Image
                  src={image}
                  alt={`Manidvipa flowers gallery ${index + 1}`}
                  fill
                  sizes="(max-width: 900px) 45vw, (max-width: 1200px) 22vw, 11vw"
                />
              </Link>
            ))}
          </div>
        </div>
      </section>

      {visibleFaqs.length ? (
        <section className={styles.sectionSoft} id="faqs">
          <div className={styles.homeContainer}>
            <SectionTitle
              title="Frequently Asked Questions"
              subtitle="Quick answers from Manidvipa Flowers for ordering, delivery and flower freshness."
            />
            <div className={styles.faqGrid}>
              {visibleFaqs.map((faq, index) => (
                <details className={styles.faqItem} key={`${faq.question}-${index}`}>
                  <summary>{faq.question}</summary>
                  <p>{faq.answer}</p>
                </details>
              ))}
            </div>
          </div>
        </section>
      ) : null}

      <section className={styles.finalCta} aria-label="Next morning flower delivery">
        <div className={styles.homeContainer}>
          <div className={styles.finalCtaGrid}>
            <div className={styles.finalCtaContent}>
              <h2>Need Flowers Tomorrow Morning?</h2>
              <p>Order today and wake up to fresh flowers.</p>
              <div className={styles.finalCtaActions}>
                <Link href="/flowers" className={styles.finalShopButton}>
                  SHOP NOW
                </Link>
                <Link
                  href={whatsappHref}
                  target="_blank"
                  rel="noopener noreferrer"
                  className={styles.finalWhatsAppButton}
                >
                  <FaWhatsapp aria-hidden="true" />
                  WHATSAPP US
                </Link>
              </div>
            </div>
            <div className={styles.finalCtaImage}>
              <Image
                src="/assets/images/home-v2/cta-basket-flowers.png"
                alt="Fresh flowers for morning delivery"
                width={520}
                height={260}
              />
            </div>
          </div>
        </div>
      </section>
    </main>
  );
}

