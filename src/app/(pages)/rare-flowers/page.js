import React from "react";
import FlowerCategoryPage from "@/components/pages/FlowerCategoryPage";
import {
  buildPageMetadata,
  productCategoryPageConfigs,
} from "@/data/storefrontNavigation";

const pageConfig = productCategoryPageConfigs.rareFlowers;

export const metadata = buildPageMetadata(pageConfig);

export default async function RareFlowersPage() {
  return <FlowerCategoryPage config={pageConfig} />;
}
