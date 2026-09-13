import React from "react";
import MenuLandingPage from "@/components/pages/MenuLandingPage";
import {
  getPageMetadataOptions,
  menuLandingPageConfigs,
} from "@/data/storefrontNavigation";
import { buildMetadataWithAdminSeo } from "@/lib/metadata";

const pageConfig = menuLandingPageConfigs.offers;

export async function generateMetadata() {
  return buildMetadataWithAdminSeo(getPageMetadataOptions(pageConfig));
}

export default function OffersPage() {
  return <MenuLandingPage config={pageConfig} />;
}
