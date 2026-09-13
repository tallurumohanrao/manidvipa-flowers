import React from "react";
import FlowerCategoryPage from "@/components/pages/FlowerCategoryPage";
import {
  getPageMetadataOptions,
  productCategoryPageConfigs,
} from "@/data/storefrontNavigation";
import { buildMetadataWithAdminSeo } from "@/lib/metadata";

const pageConfig = productCategoryPageConfigs.premiumFlowers;

export async function generateMetadata() {
  return buildMetadataWithAdminSeo(getPageMetadataOptions(pageConfig));
}

export default async function PremiumFlowersPage() {
  return <FlowerCategoryPage config={pageConfig} />;
}
