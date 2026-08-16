import React from "react";
import MenuLandingPage from "@/components/pages/MenuLandingPage";
import {
  buildPageMetadata,
  menuLandingPageConfigs,
} from "@/data/storefrontNavigation";

const pageConfig = menuLandingPageConfigs.offers;

export const metadata = buildPageMetadata(pageConfig);

export default function OffersPage() {
  return <MenuLandingPage config={pageConfig} />;
}
