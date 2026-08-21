import React from "react";
import styles from "@/scss/pages/about.module.scss";
import Banner from "@/components/banner";
import { fetchListingData } from "../../../../hook/userCookie";
import { buildMetadata } from "@/lib/seo";

export const metadata = buildMetadata({
  title: "About Manidvipa Flowers | Fresh Flowers in Hyderabad",
  description:
    "Learn about Manidvipa Flowers, Hyderabad's fresh flower store for puja flowers, garlands, decorations, gifting and flower subscriptions.",
  path: "/about",
});

const fetchAboutData = async () => {
  try {
    return await fetchListingData("GET", "static-page?page_name=about-us");
  } catch (error) {
    console.error("Error fetching About Us page data:", error);
    return null;
  }
};

export default async function About() {
  const aboutUs = await fetchAboutData();

  return (
    <>
      <Banner title="About Us" />
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
