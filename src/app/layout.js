import { fetchStaticMetadata } from "../../hook/metaData";
import { headers } from "next/headers";
import "./global.scss";

export async function generateMetadata() {
  const headersList = await headers();
  const title = headersList.get("x-metadata-pathName") || "Fallback Title";

  if (title) {
    const data = await fetchStaticMetadata(title);
    return data;
  } else {
    return;
  }
}

export default function RootLayout({ children }) {
  return (
    <html lang="en">
      <body>{children}</body>
    </html>
  );
}
