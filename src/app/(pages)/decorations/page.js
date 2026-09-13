import React from "react";
import MenuLandingPage from "@/components/pages/MenuLandingPage";
import {
  getPageMetadataOptions,
  menuLandingPageConfigs,
} from "@/data/storefrontNavigation";
import { buildMetadataWithAdminSeo } from "@/lib/metadata";

const pageConfig = menuLandingPageConfigs.decorations;

export async function generateMetadata() {
  return buildMetadataWithAdminSeo(getPageMetadataOptions(pageConfig));
}

export default function DecorationsPage() {
  return <MenuLandingPage config={pageConfig} />;
}
