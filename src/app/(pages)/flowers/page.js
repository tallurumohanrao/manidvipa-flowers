import React from "react";
import FlowerCategoryPage from "@/components/pages/FlowerCategoryPage";
import {
  buildPageMetadata,
  productCategoryPageConfigs,
} from "@/data/storefrontNavigation";

const pageConfig = productCategoryPageConfigs.flowers;

export const metadata = buildPageMetadata(pageConfig);

export default async function FlowersPage() {
  return <FlowerCategoryPage config={pageConfig} />;
}
