"use client";
import React from "react";
import { usePathname } from "next/navigation";
import Link from "next/link";

const Breadcrumbs = ({ customBreadcrumbs }) => {
  const pathname = usePathname();

  let breadcrumbs = customBreadcrumbs || pathname.split("/").filter(Boolean);

  if (breadcrumbs.length === 0 || pathname === "/") {
    breadcrumbs = ["Home"];
  } else if (pathname.includes("password-reset")) {
    breadcrumbs = ["Home"];
  } else {
    breadcrumbs = ["Home", ...breadcrumbs];
  }

  const formatBreadcrumb = (text) => {
    return text
      .replace(/([A-Z])/g, " $1")
      .replace(/^./, (str) => str.toUpperCase())
      .trim();
  };

  const generatePath = (segments, index) => {
    return `/${segments.slice(1, index + 1).join("/")}`;
  };

  return (
    <h6>
      {breadcrumbs.map((item, index) => {
        const path = item === "Home" ? "/" : generatePath(breadcrumbs, index);

        return (
          <span key={index}>
            <Link href={path}>{formatBreadcrumb(item)}</Link>{" "}
            {index < breadcrumbs.length - 1 && " > "}
          </span>
        );
      })}
    </h6>
  );
};

export default Breadcrumbs;
