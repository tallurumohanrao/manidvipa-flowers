import React from "react";
import FlowerCategoryPage from "@/components/pages/FlowerCategoryPage";
import {
  getPageMetadataOptions,
  productCategoryPageConfigs,
} from "@/data/storefrontNavigation";
import { buildMetadataWithAdminSeo } from "@/lib/metadata";

const pageConfig = productCategoryPageConfigs.pujaFlowers;

export async function generateMetadata() {
  return buildMetadataWithAdminSeo(getPageMetadataOptions(pageConfig));
}

export default async function PujaFlowersPage() {
  return <FlowerCategoryPage config={pageConfig} />;
}
