import React from "react";
import FlowerCategoryPage from "@/components/pages/FlowerCategoryPage";
import {
  buildPageMetadata,
  productCategoryPageConfigs,
} from "@/data/storefrontNavigation";

const pageConfig = productCategoryPageConfigs.gifts;

export const metadata = buildPageMetadata(pageConfig);

export default async function GiftsPage() {
  return <FlowerCategoryPage config={pageConfig} />;
}
