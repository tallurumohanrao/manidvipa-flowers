"use client";

import Link from "next/link";
import { FiHome, FiRefreshCw } from "react-icons/fi";
import StoreStatusPage from "@/components/system/StoreStatusPage";

export default function GlobalError({ retry }) {
  return (
    <html lang="en">
      <head>
        <title>Something Went Wrong | Manidvipa Flowers</title>
        <meta name="robots" content="noindex, nofollow" />
      </head>
      <body style={{ margin: 0 }}>
        <StoreStatusPage
          code="Oops"
          eyebrow="Store unavailable"
          title="Something went wrong."
          description="We couldn't open the store right now. Please retry, or return to the homepage."
          variant="error"
        >
          <button type="button" onClick={() => retry()}>
            <FiRefreshCw aria-hidden="true" />
            Try again
          </button>
          <Link href="/">
            <FiHome aria-hidden="true" />
            Return to home
          </Link>
        </StoreStatusPage>
      </body>
    </html>
  );
}
