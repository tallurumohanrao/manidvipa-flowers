function normalizeStockText(value) {
  return String(value ?? "").trim().toLowerCase().replace(/[_-]+/g, " ");
}

function isTruthyStockFlag(value) {
  return value === true || value === 1 || value === "1" || ["true", "yes", "on"].includes(normalizeStockText(value));
}

function getNumericStock(value) {
  if (value === null || value === undefined || value === "") return null;
  const number = Number(value);
  return Number.isFinite(number) ? number : null;
}

function getStockState(item) {
  if (!item || typeof item !== "object") return null;

  if (item.is_out_of_stock !== null && item.is_out_of_stock !== undefined) {
    return isTruthyStockFlag(item.is_out_of_stock);
  }

  if (item.in_stock !== null && item.in_stock !== undefined) {
    return !isTruthyStockFlag(item.in_stock);
  }

  const status = normalizeStockText(
    item.stock_status ?? item.inventory_status ?? item.availability_status ?? item.availability
  );
  if (["out of stock", "sold out", "unavailable"].includes(status)) return true;
  if (["in stock", "available", "pre order", "preorder"].includes(status)) return false;

  const trackingFlag = item.track_stock ?? item.stock_tracking ?? item.manage_stock ?? item.stock;
  if (trackingFlag !== null && trackingFlag !== undefined && !isTruthyStockFlag(trackingFlag)) {
    return null;
  }

  const availableQuantity = [
    item.available_stock,
    item.available_quantity,
    item.stock_quantity,
    item.quantity_available,
    item.qty,
  ].map(getNumericStock).find((value) => value !== null);

  if (availableQuantity !== undefined) {
    const requiredQuantity = getNumericStock(item.quantity_value) || 1;
    return availableQuantity < requiredQuantity;
  }

  return null;
}

export function isProductOutOfStock(product) {
  const weights = Array.isArray(product?.weights) ? product.weights : [];

  if (weights.length) {
    const defaultWeight = weights.find((weight) =>
      isTruthyStockFlag(weight?.is_default) || isTruthyStockFlag(weight?.default)
    ) || weights[0];
    const defaultWeightState = getStockState(defaultWeight);

    // Store cards select the configured default option first. Match that
    // visible state so every card labelled "Out of stock" is ordered last.
    if (defaultWeightState !== null) return defaultWeightState;
  }

  const weightStates = weights.map(getStockState);

  if (weightStates.some((state) => state === false)) return false;
  if (weightStates.length && weightStates.every((state) => state === true)) return true;

  return getStockState(product) === true;
}

export function compareProductsByStock(leftProduct, rightProduct) {
  return Number(isProductOutOfStock(leftProduct)) - Number(isProductOutOfStock(rightProduct));
}

export function sortAvailableProductsFirst(products = []) {
  return [...products]
    .map((product, index) => ({ product, index }))
    .sort((left, right) =>
      compareProductsByStock(left.product, right.product) || left.index - right.index
    )
    .map(({ product }) => product);
}
