import React from "react";
import SubscriptionsPage from "@/components/pages/SubscriptionsPage";
import {
  getPageMetadataOptions,
  menuLandingPageConfigs,
} from "@/data/storefrontNavigation";
import { buildMetadataWithAdminSeo } from "@/lib/metadata";
import {
  fetchListingData,
  fetchSiteSettingsData,
} from "../../../../hook/userCookie";

const pageConfig = menuLandingPageConfigs.subscriptions;

export async function generateMetadata() {
  return buildMetadataWithAdminSeo(getPageMetadataOptions(pageConfig));
}

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
