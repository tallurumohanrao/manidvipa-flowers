import React from "react";
import MenuLandingPage from "@/components/pages/MenuLandingPage";
import {
  buildPageMetadata,
  menuLandingPageConfigs,
} from "@/data/storefrontNavigation";

const pageConfig = menuLandingPageConfigs.decorations;

export const metadata = buildPageMetadata(pageConfig);

export default function DecorationsPage() {
  return <MenuLandingPage config={pageConfig} />;
}
