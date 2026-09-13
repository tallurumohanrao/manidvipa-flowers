import React from "react";
import styles from "@/scss/pages/about.module.scss";
import Banner from "@/components/banner";
import { fetchListingData } from "../../../../hook/userCookie";
import { buildMetadataWithAdminSeo } from "@/lib/metadata";

export const dynamic = "force-dynamic";

export async function generateMetadata() {
  return buildMetadataWithAdminSeo({
    title: "Terms and Conditions | Manidvipa Flowers",
    description:
      "Read Manidvipa Flowers terms and conditions for orders, delivery, payments and website usage.",
    path: "/terms-conditions",
  });
}

const fetchAboutData = async () => {
  try {
    return await fetchListingData(
      "GET",
      "static-page?page_name=terms-conditions"
    );
  } catch (error) {
    console.error("Error fetching About Us page data:", error);
    return null;
  }
};

export default async function About() {
  const aboutUs = await fetchAboutData();

  return (
    <>
      <Banner title="Terms and Conditions" />
      <section className={styles.about}>
        <div className="container">
          <div className="row">
            <div className="col">
              {aboutUs?.data?.description ? (
                <div
                  className={styles.about_body}
                  dangerouslySetInnerHTML={{
                    __html: aboutUs?.data?.description,
                  }}
                ></div>
              ) : (
                <p>Content not available.</p>
              )}
            </div>
          </div>
        </div>
      </section>
    </>
  );
}
