export const MAX_CART_ITEM_QUANTITY = 9999;
export const MAX_CUSTOM_QUANTITY = 1000000;

export function getPurchaseQuantityMaximum(usesCustomQuantity, configuredMaximum) {
  if (!usesCustomQuantity) return MAX_CART_ITEM_QUANTITY;

  const maximum = Number(configuredMaximum);
  return Number.isFinite(maximum) && maximum > 0
    ? Math.min(maximum, MAX_CUSTOM_QUANTITY)
    : MAX_CUSTOM_QUANTITY;
}
