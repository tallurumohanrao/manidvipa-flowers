import Link from "next/link";
import { FiHome, FiShoppingBag } from "react-icons/fi";
import StoreStatusPage from "@/components/system/StoreStatusPage";

export const metadata = {
  title: "Page Not Found | Manidvipa Flowers",
  description: "The requested Manidvipa Flowers page could not be found.",
  robots: {
    index: false,
    follow: false,
  },
};

export default function NotFound() {
  return (
    <StoreStatusPage
      code="404"
      eyebrow="Page not found"
      title="This page is no longer blooming."
      description="The link may be outdated, the product may have moved, or the address may have been typed incorrectly."
      suggestions={
        <>
          <span>Popular places:</span>
          <Link href="/pooja-flowers">Pooja Flowers</Link>
          <Link href="/premium-flowers">Premium Flowers</Link>
          <Link href="/bouquets-gifts">Bouquets &amp; Gifts</Link>
        </>
      }
    >
      <Link href="/">
        <FiHome aria-hidden="true" />
        Return to home
      </Link>
      <Link href="/flowers">
        <FiShoppingBag aria-hidden="true" />
        Browse all flowers
      </Link>
    </StoreStatusPage>
  );
}
