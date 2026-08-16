import React from "react";
import FlowerCategoryPage from "@/components/pages/FlowerCategoryPage";
import {
  buildPageMetadata,
  productCategoryPageConfigs,
} from "@/data/storefrontNavigation";

const pageConfig = productCategoryPageConfigs.pujaFlowers;

export const metadata = buildPageMetadata(pageConfig);

export default async function PujaFlowersPage() {
  return <FlowerCategoryPage config={pageConfig} />;
}
