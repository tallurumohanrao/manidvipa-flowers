import React from "react";
import FlowerCategoryPage from "@/components/pages/FlowerCategoryPage";
import {
  buildPageMetadata,
  productCategoryPageConfigs,
} from "@/data/storefrontNavigation";

const pageConfig = productCategoryPageConfigs.garlands;

export const metadata = buildPageMetadata(pageConfig);

export default async function GarlandsPage() {
  return <FlowerCategoryPage config={pageConfig} />;
}
