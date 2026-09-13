import React from "react";
import FlowerCategoryPage from "@/components/pages/FlowerCategoryPage";
import {
  getPageMetadataOptions,
  productCategoryPageConfigs,
} from "@/data/storefrontNavigation";
import { buildMetadataWithAdminSeo } from "@/lib/metadata";

const pageConfig = productCategoryPageConfigs.rareFlowers;

export async function generateMetadata() {
  return buildMetadataWithAdminSeo(getPageMetadataOptions(pageConfig));
}

export default async function RareFlowersPage() {
  return <FlowerCategoryPage config={pageConfig} />;
}
