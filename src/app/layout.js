import { fetchStaticMetadata } from "../../hook/metaData";
import { headers } from "next/headers";
import "./global.scss";
import {
  buildLocalBusinessSchema,
  buildMetadata,
  buildNoIndexMetadata,
  buildWebSiteSchema,
  DEFAULT_SEO_DESCRIPTION,
  isNoIndexPath,
  jsonLdScriptContent,
  normalizePath,
  parseAdminSchemaMarkup,
  SITE_NAME,
} from "@/lib/seo";

export async function generateMetadata() {
  const headersList = await headers();
  const pathName = normalizePath(headersList.get("x-metadata-pathName") || "/");

  if (isNoIndexPath(pathName)) {
    return buildNoIndexMetadata(pathName, `${SITE_NAME} Account Page`);
  }

  const data = await fetchStaticMetadata(pathName);

  return buildMetadata({
    title: data?.page_title || `${SITE_NAME} | Fresh Flowers Online in Hyderabad`,
    description: data?.meta_description || DEFAULT_SEO_DESCRIPTION,
    keywords: data?.meta_keywords,
    robots: data?.robots,
    path: pathName,
  });
}

export default async function RootLayout({ children }) {
  const headersList = await headers();
  const pathName = normalizePath(headersList.get("x-metadata-pathName") || "/");
  const data = await fetchStaticMetadata(pathName);
  const adminSchemas = parseAdminSchemaMarkup(data?.schema_markup);
  const siteSchema = {
    "@context": "https://schema.org",
    "@graph": [buildLocalBusinessSchema(), buildWebSiteSchema()],
  };

  return (
    <html lang="en-IN">
      <body>
        <script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: jsonLdScriptContent(siteSchema) }}
        />
        {adminSchemas.map((schema, index) => (
          <script
            key={`admin-schema-${index}`}
            type="application/ld+json"
            dangerouslySetInnerHTML={{ __html: jsonLdScriptContent(schema) }}
          />
        ))}
        {children}
      </body>
    </html>
  );
}
