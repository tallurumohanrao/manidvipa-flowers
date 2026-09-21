"use client";

import { usePathname } from "next/navigation";
import { useEffect, useRef } from "react";

export default function TrackingPageViews({
  googleAnalyticsId = "",
  googleTagManagerId = "",
  metaPixelId = "",
}) {
  const pathname = usePathname();
  const isInitialPage = useRef(true);

  useEffect(() => {
    if (isInitialPage.current) {
      isInitialPage.current = false;
      return;
    }

    const pagePath = pathname || "/";

    if (googleTagManagerId) {
      window.dataLayer = window.dataLayer || [];
      window.dataLayer.push({
        event: "virtual_page_view",
        page_location: window.location.href,
        page_path: pagePath,
        page_title: document.title,
      });
      return;
    }

    if (googleAnalyticsId && typeof window.gtag === "function") {
      window.gtag("event", "page_view", {
        page_location: window.location.href,
        page_path: pagePath,
        page_title: document.title,
      });
    }

    if (metaPixelId && typeof window.fbq === "function") {
      window.fbq("track", "PageView");
    }
  }, [googleAnalyticsId, googleTagManagerId, metaPixelId, pathname]);

  return null;
}
