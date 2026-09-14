import { permanentRedirect } from "next/navigation";

function cleanCategoryPath(value) {
  const parts = Array.isArray(value) ? value : [value];

  return parts
    .map((part) =>
      String(part || "")
        .toLowerCase()
        .trim()
        .replace(/&/g, " and ")
        .replace(/[^a-z0-9]+/g, "-")
        .replace(/^-+|-+$/g, "")
    )
    .filter(Boolean)
    .join("/");
}

export default async function Page({ params }) {
  const { category_slug } = await params;
  const categoryPath = cleanCategoryPath(category_slug);

  permanentRedirect(categoryPath ? `/${categoryPath}` : "/flowers");
}
