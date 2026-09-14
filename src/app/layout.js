import { fetchStaticMetadata } from "../../hook/metaData";
import { headers } from "next/headers";
import Script from "next/script";
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

const googleAnalyticsPattern = /\bG-[A-Z0-9]+\b/i;

function getApiBaseUrl() {
  return String(
    process.env.NEXT_PUBLIC_MANIDVIPA_URL ||
      "https://admin.manidvipastore.com/api"
  ).replace(/\/+$/, "");
}

async function fetchGlobalSiteSettings() {
  try {
    const response = await fetch(`${getApiBaseUrl()}/settings`, {
      next: { revalidate: 60 },
    });

    if (!response.ok) return {};

    const settings = await response.json();
    return settings?.data || {};
  } catch (error) {
    return {};
  }
}

function resolveGoogleAnalyticsId(settings = {}) {
  const value =
    settings.GOOGLE_ANALYTICS_ID ||
    process.env.NEXT_PUBLIC_GOOGLE_ANALYTICS_ID ||
    "";
  const match = String(value).match(googleAnalyticsPattern);

  return match?.[0]?.toUpperCase() || "";
}

function resolveSearchConsoleVerification(settings = {}) {
  const value =
    settings.GOOGLE_SEARCH_CONSOLE_VERIFICATION ||
    process.env.NEXT_PUBLIC_GOOGLE_SEARCH_CONSOLE_VERIFICATION ||
    "";
  const cleanValue = String(value || "").trim();

  if (!cleanValue) return "";

  const contentMatch = cleanValue.match(/content=["']([^"']+)["']/i);
  if (contentMatch?.[1]) return contentMatch[1].trim();

  if (/<script|<style/i.test(cleanValue)) return "";

  return cleanValue.replace(/^google-site-verification[:=]\s*/i, "").trim();
}

export async function generateMetadata() {
  const headersList = await headers();
  const pathName = normalizePath(headersList.get("x-metadata-pathName") || "/");
  const settings = await fetchGlobalSiteSettings();
  const searchConsoleVerification = resolveSearchConsoleVerification(settings);

  if (isNoIndexPath(pathName)) {
    const metadata = buildNoIndexMetadata(pathName, `${SITE_NAME} Account Page`);

    if (searchConsoleVerification) {
      metadata.verification = {
        google: searchConsoleVerification,
      };
    }

    return metadata;
  }

  const data = await fetchStaticMetadata(pathName);
  const metadata = buildMetadata({
    title: data?.page_title || `${SITE_NAME} | Fresh Flowers Online in Hyderabad`,
    description: data?.meta_description || DEFAULT_SEO_DESCRIPTION,
    keywords: data?.meta_keywords,
    robots: data?.robots,
    path: pathName,
  });

  if (searchConsoleVerification) {
    metadata.verification = {
      google: searchConsoleVerification,
    };
  }

  return metadata;
}

export default async function RootLayout({ children }) {
  const headersList = await headers();
  const pathName = normalizePath(headersList.get("x-metadata-pathName") || "/");
  const [data, settings] = await Promise.all([
    fetchStaticMetadata(pathName),
    fetchGlobalSiteSettings(),
  ]);
  const adminSchemas = parseAdminSchemaMarkup(data?.schema_markup);
  const googleAnalyticsId = resolveGoogleAnalyticsId(settings);
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
        {googleAnalyticsId ? (
          <>
            <Script
              src={`https://www.googletagmanager.com/gtag/js?id=${googleAnalyticsId}`}
              strategy="afterInteractive"
            />
            <Script id="google-analytics" strategy="afterInteractive">
              {`
                window.dataLayer = window.dataLayer || [];
                function gtag(){dataLayer.push(arguments);}
                gtag('js', new Date());
                gtag('config', '${googleAnalyticsId}');
              `}
            </Script>
          </>
        ) : null}
        {children}
      </body>
    </html>
  );
}
