import { notFound } from "next/navigation";
import CustomBouquetBuilder from "@/components/pages/CustomBouquetBuilder";
import { buildMetadataWithAdminSeo } from "@/lib/metadata";
import { fetchListingData } from "../../../../hook/userCookie";
import { CUSTOM_BOUQUET_ENABLED } from "@/lib/features";

export const dynamic = "force-dynamic";

export async function generateMetadata() {
  return buildMetadataWithAdminSeo({
    title: "Create Your Own Flower Bouquet in Hyderabad | Manidvipa Flowers",
    description:
      "Design a custom flower bouquet for birthdays, anniversaries, weddings and special occasions. Choose flowers, colours, size and add-ons, then request a quotation.",
    path: "/create-bouquet",
  });
}

function extractProducts(response) {
  if (Array.isArray(response?.data?.data)) return response.data.data;
  if (Array.isArray(response?.data)) return response.data;
  return [];
}

async function fetchCompleteCatalogue() {
  const firstResponse = await fetchListingData(
    "GET",
    "products-by-category?category_slug=all&per_page=200&orderby=newest&page=1"
  );
  const products = extractProducts(firstResponse);
  const lastPage = Number(firstResponse?.data?.last_page || 1);

  if (lastPage > 1) {
    const remainingResponses = await Promise.all(
      Array.from({ length: lastPage - 1 }, (_, index) => (
        fetchListingData(
          "GET",
          "products-by-category?category_slug=all&per_page=200&orderby=newest&page=" + (index + 2)
        )
      ))
    );
    remainingResponses.forEach((response) => products.push(...extractProducts(response)));
  }

  return [...new Map(products.map((product) => [
    Number(product?.product_id || product?.id),
    product,
  ])).values()];
}

function compactDesignProduct(product) {
  const options = Array.isArray(product?.weights) ? product.weights : [];
  const defaultOption = options.find((option) => option?.is_default) || options[0] || null;

  return {
    id: product?.id,
    product_id: product?.product_id || product?.id,
    title: product?.title,
    sku: product?.sku,
    image_name: product?.image_name,
    category_title: product?.category_title,
    categories: (product?.categories || []).map((category) => ({
      title: category?.title,
      slug: category?.slug,
    })),
    sell_price: product?.sell_price,
    show_price: Boolean(product?.show_price),
    bouquet_builder: product?.bouquet_builder || null,
    default_weight_label: product?.default_weight_label,
    weights: defaultOption ? [{
      name: defaultOption?.name,
      display_name: defaultOption?.display_name,
      is_default: true,
      is_out_of_stock: Boolean(defaultOption?.is_out_of_stock),
    }] : [],
  };
}

export default async function Page() {
  if (!CUSTOM_BOUQUET_ENABLED) notFound();

  let bouquetProducts = [];
  let designProducts = [];

  try {
    const [bouquetResponse, catalogueProducts] = await Promise.all([
      fetchListingData("GET", "products-by-category?category_slug=gifts&per_page=24"),
      fetchCompleteCatalogue(),
    ]);
    bouquetProducts = extractProducts(bouquetResponse);
    designProducts = catalogueProducts.map(compactDesignProduct);
  } catch (error) {
    console.error("Unable to load custom bouquet catalogue:", error);
  }

  const minimumDeliveryDate = new Intl.DateTimeFormat("en-CA", {
    timeZone: "Asia/Kolkata",
    year: "numeric",
    month: "2-digit",
    day: "2-digit",
  }).format(new Date());

  return (
    <CustomBouquetBuilder
      bouquetProducts={bouquetProducts}
      designProducts={designProducts}
      minimumDeliveryDate={minimumDeliveryDate}
    />
  );
}
