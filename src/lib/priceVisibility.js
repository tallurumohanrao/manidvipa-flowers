export const PRICE_VISIBILITY = Object.freeze({
  SHOW_EVERYWHERE: "show_everywhere",
  DETAILS_ONLY: "details_only",
  SHOW_AFTER_SELECTION: "show_after_selection",
  ENQUIRY_ONLY: "enquiry_only",
  COMING_SOON: "coming_soon",
});

const messages = {
  [PRICE_VISIBILITY.DETAILS_ONLY]: "View product for price",
  [PRICE_VISIBILITY.SHOW_AFTER_SELECTION]: "Select an option to see price",
  [PRICE_VISIBILITY.ENQUIRY_ONLY]: "Contact us for price",
  [PRICE_VISIBILITY.COMING_SOON]: "Coming soon",
};

const ctaLabels = {
  [PRICE_VISIBILITY.DETAILS_ONLY]: "View Price",
  [PRICE_VISIBILITY.SHOW_AFTER_SELECTION]: "Choose Options",
  [PRICE_VISIBILITY.ENQUIRY_ONLY]: "Enquire Now",
  [PRICE_VISIBILITY.COMING_SOON]: "Coming Soon",
};

function readBoolean(value, fallback) {
  if (typeof value === "boolean") return value;
  if (value === 1 || value === "1") return true;
  if (value === 0 || value === "0") return false;
  return fallback;
}

export function getPriceVisibility(item, context = "listing", fallbackMode = PRICE_VISIBILITY.SHOW_EVERYWHERE) {
  const mode = item?.price_visibility || item?.effective_price_visibility || fallbackMode;
  const modeCanPurchase = ![
    PRICE_VISIBILITY.ENQUIRY_ONLY,
    PRICE_VISIBILITY.COMING_SOON,
  ].includes(mode);
  const computedShowPrice =
    mode === PRICE_VISIBILITY.SHOW_EVERYWHERE ||
    (context === "detail" && mode === PRICE_VISIBILITY.DETAILS_ONLY);

  return {
    mode,
    showPrice: readBoolean(item?.show_price, computedShowPrice),
    canPurchase: readBoolean(item?.can_purchase, modeCanPurchase),
    message: item?.price_message || messages[mode] || "",
    ctaLabel: item?.price_cta_label || ctaLabels[mode] || "Add to Cart",
    revealAfterSelection: mode === PRICE_VISIBILITY.SHOW_AFTER_SELECTION,
  };
}
