"use client";

import Image from "next/image";
import { useMemo, useRef, useState } from "react";
import { FaCheck, FaLayerGroup, FaMinus, FaPlus, FaRedoAlt, FaSearch, FaTrashAlt } from "react-icons/fa";
import styles from "@/scss/pages/customBouquet.module.scss";

const IMG_URL = process.env.NEXT_PUBLIC_IMG_URL;
const MAX_ITEMS = 16;
const POSITIONS = [
  [50, 25], [35, 32], [65, 32], [25, 43], [48, 43], [75, 43],
  [34, 54], [62, 54], [19, 58], [81, 58], [45, 65], [67, 65],
  [29, 69], [55, 75], [75, 72], [40, 80],
];
const SIZE_RANGES = {
  Small: [8, 12],
  Medium: [15, 25],
  Large: [30, 45],
  Grand: [50, null],
};
const PALETTE_COLORS = {
  "Red & Pink": ["#981a3c", "#efa1ae"],
  "White & Green": ["#fffdf7", "#80a46f"],
  "Yellow & Orange": ["#f6c83e", "#ef7b2d"],
  "Purple & White": ["#7958a8", "#fffdf7"],
  "Pastel Mix": ["#efb8be", "#efd4a6"],
  "Bright Mix": ["#d82d4b", "#f2b62f"],
};
const GREENERY_WORDS = [
  "leaf", "leaves", "foliage", "fern", "palm", "eucalyptus", "greenery",
  "filler", "gypsophila", "baby breath", "aralia", "dracaena", "monstera",
  "ruscus", "asparagus", "patri",
];
const EXCLUDED_WORDS = [
  "bouquet", "gift", "garland", "mala", "decoration", "subscription",
  "basket arrangement", "flower set",
];

function normalize(value) {
  return String(value || "").toLowerCase().replace(/&/g, " and ").replace(/[^a-z0-9]+/g, " ").trim();
}

function searchableText(product) {
  const categories = Array.isArray(product?.categories)
    ? product.categories.flatMap((category) => [category?.title, category?.slug])
    : [];
  return normalize([product?.title, product?.sku, product?.category_title, ...categories].join(" "));
}

export function classifyBouquetProduct(product) {
  const text = searchableText(product);
  if (product?.bouquet_builder?.configured && product.bouquet_builder.enabled === false) return "excluded";
  if (!text || EXCLUDED_WORDS.some((word) => text.includes(word))) return "excluded";
  return GREENERY_WORDS.some((word) => text.includes(word)) ? "greenery" : "flower";
}

function defaultOption(product) {
  const options = Array.isArray(product?.weights) ? product.weights : [];
  return options.find((option) => option?.is_default) || options[0] || null;
}

function bouquetSettings(product) {
  const configured = Boolean(product?.bouquet_builder?.configured);
  const setting = product?.bouquet_builder || {};
  const minimum = Math.max(1, Number(setting.minimum_quantity || 1));
  const configuredMaximum = Math.max(minimum, Number(setting.maximum_quantity || 50));
  const available = setting.available_quantity === null || setting.available_quantity === undefined
    ? null
    : Math.max(0, Number(setting.available_quantity));
  const maximum = available === null ? configuredMaximum : Math.min(configuredMaximum, available);
  const defaultQuantity = Math.min(
    Math.max(minimum, Number(setting.default_quantity || minimum)),
    Math.max(minimum, maximum)
  );

  return {
    configured,
    minimum,
    maximum,
    defaultQuantity,
    available,
    stockTracked: Boolean(setting.stock_tracked),
    unitLabel: setting.unit_label || "Stem",
    unitPrice: setting.price_visible && Number.isFinite(Number(setting.unit_price))
      ? Number(setting.unit_price)
      : null,
  };
}

function copyLayout(quantity) {
  const count = Math.max(1, Math.min(200, Number(quantity) || 1));
  if (count === 1) return [{ x: 50, y: 50, size: 112, rotation: 0 }];
  const size = Math.max(24, Math.min(72, 78 - Math.sqrt(count) * 5.2));
  return Array.from({ length: count }, (_, index) => {
    const progress = count === 1 ? 0 : index / (count - 1);
    const angle = index * 137.508 * (Math.PI / 180);
    const radius = 7 + Math.sqrt(progress) * 38;
    return {
      x: 50 + Math.cos(angle) * radius,
      y: 50 + Math.sin(angle) * radius * 0.82,
      size,
      rotation: ((index * 29) % 30) - 15,
    };
  });
}

export function bouquetProductImage(product) {
  if (product?.image_url) return product.image_url;
  if (product?.image_name && IMG_URL) return IMG_URL + "/" + product.image_name;
  if (product?.images?.[0]?.name && IMG_URL) return IMG_URL + "/" + product.images[0].name;
  return "/assets/images/no-image.png";
}

export function createBouquetDesignItem(product, index = 0) {
  const option = defaultOption(product);
  const setting = bouquetSettings(product);
  const position = POSITIONS[index % POSITIONS.length];
  const legacyUnitPrice = product?.show_price && Number.isFinite(Number(product?.sell_price))
    ? Number(product.sell_price)
    : null;
  const unitPrice = setting.configured ? setting.unitPrice : legacyUnitPrice;
  return {
    productId: Number(product?.product_id || product?.id),
    title: product?.title || "Flower",
    sku: product?.sku || "",
    kind: classifyBouquetProduct(product) === "greenery" ? "greenery" : "flower",
    imageUrl: bouquetProductImage(product),
    imageName: product?.image_name || product?.images?.[0]?.name || "",
    unitLabel: setting.configured
      ? "per " + setting.unitLabel.toLowerCase()
      : (product?.default_weight_label || option?.display_name || option?.name || "pack"),
    unitPrice,
    priceVisible: unitPrice !== null,
    quantity: setting.configured ? setting.defaultQuantity : 1,
    minimumQuantity: setting.configured ? setting.minimum : 1,
    maximumQuantity: setting.configured ? setting.maximum : 50,
    availableQuantity: setting.available,
    stockTracked: setting.stockTracked,
    x: position[0],
    y: position[1],
    scale: 1,
    rotation: index % 2 ? 6 : -5,
    z: index + 5,
  };
}

function money(value) {
  return new Intl.NumberFormat("en-IN", {
    style: "currency", currency: "INR", maximumFractionDigits: 0,
  }).format(Number(value) || 0);
}

function wrapClass(presentation) {
  if (presentation === "Kraft paper wrap") return styles.previewWrapKraft;
  if (presentation === "Flower basket") return styles.previewWrapBasket;
  if (presentation === "Glass vase") return styles.previewWrapVase;
  if (presentation === "Hand-tied ribbon") return styles.previewWrapRibbon;
  return styles.previewWrapPremium;
}

export default function BouquetDesignStudio({
  products = [], items = [], onItemsChange, palette = [], presentation, bouquetSize,
}) {
  const [activeTab, setActiveTab] = useState("flower");
  const [search, setSearch] = useState("");
  const [category, setCategory] = useState("all");
  const [selectedId, setSelectedId] = useState(null);
  const [notice, setNotice] = useState("");
  const stageRef = useRef(null);
  const dragRef = useRef(null);

  const catalogueProducts = useMemo(
    () => products.map((product) => ({ ...product, designKind: classifyBouquetProduct(product) }))
      .filter((product) => product.designKind !== "excluded"),
    [products]
  );

  const categories = useMemo(() => {
    const values = new Map();
    catalogueProducts.filter((product) => product.designKind === activeTab).forEach((product) => {
      const label = product?.category_title || product?.categories?.[0]?.title || "Other";
      values.set(normalize(label), label);
    });
    return [...values.entries()].sort((first, second) => first[1].localeCompare(second[1]));
  }, [activeTab, catalogueProducts]);

  const visibleProducts = useMemo(() => {
    const term = normalize(search);
    return catalogueProducts.filter((product) => {
      if (product.designKind !== activeTab) return false;
      if (term && !searchableText(product).includes(term)) return false;
      if (category === "all") return true;
      const productCategories = normalize([
        product?.category_title,
        ...(product?.categories || []).flatMap((item) => [item?.title, item?.slug]),
      ].join(" "));
      return productCategories.includes(category);
    });
  }, [activeTab, catalogueProducts, category, search]);

  const paletteColors = useMemo(() => {
    const chosen = palette.flatMap((name) => PALETTE_COLORS[name] || []);
    return chosen.length ? chosen.slice(0, 3) : ["#f8d9df", "#fff8ed"];
  }, [palette]);

  const selectedItem = items.find((item) => item.productId === selectedId) || null;
  const knownEstimate = items.reduce(
    (total, item) => total + (item.priceVisible ? Number(item.unitPrice || 0) * item.quantity : 0), 0
  );
  const hiddenPriceCount = items.filter((item) => !item.priceVisible).length;
  const totalFlowerQuantity = items
    .filter((item) => item.kind === "flower")
    .reduce((total, item) => total + Number(item.quantity || 0), 0);
  const selectedSizeRange = SIZE_RANGES[bouquetSize] || null;
  const sizeGuidance = selectedSizeRange
    ? `${bouquetSize} bouquets usually use ${selectedSizeRange[1] ? `${selectedSizeRange[0]}-${selectedSizeRange[1]}` : `${selectedSizeRange[0]}+`} flowers. You selected ${totalFlowerQuantity}.`
    : "Choose a bouquet size to see the recommended flower count.";
  const requirements = [
    { label: "Flower", done: items.some((item) => item.kind === "flower") },
    { label: "Colour", done: palette.length > 0 },
    { label: "Size", done: Boolean(bouquetSize) },
    { label: "Wrapping", done: Boolean(presentation) },
  ];
  const completeCount = requirements.filter((requirement) => requirement.done).length;

  const updateItem = (productId, changes) => {
    onItemsChange(items.map((item) => item.productId === productId ? { ...item, ...changes } : item));
  };

  const addProduct = (product) => {
    const productId = Number(product?.product_id || product?.id);
    const existing = items.find((item) => item.productId === productId);
    if (existing) {
      setSelectedId(productId);
      setNotice(existing.title + " is already selected. Change its quantity below.");
      return;
    }
    if (items.length >= MAX_ITEMS) {
      setNotice("You can combine up to " + MAX_ITEMS + " different catalogue items.");
      return;
    }
    onItemsChange([...items, createBouquetDesignItem(product, items.length)]);
    setSelectedId(productId);
    setNotice("");
  };

  const removeItem = (productId) => {
    onItemsChange(items.filter((item) => item.productId !== productId));
    if (selectedId === productId) setSelectedId(null);
    setNotice("");
  };

  const autoArrange = () => {
    onItemsChange(items.map((item, index) => {
      const position = POSITIONS[index % POSITIONS.length];
      return { ...item, x: position[0], y: position[1], rotation: index % 2 ? 6 : -5, z: index + 5 };
    }));
  };

  const handlePointerDown = (event, item) => {
    const bounds = stageRef.current?.getBoundingClientRect();
    if (!bounds) return;
    const pointerX = ((event.clientX - bounds.left) / bounds.width) * 100;
    const pointerY = ((event.clientY - bounds.top) / bounds.height) * 100;
    dragRef.current = {
      productId: item.productId,
      pointerId: event.pointerId,
      offsetX: item.x - pointerX,
      offsetY: item.y - pointerY,
    };
    event.currentTarget.setPointerCapture(event.pointerId);
    setSelectedId(item.productId);
  };

  const handlePointerMove = (event) => {
    const drag = dragRef.current;
    const bounds = stageRef.current?.getBoundingClientRect();
    if (!drag || !bounds || drag.pointerId !== event.pointerId) return;
    const x = Math.min(92, Math.max(8, ((event.clientX - bounds.left) / bounds.width) * 100 + drag.offsetX));
    const y = Math.min(86, Math.max(10, ((event.clientY - bounds.top) / bounds.height) * 100 + drag.offsetY));
    updateItem(drag.productId, { x: Number(x.toFixed(2)), y: Number(y.toFixed(2)) });
  };

  const handlePointerEnd = (event) => {
    if (dragRef.current?.pointerId === event.pointerId) dragRef.current = null;
  };

  const bringToFront = (item) => {
    updateItem(item.productId, { z: Math.max(5, ...items.map((candidate) => candidate.z || 5)) + 1 });
  };

  const selectedIds = new Set(items.map((item) => item.productId));
  const flowerCount = catalogueProducts.filter((product) => product.designKind === "flower").length;
  const greeneryCount = catalogueProducts.filter((product) => product.designKind === "greenery").length;

  return (
    <div className={styles.designStudio}>
      <div className={styles.requiredBar}>
        <div>
          <strong>{completeCount} of {requirements.length} required choices complete</strong>
          <span>Choose each required part before submitting your design.</span>
        </div>
        <div className={styles.requiredChecks}>
          {requirements.map((requirement) => (
            <span className={requirement.done ? styles.requirementDone : ""} key={requirement.label}>
              <i>{requirement.done ? <FaCheck /> : "•"}</i>{requirement.label}
            </span>
          ))}
        </div>
      </div>

      <div className={styles.studioGrid}>
        <div className={styles.libraryPanel}>
          <div className={styles.libraryHeader}>
            <div><span>Item library</span><strong>Select what you like</strong></div>
            <small>{items.length}/{MAX_ITEMS} selected</small>
          </div>

          <div className={styles.libraryTabs} role="tablist" aria-label="Bouquet item types">
            <button
              type="button"
              className={activeTab === "flower" ? styles.libraryTabActive : ""}
              onClick={() => { setActiveTab("flower"); setCategory("all"); }}
            >
              Flowers <span>{flowerCount}</span>
            </button>
            <button
              type="button"
              className={activeTab === "greenery" ? styles.libraryTabActive : ""}
              onClick={() => { setActiveTab("greenery"); setCategory("all"); }}
            >
              Greenery & fillers <span>{greeneryCount}</span>
            </button>
          </div>

          <div className={styles.libraryFilters}>
            <label>
              <FaSearch aria-hidden="true" />
              <input
                type="search"
                value={search}
                onChange={(event) => setSearch(event.target.value)}
                placeholder={"Search " + (activeTab === "flower" ? "flowers" : "greenery")}
              />
            </label>
            <select value={category} onChange={(event) => setCategory(event.target.value)} aria-label="Filter item category">
              <option value="all">All types</option>
              {categories.map(([value, label]) => <option value={value} key={value}>{label}</option>)}
            </select>
          </div>

          <div className={styles.productLibrary}>
            {visibleProducts.length ? visibleProducts.map((product) => {
              const productId = Number(product?.product_id || product?.id);
              const option = defaultOption(product);
              const setting = bouquetSettings(product);
              const unavailable = setting.configured
                ? setting.maximum < setting.minimum
                : Boolean(option?.is_out_of_stock);
              const isSelected = selectedIds.has(productId);
              return (
                <article className={[styles.libraryCard, isSelected ? styles.libraryCardSelected : ""].join(" ")} key={productId}>
                  <span className={styles.libraryImage}>
                    <Image src={bouquetProductImage(product)} alt={product.title || "Flower"} fill sizes="(max-width: 575px) 38vw, 110px" />
                    {isSelected ? <i><FaCheck /></i> : null}
                  </span>
                  <div>
                    <strong>{product.title}</strong>
                    <small>{setting.configured
                      ? `${setting.minimum}-${Math.max(setting.minimum, setting.maximum)} ${setting.unitLabel.toLowerCase()}s`
                      : (product.default_weight_label || option?.display_name || option?.name || "Catalogue item")}</small>
                    <span>{setting.configured
                      ? (setting.unitPrice !== null ? `${money(setting.unitPrice)} / ${setting.unitLabel.toLowerCase()}` : "Price after review")
                      : (product.show_price && product.sell_price ? money(product.sell_price) : "Price after review")}</span>
                  </div>
                  <button type="button" disabled={unavailable} onClick={() => isSelected ? removeItem(productId) : addProduct(product)}>
                    {unavailable ? "Out of stock" : isSelected ? "Remove" : "Add"}
                  </button>
                </article>
              );
            }) : (
              <div className={styles.libraryEmpty}>No matching {activeTab === "flower" ? "flowers" : "greenery"} found.</div>
            )}
          </div>
          {notice ? <div className={styles.studioNotice} role="status">{notice}</div> : null}
        </div>

        <div className={styles.previewPanel}>
          <div className={styles.previewHeader}>
            <div><span>Live preview</span><strong>Move flowers to arrange them</strong></div>
            <div>
              <button type="button" onClick={autoArrange} disabled={!items.length}><FaRedoAlt /> Auto arrange</button>
              <button type="button" onClick={() => { onItemsChange([]); setSelectedId(null); }} disabled={!items.length}>Clear</button>
            </div>
          </div>

          <div
            ref={stageRef}
            className={styles.previewStage}
            style={{
              "--preview-color-one": paletteColors[0],
              "--preview-color-two": paletteColors[1] || paletteColors[0],
              "--preview-color-three": paletteColors[2] || paletteColors[1] || paletteColors[0],
            }}
            onPointerMove={handlePointerMove}
            onPointerUp={handlePointerEnd}
            onPointerCancel={handlePointerEnd}
            onPointerLeave={handlePointerEnd}
            onClick={() => setSelectedId(null)}
          >
            <div className={styles.previewGlow} />
            {!items.length ? (
              <div className={styles.previewEmpty}>
                <FaLayerGroup /><strong>Your bouquet preview will appear here</strong><span>Select flowers from the item library.</span>
              </div>
            ) : null}
            {items.map((item) => {
              const copies = copyLayout(item.quantity);
              return (
                <button
                  type="button"
                  key={item.productId}
                  className={[styles.previewLayer, selectedId === item.productId ? styles.previewLayerSelected : ""].join(" ")}
                  style={{
                    left: item.x + "%",
                    top: item.y + "%",
                    transform: "translate(-50%, -50%) rotate(" + item.rotation + "deg) scale(" + item.scale + ")",
                    zIndex: item.z,
                  }}
                  onPointerDown={(event) => handlePointerDown(event, item)}
                  onClick={(event) => { event.stopPropagation(); setSelectedId(item.productId); }}
                  aria-label={"Move " + item.quantity + " " + item.title}
                >
                  <span className={styles.previewLayerCopies}>
                    {copies.map((copy, copyIndex) => (
                      <span
                        className={styles.previewFlowerCopy}
                        key={copyIndex}
                        style={{
                          left: copy.x + "%",
                          top: copy.y + "%",
                          width: copy.size + "px",
                          height: copy.size + "px",
                          transform: "translate(-50%, -50%) rotate(" + copy.rotation + "deg)",
                          zIndex: copyIndex + 1,
                        }}
                      >
                        <Image src={item.imageUrl} alt="" fill sizes="72px" draggable={false} />
                      </span>
                    ))}
                  </span>
                  <b>{item.quantity} {item.unitLabel.replace(/^per\s+/i, "")}{item.quantity === 1 ? "" : "s"}</b>
                </button>
              );
            })}
            <div className={[styles.previewWrap, wrapClass(presentation)].join(" ")}><span /></div>
            <div className={styles.previewRibbon} />
          </div>

          {selectedItem ? (
            <div className={styles.layerToolbar}>
              <div><span>Editing</span><strong>{selectedItem.title}</strong></div>
              <button type="button" onClick={() => updateItem(selectedItem.productId, { scale: Math.max(0.65, selectedItem.scale - 0.1) })} aria-label="Make smaller"><FaMinus /></button>
              <button type="button" onClick={() => updateItem(selectedItem.productId, { scale: Math.min(1.65, selectedItem.scale + 0.1) })} aria-label="Make larger"><FaPlus /></button>
              <button type="button" onClick={() => updateItem(selectedItem.productId, { rotation: selectedItem.rotation >= 165 ? -180 : selectedItem.rotation + 15 })} aria-label="Rotate"><FaRedoAlt /></button>
              <button type="button" onClick={() => bringToFront(selectedItem)} aria-label="Bring to front"><FaLayerGroup /></button>
              <button type="button" onClick={() => removeItem(selectedItem.productId)} aria-label="Remove item"><FaTrashAlt /></button>
            </div>
          ) : <p className={styles.previewTip}>Tap a flower in the preview to resize, rotate or bring it forward.</p>}

          <div className={styles.selectedItems}>
            <div className={styles.selectedItemsHeading}><strong>Selected items</strong><span>{items.length ? `${items.length} types · ${totalFlowerQuantity} flowers` : "None yet"}</span></div>
            {items.length ? <div className={styles.sizeGuidance}>{sizeGuidance}</div> : null}
            {items.map((item) => (
              <div className={styles.selectedItemRow} key={item.productId}>
                <span className={styles.selectedThumb}><Image src={item.imageUrl} alt="" fill sizes="44px" /></span>
                <div><strong>{item.title}</strong><small>{item.unitLabel}</small></div>
                <div className={styles.quantityControl}>
                  <button type="button" onClick={() => updateItem(item.productId, { quantity: Math.max(item.minimumQuantity || 1, item.quantity - 1) })} aria-label={"Decrease " + item.title}><FaMinus /></button>
                  <input
                    type="number"
                    min={item.minimumQuantity || 1}
                    max={item.maximumQuantity || 50}
                    step="1"
                    value={item.quantity}
                    onChange={(event) => {
                      const value = Number(event.target.value);
                      if (!Number.isFinite(value)) return;
                      updateItem(item.productId, {
                        quantity: Math.min(item.maximumQuantity || 50, Math.max(item.minimumQuantity || 1, Math.round(value))),
                      });
                    }}
                    aria-label={item.title + " quantity"}
                  />
                  <button type="button" onClick={() => updateItem(item.productId, { quantity: Math.min(item.maximumQuantity || 50, item.quantity + 1) })} aria-label={"Increase " + item.title}><FaPlus /></button>
                </div>
                <button type="button" className={styles.removeSelectedItem} onClick={() => removeItem(item.productId)} aria-label={"Remove " + item.title}><FaTrashAlt /></button>
              </div>
            ))}
            {items.length ? (
              <div className={styles.estimateRow}>
                <span>
                  <strong>Known-price estimate</strong>
                  <small>{hiddenPriceCount ? hiddenPriceCount + " selected item price(s) confirmed after review" : "Final florist quote may include preparation and wrapping"}</small>
                </span>
                <b>{knownEstimate ? money(knownEstimate) : "Quote required"}</b>
              </div>
            ) : null}
          </div>
        </div>
      </div>
    </div>
  );
}
