import { permanentRedirect } from "next/navigation";

function cleanSlug(value) {
  return String(value || "")
    .toLowerCase()
    .trim()
    .replace(/&/g, " and ")
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-+|-+$/g, "");
}

export default async function Page({ params }) {
  const { id } = await params;
  const slug = cleanSlug(id);

  permanentRedirect(slug ? `/flowers/${slug}` : "/flowers");
}
