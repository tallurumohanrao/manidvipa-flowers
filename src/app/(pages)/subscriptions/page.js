import React from "react";
import MenuLandingPage from "@/components/pages/MenuLandingPage";
import {
  buildPageMetadata,
  menuLandingPageConfigs,
} from "@/data/storefrontNavigation";

const pageConfig = menuLandingPageConfigs.subscriptions;

export const metadata = buildPageMetadata(pageConfig);

export default function SubscriptionsPage() {
  return <MenuLandingPage config={pageConfig} />;
}
