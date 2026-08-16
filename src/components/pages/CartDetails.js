"use client";

import React, { useCallback, useEffect, useMemo, useState } from "react";
import Image from "next/image";
import Link from "next/link";
import { useRouter } from "next/navigation";
import {
  FaClock,
  FaCreditCard,
  FaLeaf,
  FaShippingFast,
  FaTrashAlt,
  FaUndoAlt,
  FaWhatsapp,
} from "react-icons/fa";
import Cart from "@/components/Cart";
import Toast from "@/components/Toast";
import styles from "@/scss/pages/cartDetails.module.scss";
import { useCartCount, useToast, useUser } from "@/context/UserContext";
import { fetchListingData, getCartCount } from "../../../hook/userCookie";

const url = process.env.NEXT_PUBLIC_MANIDVIPA_URL;
const IMG_URL = process.env.NEXT_PUBLIC_IMG_URL;
const EMPTY_CART_ITEMS = [];
const DELIVERY_PREFERENCE_STORAGE_KEY = "manidvipaDeliveryPreference";

const deliverySlotOptions = [
  { value: "6-9", label: "6 AM - 9 AM" },
  { value: "9-12", label: "9 AM - 12 PM" },
  { value: "12-3", label: "12 PM - 3 PM" },
  { value: "3-6", label: "3 PM - 6 PM" },
];

const CART_IMAGE_RULES = [
  { keywords: ["kanakambaram", "crossandra"], image: "/assets/images/home-v2/fresh-arrivals/fresh-kanakambaram.jpg" },
  { keywords: ["lotus"], image: "/assets/images/home-v2/fresh-arrivals/fresh-lotus.jpg" },
  { keywords: ["jasmine", "malli", "mogra"], image: "/assets/images/home-v2/rare-seasonal/rare-jasmine.jpg" },
  { keywords: ["orchid"], image: "/assets/images/home-v2/premium-collection/premium-orchids.jpg" },
  { keywords: ["lily", "lilies"], image: "/assets/images/home-v2/premium-collection/premium-lilies.jpg" },
  { keywords: ["tulip"], image: "/assets/images/home-v2/premium-collection/premium-tulips.jpg" },
  { keywords: ["rose", "roses"], image: "/assets/images/home-v2/fresh-arrivals/fresh-red-roses.jpg" },
  { keywords: ["banthi", "marigold", "sevanthi", "chamanthi"], image: "/assets/images/home-v2/fresh-arrivals/fresh-chamanthi.jpg" },
  { keywords: ["mixed", "basket", "puja"], image: "/assets/images/home-v2/cta-basket-flowers.png" },
];

const RECOMMENDATION_PRODUCTS = [
  {
    title: "Pink Roses",
    slug: "pink-roses",
    price: 300,
    image: "/assets/images/home-v2/premium-collection/premium-roses.jpg",
  },
  {
    title: "White Roses",
    slug: "white-roses",
    price: 300,
    image: "/assets/images/home-v2/premium-collection/premium-lilies.jpg",
  },
  {
    title: "Yellow Banthi",
    slug: "yellow-banthi",
    price: 110,
    image: "/assets/images/home-v2/fresh-arrivals/fresh-yellow-sevanthi.jpg",
  },
  {
    title: "Lotus Flowers",
    slug: "lotus-flowers",
    price: 60,
    image: "/assets/images/home-v2/fresh-arrivals/fresh-lotus.jpg",
  },
];

function getCartItems(cartResponse) {
  if (Array.isArray(cartResponse?.data)) return cartResponse.data;
  if (Array.isArray(cartResponse?.data?.data)) return cartResponse.data.data;
  if (Array.isArray(cartResponse?.cart)) return cartResponse.cart;
  if (Array.isArray(cartResponse?.items)) return cartResponse.items;
  return [];
}

function parseAmount(value) {
  const amount = Number(String(value ?? "").replace(/[^0-9.-]/g, ""));
  return Number.isFinite(amount) ? amount : 0;
}

function formatCartPrice(value) {
  const amount = parseAmount(value);
  const absoluteAmount = Math.abs(amount);
  const formattedAmount = new Intl.NumberFormat("en-IN", {
    maximumFractionDigits: absoluteAmount % 1 === 0 ? 0 : 2,
  }).format(absoluteAmount);

  return `${amount < 0 ? "-" : ""}\u20B9${formattedAmount}`;
}

function getDateInputValue(daysFromToday = 1) {
  const date = new Date();
  date.setDate(date.getDate() + daysFromToday);
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, "0");
  const day = String(date.getDate()).padStart(2, "0");

  return `${year}-${month}-${day}`;
}

function getDeliverySlotLabel(value) {
  return deliverySlotOptions.find((option) => option.value === value)?.label || value;
}

function formatDeliveryDateForMessage(value) {
  const [year, month, day] = String(value || "").split("-").map(Number);
  const date = year && month && day ? new Date(year, month - 1, day) : null;

  if (!date || Number.isNaN(date.getTime())) return value || "Tomorrow";

  return new Intl.DateTimeFormat("en-IN", {
    day: "2-digit",
    month: "short",
    year: "numeric",
  }).format(date);
}

function openDatePicker(event) {
  try {
    event.currentTarget.showPicker?.();
  } catch {
    // Native date input still works if the browser blocks showPicker.
  }
}

function getTotalEntry(totals, keys) {
  for (const key of keys) {
    if (totals?.[key]?.amount !== undefined && totals?.[key]?.amount !== null) {
      return totals[key];
    }
  }

  return null;
}

function getCartSubTotal(cartResponse, items) {
  const totals = cartResponse?.totals || cartResponse?.data?.totals || {};
  const apiSubTotal = getTotalEntry(totals, ["sub_total", "subtotal"]);

  if (apiSubTotal) return parseAmount(apiSubTotal.amount);

  const rawSubTotal =
    cartResponse?.sub_total ??
    cartResponse?.subtotal ??
    cartResponse?.data?.sub_total ??
    cartResponse?.data?.subtotal;

  if (rawSubTotal !== null && rawSubTotal !== undefined) {
    return parseAmount(rawSubTotal);
  }

  return items.reduce(
    (total, item) => total + parseAmount(item?.sell_price) * Math.max(1, Number(item?.quantity) || 1),
    0
  );
}

function normalizeCartItem(item) {
  return {
    ...item,
    quantity: Math.max(1, Number(item?.quantity) || 1),
    sell_price: parseAmount(item?.sell_price),
    list_price: parseAmount(item?.list_price || item?.sell_price),
  };
}

function normalizeCartState(cartResponse) {
  const items = getCartItems(cartResponse).map(normalizeCartItem);
  const totals = cartResponse?.totals || cartResponse?.data?.totals || {};
  const subTotal = getCartSubTotal(cartResponse, items);
  const couponEntry = getTotalEntry(totals, ["coupon", "discount"]);
  const gstEntry = getTotalEntry(totals, ["gst", "tax"]);
  const deliveryEntry = getTotalEntry(totals, ["shipping", "delivery", "delivery_charges"]);
  const totalEntry = getTotalEntry(totals, ["total", "grand_total"]);
  const couponAmount = couponEntry ? parseAmount(couponEntry.amount) : 0;
  const gstAmount = gstEntry ? parseAmount(gstEntry.amount) : 0;
  const deliveryAmount = deliveryEntry ? parseAmount(deliveryEntry.amount) : 0;
  const total =
    totalEntry !== null
      ? parseAmount(totalEntry.amount)
      : subTotal + couponAmount + gstAmount + deliveryAmount;

  return {
    data: items,
    totals,
    subTotal,
    couponAmount,
    couponTitle: couponEntry?.title || "Coupon",
    gstAmount,
    gstTitle: gstEntry?.title || "GST",
    deliveryAmount,
    hasDeliveryAmount: Boolean(deliveryEntry),
    deliveryTitle: deliveryEntry?.title || "Delivery Charges",
    total,
  };
}

function buildStateFromItems(items, previousState = {}) {
  const normalizedItems = items.map(normalizeCartItem);
  const subTotal = normalizedItems.reduce(
    (total, item) => total + item.sell_price * item.quantity,
    0
  );
  const total =
    subTotal +
    parseAmount(previousState.couponAmount) +
    parseAmount(previousState.gstAmount) +
    parseAmount(previousState.deliveryAmount);

  return {
    ...previousState,
    data: normalizedItems,
    subTotal,
    total,
  };
}

function slugify(value) {
  return String(value || "")
    .toLowerCase()
    .trim()
    .replace(/&/g, "and")
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-|-$/g, "");
}

function getProductSlug(item) {
  return item?.product_slug || item?.slug || slugify(item?.product_title);
}

function getProductImage(item) {
  if (item?.localImage) return item.localImage;

  const image = item?.image || item?.image_name;
  if (image) {
    const src = String(image);
    if (src.startsWith("http") || src.startsWith("/")) return src;
    if (IMG_URL) return `${IMG_URL}/${src}`;
  }

  const searchableText = `${item?.product_title || ""} ${item?.weight || ""}`.toLowerCase();
  const localImage = CART_IMAGE_RULES.find((rule) =>
    rule.keywords.some((keyword) => searchableText.includes(keyword))
  );

  return localImage?.image || "/assets/images/no-image.png";
}

function normalizePhone(value) {
  return String(value || "").replace(/\D/g, "");
}

function saveDeliveryPreference(date, slot) {
  if (typeof window === "undefined") return;

  window.sessionStorage.setItem(
    DELIVERY_PREFERENCE_STORAGE_KEY,
    JSON.stringify({
      delivery_date: date,
      delivery_slot: slot,
      delivery_slot_label: getDeliverySlotLabel(slot),
    })
  );
}

function buildCartWhatsAppHref(contactValue, items, total, deliveryDate, deliverySlot) {
  const phone = normalizePhone(contactValue) || "917337525445";
  const itemLines = items
    .map((item) => {
      const weight = item?.weight ? ` - ${item.weight}` : "";
      return `${item.product_title}${weight} x ${item.quantity}`;
    })
    .join("\n");
  const message = [
    "Hello Manidvipa Flowers, I want to order these cart items:",
    itemLines,
    `Delivery Date: ${formatDeliveryDateForMessage(deliveryDate)}`,
    `Delivery Time: ${getDeliverySlotLabel(deliverySlot)}`,
    `Total: ${formatCartPrice(total)}`,
  ].join("\n");

  return `https://wa.me/${phone}?text=${encodeURIComponent(message)}`;
}

function getRecommendationProducts(cartItems) {
  const cartTitles = cartItems.map((item) => slugify(item?.product_title));
  const filteredProducts = RECOMMENDATION_PRODUCTS.filter(
    (product) => !cartTitles.includes(slugify(product.title))
  );

  return (filteredProducts.length ? filteredProducts : RECOMMENDATION_PRODUCTS).slice(0, 3);
}

function TrustItem({ icon, title, subtitle }) {
  return (
    <div className={styles.trustItem}>
      <span>{icon}</span>
      <div>
        <strong>{title}</strong>
        <small>{subtitle}</small>
      </div>
    </div>
  );
}

function QuantityStepper({ value, onChange, label }) {
  return (
    <div className={styles.quantityStepper} aria-label={label}>
      <button type="button" onClick={() => onChange(value - 1)} disabled={value <= 1}>
        -
      </button>
      <span>{value}</span>
      <button type="button" onClick={() => onChange(value + 1)}>
        +
      </button>
    </div>
  );
}

export default function CartDetails({
  CartDetailsData,
  guestSession,
  userToken,
  siteSettings,
}) {
  const router = useRouter();
  const { showToast } = useToast();
  const { guestSession: clientGuestSession } = useUser();
  const { setCartCount } = useCartCount();
  const effectiveGuestSession = clientGuestSession || guestSession;
  const contactUs = siteSettings?.data || siteSettings || {};

  const [data, setData] = useState(() => normalizeCartState(CartDetailsData));
  const [couponCode, setCouponCode] = useState("");
  const [isApplyingCoupon, setIsApplyingCoupon] = useState(false);
  const [isCheckingOut, setIsCheckingOut] = useState(false);
  const [addingRecommendation, setAddingRecommendation] = useState("");
  const [selectedDeliveryDate, setSelectedDeliveryDate] = useState(() =>
    getDateInputValue(1)
  );
  const [selectedDeliverySlot, setSelectedDeliverySlot] = useState("6-9");

  const cartItems = useMemo(() => data.data || EMPTY_CART_ITEMS, [data.data]);
  const recommendationProducts = useMemo(
    () => getRecommendationProducts(cartItems),
    [cartItems]
  );
  const whatsappHref = useMemo(
    () =>
      buildCartWhatsAppHref(
        contactUs?.SITE_WHATSAPP || contactUs?.SITE_PHONE,
        cartItems,
        data.total,
        selectedDeliveryDate,
        selectedDeliverySlot
      ),
    [
      cartItems,
      contactUs?.SITE_PHONE,
      contactUs?.SITE_WHATSAPP,
      data.total,
      selectedDeliveryDate,
      selectedDeliverySlot,
    ]
  );

  const refreshCart = useCallback(async () => {
    if (!effectiveGuestSession) return;

    try {
      const response = await fetch(
        `${url}/get-cart?cart_session=${encodeURIComponent(effectiveGuestSession)}`,
        {
          method: "GET",
          cache: "no-store",
          headers: {
            "Content-Type": "application/json",
            ...(userToken && { Authorization: `Bearer ${userToken}` }),
          },
        }
      );

      if (!response.ok) return;

      const result = await response.json();
      const nextState = normalizeCartState(result);
      setData(nextState);
      setCartCount(getCartCount(result));
    } catch (error) {
      console.error("Error refreshing cart:", error);
    }
  }, [effectiveGuestSession, setCartCount, userToken]);

  useEffect(() => {
    refreshCart();
  }, [refreshCart]);

  const syncCartItems = useCallback(
    async (items, { showSuccess = false } = {}) => {
      if (!effectiveGuestSession || !items.length) return true;

      try {
        const response = await fetch(`${url}/update-cart`, {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            ...(userToken && { Authorization: `Bearer ${userToken}` }),
          },
          body: JSON.stringify({
            cart_session: effectiveGuestSession,
            products: items.map((item) => ({
              cart_id: item.cart_id,
              product_title: item.product_title,
              quantity: Math.max(1, Number(item.quantity) || 1),
            })),
          }),
        });

        const result = await response.json();

        if (!response.ok || !result?.success) {
          showToast(result?.message || "Failed to update cart", "error");
          return false;
        }

        if (showSuccess) {
          showToast(result.message || "Cart updated successfully", "success");
        }

        return true;
      } catch (error) {
        console.error("Error during cart update:", error);
        showToast("An unexpected error occurred. Please try again later.", "error");
        return false;
      }
    },
    [effectiveGuestSession, showToast, userToken]
  );

  const handleQuantityChange = (newQuantity, index) => {
    const safeQuantity = Math.max(1, Number(newQuantity) || 1);
    const updatedItems = cartItems.map((item, itemIndex) =>
      itemIndex === index ? { ...item, quantity: safeQuantity } : item
    );

    setData((currentState) => buildStateFromItems(updatedItems, currentState));
    syncCartItems(updatedItems).then((success) => {
      if (success) refreshCart();
    });
  };

  const handleProceedCheckout = async () => {
    setIsCheckingOut(true);
    const updated = await syncCartItems(cartItems);
    setIsCheckingOut(false);

    if (updated) {
      saveDeliveryPreference(selectedDeliveryDate, selectedDeliverySlot);
      router.push("/checkout");
    }
  };

  const handleDelete = async (cartId) => {
    try {
      const response = await fetch(`${url}/delete-cart`, {
        method: "DELETE",
        headers: {
          "Content-Type": "application/json",
          ...(userToken && { Authorization: `Bearer ${userToken}` }),
        },
        body: JSON.stringify({
          cart_id: cartId,
          cart_session: effectiveGuestSession,
        }),
      });

      const result = await response.json();

      if (!response.ok || !result?.success) {
        showToast(result?.message || "Failed to delete the item", "error");
        return;
      }

      const updatedItems = cartItems.filter((item) => item.cart_id !== cartId);
      setData((currentState) => buildStateFromItems(updatedItems, currentState));
      setCartCount(updatedItems.length);
      showToast(result.message || "Item deleted successfully", "success");
      refreshCart();
    } catch (error) {
      console.error("Error deleting item:", error);
      showToast("An unexpected error occurred. Please try again later.", "error");
    }
  };

  const handleApplyCoupon = async (event) => {
    event.preventDefault();

    if (!couponCode.trim()) {
      showToast("Please enter a coupon code.", "error");
      return;
    }

    if (!effectiveGuestSession) {
      showToast("Please wait while your cart is getting ready.", "error");
      return;
    }

    setIsApplyingCoupon(true);
    try {
      const response = await fetch(`${url}/add-coupon`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          ...(userToken && { Authorization: `Bearer ${userToken}` }),
        },
        body: JSON.stringify({
          coupon_code: couponCode.trim(),
          cart_session: effectiveGuestSession,
        }),
      });
      const result = await response.json();

      if (!response.ok || !result?.success) {
        showToast(result?.message || "Invalid coupon.", "error");
        return;
      }

      setCouponCode("");
      showToast(result.message || "Coupon added successfully.", "success");
      refreshCart();
    } catch (error) {
      console.error("Error applying coupon:", error);
      showToast("Unable to apply coupon right now.", "error");
    } finally {
      setIsApplyingCoupon(false);
    }
  };

  const handleRemoveCoupon = async () => {
    if (!effectiveGuestSession) return;

    try {
      const response = await fetch(
        `${url}/remove-coupon?cart_session=${encodeURIComponent(effectiveGuestSession)}`,
        {
          method: "DELETE",
          headers: {
            "Content-Type": "application/json",
            ...(userToken && { Authorization: `Bearer ${userToken}` }),
          },
        }
      );
      const result = await response.json();

      if (!response.ok || !result?.success) {
        showToast(result?.message || "Unable to remove coupon.", "error");
        return;
      }

      showToast(result.message || "Coupon removed successfully.", "success");
      refreshCart();
    } catch (error) {
      console.error("Error removing coupon:", error);
      showToast("Unable to remove coupon right now.", "error");
    }
  };

  const handleRecommendationAdd = async (product) => {
    if (!effectiveGuestSession) {
      showToast("Please wait while your cart is getting ready.", "error");
      return;
    }

    setAddingRecommendation(product.slug);
    try {
      const productDetailsResponse = await fetch(
        `${url}/product-details?product_slug=${encodeURIComponent(product.slug)}`,
        {
          method: "GET",
          cache: "no-store",
          headers: {
            "Content-Type": "application/json",
            ...(userToken && { Authorization: `Bearer ${userToken}` }),
          },
        }
      );
      const productDetails = productDetailsResponse.ok
        ? await productDetailsResponse.json()
        : null;
      const firstWeight = Array.isArray(productDetails?.weights)
        ? productDetails.weights[0]
        : null;
      const productId = productDetails?.data?.id || productDetails?.data?.product_id;
      const weightId =
        firstWeight?.id ||
        firstWeight?.weight_id ||
        productDetails?.data?.weight_id ||
        productDetails?.data?.default_weight_id;

      if (!productId || !weightId) {
        router.push(`/product-details/${product.slug}`);
        return;
      }

      const cartData = await fetchListingData(
        "POST",
        "add-to-cart",
        userToken ? userToken : null,
        {
          cart_session: effectiveGuestSession,
          product_id: productId,
          quantity: 1,
          weight_id: weightId,
        }
      );

      if (!cartData?.success) {
        showToast(cartData?.message || "Failed to add product.", "error");
        return;
      }

      showToast(cartData.message || "Product added to cart successfully.", "success");
      refreshCart();
    } catch (error) {
      console.error("Recommended add failed:", error);
      showToast("Unable to add this product right now.", "error");
    } finally {
      setAddingRecommendation("");
    }
  };

  if (!cartItems.length) {
    return (
      <>
        <Cart />
        <Toast />
      </>
    );
  }

  const primaryItem = cartItems[0];

  return (
    <>
      <section className={styles.cartPage}>
        <div className="container">
          <div className={styles.cartShell}>
            <header className={styles.cartHeader}>
              <div>
                <h1>Your Cart</h1>
                <p>{cartItems.length} {cartItems.length === 1 ? "Item" : "Items"}</p>
              </div>
              <Link href="/flowers" className={styles.continueShopping}>
                Continue Shopping
              </Link>
            </header>

            <div className={styles.cartTableCard}>
              <div className={styles.cartTableHeader}>
                <span>Product</span>
                <span>Price</span>
                <span>Quantity</span>
                <span>Subtotal</span>
                <span aria-label="Remove item" />
              </div>

              {cartItems.map((item, index) => {
                const itemSlug = getProductSlug(item);
                const subtotal = item.sell_price * item.quantity;

                return (
                  <div className={styles.cartRow} key={item.cart_id || `${item.product_title}-${index}`}>
                    <div className={styles.productCell}>
                      <Link href={`/product-details/${itemSlug}`} className={styles.productImage}>
                        <Image
                          src={getProductImage(item)}
                          alt={item.product_title || "Fresh flowers"}
                          width={96}
                          height={96}
                        />
                      </Link>
                      <div>
                        <Link href={`/product-details/${itemSlug}`} className={styles.productName}>
                          {item.product_title}
                        </Link>
                        <span>{item.weight || "Selected quantity"}</span>
                      </div>
                    </div>
                    <div className={styles.priceCell}>{formatCartPrice(item.sell_price)}</div>
                    <div className={styles.quantityCell}>
                      <QuantityStepper
                        value={item.quantity}
                        onChange={(quantity) => handleQuantityChange(quantity, index)}
                        label={`Quantity for ${item.product_title}`}
                      />
                    </div>
                    <div className={styles.subtotalCell}>{formatCartPrice(subtotal)}</div>
                    <button
                      type="button"
                      className={styles.removeButton}
                      onClick={() => handleDelete(item.cart_id)}
                      aria-label={`Remove ${item.product_title}`}
                    >
                      <FaTrashAlt />
                    </button>
                  </div>
                );
              })}
            </div>

            <div className={styles.cartActionGrid}>
              <div className={styles.couponCard}>
                <h2>Have a coupon?</h2>
                <form onSubmit={handleApplyCoupon} className={styles.couponForm}>
                  <input
                    type="text"
                    value={couponCode}
                    onChange={(event) => setCouponCode(event.target.value)}
                    placeholder="Enter coupon code"
                    aria-label="Coupon code"
                  />
                  <button type="submit" disabled={isApplyingCoupon}>
                    {isApplyingCoupon ? "APPLYING" : "APPLY"}
                  </button>
                </form>
                {data.couponAmount ? (
                  <div className={styles.appliedCoupon}>
                    <span>{data.couponTitle}: {formatCartPrice(data.couponAmount)}</span>
                    <button type="button" onClick={handleRemoveCoupon}>
                      Remove
                    </button>
                  </div>
                ) : null}
              </div>

              <aside className={styles.summaryCard} aria-label="Cart summary">
                <div className={styles.summaryLine}>
                  <span>Subtotal</span>
                  <strong>{formatCartPrice(data.subTotal)}</strong>
                </div>
                {data.couponAmount ? (
                  <div className={styles.summaryLine}>
                    <span>{data.couponTitle}</span>
                    <strong className={styles.discountText}>{formatCartPrice(data.couponAmount)}</strong>
                  </div>
                ) : null}
                {data.gstAmount ? (
                  <div className={styles.summaryLine}>
                    <span>{data.gstTitle}</span>
                    <strong>{formatCartPrice(data.gstAmount)}</strong>
                  </div>
                ) : null}
                <div className={styles.summaryLine}>
                  <span>{data.deliveryTitle}</span>
                  <strong className={!data.hasDeliveryAmount || data.deliveryAmount <= 0 ? styles.freeText : ""}>
                    {data.hasDeliveryAmount && data.deliveryAmount > 0
                      ? formatCartPrice(data.deliveryAmount)
                      : "FREE"}
                  </strong>
                </div>
                <div className={styles.deliveryPreference}>
                  <label>
                    <span>Delivery Date</span>
                    <input
                      type="date"
                      value={selectedDeliveryDate}
                      min={getDateInputValue(1)}
                      onChange={(event) => setSelectedDeliveryDate(event.target.value)}
                      onFocus={openDatePicker}
                      onClick={openDatePicker}
                    />
                  </label>
                  <label>
                    <span>Delivery Time Slot</span>
                    <select
                      value={selectedDeliverySlot}
                      onChange={(event) => setSelectedDeliverySlot(event.target.value)}
                    >
                      {deliverySlotOptions.map((option) => (
                        <option value={option.value} key={option.value}>
                          {option.label}
                        </option>
                      ))}
                    </select>
                  </label>
                </div>
                <div className={styles.totalLine}>
                  <span>Total</span>
                  <strong>{formatCartPrice(data.total)}</strong>
                </div>
                <button
                  type="button"
                  className={styles.checkoutButton}
                  onClick={handleProceedCheckout}
                  disabled={isCheckingOut}
                >
                  {isCheckingOut ? "UPDATING CART..." : "PROCEED TO CHECKOUT"}
                </button>
                <a
                  href={whatsappHref}
                  target="_blank"
                  rel="noopener noreferrer"
                  className={styles.whatsappButton}
                >
                  <FaWhatsapp />
                  ORDER ON WHATSAPP
                </a>
              </aside>
            </div>

            <div className={styles.trustStrip}>
              <TrustItem icon={<FaCreditCard />} title="Secure Payments" subtitle="100% Safe" />
              <TrustItem icon={<FaShippingFast />} title="On Time Delivery" subtitle="Across Hyderabad" />
              <TrustItem icon={<FaUndoAlt />} title="Easy Returns" subtitle="Hassle Free" />
              <TrustItem icon={<FaClock />} title="Fresh Every Morning" subtitle="Sourced Daily" />
            </div>

            <div className={styles.cartBottomGrid}>
              <section className={styles.descriptionCard}>
                <h2>Product Description</h2>
                <p>
                  {primaryItem?.product_title || "Fresh flowers"} are packed fresh for puja,
                  decoration, gifting and special occasions. Flower appearance may vary slightly
                  based on daily market availability.
                </p>
                <p>
                  Store flowers in a cool place and sprinkle water lightly every few hours to
                  maintain freshness.
                </p>
              </section>

              <section className={styles.recommendCard}>
                <h2>You may also like</h2>
                <div className={styles.recommendGrid}>
                  {recommendationProducts.map((product) => (
                    <article className={styles.recommendProduct} key={product.slug}>
                      <Link href={`/product-details/${product.slug}`} className={styles.recommendImage}>
                        <Image src={product.image} alt={product.title} width={150} height={120} />
                      </Link>
                      <h3>{product.title}</h3>
                      <p>{formatCartPrice(product.price)} / kg</p>
                      <button
                        type="button"
                        onClick={() => handleRecommendationAdd(product)}
                        disabled={addingRecommendation === product.slug}
                      >
                        {addingRecommendation === product.slug ? "ADDING" : "ADD"}
                      </button>
                    </article>
                  ))}
                </div>
              </section>
            </div>

            <div className={styles.pujaNote}>
              <FaLeaf />
              <span>Need exact morning puja timing? Order on WhatsApp and confirm delivery slot before checkout.</span>
            </div>
          </div>
        </div>
      </section>
      <Toast />
    </>
  );
}
