import React from "react";
import styles from "@/scss/pages/about.module.scss";
import Banner from "@/components/banner";
import { fetchListingData } from "../../../../hook/userCookie";
import { buildMetadata } from "@/lib/seo";

export const metadata = buildMetadata({
  title: "Refund and Cancellation Policy | Manidvipa Flowers",
  description:
    "Read Manidvipa Flowers refund and cancellation policy for fresh flower orders, delivery changes and customer support.",
  path: "/refund-cancellation",
});

const fetchAboutData = async () => {
  try {
    return await fetchListingData("GET", "static-page?page_name=refund-policy");
  } catch (error) {
    console.error("Error fetching About Us page data:", error);
    return null;
  }
};

export default async function About() {
  const aboutUs = await fetchAboutData();

  return (
    <>
      <Banner title="Refund and Cancellation Policy" />
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
