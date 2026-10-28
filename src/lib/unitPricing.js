const COUNTABLE_UNITS = new Set([
  "flower",
  "piece",
  "stem",
  "bunch",
  "packet",
  "box",
  "basket",
  "set",
]);

const UNIT_LABELS = {
  flower: "flower",
  piece: "piece",
  stem: "stem",
  bunch: "bunch",
  packet: "packet",
  box: "box",
  basket: "basket",
  set: "set",
};

const UNIT_ALIASES = {
  flowers: "flower",
  pcs: "piece",
  pc: "piece",
  pieces: "piece",
  stems: "stem",
  bunches: "bunch",
  packets: "packet",
  boxes: "box",
  baskets: "basket",
  sets: "set",
};

function toPositiveNumber(value) {
  const number = Number(String(value ?? "").replace(/[^0-9.-]/g, ""));
  return Number.isFinite(number) && number > 0 ? number : 0;
}

/**
 * Returns an explicit per-unit price only for countable selling options.
 * Weight and volume options intentionally stay in their normal pack format
 * (for example, ₹200 / 250 Grams) because a per-gram figure is less useful.
 */
export function getPerUnitPriceDetails(option, selectedQuantity = null) {
  const rawUnit = String(option?.quantityUnit ?? option?.quantity_unit ?? "")
    .trim()
    .toLowerCase();
  const unit = UNIT_ALIASES[rawUnit] || rawUnit;
  const quantity = toPositiveNumber(
    selectedQuantity ?? option?.quantityValue ?? option?.quantity_value
  );
  const unitPrice = toPositiveNumber(
    option?.unitSellPrice ?? option?.unit_sell_price
  );

  if (!COUNTABLE_UNITS.has(unit) || quantity <= 1 || unitPrice <= 0) {
    return null;
  }

  return {
    quantity,
    unit,
    unitLabel: UNIT_LABELS[unit],
    unitPrice,
  };
}
