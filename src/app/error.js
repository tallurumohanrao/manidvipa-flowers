"use client";

import { useEffect } from "react";
import Link from "next/link";
import { FiHome, FiRefreshCw } from "react-icons/fi";
import StoreStatusPage from "@/components/system/StoreStatusPage";

export default function StoreError({ error, retry }) {
  useEffect(() => {
    console.error(error);
  }, [error]);

  return (
    <StoreStatusPage
      code="Oops"
      eyebrow="Temporary problem"
      title="We couldn't load this page."
      description="The store service may be temporarily unavailable. Your information is safe—please try loading the page again."
      variant="error"
      suggestions={
        <>
          <span>Still having trouble?</span>
          <Link href="/contact-us">Contact us</Link>
        </>
      }
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
  );
}
