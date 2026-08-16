export const storefrontNavItems = [
  { key: "home", label: "Home", href: "/" },
  { key: "flowers", label: "Flowers", href: "/flowers" },
  { key: "pujaFlowers", label: "Puja Flowers", href: "/puja-flowers" },
  { key: "subscriptions", label: "Subscriptions", href: "/subscriptions" },
  { key: "premiumFlowers", label: "Premium", href: "/premium-flowers" },
  { key: "rareFlowers", label: "Rare Flowers", href: "/rare-flowers" },
  { key: "garlands", label: "Garlands", href: "/garlands" },
  { key: "decorations", label: "Decorations", href: "/decorations" },
  { key: "gifts", label: "Gifts", href: "/gifts" },
  { key: "offers", label: "Offers", href: "/offers" },
];

export const productCategoryPageConfigs = {
  flowers: {
    key: "flowers",
    label: "Flowers",
    href: "/flowers",
    categorySlug: "all-flowers",
    title: "All Flowers",
    description:
      "Explore our wide range of fresh flowers for daily puja, decorations, gifting and special rituals.",
    metaTitle: "Fresh Flowers Online in Hyderabad | Manidvipa Flowers",
    metaDescription:
      "Shop fresh flowers, puja flowers, premium flowers, rare flowers, seasonal flowers, roses, jasmine, lotus, garlands and leaves online in Hyderabad.",
  },
  pujaFlowers: {
    key: "pujaFlowers",
    label: "Puja Flowers",
    href: "/puja-flowers",
    categorySlug: "daily-puja-flowers",
    title: "Puja Flowers",
    description:
      "Fresh flowers and leaves selected for daily puja, temple offerings, vrathams and morning rituals.",
    metaTitle: "Puja Flowers Online in Hyderabad | Manidvipa Flowers",
    metaDescription:
      "Order fresh puja flowers, loose flowers, lotus, jasmine, banthi, chamanthi and ritual flower sets in Hyderabad.",
  },
  premiumFlowers: {
    key: "premiumFlowers",
    label: "Premium Flowers",
    href: "/premium-flowers",
    categorySlug: "premium-flowers",
    title: "Premium Flowers",
    description:
      "Handpicked premium roses, lilies, orchids, tulips and exotic flowers for unforgettable moments.",
    metaTitle: "Premium Flowers Online in Hyderabad | Manidvipa Flowers",
    metaDescription:
      "Explore premium roses, lilies, orchids, tulips and imported flowers for gifting, decor and special occasions.",
  },
  rareFlowers: {
    key: "rareFlowers",
    label: "Rare Flowers",
    href: "/rare-flowers",
    categorySlug: "rare-flowers",
    title: "Rare & Seasonal Flowers",
    description:
      "Limited seasonal blooms and ritual-special flowers sourced fresh based on availability.",
    metaTitle: "Rare and Seasonal Flowers in Hyderabad | Manidvipa Flowers",
    metaDescription:
      "Shop rare, seasonal and ritual-special flowers including jasmine, lotus, kanakambaram, sampangi and tuberose.",
  },
  garlands: {
    key: "garlands",
    label: "Garlands",
    href: "/garlands",
    categorySlug: "garlands",
    title: "Fresh Flower Garlands",
    description:
      "Garlands and mala options for puja, temples, functions, welcome decor and special ceremonies.",
    metaTitle: "Flower Garlands Online in Hyderabad | Manidvipa Flowers",
    metaDescription:
      "Order fresh flower garlands and mala for puja, temples, events and ceremonies in Hyderabad.",
  },
  gifts: {
    key: "gifts",
    label: "Gifts",
    href: "/gifts",
    categorySlug: "bouquets-gifting",
    title: "Flower Gifts",
    description:
      "Fresh bouquets, premium flower baskets and gifting-ready flowers for birthdays, visits and celebrations.",
    metaTitle: "Flower Gifts Online in Hyderabad | Manidvipa Flowers",
    metaDescription:
      "Send fresh flower gifts, bouquets, baskets and premium blooms for birthdays, celebrations and special moments.",
  },
};

export const menuLandingPageConfigs = {
  subscriptions: {
    key: "subscriptions",
    label: "Subscriptions",
    href: "/subscriptions",
    title: "Flower Subscriptions",
    eyebrow: "Fresh flowers on schedule",
    description:
      "Set up daily, weekly or monthly flower delivery for puja, home decor, temples and office spaces.",
    heroImage: "/assets/images/home-v2/puja-box.jpg",
    primaryCta: { label: "Start a Subscription", href: "/contact-us" },
    secondaryCta: { label: "Browse Puja Flowers", href: "/puja-flowers" },
    highlights: ["Daily / weekly / monthly", "Fresh morning packing", "Flexible quantity"],
    cards: [
      {
        title: "Daily Puja Subscription",
        description: "Fresh puja flowers and leaves delivered every morning.",
        meta: "From Rs. 299 / week",
        href: "/puja-flowers",
      },
      {
        title: "Weekly Subscription",
        description: "A planned weekly flower delivery for home rituals and decor.",
        meta: "From Rs. 799 / month",
        href: "/flowers",
      },
      {
        title: "Temple Subscription",
        description: "Bulk flowers, garlands and leaves for temple needs.",
        meta: "From Rs. 1,199 / month",
        href: "/garlands",
      },
      {
        title: "Office / Business",
        description: "Reception, event and regular business flower support.",
        meta: "From Rs. 1,499 / month",
        href: "/decorations",
      },
    ],
    processTitle: "How subscription works",
    processSteps: ["Choose plan", "Confirm delivery time", "Receive fresh flowers", "Pause or change anytime"],
  },
  decorations: {
    key: "decorations",
    label: "Decorations",
    href: "/decorations",
    title: "Flower Decorations",
    eyebrow: "Homes, temples, weddings and events",
    description:
      "Decoration support for pooja setups, temple spaces, weddings, house functions and special events.",
    heroImage: "/assets/images/home-v2/recent-decorations/recent-decoration-wedding.jpg",
    primaryCta: { label: "Request Decoration", href: "/contact-us" },
    secondaryCta: { label: "View Garlands", href: "/garlands" },
    highlights: ["Wedding decor", "Pooja mandap", "Temple decoration", "Event flower setup"],
    cards: [
      {
        title: "Wedding Decorations",
        description: "Fresh flower decor for wedding spaces, entrances and ceremony areas.",
        meta: "Custom quotation",
        href: "/contact-us",
      },
      {
        title: "Pooja Decorations",
        description: "Traditional flower decoration for puja rooms, mandaps and vrathams.",
        meta: "Home / temple support",
        href: "/puja-flowers",
      },
      {
        title: "Temple Decorations",
        description: "Garlands, loose flowers and floral arrangements for temple events.",
        meta: "Bulk flowers available",
        href: "/garlands",
      },
      {
        title: "Event Decorations",
        description: "Simple fresh flower decor support for birthdays, openings and functions.",
        meta: "Same-city service",
        href: "/contact-us",
      },
    ],
    processTitle: "Decoration booking flow",
    processSteps: ["Share date and location", "Choose decoration type", "Confirm flowers and budget", "Team prepares setup"],
  },
  offers: {
    key: "offers",
    label: "Offers",
    href: "/offers",
    title: "Fresh Flower Offers",
    eyebrow: "Current deals and value packs",
    description:
      "Explore practical flower combos, subscription value packs and bulk flower options for regular needs.",
    heroImage: "/assets/images/home-v2/cta-basket-flowers.png",
    primaryCta: { label: "Browse All Flowers", href: "/flowers" },
    secondaryCta: { label: "Ask on WhatsApp", href: "/contact-us" },
    highlights: ["Daily fresh prices", "Bulk support", "Subscription value", "Hyderabad delivery"],
    cards: [
      {
        title: "Morning Puja Combo",
        description: "Loose flowers and leaves selected for next-morning puja.",
        meta: "Best for daily rituals",
        href: "/puja-flowers",
      },
      {
        title: "Garland Value Pack",
        description: "Garlands and loose flower support for special puja days.",
        meta: "Bulk friendly",
        href: "/garlands",
      },
      {
        title: "Premium Gift Pack",
        description: "Premium blooms and bouquet-style flowers for gifting.",
        meta: "From premium collection",
        href: "/gifts",
      },
      {
        title: "Subscription Saver",
        description: "Set regular deliveries and avoid last-minute flower planning.",
        meta: "Weekly / monthly",
        href: "/subscriptions",
      },
    ],
    processTitle: "How to get the latest offer",
    processSteps: ["Open the offer", "Share your quantity", "Confirm availability", "Place order"],
  },
};

export function buildPageMetadata(config) {
  return {
    title: config.metaTitle || `${config.title} | Manidvipa Flowers`,
    description: config.metaDescription || config.description,
    alternates: {
      canonical: config.href,
    },
  };
}
