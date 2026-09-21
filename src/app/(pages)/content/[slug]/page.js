import Banner from "@/components/banner";
import { buildMetadataWithAdminSeo } from "@/lib/metadata";
import styles from "@/scss/pages/about.module.scss";
import { notFound } from "next/navigation";
import { fetchListingData } from "../../../../../hook/userCookie";

export const dynamic = "force-dynamic";

async function fetchPage(slug) {
  try {
    return await fetchListingData(
      "GET",
      `static-page?page_name=${encodeURIComponent(slug)}`
    );
  } catch {
    return null;
  }
}

export async function generateMetadata({ params }) {
  const { slug } = await params;
  const page = await fetchPage(slug);

  return buildMetadataWithAdminSeo({
    title: `${page?.data?.name || "Page"} | Manidvipa Flowers`,
    description: page?.data?.description || "Manidvipa Flowers",
    path: `/content/${slug}`,
  });
}

export default async function ContentPage({ params }) {
  const { slug } = await params;
  const page = await fetchPage(slug);

  if (!page?.data) {
    notFound();
  }

  return (
    <>
      <Banner title={page.data.name} />
      <section className={styles.about}>
        <div className="container">
          <div className="row">
            <div className="col">
              <div
                className={styles.about_body}
                dangerouslySetInnerHTML={{ __html: page.data.description }}
              />
            </div>
          </div>
        </div>
      </section>
    </>
  );
}
