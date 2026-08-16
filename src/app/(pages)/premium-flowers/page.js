import React from "react";
import FlowerCategoryPage from "@/components/pages/FlowerCategoryPage";
import {
  buildPageMetadata,
  productCategoryPageConfigs,
} from "@/data/storefrontNavigation";

const pageConfig = productCategoryPageConfigs.premiumFlowers;

export const metadata = buildPageMetadata(pageConfig);

export default async function PremiumFlowersPage() {
  return <FlowerCategoryPage config={pageConfig} />;
}
