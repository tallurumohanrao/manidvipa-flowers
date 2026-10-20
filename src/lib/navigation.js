const CATEGORY_SLUG_ALIASES = {
  "primimum-flowers": "premium-flowers",
  premium: "premium-flowers",
  rare: "rare-flowers",
  garland: "garlands",
  fragrance: "fragrant-flowers",
  "fragrance-flowers": "fragrant-flowers",
};

export function isExternalNavigationHref(value) {
  return /^https?:\/\//i.test(String(value || "").trim());
}

export function normalizeNavigationHref(value) {
  const raw = String(value || "").trim();
  if (!raw) return "";
  if (isExternalNavigationHref(raw)) return raw;

  const urlParts = raw.match(/^([^?#]*)(\?[^#]*)?(#.*)?$/);
  const sourcePath = urlParts?.[1] || "/";
  const segments = sourcePath
    .split("/")
    .filter(Boolean);
  const pathname = segments.length ? `/${segments.join("/")}` : "/";

  return `${pathname}${urlParts?.[2] || ""}${urlParts?.[3] || ""}`;
}

export function navigationKey(value) {
  const normalized = normalizeNavigationHref(value);
  if (!normalized) return "";
  if (isExternalNavigationHref(normalized)) return normalized.toLowerCase();
  return normalized.split(/[?#]/)[0].toLowerCase();
}

function normalizeItems(items) {
  const seen = new Set();

  return (Array.isArray(items) ? items : [])
    .map((item) => {
      const href = normalizeNavigationHref(item?.href);
      const key = navigationKey(href);
      const label = String(item?.label || "").trim();
      if (!label || !href || !key || seen.has(key)) return null;
      seen.add(key);

      return {
        key: item?.key || item?.id || key,
        label,
        href,
        children: normalizeItems(item?.children),
      };
    })
    .filter(Boolean);
}

export function prepareNavigationItems(items, fallbackItems = []) {
  const source = Array.isArray(items) && items.length ? items : fallbackItems;
  return normalizeItems(source);
}

export function isNavigationItemActive(pathname, itemOrHref) {
  const currentPath = normalizeNavigationHref(pathname).split(/[?#]/)[0];
  const item =
    typeof itemOrHref === "string" ? { href: itemOrHref } : itemOrHref || {};
  const href = normalizeNavigationHref(item.href);
  if (!href || isExternalNavigationHref(href)) return false;

  const itemPath = href.split(/[?#]/)[0];
  if (itemPath === "/") return currentPath === "/";
  if (currentPath === itemPath || currentPath.startsWith(`${itemPath}/`)) {
    return true;
  }

  return (item.children || []).some((child) =>
    isNavigationItemActive(currentPath, child)
  );
}

export function buildCategoryNavigationHref(category) {
  if (category?.public_path) {
    return normalizeNavigationHref(category.public_path);
  }
  const cleanSegment = (value) =>
    String(value || "")
      .toLowerCase()
      .trim()
      .replace(/&/g, " and ")
      .replace(/[^a-z0-9]+/g, "-")
      .replace(/^-+|-+$/g, "");
  const rawSlug = cleanSegment(
    category?.route_slug || category?.slug || category?.title
  );
  const rawParentSlug = cleanSegment(
    category?.parent_route_slug ||
      category?.parent_slug ||
      category?.parent_title
  );
  const slug = CATEGORY_SLUG_ALIASES[rawSlug] || rawSlug;
  const parentSlug = CATEGORY_SLUG_ALIASES[rawParentSlug] || rawParentSlug;

  if (!slug || slug === "all-flowers") return "/flowers";
  return parentSlug && parentSlug !== slug
    ? `/${parentSlug}/${slug}`
    : `/${slug}`;
}
