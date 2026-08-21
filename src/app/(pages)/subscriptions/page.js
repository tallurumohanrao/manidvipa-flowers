import React from "react";
import SubscriptionsPage from "@/components/pages/SubscriptionsPage";
import {
  buildPageMetadata,
  menuLandingPageConfigs,
} from "@/data/storefrontNavigation";
import {
  fetchListingData,
  fetchSiteSettingsData,
} from "../../../../hook/userCookie";

const pageConfig = menuLandingPageConfigs.subscriptions;

export const metadata = buildPageMetadata(pageConfig);

export default async function SubscriptionsRoutePage() {
  const [plansData, siteSettings] = await Promise.all([
    fetchListingData("GET", "subscription-plans"),
    fetchSiteSettingsData(),
  ]);

  return (
    <SubscriptionsPage
      initialPlans={plansData?.data || []}
      siteSettings={siteSettings}
    />
  );
}
