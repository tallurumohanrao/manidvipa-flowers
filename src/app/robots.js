import { SITE_URL } from "@/lib/seo";

export default function robots() {
  return {
    rules: {
      userAgent: "*",
      allow: "/",
      disallow: [
        "/admin",
        "/cart",
        "/checkout",
        "/login",
        "/register",
        "/my-account",
        "/address",
        "/orderDetails",
        "/forgot-password",
        "/password-reset",
        "/thank-you",
        "/watchlist",
        "/search",
        "/api",
      ],
    },
    sitemap: `${SITE_URL}/sitemap.xml`,
  };
}
