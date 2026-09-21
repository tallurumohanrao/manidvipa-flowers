import { fetchStaticMetadata } from "../../hook/metaData";
import { headers } from "next/headers";
import Script from "next/script";
import TrackingPageViews from "@/components/TrackingPageViews";
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
const googleTagManagerPattern = /\bGTM-[A-Z0-9]+\b/i;
const metaPixelPattern = /^\d{5,25}$/;
const fallbackFavicon = "/favicon.jpg";

function getApiBaseUrl() {
  return String(
    process.env.NEXT_PUBLIC_MANIDVIPA_URL ||
      "https://admin.manidvipastore.com/api"
  ).replace(/\/+$/, "");
}

async function fetchGlobalSiteSettings() {
  try {
    const response = await fetch(`${getApiBaseUrl()}/settings`, {
      cache: "no-store",
      headers: { "Cache-Control": "no-cache" },
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

function resolveGoogleTagManagerId(settings = {}) {
  const value =
    settings.GOOGLE_TAG_MANAGER_ID ||
    process.env.NEXT_PUBLIC_GOOGLE_TAG_MANAGER_ID ||
    "";
  const match = String(value).match(googleTagManagerPattern);

  return match?.[0]?.toUpperCase() || "";
}

function resolveMetaPixelId(settings = {}) {
  const value = String(
    settings.META_PIXEL_ID || process.env.NEXT_PUBLIC_META_PIXEL_ID || ""
  ).trim();

  return metaPixelPattern.test(value) ? value : "";
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

function resolveSiteFavicon(settings = {}) {
  const value = String(settings.SITE_FAVICON || "").trim();

  if (!value) return fallbackFavicon;

  try {
    const url = new URL(value);
    return ["http:", "https:"].includes(url.protocol) ? url.toString() : fallbackFavicon;
  } catch (error) {
    return fallbackFavicon;
  }
}

function applySiteFavicon(metadata, settings = {}) {
  const favicon = resolveSiteFavicon(settings);

  metadata.icons = {
    icon: [{ url: favicon }],
    shortcut: [{ url: favicon }],
    apple: [{ url: favicon }],
  };

  return metadata;
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

    return applySiteFavicon(metadata, settings);
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

  return applySiteFavicon(metadata, settings);
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
  const googleTagManagerId = resolveGoogleTagManagerId(settings);
  const metaPixelId = resolveMetaPixelId(settings);
  const directGoogleAnalyticsId = googleTagManagerId ? "" : googleAnalyticsId;
  const directMetaPixelId = googleTagManagerId ? "" : metaPixelId;
  const siteSchema = {
    "@context": "https://schema.org",
    "@graph": [buildLocalBusinessSchema(), buildWebSiteSchema()],
  };

  return (
    <html lang="en-IN">
      <body>
        {googleTagManagerId ? (
          <noscript>
            <iframe
              src={`https://www.googletagmanager.com/ns.html?id=${googleTagManagerId}`}
              height="0"
              width="0"
              style={{ display: "none", visibility: "hidden" }}
              title="Google Tag Manager"
            />
          </noscript>
        ) : null}
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
        {googleTagManagerId ? (
          <Script id="google-tag-manager" strategy="afterInteractive">
            {`
              (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
              new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
              j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
              'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
              })(window,document,'script','dataLayer','${googleTagManagerId}');
            `}
          </Script>
        ) : null}
        {directGoogleAnalyticsId ? (
          <>
            <Script
              src={`https://www.googletagmanager.com/gtag/js?id=${directGoogleAnalyticsId}`}
              strategy="afterInteractive"
            />
            <Script id="google-analytics" strategy="afterInteractive">
              {`
                window.dataLayer = window.dataLayer || [];
                function gtag(){dataLayer.push(arguments);}
                gtag('js', new Date());
                gtag('config', '${directGoogleAnalyticsId}');
              `}
            </Script>
          </>
        ) : null}
        {directMetaPixelId ? (
          <>
            <Script id="meta-pixel" strategy="afterInteractive">
              {`
                !function(f,b,e,v,n,t,s)
                {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
                n.callMethod.apply(n,arguments):n.queue.push(arguments)};
                if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
                n.queue=[];t=b.createElement(e);t.async=!0;
                t.src=v;s=b.getElementsByTagName(e)[0];
                s.parentNode.insertBefore(t,s)}(window, document,'script',
                'https://connect.facebook.net/en_US/fbevents.js');
                fbq('init', '${directMetaPixelId}');
                fbq('track', 'PageView');
              `}
            </Script>
            <noscript>
              {/* Meta requires this raw 1x1 fallback when JavaScript is disabled. */}
              {/* eslint-disable-next-line @next/next/no-img-element */}
              <img
                height="1"
                width="1"
                style={{ display: "none" }}
                src={`https://www.facebook.com/tr?id=${directMetaPixelId}&ev=PageView&noscript=1`}
                alt=""
              />
            </noscript>
          </>
        ) : null}
        <TrackingPageViews
          googleAnalyticsId={directGoogleAnalyticsId}
          googleTagManagerId={googleTagManagerId}
          metaPixelId={directMetaPixelId}
        />
        {children}
      </body>
    </html>
  );
}
