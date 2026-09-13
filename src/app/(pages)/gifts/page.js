import React from "react";
import FlowerCategoryPage from "@/components/pages/FlowerCategoryPage";
import {
  getPageMetadataOptions,
  productCategoryPageConfigs,
} from "@/data/storefrontNavigation";
import { buildMetadataWithAdminSeo } from "@/lib/metadata";

const pageConfig = productCategoryPageConfigs.gifts;

export async function generateMetadata() {
  return buildMetadataWithAdminSeo(getPageMetadataOptions(pageConfig));
}

export default async function GiftsPage() {
  return <FlowerCategoryPage config={pageConfig} />;
}
